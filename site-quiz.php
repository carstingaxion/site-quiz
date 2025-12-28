<?php
/**
 * Plugin Name:       Site Quiz
 * Description:       A dynamic quiz block that generates questions from your site's posts with achievement badges and progress tracking.
 * Version:           0.1.0
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:            WordPress Telex
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       site-quiz
 *
 * @package SiteQuiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// File: includes/class-pattern-interface.php
// ============================================================================

/**
 * Question Pattern Interface
 * 
 * Defines the contract for all question pattern implementations.
 * 
 * @since 0.1.0
 */
interface Site_Quiz_Pattern_Interface {
	/**
	 * Get pattern identifier
	 * 
	 * @return string Unique pattern ID
	 */
	public function get_id();

	/**
	 * Get pattern name
	 * 
	 * @return string Human-readable pattern name
	 */
	public function get_name();

	/**
	 * Get pattern description
	 * 
	 * @return string Pattern description
	 */
	public function get_description();

	/**
	 * Check if pattern can generate questions
	 * 
	 * @return bool True if pattern has sufficient data
	 */
	public function can_generate();

	/**
	 * Generate a question
	 * 
	 * @return array|WP_Error Question data or error
	 */
	public function generate_question();
}

// ============================================================================
// File: includes/class-pattern-registry.php
// ============================================================================

/**
 * Question Pattern Registry
 * 
 * Manages registration and retrieval of question patterns.
 * 
 * @since 0.1.0
 */
class Site_Quiz_Pattern_Registry {
	/**
	 * Registered patterns
	 * 
	 * @var array<string, Site_Quiz_Pattern_Interface>
	 */
	private $patterns = array();

	/**
	 * Register a question pattern
	 * 
	 * @param Site_Quiz_Pattern_Interface $pattern Pattern to register
	 * @return bool True on success, false if pattern ID already exists
	 */
	public function register( Site_Quiz_Pattern_Interface $pattern ) {
		$id = $pattern->get_id();
		
		if ( isset( $this->patterns[ $id ] ) ) {
			return false;
		}
		
		$this->patterns[ $id ] = $pattern;
		return true;
	}

	/**
	 * Get a registered pattern
	 * 
	 * @param string $id Pattern identifier
	 * @return Site_Quiz_Pattern_Interface|null Pattern instance or null
	 */
	public function get( $id ) {
		return isset( $this->patterns[ $id ] ) ? $this->patterns[ $id ] : null;
	}

	/**
	 * Get all registered patterns
	 * 
	 * @return array<string, Site_Quiz_Pattern_Interface> All patterns
	 */
	public function get_all() {
		return $this->patterns;
	}

	/**
	 * Get enabled patterns
	 * 
	 * @param array $enabled_ids Array of enabled pattern IDs
	 * @return array<Site_Quiz_Pattern_Interface> Enabled patterns that can generate
	 */
	public function get_enabled( $enabled_ids ) {
		$enabled = array();
		
		foreach ( $enabled_ids as $id ) {
			$pattern = $this->get( $id );
			if ( $pattern && $pattern->can_generate() ) {
				$enabled[] = $pattern;
			}
		}
		
		return $enabled;
	}

	/**
	 * Generate questions using enabled patterns
	 * 
	 * @param int   $count        Number of questions to generate
	 * @param array $enabled_ids  Array of enabled pattern IDs
	 * @return array|WP_Error Array of questions or error
	 */
	public function generate_questions( $count, $enabled_ids ) {
		$enabled = $this->get_enabled( $enabled_ids );
		
		if ( empty( $enabled ) ) {
			return new WP_Error(
				'no_patterns',
				__( 'No enabled patterns are available to generate questions.', 'site-quiz' )
			);
		}
		
		$questions = array();
		$attempts  = 0;
		$max_attempts = $count * 3;
		
		while ( count( $questions ) < $count && $attempts < $max_attempts ) {
			$pattern = $enabled[ array_rand( $enabled ) ];
			$question = $pattern->generate_question();
			
			if ( ! is_wp_error( $question ) ) {
				$questions[] = $question;
			}
			
			$attempts++;
		}
		
		if ( count( $questions ) < $count ) {
			return new WP_Error(
				'insufficient_questions',
				sprintf(
					/* translators: %d: number of questions generated */
					__( 'Could only generate %d questions. Your site may not have enough content.', 'site-quiz' ),
					count( $questions )
				)
			);
		}
		
		return $questions;
	}
}

// ============================================================================
// File: includes/patterns/class-publication-date-pattern.php
// ============================================================================

/**
 * Publication Date Question Pattern
 * 
 * Generates questions about post publication dates.
 * 
 * @since 0.1.0
 */
