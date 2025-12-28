<?php
// This file is generated. Do not modify it manually.
return array(
	'build' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'telex/block-site-quiz',
		'version' => '0.1.0',
		'title' => 'Site Quiz',
		'category' => 'widgets',
		'icon' => 'welcome-learn-more',
		'description' => 'A dynamic quiz block that generates questions from your site\'s posts',
		'example' => array(
			'attributes' => array(
				'questionCount' => 5,
				'enabledPatterns' => array(
					'publication-date',
					'author',
					'category'
				)
			)
		),
		'attributes' => array(
			'questionCount' => array(
				'type' => 'number',
				'default' => 5
			),
			'enabledPatterns' => array(
				'type' => 'array',
				'default' => array(
					'publication-date',
					'author',
					'tag',
					'category',
					'image-count',
					'word-count',
					'comment-count'
				)
			)
		),
		'supports' => array(
			'html' => false,
			'align' => true,
			'spacing' => array(
				'padding' => true,
				'margin' => true
			),
			'color' => array(
				'background' => true,
				'text' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true
			),
			'interactivity' => true
		),
		'styles' => array(
			array(
				'name' => 'default',
				'label' => 'Minimal',
				'isDefault' => true
			),
			array(
				'name' => 'card',
				'label' => 'Card'
			),
			array(
				'name' => 'gradient',
				'label' => 'Gradient'
			),
			array(
				'name' => 'bold',
				'label' => 'Bold'
			)
		),
		'textdomain' => 'site-quiz',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'viewScript' => 'file:./view.js',
		'render' => 'file:./render.php'
	)
);
