
/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';

/**
 * WordPress components
 */
import { 
	PanelBody, 
	RangeControl, 
	CheckboxControl,
	Notice
} from '@wordpress/components';

/**
 * React hooks
 */
import { useState, useEffect } from '@wordpress/element';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * Available question patterns
 * 
 * @constant {Array<Object>}
 */
const QUESTION_PATTERNS = [
	{ id: 'publication-date', label: __('Publication Date', 'site-quiz'), description: __('Questions about when posts were published', 'site-quiz') },
	{ id: 'author', label: __('Post Author', 'site-quiz'), description: __('Match posts to their authors', 'site-quiz') },
	{ id: 'tag', label: __('Tag Identification', 'site-quiz'), description: __('Find unrelated tags', 'site-quiz') },
	{ id: 'category', label: __('Category Matching', 'site-quiz'), description: __('Identify post categories', 'site-quiz') },
	{ id: 'image-count', label: __('Image Count', 'site-quiz'), description: __('Count images in posts', 'site-quiz') },
	{ id: 'word-count', label: __('Word Count Range', 'site-quiz'), description: __('Estimate word counts', 'site-quiz') },
	{ id: 'comment-count', label: __('Comment Count', 'site-quiz'), description: __('Posts by comment count', 'site-quiz') }
];

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @param {Object}   props               Component props
 * @param {Object}   props.attributes    Block attributes
 * @param {Function} props.setAttributes Function to update attributes
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const { questionCount, enabledPatterns } = attributes;
	const [previewPatterns, setPreviewPatterns] = useState([]);

	/**
	 * Update preview patterns when enabled patterns change
	 */
	useEffect(() => {
		const preview = QUESTION_PATTERNS.filter(pattern => 
			enabledPatterns.includes(pattern.id)
		);
		setPreviewPatterns(preview);
	}, [enabledPatterns]);

	/**
	 * Handle question count change
	 * 
	 * @param {number} value New question count
	 */
	const onQuestionCountChange = (value) => {
		setAttributes({ questionCount: value });
	};

	/**
	 * Handle pattern toggle
	 * 
	 * @param {string}  patternId Pattern identifier
	 * @param {boolean} checked   Whether pattern is enabled
	 */
	const onPatternToggle = (patternId, checked) => {
		let newPatterns;
		if (checked) {
			newPatterns = [...enabledPatterns, patternId];
		} else {
			newPatterns = enabledPatterns.filter(id => id !== patternId);
		}
		setAttributes({ enabledPatterns: newPatterns });
	};

	const blockProps = useBlockProps({
		className: 'site-quiz-editor'
	});

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Quiz Settings', 'site-quiz')} initialOpen={true}>
					<RangeControl
						label={__('Number of Questions', 'site-quiz')}
						value={questionCount}
						onChange={onQuestionCountChange}
						min={3}
						max={20}
						help={__('Select between 3 and 20 questions for your quiz', 'site-quiz')}
					/>
				</PanelBody>
				
				<PanelBody title={__('Question Patterns', 'site-quiz')} initialOpen={true}>
					<p className="components-base-control__help">
						{__('Enable or disable specific question types', 'site-quiz')}
					</p>
					{QUESTION_PATTERNS.map(pattern => (
						<CheckboxControl
							key={pattern.id}
							label={pattern.label}
							help={pattern.description}
							checked={enabledPatterns.includes(pattern.id)}
							onChange={(checked) => onPatternToggle(pattern.id, checked)}
						/>
					))}
					{enabledPatterns.length === 0 && (
						<Notice status="warning" isDismissible={false}>
							{__('Please enable at least one question pattern', 'site-quiz')}
						</Notice>
					)}
				</PanelBody>

				<PanelBody title={__('Active Patterns Preview', 'site-quiz')} initialOpen={false}>
					{previewPatterns.length > 0 ? (
						<ul className="site-quiz-pattern-preview">
							{previewPatterns.map(pattern => (
								<li key={pattern.id}>
									<strong>{pattern.label}</strong>
									<br />
									<small>{pattern.description}</small>
								</li>
							))}
						</ul>
					) : (
						<p className="components-base-control__help">
							{__('No patterns enabled. Enable patterns above to see them here.', 'site-quiz')}
						</p>
					)}
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<div className="site-quiz__preview">
					<div className="site-quiz__icon">
						<svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" fill="currentColor"/>
						</svg>
					</div>
					<h3>{__('Site Quiz Block', 'site-quiz')}</h3>
					<p className="site-quiz__config">
						{questionCount} {__('questions', 'site-quiz')} • {enabledPatterns.length} {__('pattern(s) enabled', 'site-quiz')}
					</p>
					{enabledPatterns.length === 0 && (
						<div className="site-quiz__warning">
							⚠️ {__('Please enable at least one question pattern in the sidebar', 'site-quiz')}
						</div>
					)}
					{enabledPatterns.length > 0 && (
						<div className="site-quiz__patterns">
							<strong>{__('Active Patterns:', 'site-quiz')}</strong>
							<ul>
								{previewPatterns.map(pattern => (
									<li key={pattern.id}>{pattern.label}</li>
								))}
							</ul>
						</div>
					)}
					<p className="site-quiz__help">
						{__('Configure quiz settings in the block sidebar. The quiz will appear on the frontend with interactive questions generated from your posts.', 'site-quiz')}
					</p>
				</div>
			</div>
		</>
	);
}
	