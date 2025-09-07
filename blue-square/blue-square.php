<?php
/*
Plugin Name: Blue Square
Description: Embeds a blue square on a gray background using the [blue-square] shortcode. Includes free and premium settings with Freemius checkout.
Version: 1.4.0
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * -----------------------------------------------------------------------------
 * Freemius SDK bootstrap (matches your generator snippet).
 * -----------------------------------------------------------------------------
 */
if ( ! function_exists( 'bsp_fs' ) ) {
    // Create a helper function for easy SDK access.
    function bsp_fs() {
        global $bsp_fs;

        if ( ! isset( $bsp_fs ) ) {
            // Include Freemius SDK.
            $sdk = dirname( __FILE__ ) . '/vendor/freemius/start.php';
            if ( file_exists( $sdk ) ) {
                require_once $sdk;
            } else {
                // SDK missing → run in free mode without fatals.
                return null;
            }

            if ( function_exists( 'fs_dynamic_init' ) ) {
                $bsp_fs = fs_dynamic_init( array(
                    'id'                  => '20613',
                    // PRODUCT slug — from generator. Not your WP menu page slug.
                    'slug'                => 'blue-square-plugin',
                    'type'                => 'plugin',
                    'public_key'          => 'pk_aa2bca56a61e0ed6b824d890caeb0',
                    'is_premium'          => true,
                    'premium_suffix'      => 'Premium',
                    // If your plugin is a serviceware, set this option to false.
                    'has_premium_version' => true,
                    'has_addons'          => false,
                    'has_paid_plans'      => true,
                    // Automatically removed in the free version. If you're not using the
                    // auto-generated free version, delete this line before uploading to wp.org.
                    'wp_org_gatekeeper'   => 'OA7#BoRiBNqdf52FvzEf!!074aRLPs8fspif$7K1#4u4Csys1fQlCecVcUTOs2mcpeVHi#C2j9d09fOTvbC0HloPT7fFee5WdS3G',
                    'trial'               => array(
                        'days'               => 30,
                        'is_require_payment' => false,
                    ),
                    // Freemius admin menu config (this is your SETTINGS PAGE slug).
                    'menu'                => array(
                        'slug'           => 'blue-square',                 // WP settings page slug
                        'support'        => false,
                        'parent'         => array(
                            'slug' => 'options-general.php',               // under Settings
                        ),
                        // Optional: force first landing path if you like:
                        'first-path'     => 'options-general.php?page=blue-square',
                        'account'        => true,                          // show account/licensing
                        'contact'        => false,
                    ),
                ) );
            }
        }

        return $bsp_fs;
    }

    // Init Freemius (if SDK present).
    $fs = bsp_fs();

    // Set basename (recommended by Freemius for upgrade/deactivation handling).
    if ( $fs && method_exists( $fs, 'set_basename' ) ) {
        $fs->set_basename( true, __FILE__ );
    }

    // Signal that SDK was initiated.
    do_action( 'bsp_fs_loaded' );
}

/**
 * Helpers
 */
function bsp_is_premium() {
    $fs = function_exists( 'bsp_fs' ) ? bsp_fs() : null;
    return ( $fs && method_exists( $fs, 'can_use_premium_code' ) && $fs->can_use_premium_code() );
}
function bsp_upgrade_url() {
    $fs = function_exists( 'bsp_fs' ) ? bsp_fs() : null;
    // Prefer hosted checkout/pricing URL; never point to wp-admin here.
    return ( $fs && method_exists( $fs, 'get_upgrade_url' ) ) ? $fs->get_upgrade_url() : '';
}

/**
 * -----------------------------------------------------------------------------
 * Options & sanitization
 * -----------------------------------------------------------------------------
 */
function blue_square_get_settings() {
    $defaults = array(
        'width'         => 100,        // free
        'height'        => 100,        // free
        'color'         => '#0074D9',  // premium
        'border_radius' => 0,          // premium
    );
    $opts = get_option( 'blue_square_settings' );
    if ( ! is_array( $opts ) ) $opts = array();
    return array_merge( $defaults, $opts );
}

