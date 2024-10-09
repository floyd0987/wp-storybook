<?php
/*
Plugin Name: WP Storybook
Description: Dynamically generate an index of all template parts in your WordPress theme and view them individually like Storybook.js, with a "Code" button to view the template code.
Version: 1.4
Author: Bernucci Eronne
GitHub Plugin URI: https://github.com/floyd0987/wp-storybook
GitHub Branch: main
*/

// Hook into WordPress to create a custom rewrite rule for single template part pages.
add_action('init', 'wp_storybook_register_rewrite_rule');

// Add the custom rewrite rule for single template part URLs.
function wp_storybook_register_rewrite_rule() {
    add_rewrite_rule('^wp-storybook/([^/]*)/?', 'index.php?wp_storybook_template=$matches[1]', 'top');
}

// Register query variable to capture the template part name.
add_filter('query_vars', function($vars) {
    $vars[] = 'wp_storybook_template';
    return $vars;
});

// Handle the display of individual template part pages.
add_action('template_redirect', 'wp_storybook_template_redirect');

function wp_storybook_template_redirect() {
    $template_part = get_query_var('wp_storybook_template');
    if ($template_part) {
        // Get the path to the template part.
        $template_part_path = get_template_directory() . '/template-parts/' . sanitize_text_field($template_part) . '.php';

        // Check if the template part exists, otherwise show 404.
        if (file_exists($template_part_path)) {
            get_header();
            echo '<h1>Template Part: ' . ucfirst($template_part) . '</h1>';
            echo '<div class="wp-storybook-component">';
            include $template_part_path;
            echo '</div>';
            
            // Add a button to view the source code
            echo '<button id="show-code-btn" style="margin-top: 20px;">Show Code</button>';
            
            // Display the source code of the template part (hidden by default)
            echo '<pre id="template-code" style="display: none; background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto;">';
            echo htmlspecialchars(file_get_contents($template_part_path)); // Safely display the code
            echo '</pre>';

            // Add JavaScript for the toggle button
            echo '
            <script type="text/javascript">
                document.getElementById("show-code-btn").addEventListener("click", function() {
                    var codeBlock = document.getElementById("template-code");
                    if (codeBlock.style.display === "none") {
                        codeBlock.style.display = "block";
                        this.textContent = "Hide Code";
                    } else {
                        codeBlock.style.display = "none";
                        this.textContent = "Show Code";
                    }
                });
            </script>';

            get_footer();
            exit; // Ensure the WordPress loop doesn't interfere.
        } else {
            wp_die('Template part not found', '404');
        }
    }
}

// Add a custom page to the WordPress admin menu to display the template parts index.
add_action('admin_menu', 'wp_storybook_menu');

function wp_storybook_menu() {
    add_menu_page(
        'WP Storybook',   // Page title
        'WP Storybook',   // Menu title
        'manage_options',         // Capability
        'wp-storybook',   // Menu slug
        'wp_storybook_admin_page',  // Function to display content
        'dashicons-editor-code',  // Icon
        6                         // Position
    );
}

// Display the template parts index in the WordPress admin.
function wp_storybook_admin_page() {
    echo '<div class="wrap">';
    echo wp_storybook_display_index();
    echo '</div>';
}

// Display the index of all template parts with links to their respective pages.
function wp_storybook_display_index() {
    // Get the template-parts directory.
    $template_parts_dir = get_template_directory() . '/template-parts/';
    
    // Check if the directory exists.
    if (!is_dir($template_parts_dir)) {
        return '<p>No template parts found.</p>';
    }

    // Scan the template-parts directory for PHP files.
    $template_parts_files = scandir($template_parts_dir);

    $output = '<h2>WP Storybook - Template Parts Index</h2><ul>';

    foreach ($template_parts_files as $file) {
        // Skip '.' and '..' special directories and show only .php files.
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            // Remove the '.php' extension for display.
            $part_name = basename($file, '.php');
            // Create a link to the individual template part page.
            $output .= '<li><a href="' . site_url('/wp-storybook/' . $part_name) . '">' . ucfirst($part_name) . '</a></li>';
        }
    }

    $output .= '</ul>';

    return $output;
}


// Hook to enqueue theme CSS files
add_action('wp_enqueue_scripts', 'wp_storybook_enqueue_theme_css');

function wp_storybook_enqueue_theme_css() {
    // Get the current active theme's directory path
    $theme_directory = get_template_directory() . '/assets/build/css/';
    
    // Check if the directory exists
    if (is_dir($theme_directory)) {
        // Open the directory and read its contents
        if ($handle = opendir($theme_directory)) {
            while (false !== ($file = readdir($handle))) {
                // Only enqueue .css files
                if (pathinfo($file, PATHINFO_EXTENSION) === 'css') {
                    // Generate the file's URL
                    $file_url = get_template_directory_uri() . '/assets/build/css/' . $file;

                    // Use the file name without the extension as the handle
                    $handle_name = pathinfo($file, PATHINFO_FILENAME) . '-style';

                    // Enqueue the stylesheet
                    wp_enqueue_style($handle_name, $file_url, array(), filemtime($theme_directory . $file));
                }
            }
            closedir($handle);
        }
    }
}
