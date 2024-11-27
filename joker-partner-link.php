<?php

/**
 * Plugin Name: Joker Partner News Link
 * Description: Displays related news posts based on a custom partner category.
 * Version: 1.1
 * Author: William Rice
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Shortcode for related posts
function joker_display_related_posts_with_pagination($atts)
{
    // Extract attributes with default values
    $atts = shortcode_atts([
        'posts_per_page' => 6, // Default posts per page
        'default_image' => 174,  // Default fallback image ID
    ], $atts, 'joker_related_posts');

    // Get the current Partner post ID
    $post_id = get_the_ID();

    // Ensure the shortcode is used on the Partners post type
    if (get_post_type($post_id) !== 'partner') {
        return '<p>This shortcode is only valid on Partner posts.</p>';
    }

    // Get the partner_category custom field
    $partner_category = get_field('partner_category', $post_id);

    if (!$partner_category) {
        return '<p id="joker-no-related-news">No related news found</p>';
    }

    // Get current page from query string for pagination
    $paged = get_query_var('paged') ? get_query_var('paged') : 1;

    // Query posts
    $args = [
        'post_type' => 'post',
        'posts_per_page' => intval($atts['posts_per_page']),
        'paged' => $paged,
        'tax_query' => [
            [
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => $partner_category,
            ],
        ],
        'orderby' => 'date', // Order by date
        'order' => 'DESC',   // Most recent first
    ];
    $query = new WP_Query($args);

    // Check if posts exist
    if (!$query->have_posts()) {
        return '<p id="joker-no-related-news">No related news found. Check back later for news about ' . get_the_title() . '.</p>';
    }

    // Get default image URL if provided
    $default_image_url = $atts['default_image'] ? wp_get_attachment_image_url($atts['default_image'], 'full') : '';

    // Start output buffering
    ob_start();

    // Output posts in a responsive grid
    echo '<div class="joker-related-posts-grid">';
    while ($query->have_posts()) {
        $query->the_post();

        // Get the featured image or fallback to default image
        $featured_image_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
        if (!$featured_image_url && $atts['default_image']) {
            $featured_image_url = wp_get_attachment_image_url($atts['default_image'], 'full');
        }

        // Get post author and content snippet
        $author_name = get_the_author();
        $excerpt = wp_trim_words(get_the_content(), 30, '...');

        // Render the card
        echo '<a href="' . get_the_permalink() . '" class="joker-related-post-item">';
        echo '<div class="joker-post-image">';
        echo '<img src="' . esc_url($featured_image_url) . '" alt="' . esc_attr(get_the_title()) . '" />';
        echo '</div>';
        echo '<div class="joker-post-content">';
        echo '<h4>' . get_the_title() . '</h4>';
        echo '<p class="joker-post-author">' . esc_html($author_name) . '</p>';
        echo '<p class="joker-post-excerpt">' . esc_html($excerpt) . '</p>';
        echo '</div>';
        echo '</a>';
    }
    echo '</div>';

    // "Load More" button (AJAX-enabled)
    if ($query->max_num_pages > 1 && $paged < $query->max_num_pages) {
        echo '<div class="joker-load-more-container"><button id="joker-load-more-posts" 
            data-page="' . ($paged + 1) . '" 
            data-max-page="' . $query->max_num_pages . '" 
            data-posts-per-page="' . $atts['posts_per_page'] . '" 
            data-category="' . $partner_category . '">Load More</button></div>';
    }

    // Reset post data
    wp_reset_postdata();

    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('joker_related_posts', 'joker_display_related_posts_with_pagination');

// AJAX handler for "Load More" button
function joker_load_more_posts_ajax()
{
    $page = intval($_POST['page']);
    $posts_per_page = intval($_POST['posts_per_page']);
    $category = intval($_POST['category']);

    $args = [
        'post_type' => 'post',
        'posts_per_page' => $posts_per_page,
        'paged' => $page,
        'tax_query' => [
            [
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => $category,
            ],
        ],
    ];
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            // Get the featured image or fallback to default image
            $featured_image_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
            if (!$featured_image_url) {
                $featured_image_url = wp_get_attachment_image_url(174, 'full');
            }

            // Get post author and content snippet
            $author_name = get_the_author();
            $excerpt = wp_trim_words(get_the_content(), 30, '...');

            echo '<div class="joker-related-post-item">';
            echo '<div class="joker-post-image">';
            echo '<img src="' . esc_url($featured_image_url) . '" alt="' . esc_attr(get_the_title()) . '" />';
            echo '</div>';
            echo '<div class="joker-post-content">';
            echo '<h4><a href="' . get_the_permalink() . '">' . get_the_title() . '</a></h4>';
            echo '<p class="joker-post-author">' . esc_html($author_name) . '</p>';
            echo '<p class="joker-post-excerpt">' . esc_html($excerpt) . '</p>';
            echo '</div>';
            echo '</div>';
        }
    }

    wp_reset_postdata();
    wp_die();
}
add_action('wp_ajax_joker_load_more_posts', 'joker_load_more_posts_ajax');
add_action('wp_ajax_nopriv_joker_load_more_posts', 'joker_load_more_posts_ajax');
add_action('after_setup_theme', function () {
    add_image_size('custom-400x250', 400, 250); // Width: 400px, Height: 250px, Hard Crop
});


// Enqueue scripts and styles
function joker_enqueue_related_posts_assets()
{
    // Enqueue styles
    wp_enqueue_style('joker-related-posts-styles', plugin_dir_url(__FILE__) . 'assets/css/related-posts.css');

    // Enqueue scripts
    wp_enqueue_script('joker-related-posts-load-more', plugin_dir_url(__FILE__) . 'assets/js/related-posts.js', ['jquery'], null, true);

    // Pass AJAX URL and default image ID to script
    wp_localize_script('joker-related-posts-load-more', 'joker_ajax_object', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
}
add_action('wp_enqueue_scripts', 'joker_enqueue_related_posts_assets');
