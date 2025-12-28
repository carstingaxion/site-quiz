<?php
/**
 * Render callback for Site Quiz block
 *
 * @param array    $attributes Block attributes
 * @param string   $content    Block content
 * @param WP_Block $block      Block instance
 * @return string Rendered HTML
 * 
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$question_count   = isset( $attributes['questionCount'] ) ? absint( $attributes['questionCount'] ) : 5;
$enabled_patterns = isset( $attributes['enabledPatterns'] ) ? $attributes['enabledPatterns'] : array();
$block_id         = uniqid( 'quiz_' );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'data-block-id'         => esc_attr( $block_id ),
		'data-question-count'   => esc_attr( $question_count ),
		'data-enabled-patterns' => esc_attr( wp_json_encode( $enabled_patterns ) ),
	)
);
?>

<div <?php echo $wrapper_attributes; ?>>
	<div class="site-quiz__container">
		<div class="site-quiz__loading">
			<div class="site-quiz__spinner"></div>
			<p><?php esc_html_e( 'Loading quiz...', 'site-quiz' ); ?></p>
		</div>
	</div>
</div>