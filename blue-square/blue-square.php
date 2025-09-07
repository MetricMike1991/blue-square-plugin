<?php
/*
Plugin Name: Blue Square
Description: Embeds a blue square on a gray background using the [blue-square] shortcode. Includes free and premium settings.
Version: 1.0.1
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// ----- Helpers: get settings with safe defaults -----
function blue_square_get_settings() {
    $defaults = array(
        'width'         => 100,
        'height'        => 100,
        'color'         => '#0074D9', // Premium
        'border_radius' => 0,         // Premium
    );
    $opts = get_option('blue_square_settings');
    if (!is_array($opts)) {
        $opts = array();
    }
    // Merge, giving priority to saved values
    return array_merge($defaults, $opts);
}

// ----- Shortcode -----
function blue_square_shortcode($atts) {
    $options       = blue_square_get_settings();
    $width         = intval($options['width']);
    $height        = intval($options['height']);
    $color         = isset($options['color']) ? $options['color'] : '#0074D9';
    $border_radius = isset($options['border_radius']) ? intval($options['border_radius']) : 0;

    // Placeholder for Freemius/licensing check
    $is_premium = false;

    $outer_style  = sprintf(
        'background:#eee;display:flex;align-items:center;justify-content:center;width:%dpx;height:%dpx;',
        $width,
        $height
    );

    $inner_bg   = $is_premium ? $color : '#0074D9';
    $inner_rad  = $is_premium ? $border_radius : 0;
    $square_style = sprintf('background:%s;width:80%%;height:80%%;border-radius:%dpx;', esc_attr($inner_bg), $inner_rad);

    return "<div style=\"" . esc_attr($outer_style) . "\"><div style=\"" . esc_attr($square_style) . "\"></div></div>";
}
add_shortcode('blue-square', 'blue_square_shortcode');

// ----- Admin menu -----
function blue_square_add_admin_menu() {
    add_options_page('Blue Square Settings', 'Blue Square', 'manage_options', 'blue-square', 'blue_square_settings_page');
}
add_action('admin_menu', 'blue_square_add_admin_menu');

// ----- Settings registration -----
function blue_square_settings_init() {
    register_setting('blue_square', 'blue_square_settings', 'blue_square_sanitize_settings');

    add_settings_section('blue_square_section', 'Blue Square Settings', '__return_null', 'blue_square');

    add_settings_field('width', 'Width (px)', 'blue_square_width_render', 'blue_square', 'blue_square_section');
    add_settings_field('height', 'Height (px)', 'blue_square_height_render', 'blue_square', 'blue_square_section');
    add_settings_field('color', 'Square Color (Premium)', 'blue_square_color_render', 'blue_square', 'blue_square_section');
    add_settings_field('border_radius', 'Border Radius (Premium)', 'blue_square_border_radius_render', 'blue_square', 'blue_square_section');
}
add_action('admin_init', 'blue_square_settings_init');

// Sanitize
function blue_square_sanitize_settings($input) {
    $out = array();

    if (isset($input['width'])) {
        $out['width'] = max(10, min(1000, intval($input['width'])));
    }
    if (isset($input['height'])) {
        $out['height'] = max(10, min(1000, intval($input['height'])));
    }
    // Premium values will be stored but not editable in free UI
    if (isset($input['color'])) {
        $out['color'] = sanitize_text_field($input['color']);
    }
    if (isset($input['border_radius'])) {
        $out['border_radius'] = max(0, min(100, intval($input['border_radius'])));
    }

    return $out;
}

// ----- Field renders (no PHP 7+ only syntax) -----
function blue_square_width_render() {
    $options = blue_square_get_settings();
    $val = isset($options['width']) ? intval($options['width']) : 100;
    echo "<input type='number' name='blue_square_settings[width]' value='" . esc_attr($val) . "' min='10' max='1000' />";
}

function blue_square_height_render() {
    $options = blue_square_get_settings();
    $val = isset($options['height']) ? intval($options['height']) : 100;
    echo "<input type='number' name='blue_square_settings[height]' value='" . esc_attr($val) . "' min='10' max='1000' />";
}

function blue_square_color_render() {
    $options = blue_square_get_settings();
    $val = isset($options['color']) ? $options['color'] : '#0074D9';
    echo "<input type='color' name='blue_square_settings[color]' value='" . esc_attr($val) . "' disabled='disabled' title='Premium feature' /> <span style='color:#888;'>Premium only</span>";
}

function blue_square_border_radius_render() {
    $options = blue_square_get_settings();
    $val = isset($options['border_radius']) ? intval($options['border_radius']) : 0;
    echo "<input type='number' name='blue_square_settings[border_radius]' value='" . esc_attr($val) . "' min='0' max='100' disabled='disabled' title='Premium feature' /> <span style='color:#888;'>Premium only</span>";
}

// ----- Settings page -----
function blue_square_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    echo '<div class="wrap"><h1>Blue Square Settings</h1>';
    echo '<form action="options.php" method="post">';
    settings_fields('blue_square');
    do_settings_sections('blue_square');
    submit_button();
    echo '</form></div>';
}