function blue_square_sanitize_settings( $input ) {
    $out = array();
    if ( isset( $input['width'] ) )         $out['width'] = max( 10, min( 1000, intval( $input['width'] ) ) );
    if ( isset( $input['height'] ) )        $out['height'] = max( 10, min( 1000, intval( $input['height'] ) ) );
    // Store premium values so they "light up" immediately after activation.
    if ( isset( $input['color'] ) )         $out['color'] = sanitize_text_field( $input['color'] );
    if ( isset( $input['border_radius'] ) ) $out['border_radius'] = max( 0, min( 100, intval( $input['border_radius'] ) ) );
    return $out;
}

/**
 * -----------------------------------------------------------------------------
 * Shortcode: [blue-square]
 * -----------------------------------------------------------------------------
 */
function blue_square_shortcode( $atts ) {
    $o             = blue_square_get_settings();
    $width         = intval( $o['width'] );
    $height        = intval( $o['height'] );
    $color         = isset( $o['color'] ) ? $o['color'] : '#0074D9';
    $border_radius = isset( $o['border_radius'] ) ? intval( $o['border_radius'] ) : 0;

    $is_premium = bsp_is_premium();

    $outer_style = sprintf(
        'background:#eee;display:flex;align-items:center;justify-content:center;width:%dpx;height:%dpx;',
        $width, $height
    );

    $inner_bg  = $is_premium ? $color : '#0074D9';
    $inner_rad = $is_premium ? $border_radius : 0;

    $square_style = sprintf(
        'background:%s;width:80%%;height:80%%;border-radius:%dpx;',
        esc_attr( $inner_bg ), $inner_rad
    );

    return '<div style="' . esc_attr( $outer_style ) . '"><div style="' . esc_attr( $square_style ) . '"></div></div>';
}
add_shortcode( 'blue-square', 'blue_square_shortcode' );

/**
 * -----------------------------------------------------------------------------
 * Admin: settings & menu (your settings page *must* use slug 'blue-square')
 * -----------------------------------------------------------------------------
 */
function blue_square_settings_init() {
    register_setting( 'blue_square', 'blue_square_settings', 'blue_square_sanitize_settings' );

    add_settings_section(
        'blue_square_section',
        'Blue Square Settings',
        'blue_square_section_cb',
        'blue_square'
    );

    add_settings_field( 'width',         'Width (px)',               'blue_square_width_render',          'blue_square', 'blue_square_section' );
    add_settings_field( 'height',        'Height (px)',              'blue_square_height_render',         'blue_square', 'blue_square_section' );
    add_settings_field( 'color',         'Square Color (Premium)',   'blue_square_color_render',          'blue_square', 'blue_square_section' );
    add_settings_field( 'border_radius', 'Border Radius (Premium)',  'blue_square_border_radius_render',  'blue_square', 'blue_square_section' );
}
add_action( 'admin_init', 'blue_square_settings_init' );

function blue_square_section_cb() {
    // Intentionally empty (avoids relying on __return_null on very old WP).
}

function blue_square_add_admin_menu() {
    add_options_page(
        'Blue Square Settings',
        'Blue Square',
        'manage_options',
        'blue-square',                 // ← MUST match Freemius menu['slug']
        'blue_square_settings_page'
    );
}
add_action( 'admin_menu', 'blue_square_add_admin_menu' );