class Site_Quiz_Publication_Date_Pattern implements Site_Quiz_Pattern_Interface {
	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'publication-date';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Publication Date', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description() {
		return __( 'Questions about when posts were published', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function can_generate() {
		$count = wp_count_posts( 'post' );
		return ( $count->publish >= 4 );
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate_question() {
		$posts = get_posts(
			array(
				'numberposts' => 4,
				'orderby'     => 'rand',
				'post_status' => 'publish',
			)
		);

		if ( count( $posts ) < 4 ) {
			return new WP_Error( 'insufficient_posts', __( 'Not enough posts', 'site-quiz' ) );
		}

		$correct_post = $posts[0];
		$options = array_map(
			function( $post ) {
				return wp_date( 'F Y', strtotime( $post->post_date ) );
			},
			$posts
		);

		return array(
			'question'      => sprintf(
				/* translators: %s: post title */
				__( 'When was "%s" published?', 'site-quiz' ),
				get_the_title( $correct_post )
			),
			'options'       => $options,
			'correctAnswer' => 0,
			'pattern'       => $this->get_id(),
		);
	}
}

// ============================================================================
// File: includes/patterns/class-author-pattern.php
// ============================================================================

/**
 * Author Question Pattern
 * 
 * Generates questions about post authors.
 * 
 * @since 0.1.0
 */
class Site_Quiz_Author_Pattern implements Site_Quiz_Pattern_Interface {
	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'author';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Post Author', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description() {
		return __( 'Match posts to their authors', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function can_generate() {
		$authors = get_users(
			array(
				'who'    => 'authors',
				'fields' => 'ID',
			)
		);
		return ( count( $authors ) >= 2 );
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate_question() {
		$authors = get_users(
			array(
				'who'      => 'authors',
				'orderby'  => 'rand',
				'number'   => 4,
			)
		);

		if ( count( $authors ) < 2 ) {
			return new WP_Error( 'insufficient_authors', __( 'Not enough authors', 'site-quiz' ) );
		}

		$correct_author = $authors[0];
		$posts = get_posts(
			array(
				'author'      => $correct_author->ID,
				'numberposts' => 1,
				'orderby'     => 'rand',
			)
		);

		if ( empty( $posts ) ) {
			return new WP_Error( 'no_posts', __( 'Author has no posts', 'site-quiz' ) );
		}

		$options = array_map(
			function( $author ) {
				return $author->display_name;
			},
			$authors
		);

		return array(
			'question'      => sprintf(
				/* translators: %s: post title */
				__( 'Who wrote "%s"?', 'site-quiz' ),
				get_the_title( $posts[0] )
			),
			'options'       => $options,
			'correctAnswer' => 0,
			'pattern'       => $this->get_id(),
		);
	}
}

// ============================================================================
// File: includes/patterns/class-tag-pattern.php
// ============================================================================

/**
 * Tag Question Pattern
 * 
 * Generates questions about post tags.
 * 
 * @since 0.1.0
 */
class Site_Quiz_Tag_Pattern implements Site_Quiz_Pattern_Interface {
	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'tag';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Tag Identification', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description() {
		return __( 'Find unrelated tags', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function can_generate() {
		$tag_count = wp_count_terms(
			array(
				'taxonomy'   => 'post_tag',
				'hide_empty' => true,
			)
		);
		return ( $tag_count >= 8 );
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate_question() {
		$posts = get_posts(
			array(
				'numberposts' => 1,
				'orderby'     => 'rand',
				'tax_query'   => array(
					array(
						'taxonomy' => 'post_tag',
						'operator' => 'EXISTS',
					),
				),
			)
		);

		if ( empty( $posts ) ) {
			return new WP_Error( 'no_tagged_posts', __( 'No posts with tags', 'site-quiz' ) );
		}

		$post = $posts[0];
		$post_tags = wp_get_post_tags( $post->ID );

		if ( count( $post_tags ) < 2 ) {
			return new WP_Error( 'insufficient_tags', __( 'Post has too few tags', 'site-quiz' ) );
		}

		$all_tags = get_tags(
			array(
				'orderby' => 'rand',
				'number'  => 10,
				'exclude' => wp_list_pluck( $post_tags, 'term_id' ),
			)
		);

		if ( empty( $all_tags ) ) {
			return new WP_Error( 'no_other_tags', __( 'Not enough other tags', 'site-quiz' ) );
		}

		$correct_tags = array_slice( $post_tags, 0, 3 );
		$wrong_tag = $all_tags[0];

		$options = array_merge( $correct_tags, array( $wrong_tag ) );
		shuffle( $options );

		$correct_index = array_search( $wrong_tag, $options, true );

		return array(
			'question'      => sprintf(
				/* translators: %s: post title */
				__( 'Which tag is NOT associated with "%s"?', 'site-quiz' ),
				get_the_title( $post )
			),
			'options'       => array_map(
				function( $tag ) {
					return $tag->name;
				},
				$options
			),
			'correctAnswer' => $correct_index,
			'pattern'       => $this->get_id(),
		);
	}
}

// ============================================================================
// File: includes/patterns/class-category-pattern.php
// ============================================================================

/**
 * Category Question Pattern
 * 
 * Generates questions about post categories.
 * 
 * @since 0.1.0
 */
class Site_Quiz_Category_Pattern implements Site_Quiz_Pattern_Interface {
	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'category';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Category Matching', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description() {
		return __( 'Identify post categories', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function can_generate() {
		$cat_count = wp_count_terms(
			array(
				'taxonomy'   => 'category',
				'hide_empty' => true,
			)
		);
		return ( $cat_count >= 4 );
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate_question() {
		$categories = get_categories(
			array(
				'orderby'    => 'rand',
				'number'     => 4,
				'hide_empty' => true,
			)
		);

		if ( count( $categories ) < 4 ) {
			return new WP_Error( 'insufficient_categories', __( 'Not enough categories', 'site-quiz' ) );
		}

		$correct_category = $categories[0];
		$posts = get_posts(
			array(
				'category'    => $correct_category->term_id,
				'numberposts' => 1,
				'orderby'     => 'rand',
			)
		);

		if ( empty( $posts ) ) {
			return new WP_Error( 'no_posts', __( 'Category has no posts', 'site-quiz' ) );
		}

		return array(
			'question'      => sprintf(
				/* translators: %s: post title */
				__( 'Which category does "%s" belong to?', 'site-quiz' ),
				get_the_title( $posts[0] )
			),
			'options'       => array_map(
				function( $cat ) {
					return $cat->name;
				},
				$categories
			),
			'correctAnswer' => 0,
			'pattern'       => $this->get_id(),
		);
	}
}

// ============================================================================
// File: includes/patterns/class-image-count-pattern.php
// ============================================================================

/**
 * Image Count Question Pattern
 * 
 * Generates questions about number of images in posts.
 * 
 * @since 0.1.0
 */
class Site_Quiz_Image_Count_Pattern implements Site_Quiz_Pattern_Interface {
	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'image-count';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Image Count', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description() {
		return __( 'Count images in posts', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function can_generate() {
		$posts = get_posts(
			array(
				'numberposts' => 5,
				'post_status' => 'publish',
			)
		);
		return ( count( $posts ) >= 5 );
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate_question() {
		$posts = get_posts(
			array(
				'numberposts' => 10,
				'orderby'     => 'rand',
			)
		);

		if ( empty( $posts ) ) {
			return new WP_Error( 'no_posts', __( 'No posts available', 'site-quiz' ) );
		}

		$post_with_images = null;
		foreach ( $posts as $post ) {
			$image_count = substr_count( $post->post_content, '<img' );
			if ( $image_count > 0 ) {
				$post_with_images = $post;
				break;
			}
		}

		if ( ! $post_with_images ) {
			return new WP_Error( 'no_images', __( 'No posts with images found', 'site-quiz' ) );
		}

		$correct_count = substr_count( $post_with_images->post_content, '<img' );
		$options = array( $correct_count );

		while ( count( $options ) < 4 ) {
			$option = $correct_count + rand( -2, 3 );
			if ( $option >= 0 && ! in_array( $option, $options, true ) ) {
				$options[] = $option;
			}
		}

		shuffle( $options );
		$correct_index = array_search( $correct_count, $options, true );

		return array(
			'question'      => sprintf(
				/* translators: %s: post title */
				__( 'How many images are in "%s"?', 'site-quiz' ),
				get_the_title( $post_with_images )
			),
			'options'       => $options,
			'correctAnswer' => $correct_index,
			'pattern'       => $this->get_id(),
		);
	}
}

// ============================================================================
// File: includes/patterns/class-word-count-pattern.php
// ============================================================================

/**
 * Word Count Question Pattern
 * 
 * Generates questions about post word counts.
 * 
 * @since 0.1.0
 */
class Site_Quiz_Word_Count_Pattern implements Site_Quiz_Pattern_Interface {
	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'word-count';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Word Count Range', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description() {
		return __( 'Estimate word counts', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function can_generate() {
		$count = wp_count_posts( 'post' );
		return ( $count->publish >= 5 );
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate_question() {
		$posts = get_posts(
			array(
				'numberposts' => 1,
				'orderby'     => 'rand',
			)
		);

		if ( empty( $posts ) ) {
			return new WP_Error( 'no_posts', __( 'No posts available', 'site-quiz' ) );
		}

		$post = $posts[0];
		$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );

		if ( $word_count < 50 ) {
			return new WP_Error( 'post_too_short', __( 'Post too short', 'site-quiz' ) );
		}

		$ranges = array(
			array( 0, 100, __( '0-100 words', 'site-quiz' ) ),
			array( 100, 300, __( '100-300 words', 'site-quiz' ) ),
			array( 300, 600, __( '300-600 words', 'site-quiz' ) ),
			array( 600, 1000, __( '600-1000 words', 'site-quiz' ) ),
			array( 1000, 2000, __( '1000-2000 words', 'site-quiz' ) ),
			array( 2000, 5000, __( '2000+ words', 'site-quiz' ) ),
		);

		$correct_range = null;
		$correct_index = 0;

		foreach ( $ranges as $index => $range ) {
			if ( $word_count >= $range[0] && ( $word_count < $range[1] || $range[1] === 5000 ) ) {
				$correct_range = $range;
				$correct_index = $index;
				break;
			}
		}

		$selected_ranges = array( $correct_range );
		foreach ( $ranges as $range ) {
			if ( $range !== $correct_range && count( $selected_ranges ) < 4 ) {
				$selected_ranges[] = $range;
			}
		}

		shuffle( $selected_ranges );
		$correct_answer = array_search( $correct_range, $selected_ranges, true );

		return array(
			'question'      => sprintf(
				/* translators: %s: post title */
				__( 'Approximately how many words are in "%s"?', 'site-quiz' ),
				get_the_title( $post )
			),
			'options'       => array_column( $selected_ranges, 2 ),
			'correctAnswer' => $correct_answer,
			'pattern'       => $this->get_id(),
		);
	}
}

// ============================================================================
// File: includes/patterns/class-comment-count-pattern.php
// ============================================================================

/**
 * Comment Count Question Pattern
 * 
 * Generates questions about post comment counts.
 * 
 * @since 0.1.0
 */
class Site_Quiz_Comment_Count_Pattern implements Site_Quiz_Pattern_Interface {
	/**
	 * {@inheritdoc}
	 */
	public function get_id() {
		return 'comment-count';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name() {
		return __( 'Comment Count', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description() {
		return __( 'Posts by comment count', 'site-quiz' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function can_generate() {
		$posts = get_posts(
			array(
				'numberposts' => 4,
				'post_status' => 'publish',
			)
		);
		return ( count( $posts ) >= 4 );
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate_question() {
		$posts = get_posts(
			array(
				'numberposts' => 4,
				'orderby'     => 'comment_count',
				'order'       => 'DESC',
			)
		);

		if ( count( $posts ) < 4 ) {
			return new WP_Error( 'insufficient_posts', __( 'Not enough posts', 'site-quiz' ) );
		}

		$has_comments = false;
		foreach ( $posts as $post ) {
			if ( $post->comment_count > 0 ) {
				$has_comments = true;
				break;
			}
		}

		if ( ! $has_comments ) {
			return new WP_Error( 'no_comments', __( 'No posts with comments', 'site-quiz' ) );
		}

		$options = array();
		foreach ( $posts as $post ) {
			$options[] = sprintf(
				/* translators: %d: number of comments */
				_n( '%d comment', '%d comments', $post->comment_count, 'site-quiz' ),
				$post->comment_count
			);
		}

		return array(
			'question'      => sprintf(
				/* translators: %s: post title */
				__( 'How many comments does "%s" have?', 'site-quiz' ),
				get_the_title( $posts[0] )
			),
			'options'       => $options,
			'correctAnswer' => 0,
			'pattern'       => $this->get_id(),
		);
	}
}

// ============================================================================
// File: includes/class-demo-data-generator.php
// ============================================================================

/**
 * Demo Data Generator
 * 
 * Generates comprehensive demo content for all quiz patterns.
 * Enabled by default for local environments.
 * 
 * @since 0.1.0
 */
class Site_Quiz_Demo_Data_Generator {
	/**
	 * Option name for tracking demo data
	 * 
	 * @var string
	 */
	const OPTION_NAME = 'site_quiz_demo_data';

	/**
	 * Demo content data
	 * 
	 * @var array
	 */
	private $demo_data = array(
		'authors'    => array(),
		'categories' => array(),
		'tags'       => array(),
		'posts'      => array(),
		'comments'   => array(),
	);

	/**
	 * Check if demo data is enabled
	 * 
	 * @return bool
	 */
	public function is_enabled() {
		// Enable for local/development environments
		if ( defined( 'WP_ENVIRONMENT_TYPE' ) ) {
			return in_array( WP_ENVIRONMENT_TYPE, array( 'local', 'development' ), true );
		}
		
		// Fallback: check if site URL contains localhost or .local
		$site_url = get_site_url();
		return ( strpos( $site_url, 'localhost' ) !== false || strpos( $site_url, '.local' ) !== false );
	}

	/**
	 * Check if demo data exists
	 * 
	 * @return bool
	 */
	public function has_demo_data() {
		$data = get_option( self::OPTION_NAME, array() );
		return ! empty( $data['posts'] );
	}

	/**
	 * Generate all demo data
	 * 
	 * @return array|WP_Error Generated data IDs or error
	 */
	public function generate_all() {
		if ( $this->has_demo_data() ) {
			return new WP_Error( 'demo_exists', __( 'Demo data already exists', 'site-quiz' ) );
		}

		// Generate authors
		$this->generate_authors();

		// Generate categories
		$this->generate_categories();

		// Generate tags
		$this->generate_tags();

		// Generate posts with varied content
		$this->generate_posts();

		// Generate comments
		$this->generate_comments();

		// Save demo data IDs
		update_option( self::OPTION_NAME, $this->demo_data );

		return $this->demo_data;
	}

	/**
	 * Generate demo authors
	 * 
	 * @return void
	 */
	private function generate_authors() {
		$authors = array(
			array( 'login' => 'alice_writer', 'display' => 'Alice Writer', 'email' => 'alice@demo.test' ),
			array( 'login' => 'bob_blogger', 'display' => 'Bob Blogger', 'email' => 'bob@demo.test' ),
			array( 'login' => 'carol_author', 'display' => 'Carol Author', 'email' => 'carol@demo.test' ),
			array( 'login' => 'dave_editor', 'display' => 'Dave Editor', 'email' => 'dave@demo.test' ),
		);

		foreach ( $authors as $author ) {
			$user_id = wp_create_user( $author['login'], wp_generate_password(), $author['email'] );
			
			if ( ! is_wp_error( $user_id ) ) {
				wp_update_user(
					array(
						'ID'           => $user_id,
						'display_name' => $author['display'],
						'role'         => 'author',
					)
				);
				$this->demo_data['authors'][] = $user_id;
			}
		}
	}

	/**
	 * Generate demo categories
	 * 
	 * @return void
	 */
	private function generate_categories() {
		$categories = array(
			'Technology',
			'Travel',
			'Food & Cooking',
			'Health & Fitness',
			'Business',
			'Entertainment',
		);

		foreach ( $categories as $category ) {
			$term = wp_insert_term( $category, 'category' );
			
			if ( ! is_wp_error( $term ) ) {
				$this->demo_data['categories'][] = $term['term_id'];
			}
		}
	}

	/**
	 * Generate demo tags
	 * 
	 * @return void
	 */
	private function generate_tags() {
		$tags = array(
			'innovation', 'tutorial', 'guide', 'tips', 'review',
			'news', 'analysis', 'opinion', 'how-to', 'beginner',
			'advanced', 'trending', 'featured', 'popular', 'research',
		);

		foreach ( $tags as $tag ) {
			$term = wp_insert_term( $tag, 'post_tag' );
			
			if ( ! is_wp_error( $term ) ) {
				$this->demo_data['tags'][] = $term['term_id'];
			}
		}
	}

	/**
	 * Generate demo posts
	 * 
	 * @return void
	 */
	private function generate_posts() {
		$post_templates = array(
			array(
				'title'      => 'Getting Started with WordPress Blocks',
				'word_count' => 450,
				'images'     => 2,
				'months_ago' => 1,
			),
			array(
				'title'      => 'Top 10 Travel Destinations for 2024',
				'word_count' => 800,
				'images'     => 5,
				'months_ago' => 2,
			),
			array(
				'title'      => 'Healthy Breakfast Recipes',
				'word_count' => 650,
				'images'     => 3,
				'months_ago' => 3,
			),
			array(
				'title'      => 'Understanding Artificial Intelligence',
				'word_count' => 1200,
				'images'     => 1,
				'months_ago' => 4,
			),
			array(
				'title'      => 'Fitness Tips for Busy Professionals',
				'word_count' => 550,
				'images'     => 4,
				'months_ago' => 5,
			),
			array(
				'title'      => 'The Future of Remote Work',
				'word_count' => 950,
				'images'     => 2,
				'months_ago' => 6,
			),
			array(
				'title'      => 'Best Practices for Web Design',
				'word_count' => 720,
				'images'     => 6,
				'months_ago' => 7,
			),
			array(
				'title'      => 'Exploring National Parks',
				'word_count' => 880,
				'images'     => 8,
				'months_ago' => 8,
			),
			array(
				'title'      => 'Cybersecurity Essentials',
				'word_count' => 1100,
				'images'     => 1,
				'months_ago' => 9,
			),
			array(
				'title'      => 'Quick Dinner Ideas',
				'word_count' => 380,
				'images'     => 7,
				'months_ago' => 10,
			),
			array(
				'title'      => 'Introduction to Photography',
				'word_count' => 620,
				'images'     => 10,
				'months_ago' => 11,
			),
			array(
				'title'      => 'Building a Personal Brand',
				'word_count' => 850,
				'images'     => 3,
				'months_ago' => 12,
			),
		);

		foreach ( $post_templates as $index => $template ) {
			// Rotate through authors
			$author_id = ! empty( $this->demo_data['authors'] ) 
				? $this->demo_data['authors'][ $index % count( $this->demo_data['authors'] ) ]
				: get_current_user_id();

			// Generate content with specified word count
			$content = $this->generate_content( $template['word_count'], $template['images'] );

			// Calculate post date
			$post_date = date( 'Y-m-d H:i:s', strtotime( "-{$template['months_ago']} months" ) );

			// Create post
			$post_id = wp_insert_post(
				array(
					'post_title'   => $template['title'],
					'post_content' => $content,
					'post_status'  => 'publish',
					'post_author'  => $author_id,
					'post_date'    => $post_date,
					'post_type'    => 'post',
				)
			);

			if ( ! is_wp_error( $post_id ) ) {
				// Assign categories (1-2 per post)
				if ( ! empty( $this->demo_data['categories'] ) ) {
					$cat_count = rand( 1, 2 );
					$categories = array_rand( array_flip( $this->demo_data['categories'] ), $cat_count );
					wp_set_post_categories( $post_id, (array) $categories );
				}

				// Assign tags (3-5 per post)
				if ( ! empty( $this->demo_data['tags'] ) ) {
					$tag_count = rand( 3, 5 );
					$tags = array_rand( array_flip( $this->demo_data['tags'] ), $tag_count );
					wp_set_post_tags( $post_id, $tags, false );
				}

				$this->demo_data['posts'][] = $post_id;
			}
		}
	}

	/**
	 * Generate post content with specified word count and images
	 * 
	 * @param int $word_count Target word count
	 * @param int $image_count Number of images to include
	 * @return string Generated content
	 */
	private function generate_content( $word_count, $image_count ) {
		$paragraphs = array(
			'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
			'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
			'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
			'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
			'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.',
			'Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores.',
			'Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit.',
			'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti.',
		);

		$content = '';
		$current_words = 0;
		$images_added = 0;

		while ( $current_words < $word_count ) {
			$paragraph = $paragraphs[ array_rand( $paragraphs ) ];
			$content .= "<p>$paragraph</p>\n\n";
			$current_words += str_word_count( $paragraph );

			// Add images at intervals
			if ( $images_added < $image_count && $current_words > ( $word_count / $image_count ) * ( $images_added + 1 ) ) {
				$content .= '<img src="https://via.placeholder.com/800x400" alt="Demo image" />' . "\n\n";
				$images_added++;
			}
		}

		// Add any remaining images at the end
		while ( $images_added < $image_count ) {
			$content .= '<img src="https://via.placeholder.com/800x400" alt="Demo image" />' . "\n\n";
			$images_added++;
		}

		return $content;
	}

	/**
	 * Generate demo comments
	 * 
	 * @return void
	 */
	private function generate_comments() {
		if ( empty( $this->demo_data['posts'] ) ) {
			return;
		}

		$comment_texts = array(
			'Great article! Very informative.',
			'Thanks for sharing this helpful information.',
			'I learned a lot from this post.',
			'Excellent content, keep it up!',
			'This is exactly what I was looking for.',
			'Very well written and explained.',
			'Looking forward to more posts like this.',
			'Interesting perspective on the topic.',
		);

		$authors = array(
			array( 'name' => 'John Reader', 'email' => 'john@demo.test' ),
			array( 'name' => 'Jane Commenter', 'email' => 'jane@demo.test' ),
			array( 'name' => 'Mike Visitor', 'email' => 'mike@demo.test' ),
			array( 'name' => 'Sarah User', 'email' => 'sarah@demo.test' ),
		);

		// Add varying number of comments to posts
		foreach ( $this->demo_data['posts'] as $index => $post_id ) {
			// Vary comment count: 0-10 comments per post
			$comment_count = ( $index < 4 ) ? rand( 5, 10 ) : rand( 0, 4 );

			for ( $i = 0; $i < $comment_count; $i++ ) {
				$author = $authors[ array_rand( $authors ) ];
				
				$comment_id = wp_insert_comment(
					array(
						'comment_post_ID'      => $post_id,
						'comment_author'       => $author['name'],
						'comment_author_email' => $author['email'],
						'comment_content'      => $comment_texts[ array_rand( $comment_texts ) ],
						'comment_approved'     => 1,
						'comment_date'         => date( 'Y-m-d H:i:s', strtotime( "-" . rand( 1, 30 ) . " days" ) ),
					)
				);

				if ( $comment_id ) {
					$this->demo_data['comments'][] = $comment_id;
				}
			}
		}
	}

	/**
	 * Delete all demo data
	 * 
	 * @return bool True on success
	 */
	public function delete_all() {
		$data = get_option( self::OPTION_NAME, array() );

		if ( empty( $data ) ) {
			return false;
		}

		// Delete comments
		if ( ! empty( $data['comments'] ) ) {
			foreach ( $data['comments'] as $comment_id ) {
				wp_delete_comment( $comment_id, true );
			}
		}

		// Delete posts
		if ( ! empty( $data['posts'] ) ) {
			foreach ( $data['posts'] as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}

		// Delete tags
		if ( ! empty( $data['tags'] ) ) {
			foreach ( $data['tags'] as $tag_id ) {
				wp_delete_term( $tag_id, 'post_tag' );
			}
		}

		// Delete categories
		if ( ! empty( $data['categories'] ) ) {
			foreach ( $data['categories'] as $cat_id ) {
				wp_delete_term( $cat_id, 'category' );
			}
		}

		// Delete authors
		if ( ! empty( $data['authors'] ) ) {
			foreach ( $data['authors'] as $author_id ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
				wp_delete_user( $author_id );
			}
		}

		// Remove option
		delete_option( self::OPTION_NAME );

		return true;
	}
}

// ============================================================================
// File: site-quiz.php (Main Plugin Class)
// ============================================================================

/**
 * Main Site Quiz Plugin Class
 * 
 * Singleton pattern implementation for the plugin.
 * 
 * @since 0.1.0
 */
final class Site_Quiz_Plugin {
	/**
	 * Plugin instance
	 * 
	 * @var Site_Quiz_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Question pattern registry
	 * 
	 * @var Site_Quiz_Pattern_Registry|null
	 */
	private $pattern_registry = null;

	/**
	 * Demo data generator
	 * 
	 * @var Site_Quiz_Demo_Data_Generator|null
	 */
	private $demo_generator = null;

	/**
	 * Get plugin instance
	 * 
	 * @return Site_Quiz_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->define_constants();
		$this->init_hooks();
	}

	/**
	 * Define plugin constants
	 * 
	 * @return void
	 */
	private function define_constants() {
		if ( ! defined( 'SITE_QUIZ_VERSION' ) ) {
			define( 'SITE_QUIZ_VERSION', '0.1.0' );
		}
		if ( ! defined( 'SITE_QUIZ_PLUGIN_DIR' ) ) {
			define( 'SITE_QUIZ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}
		if ( ! defined( 'SITE_QUIZ_PLUGIN_URL' ) ) {
			define( 'SITE_QUIZ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}
	}

	/**
	 * Initialize WordPress hooks
	 * 
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'init', array( $this, 'register_patterns' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'admin_notices', array( $this, 'show_demo_notice' ) );
		add_action( 'admin_init', array( $this, 'handle_demo_actions' ) );
	}

	/**
	 * Register the block type
	 * 
	 * @return void
	 */
	public function register_block() {
		register_block_type(
			SITE_QUIZ_PLUGIN_DIR . 'build/',
			array(
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * Register question patterns
	 * 
	 * @return void
	 */
	public function register_patterns() {
		$this->pattern_registry = new Site_Quiz_Pattern_Registry();
		
		// Register default patterns
		$this->pattern_registry->register( new Site_Quiz_Publication_Date_Pattern() );
		$this->pattern_registry->register( new Site_Quiz_Author_Pattern() );
		$this->pattern_registry->register( new Site_Quiz_Tag_Pattern() );
		$this->pattern_registry->register( new Site_Quiz_Category_Pattern() );
		$this->pattern_registry->register( new Site_Quiz_Image_Count_Pattern() );
		$this->pattern_registry->register( new Site_Quiz_Word_Count_Pattern() );
		$this->pattern_registry->register( new Site_Quiz_Comment_Count_Pattern() );

		/**
		 * Fires after default patterns are registered
		 * 
		 * @param Site_Quiz_Pattern_Registry $registry Pattern registry instance
		 */
		do_action( 'site_quiz_register_patterns', $this->pattern_registry );

		// Initialize demo data generator
		$this->demo_generator = new Site_Quiz_Demo_Data_Generator();

		// Auto-generate demo data on first activation in local environments
		if ( $this->demo_generator->is_enabled() && ! $this->demo_generator->has_demo_data() ) {
			$this->demo_generator->generate_all();
		}
	}

	/**
	 * Register REST API routes
	 * 
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'site-quiz/v1',
			'/questions',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'generate_questions' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'questionCount'    => array(
						'required'          => true,
						'type'              => 'integer',
						'minimum'           => 3,
						'maximum'           => 20,
						'sanitize_callback' => 'absint',
					),
					'enabledPatterns'  => array(
						'required'          => true,
						'type'              => 'array',
						'sanitize_callback' => function( $patterns ) {
							return array_map( 'sanitize_text_field', $patterns );
						},
					),
				),
			)
		);
	}

	/**
	 * Generate quiz questions via REST API
	 * 
	 * @param WP_REST_Request $request REST request object
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate_questions( $request ) {
		$question_count   = $request->get_param( 'questionCount' );
		$enabled_patterns = $request->get_param( 'enabledPatterns' );

		$questions = $this->pattern_registry->generate_questions( $question_count, $enabled_patterns );

		if ( is_wp_error( $questions ) ) {
			return $questions;
		}

		return rest_ensure_response( $questions );
	}

	/**
	 * Render block callback
	 * 
	 * @param array    $attributes Block attributes
	 * @param string   $content    Block content
	 * @param WP_Block $block      Block instance
	 * @return string Rendered block HTML
	 */
	public function render_block( $attributes, $content, $block ) {
		$question_count   = isset( $attributes['questionCount'] ) ? absint( $attributes['questionCount'] ) : 5;
		$enabled_patterns = isset( $attributes['enabledPatterns'] ) ? $attributes['enabledPatterns'] : array();
		
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'data-question-count'   => $question_count,
				'data-enabled-patterns' => esc_attr( wp_json_encode( $enabled_patterns ) ),
				'data-block-id'         => esc_attr( uniqid( 'quiz_' ) ),
			)
		);

		$html = sprintf(
			'<div %s>
				<div class="site-quiz__container">
					<div class="site-quiz__loading">
						<div class="site-quiz__spinner"></div>
						<p>%s</p>
					</div>
				</div>
			</div>',
			$wrapper_attributes,
			esc_html__( 'Loading quiz...', 'site-quiz' )
		);

		return $html;
	}

	/**
	 * Show demo data admin notice
	 * 
	 * @return void
	 */
	public function show_demo_notice() {
		if ( ! $this->demo_generator || ! $this->demo_generator->is_enabled() ) {
			return;
		}

		if ( isset( $_GET['site-quiz-demo'] ) ) {
			$action = sanitize_text_field( $_GET['site-quiz-demo'] );
			
			if ( 'generated' === $action ) {
				echo '<div class="notice notice-success is-dismissible"><p>';
				echo esc_html__( 'Demo content generated successfully! You can now test all quiz patterns.', 'site-quiz' );
				echo '</p></div>';
			} elseif ( 'deleted' === $action ) {
				echo '<div class="notice notice-success is-dismissible"><p>';
				echo esc_html__( 'Demo content deleted successfully.', 'site-quiz' );
				echo '</p></div>';
			}
			return;
		}

		if ( $this->demo_generator->has_demo_data() ) {
			$delete_url = wp_nonce_url(
				add_query_arg( 'site-quiz-action', 'delete-demo' ),
				'site-quiz-demo',
				'site-quiz-nonce'
			);

			echo '<div class="notice notice-info"><p>';
			echo esc_html__( 'Site Quiz: Demo content is active.', 'site-quiz' );
			echo ' <a href="' . esc_url( $delete_url ) . '">';
			echo esc_html__( 'Delete Demo Content', 'site-quiz' );
			echo '</a></p></div>';
		}
	}

	/**
	 * Handle demo data actions
	 * 
	 * @return void
	 */
	public function handle_demo_actions() {
		if ( ! isset( $_GET['site-quiz-action'] ) || ! isset( $_GET['site-quiz-nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['site-quiz-nonce'], 'site-quiz-demo' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_text_field( $_GET['site-quiz-action'] );

		if ( 'delete-demo' === $action && $this->demo_generator ) {
			$this->demo_generator->delete_all();
			wp_safe_redirect( add_query_arg( 'site-quiz-demo', 'deleted', admin_url() ) );
			exit;
		}
	}

	/**
	 * Get pattern registry
	 * 
	 * @return Site_Quiz_Pattern_Registry
	 */
	public function get_pattern_registry() {
		return $this->pattern_registry;
	}

	/**
	 * Get demo data generator
	 * 
	 * @return Site_Quiz_Demo_Data_Generator
	 */
	public function get_demo_generator() {
		return $this->demo_generator;
	}

	/**
	 * Prevent cloning
	 * 
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization
	 * 
	 * @return void
	 */
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize singleton' );
	}
}

/**
 * Initialize the plugin
 * 
 * @return Site_Quiz_Plugin
 */
function site_quiz() {
	return Site_Quiz_Plugin::get_instance();
}

site_quiz();