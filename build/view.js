/******/ (() => { // webpackBootstrap
/*!*********************!*\
  !*** ./src/view.js ***!
  \*********************/
/**
 * Site Quiz Frontend Script
 * Handles quiz functionality with plain JavaScript and DOM manipulation
 */

document.addEventListener('DOMContentLoaded', function () {
  const quizBlocks = document.querySelectorAll('.wp-block-telex-block-site-quiz');
  quizBlocks.forEach(initQuiz);
});
function initQuiz(blockElement) {
  const blockId = blockElement.dataset.blockId;
  const questionCount = parseInt(blockElement.dataset.questionCount) || 5;
  const enabledPatterns = JSON.parse(blockElement.dataset.enabledPatterns || '[]');
  const state = {
    blockId,
    questionCount,
    enabledPatterns,
    questions: [],
    currentQuestionIndex: 0,
    userAnswers: [],
    score: 0,
    completed: false,
    feedbackShown: false
  };

  // Try to load saved state
  loadSavedState(state);

  // If no saved incomplete quiz, load new questions
  if (state.questions.length === 0 || state.completed) {
    loadQuestions(blockElement, state);
  } else {
    hideLoading(blockElement);
    renderQuiz(blockElement, state);
  }
}
function loadSavedState(state) {
  const key = `site_quiz_${state.blockId}`;
  const saved = localStorage.getItem(key);
  if (saved) {
    try {
      const savedState = JSON.parse(saved);
      if (savedState && !savedState.completed) {
        Object.assign(state, savedState);
      }
    } catch (e) {
      // Invalid saved state, will load fresh
    }
  }
}
function saveState(state) {
  const key = `site_quiz_${state.blockId}`;
  localStorage.setItem(key, JSON.stringify(state));
}
function clearState(state) {
  const key = `site_quiz_${state.blockId}`;
  localStorage.removeItem(key);
}
async function loadQuestions(blockElement, state) {
  try {
    const response = await fetch('/wp-json/site-quiz/v1/questions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        questionCount: state.questionCount,
        enabledPatterns: state.enabledPatterns
      })
    });
    if (!response.ok) {
      throw new Error('Failed to load questions');
    }
    const questions = await response.json();
    state.questions = questions;
    state.userAnswers = new Array(questions.length).fill(null);
    state.currentQuestionIndex = 0;
    state.score = 0;
    state.completed = false;
    state.feedbackShown = false;
    hideLoading(blockElement);
    renderQuiz(blockElement, state);
    saveState(state);
  } catch (error) {
    hideLoading(blockElement);
    showError(blockElement, error.message, state);
  }
}
function hideLoading(blockElement) {
  const loading = blockElement.querySelector('.site-quiz__loading');
  if (loading) {
    loading.style.display = 'none';
  }
}
function showError(blockElement, message, state) {
  const container = blockElement.querySelector('.site-quiz__container');
  container.innerHTML = `
		<div class="site-quiz__error" style="text-align: center; padding: 2rem;">
			<p style="color: #ef4444; margin-bottom: 1rem;">${message}</p>
			<button class="site-quiz__retry" style="padding: 0.75rem 1.5rem; border: none; border-radius: 8px; background: currentColor; color: white; cursor: pointer;">Try Again</button>
		</div>
	`;
  const retryBtn = container.querySelector('.site-quiz__retry');
  retryBtn.addEventListener('click', () => {
    container.innerHTML = '<div class="site-quiz__loading"><div class="site-quiz__spinner"></div><p>Loading quiz...</p></div>';
    loadQuestions(blockElement, state);
  });
}
function renderQuiz(blockElement, state) {
  const container = blockElement.querySelector('.site-quiz__container');
  if (state.completed) {
    renderResults(container, state);
    return;
  }
  const question = state.questions[state.currentQuestionIndex];
  const userAnswer = state.userAnswers[state.currentQuestionIndex];
  container.innerHTML = `
		<div class="site-quiz__header">
			<h2>Site Quiz</h2>
			<div class="site-quiz__progress">
				<span>Question ${state.currentQuestionIndex + 1} / ${state.questions.length}</span>
			</div>
		</div>
		
		<div class="site-quiz__question">
			<div class="question__text">${question.question}</div>
			<div class="question__options">
				${question.options.map((option, index) => {
    let className = 'question__option';
    if (userAnswer !== null) {
      className += ' disabled';
      if (index === question.correctAnswer) {
        className += ' correct';
      } else if (index === userAnswer) {
        className += ' incorrect';
      }
    }
    return `<button class="${className}" data-index="${index}">${option}</button>`;
  }).join('')}
			</div>
		</div>
	`;

  // Add event listeners to options
  const options = container.querySelectorAll('.question__option');
  options.forEach(option => {
    option.addEventListener('click', e => handleAnswerClick(e, blockElement, state));
  });
}
function handleAnswerClick(event, blockElement, state) {
  const answerIndex = parseInt(event.target.dataset.index);

  // Prevent re-answering
  if (state.userAnswers[state.currentQuestionIndex] !== null) {
    return;
  }
  const question = state.questions[state.currentQuestionIndex];
  const isCorrect = answerIndex === question.correctAnswer;
  state.userAnswers[state.currentQuestionIndex] = answerIndex;
  state.feedbackShown = true;
  if (isCorrect) {
    state.score++;
  }
  saveState(state);

  // Re-render to show feedback
  renderQuiz(blockElement, state);

  // Auto-advance after 1.5 seconds
  setTimeout(() => {
    if (state.currentQuestionIndex < state.questions.length - 1) {
      state.currentQuestionIndex++;
      state.feedbackShown = false;
      saveState(state);
      renderQuiz(blockElement, state);
    } else {
      state.completed = true;
      saveState(state);
      renderResults(blockElement.querySelector('.site-quiz__container'), state);
    }
  }, 1500);
}
function renderResults(container, state) {
  const percentage = Math.round(state.score / state.questions.length * 100);
  let achievement;
  if (percentage >= 100) {
    achievement = {
      emoji: '🥇',
      title: 'Gold Master',
      message: 'Perfect score! You\'re amazing!'
    };
  } else if (percentage >= 75) {
    achievement = {
      emoji: '🥈',
      title: 'Silver Expert',
      message: 'Great job! You know your stuff!'
    };
  } else if (percentage >= 50) {
    achievement = {
      emoji: '🥉',
      title: 'Bronze Scholar',
      message: 'Good effort! Keep learning!'
    };
  } else {
    achievement = {
      emoji: '📚',
      title: 'Knowledge Seeker',
      message: 'Keep practicing!'
    };
  }
  container.innerHTML = `
		<div class="site-quiz__results">
			<div class="results__badge">${achievement.emoji}</div>
			<h2 class="results__title">${achievement.title}</h2>
			<p class="results__score">
				You scored <strong>${state.score} / ${state.questions.length}</strong> (${percentage}%)
			</p>
			<p class="results__message">${achievement.message}</p>
			<div class="results__actions">
				<button class="results__restart">Try Again</button>
				<button class="results__review">Review Answers</button>
			</div>
		</div>
	`;
  const restartBtn = container.querySelector('.results__restart');
  const reviewBtn = container.querySelector('.results__review');
  restartBtn.addEventListener('click', () => {
    clearState(state);
    container.innerHTML = '<div class="site-quiz__loading"><div class="site-quiz__spinner"></div><p>Loading quiz...</p></div>';
    const blockElement = container.closest('.wp-block-telex-block-site-quiz');
    initQuiz(blockElement);
  });
  reviewBtn.addEventListener('click', () => {
    state.currentQuestionIndex = 0;
    state.completed = false;
    const blockElement = container.closest('.wp-block-telex-block-site-quiz');
    renderQuiz(blockElement, state);
  });
}
/******/ })()
;
//# sourceMappingURL=view.js.map