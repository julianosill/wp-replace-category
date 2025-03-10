<?php
/*
    Plugin Name: Replace Category
    Description: A plugin to bulk replace a category in posts with another category.
    Version: 1.0
    Author: Juliano Sill
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


function replace_posts_category($from_category_id, $to_category_id) {
    $args = array(
        'post_type'      => 'post',
        'posts_per_page' => -1,    
        'tax_query'      => array(
            array(
                'taxonomy' => 'category',
                'field'    => 'term_id',
                'terms'    => $from_category_id,
            ),
        ),
    );

    $posts = get_posts($args);

    foreach ($posts as $post) {
        $current_terms = wp_get_post_terms($post->ID, 'category', array('fields' => 'ids'));
        $updated_terms = array_diff($current_terms, array($from_category_id));

        if (!in_array($to_category_id, $updated_terms)) {
            $updated_terms[] = $to_category_id;
        }

        wp_set_post_terms($post->ID, $updated_terms, 'category');
    }

    wp_update_term_count_now(array($from_category_id), 'category');
    wp_update_term_count_now(array($to_category_id), 'category');

    wp_cache_flush();
}


function add_custom_admin_menu() {
    add_menu_page(
        'Replace Category',         // Page title
        'Replace Category',         // Menu title
        'manage_options',           // Capability required to access
        'replace-category',         // Menu slug
        'replace_category_page',    // Callback function
        'dashicons-update',         // Icon (optional)
        100                         // Position (optional)
    );
}
add_action('admin_menu', 'add_custom_admin_menu');


function replace_category_page() {
    if (isset($_POST['replace_category'])) {
        if (!isset($_POST['replace_category_nonce']) || !wp_verify_nonce($_POST['replace_category_nonce'], 'replace_category_action')) {
            echo '<div class="error"><p>Security check failed.</p></div>';
            return;
        }

        $from_category_id = isset($_POST['from_category_id']) ? intval($_POST['from_category_id']) : 0;
        $to_category_id = isset($_POST['to_category_id']) ? intval($_POST['to_category_id']) : 0;

        if ($from_category_id && $to_category_id) {
            replace_posts_category($from_category_id, $to_category_id);
            echo '<div class="updated"><p>Categories replaced successfully!</p></div>';
        } else {
            echo '<div class="error"><p>Please select valid categories.</p></div>';
        }
    }
    ?>

    <div class="wrap">
        <h1>Replace Category</h1>
        <form method="post" action="">
            <?php wp_nonce_field('replace_category_action', 'replace_category_nonce'); ?>
            <p>
                <label for="from_category_id">From category:</label>
                <?php
                wp_dropdown_categories(array(
                    'show_option_none' => 'Select a category',
                    'hide_empty'       => 0,
                    'name'             => 'from_category_id',
                    'id'               => 'from_category_id',
                    'selected'         => isset($_POST['from_category_id']) ? $_POST['from_category_id'] : 0,
                    'hierarchical'    => true,
                    'orderby'         => 'name',
                    'taxonomy'        => 'category',
                    'value_field'     => 'term_id',
                ));
                ?>
            </p>
            <p>
                <label for="to_category_id">To category:</label>
                <?php
                wp_dropdown_categories(array(
                    'show_option_none'  => 'Select a category',
                    'hide_empty'        => 0,
                    'name'              => 'to_category_id',
                    'id'                => 'to_category_id',
                    'selected'          => isset($_POST['to_category_id']) ? $_POST['to_category_id'] : 0,
                    'hierarchical'      => true,
                    'orderby'           => 'name',
                    'taxonomy'          => 'category',
                    'value_field'       => 'term_id',
                ));
                ?>
            </p>
            <p>Click the button below to replace the category for all posts.</p>
            <input type="submit" name="replace_category" class="button button-primary" value="Replace Category">
        </form>
    </div>
    <?php
}