/** Field renderers (+ inline Upgrade beside premium inputs) */
function blue_square_width_render() {
    $val = intval( blue_square_get_settings()['width'] );
    echo "<input type='number' name='blue_square_settings[width]' value='" . esc_attr( $val ) . "' min='10' max='1000' />";
}
function blue_square_height_render() {
    $val = intval( blue_square_get_settings()['height'] );
    echo "<input type='number' name='blue_square_settings[height]' value='" . esc_attr( $val ) . "' min='10' max='1000' />";
}
function blue_square_color_render() {
    $val        = blue_square_get_settings()['color'];
    $upgradeUrl = bsp_upgrade_url();

    if ( bsp_is_premium() ) {
        echo "<input type='color' name='blue_square_settings[color]' value='" . esc_attr( $val ) . "' />";
    } else {
        echo "<input type='color' name='blue_square_settings[color]' value='" . esc_attr( $val ) . "' disabled='disabled' title='Premium feature' />";
        if ( $upgradeUrl && strpos( $upgradeUrl, 'wp-admin' ) === false ) {
            echo " <a class='button button-small' target='_blank' rel='noopener' href='" . esc_url( $upgradeUrl ) . "'>Upgrade</a>";
        } else {
            echo " <span style='color:#888;'>Premium only</span>";
        }
    }
}
function blue_square_border_radius_render() {
    $val        = intval( blue_square_get_settings()['border_radius'] );
    $upgradeUrl = bsp_upgrade_url();

    if ( bsp_is_premium() ) {
        echo "<input type='number' name='blue_square_settings[border_radius]' value='" . esc_attr( $val ) . "' min='0' max='100' />";
    } else {
        echo "<input type='number' name='blue_square_settings[border_radius]' value='" . esc_attr( $val ) . "' min='0' max='100' disabled='disabled' title='Premium feature' />";
        if ( $upgradeUrl && strpos( $upgradeUrl, 'wp-admin' ) === false ) {
            echo " <a class='button button-small' target='_blank' rel='noopener' href='" . esc_url( $upgradeUrl ) . "'>Upgrade</a>";
        } else {
            echo " <span style='color:#888;'>Premium only</span>";
        }
    }
}

/** Settings page with top CTA + Account link (guarded) */
function blue_square_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
    }

    $fs         = function_exists( 'bsp_fs' ) ? bsp_fs() : null;
    $canPremium = bsp_is_premium();
    $upgradeUrl = bsp_upgrade_url();
    $accountUrl = ( $fs && method_exists( $fs, 'get_account_url' ) ) ? $fs->get_account_url() : '';

    echo '<div class="wrap"><h1>Blue Square Settings</h1>';

    if ( ! $canPremium ) {
        if ( $upgradeUrl && strpos( $upgradeUrl, 'wp-admin' ) === false ) {
            echo '<p><a class="button button-primary" target="_blank" rel="noopener" href="' . esc_url( $upgradeUrl ) . '">Upgrade to Premium</a></p>';
        } else {
            echo '<p style="color:#666;">Premium features are disabled.</p>';
        }
    } else {
        echo '<p style="color:#3a7;">Premium active ✅</p>';
    }

    if ( $accountUrl ) {
        echo '<p><a href="' . esc_url( $accountUrl ) . '">Manage License / Account</a></p>';
    }

    echo '<form action="options.php" method="post">';
    settings_fields( 'blue_square' );
    do_settings_sections( 'blue_square' );
    submit_button();
    echo '</form></div>';
}

/**
 * Plugins screen: Settings + Upgrade action links.
 * Points Settings → options-general.php?page=blue-square (must match above).
 */
function blue_square_add_plugin_action_links( $links ) {
    $settings   = '<a href="' . esc_url( admin_url( 'options-general.php?page=blue-square' ) ) . '">Settings</a>';
    $upgrade    = '';
    $upgradeUrl = bsp_upgrade_url();

    if ( ! bsp_is_premium() && $upgradeUrl && strpos( $upgradeUrl, 'wp-admin' ) === false ) {
        $upgrade = '<a target="_blank" rel="noopener" href="' . esc_url( $upgradeUrl ) . '"><strong>Upgrade</strong></a>';
    }

    if ( $upgrade ) array_unshift( $links, $upgrade );
    array_unshift( $links, $settings );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'blue_square_add_plugin_action_links' );
