
/**
 * Site Quiz Frontend Script
 * Handles quiz functionality on the frontend
 */

(function() {
	'use strict';

	/**
	 * Quiz state management
	 * 
	 * @typedef {Object} QuizState
	 * @property {Array} questions - Array of quiz questions
	 * @property {number} currentQuestion - Current question index
	 * @property {Array} userAnswers - User's selected answers
	 * @property {number} score - Current score
	 * @property {boolean} completed - Whether quiz is completed
	 */

	/**
	 * Achievement thresholds
	 * 
	 * @constant {Object}
	 */
	const ACHIEVEMENT_THRESHOLDS = {
		bronze: 50,
		silver: 75,
		gold: 100
	};

	/**
	 * Achievement badges
	 * 
	 * @constant {Object}
	 */
	const ACHIEVEMENT_BADGES = {
		bronze: { emoji: '🥉', title: 'Bronze Scholar', message: 'Good effort! Keep learning!' },
		silver: { emoji: '🥈', title: 'Silver Expert', message: 'Great job! You know your stuff!' },
		gold: { emoji: '🥇', title: 'Gold Master', message: 'Perfect score! You\'re amazing!' }
	};

	/**
	 * Initialize quiz blocks
	 */
	function initQuizBlocks() {
		const quizBlocks = document.querySelectorAll('.wp-block-telex-block-site-quiz');
		
		quizBlocks.forEach(block => {
			const quiz = new SiteQuiz(block);
			quiz.init();
		});
	}

	/**
	 * Site Quiz Class
	 * 
	 * @class
	 */
	class SiteQuiz {
		/**
		 * Constructor
		 * 
		 * @param {HTMLElement} element - Quiz block element
		 */
		constructor(element) {
			this.element = element;
			this.container = element.querySelector('.site-quiz__container');
			this.questionCount = parseInt(element.dataset.questionCount) || 5;
			this.enabledPatterns = JSON.parse(element.dataset.enabledPatterns || '[]');
			
			this.state = {
				questions: [],
				currentQuestion: 0,
				userAnswers: [],
				score: 0,
				completed: false
			};
		}

		/**
		 * Initialize quiz
		 * 
		 * @return {Promise<void>}
		 */
		async init() {
			// Check for saved state
			const savedState = this.loadState();
			if (savedState && !savedState.completed) {
				this.state = savedState;
				this.render();
			} else {
				await this.loadQuestions();
			}
		}

		/**
		 * Load questions from API
		 * 
		 * @return {Promise<void>}
		 */
		async loadQuestions() {
			try {
				const response = await fetch('/wp-json/site-quiz/v1/questions', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						questionCount: this.questionCount,
						enabledPatterns: this.enabledPatterns
					})
				});

				if (!response.ok) {
					throw new Error('Failed to load questions');
				}

				const questions = await response.json();
				this.state.questions = questions;
				this.state.userAnswers = new Array(questions.length).fill(null);
				this.saveState();
				this.render();
			} catch (error) {
				this.renderError(error.message);
			}
		}

		/**
		 * Render quiz interface
		 */
		render() {
			if (this.state.completed) {
				this.renderResults();
			} else {
				this.renderQuestion();
			}
		}

		/**
		 * Render current question
		 */
		renderQuestion() {
			const question = this.state.questions[this.state.currentQuestion];
			const selectedAnswer = this.state.userAnswers[this.state.currentQuestion];

			const html = `
				<div class="site-quiz__header">
					<h2>Site Quiz</h2>
					<div class="site-quiz__progress">
						<span>Question ${this.state.currentQuestion + 1} of ${this.state.questions.length}</span>
						<span>•</span>
						<span>Score: ${this.state.score}</span>
					</div>
				</div>
				<div class="site-quiz__question">
					<p class="question__text">${this.escapeHtml(question.question)}</p>
					<div class="question__options">
						${question.options.map((option, index) => `
							<button 
								class="question__option ${selectedAnswer !== null && selectedAnswer === index ? 'selected' : ''}" 
								data-index="${index}"
								${selectedAnswer !== null ? 'disabled' : ''}
							>
								${this.escapeHtml(option)}
							</button>
						`).join('')}
					</div>
				</div>
				<div class="site-quiz__navigation">
					${this.state.currentQuestion > 0 ? `
						<button class="quiz-nav__prev">← Previous</button>
					` : ''}
					${selectedAnswer !== null ? `
						<button class="quiz-nav__next">
							${this.state.currentQuestion < this.state.questions.length - 1 ? 'Next →' : 'View Results'}
						</button>
					` : ''}
				</div>
			`;

			this.container.innerHTML = html;
			this.attachQuestionListeners();
		}

		/**
		 * Attach event listeners for question interface
		 */
		attachQuestionListeners() {
			const options = this.container.querySelectorAll('.question__option');
			const prevBtn = this.container.querySelector('.quiz-nav__prev');
			const nextBtn = this.container.querySelector('.quiz-nav__next');

			options.forEach(option => {
				option.addEventListener('click', () => this.selectAnswer(parseInt(option.dataset.index)));
			});

			if (prevBtn) {
				prevBtn.addEventListener('click', () => this.previousQuestion());
			}

			if (nextBtn) {
				nextBtn.addEventListener('click', () => this.nextQuestion());
			}
		}

		/**
		 * Select an answer
		 * 
		 * @param {number} answerIndex - Selected answer index
		 */
		selectAnswer(answerIndex) {
			const question = this.state.questions[this.state.currentQuestion];
			const isCorrect = answerIndex === question.correctAnswer;

			this.state.userAnswers[this.state.currentQuestion] = answerIndex;
			
			if (isCorrect) {
				this.state.score++;
			}

			this.saveState();
			this.showAnswerFeedback(answerIndex, question.correctAnswer);
		}

		/**
		 * Show answer feedback
		 * 
		 * @param {number} selected - Selected answer index
		 * @param {number} correct - Correct answer index
		 */
		showAnswerFeedback(selected, correct) {
			const options = this.container.querySelectorAll('.question__option');
			
			options.forEach((option, index) => {
				option.disabled = true;
				if (index === correct) {
					option.classList.add('correct');
				} else if (index === selected && selected !== correct) {
					option.classList.add('incorrect');
				} else {
					option.classList.add('disabled');
				}
			});

			// Show next button after a delay
			setTimeout(() => {
				this.render();
			}, 1500);
		}

		/**
		 * Navigate to previous question
		 */
		previousQuestion() {
			if (this.state.currentQuestion > 0) {
				this.state.currentQuestion--;
				this.render();
			}
		}

		/**
		 * Navigate to next question or results
		 */
		nextQuestion() {
			if (this.state.currentQuestion < this.state.questions.length - 1) {
				this.state.currentQuestion++;
				this.render();
			} else {
				this.completeQuiz();
			}
		}

		/**
		 * Complete quiz and show results
		 */
		completeQuiz() {
			this.state.completed = true;
			this.saveState();
			this.renderResults();
		}

		/**
		 * Render quiz results
		 */
		renderResults() {
			const percentage = Math.round((this.state.score / this.state.questions.length) * 100);
			const achievement = this.getAchievement(percentage);
			const badge = ACHIEVEMENT_BADGES[achievement];

			const html = `
				<div class="site-quiz__results">
					<div class="results__badge">${badge.emoji}</div>
					<h2 class="results__title">${badge.title}</h2>
					<p class="results__score">
						You scored ${this.state.score} out of ${this.state.questions.length} (${percentage}%)
					</p>
					<p class="results__message">${badge.message}</p>
					<div class="results__actions">
						<button class="results__restart">Take Quiz Again</button>
						<button class="results__review">Review Answers</button>
					</div>
				</div>
			`;

			this.container.innerHTML = html;
			this.attachResultsListeners();
		}

		/**
		 * Attach event listeners for results interface
		 */
		attachResultsListeners() {
			const restartBtn = this.container.querySelector('.results__restart');
			const reviewBtn = this.container.querySelector('.results__review');

			restartBtn.addEventListener('click', () => this.restartQuiz());
			reviewBtn.addEventListener('click', () => this.reviewAnswers());
		}

		/**
		 * Restart quiz
		 * 
		 * @return {Promise<void>}
		 */
		async restartQuiz() {
			this.clearState();
			this.state = {
				questions: [],
				currentQuestion: 0,
				userAnswers: [],
				score: 0,
				completed: false
			};
			await this.loadQuestions();
		}

		/**
		 * Review answers
		 */
		reviewAnswers() {
			this.state.currentQuestion = 0;
			this.state.completed = false;
			this.render();
		}

		/**
		 * Get achievement level based on percentage
		 * 
		 * @param {number} percentage - Score percentage
		 * @return {string} Achievement level
		 */
		getAchievement(percentage) {
			if (percentage >= ACHIEVEMENT_THRESHOLDS.gold) {
				return 'gold';
			} else if (percentage >= ACHIEVEMENT_THRESHOLDS.silver) {
				return 'silver';
			} else if (percentage >= ACHIEVEMENT_THRESHOLDS.bronze) {
				return 'bronze';
			}
			return 'bronze';
		}

		/**
		 * Render error message
		 * 
		 * @param {string} message - Error message
		 */
		renderError(message) {
			this.container.innerHTML = `
				<div class="site-quiz__error">
					<p>⚠️ ${this.escapeHtml(message)}</p>
					<button onclick="location.reload()">Retry</button>
				</div>
			`;
		}

		/**
		 * Save quiz state to localStorage
		 */
		saveState() {
			const key = `site_quiz_${this.element.dataset.blockId || 'default'}`;
			localStorage.setItem(key, JSON.stringify(this.state));
		}

		/**
		 * Load quiz state from localStorage
		 * 
		 * @return {Object|null} Saved state or null
		 */
		loadState() {
			const key = `site_quiz_${this.element.dataset.blockId || 'default'}`;
			const saved = localStorage.getItem(key);
			return saved ? JSON.parse(saved) : null;
		}

		/**
		 * Clear saved state
		 */
		clearState() {
			const key = `site_quiz_${this.element.dataset.blockId || 'default'}`;
			localStorage.removeItem(key);
		}

		/**
		 * Escape HTML to prevent XSS
		 * 
		 * @param {string} text - Text to escape
		 * @return {string} Escaped text
		 */
		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initQuizBlocks);
	} else {
		initQuizBlocks();
	}
})();
	