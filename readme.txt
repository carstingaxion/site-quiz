
=== Site Quiz ===

Contributors:      WordPress Telex
Tags:              block, quiz, posts, interactive, education
Tested up to:      6.8
Stable tag:        0.1.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

A dynamic quiz block that generates questions from your site's posts with achievement badges and progress tracking.

== Description ==

Site Quiz is an interactive WordPress block that automatically generates quiz questions from your website's posts. Test your readers' knowledge about your content with questions about post metadata, taxonomies, dates, authors, and more.

**Key Features:**

* **Dynamic Question Generation**: Automatically creates quiz questions from your posts
* **Multiple Question Patterns**: Includes 7 different question types (publication dates, authors, tags, categories, word counts, comment counts, image counts)
* **Customizable Settings**: Control number of questions (3-20) and enable/disable specific patterns
* **Achievement System**: Award bronze, silver, and gold badges based on score thresholds
* **Progress Tracking**: Save user progress in browser localStorage
* **Four Block Styles**: Choose from minimal, card, gradient, and bold styles
* **Extensible Architecture**: Add custom question patterns via action hooks
* **Theme Integration**: Respects theme.json color and typography settings

**Question Types:**

1. **Publication Date**: Identify when a post was published
2. **Author Selection**: Match posts to their authors
3. **Tag Identification**: Find the unrelated tag among options
4. **Category Matching**: Identify correct post categories
5. **Image Count**: Determine number of images in posts
6. **Word Count**: Guess word count ranges
7. **Comment Count**: Identify posts by comment counts

**For Developers:**

* Singleton pattern implementation for main plugin class
* Extensible question pattern system with filter hooks
* Action hooks for registering custom patterns
* Comprehensive PHPDoc and JSDoc documentation
* Clean, maintainable code following WordPress standards

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/site-quiz` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Add the "Site Quiz" block to any post or page
4. Configure the quiz settings in the block sidebar
5. Publish and let your readers test their knowledge!

== Frequently Asked Questions ==

= How many questions can I include in a quiz? =

You can configure between 3 and 20 questions per quiz using the block settings.

= Can I customize which question types appear? =

Yes! Use the question pattern controls in the block sidebar to enable or disable specific question types.

= How are scores calculated? =

Scores are calculated as a percentage of correct answers. Achievement levels are: Bronze (50%+), Silver (75%+), Gold (100%).

= Is user progress saved? =

Yes, quiz progress and results are saved in the browser's localStorage, allowing users to continue where they left off.

= Can I add custom question patterns? =

Yes! Developers can add custom question patterns using the provided action hooks. See documentation for details.

= Does it work with my theme? =

Yes! The block respects your theme.json settings for colors and typography, ensuring seamless integration.

== Screenshots ==

1. Quiz block in the editor with configuration options
2. Minimal style showing a question on the frontend
3. Card style with gradient background
4. Achievement badge display after quiz completion
5. Block inspector controls for customization

== Changelog ==

= 0.1.0 =
* Initial release
* Seven question pattern types
* Achievement badge system
* localStorage progress tracking
* Four block styles
* Extensible architecture with hooks
* Full theme.json integration

== Developer Documentation ==

**Adding Custom Question Patterns:**

```php
add_action('site_quiz_register_patterns', function($registry) {
    require_once 'path/to/Custom_Pattern.php';
    $registry->register(new Custom_Pattern());
});
```

**Filtering Available Patterns:**

```php
add_filter('site_quiz_enabled_patterns', function($patterns) {
    // Modify $patterns array
    return $patterns;
});
```

**Custom Achievement Thresholds:**

```php
add_filter('site_quiz_achievement_thresholds', function($thresholds) {
    return array(
        'bronze' => 60,
        'silver' => 80,
        'gold' => 95
    );
});
```
