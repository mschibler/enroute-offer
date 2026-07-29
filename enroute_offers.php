<?php
/**
 * Plugin Name: Enroute Offers
 * Plugin URI:  https://example.com/enroute-offers
 * Description: Manages Offers, Stations and Resources with multilingual support (DE, FR, IT).
 * Version:     1.0.3
 * Author:      Enroute
 * Text Domain: enroute_offers
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ENROUTE_OFFERS_VERSION', '1.0.5' );
define( 'ENROUTE_OFFERS_PATH', plugin_dir_path( __FILE__ ) );
define( 'ENROUTE_OFFERS_URL',  plugin_dir_url( __FILE__ ) );

require_once ENROUTE_OFFERS_PATH . 'includes/post-types.php';
require_once ENROUTE_OFFERS_PATH . 'includes/taxonomies.php';
require_once ENROUTE_OFFERS_PATH . 'includes/meta-boxes.php';
require_once ENROUTE_OFFERS_PATH . 'includes/meta-save.php';
require_once ENROUTE_OFFERS_PATH . 'includes/shortcodes.php';
require_once ENROUTE_OFFERS_PATH . 'includes/helpers.php';
require_once ENROUTE_OFFERS_PATH . 'includes/booking-post-type.php';
require_once ENROUTE_OFFERS_PATH . 'includes/booking-settings.php';
require_once ENROUTE_OFFERS_PATH . 'includes/booking-ajax.php';
require_once ENROUTE_OFFERS_PATH . 'includes/guide-post-type.php';
require_once ENROUTE_OFFERS_PATH . 'includes/polylang.php';

add_action( 'plugins_loaded', function() {
    load_plugin_textdomain(
        'enroute_offers',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
});

add_action( 'wp_enqueue_scripts', function() {

    wp_enqueue_script(
        'enroute-offers-front',
        ENROUTE_OFFERS_URL . 'public/js/listings.js',
        [],
        ENROUTE_OFFERS_VERSION,
        true  // in_footer, no defer
    );

    // Pass AJAX URL + nonce for booking form
    wp_localize_script( 'enroute-offers-front', 'enrouteBookingVars', [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'enroute_booking_nonce' ),
    ] );

    // Inline CSS for offer detail responsive layout (not compiled by Tailwind)
    wp_add_inline_style( 'wp-block-library', '
        @media (max-width: 768px) {
            .enroute-offers-grid { grid-template-columns: 1fr !important; }
            .enroute-detail-layout { grid-template-columns: 1fr !important; }
            .enroute-detail-sidebar { order: -1; }
            .enroute-guides-listing > div { grid-template-columns: repeat(2,1fr) !important; }
            .enroute-guide-modal-inner { grid-template-columns: 1fr !important; }
        }
        @media (min-width: 769px) and (max-width: 1023px) {
            .enroute-offers-grid { grid-template-columns: repeat(2,1fr) !important; }
        }
        .enroute-offers-listing a,
        .enroute-offers-listing a:focus,
        .enroute-offers-listing a:hover,
        .enroute-offers-listing a:visited {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            text-decoration: none !important;
        }
        /* Grid subgrid: outer grid has auto rows so image rows align across all cards in a row */
        .enroute-offers-listing > div {
            grid-auto-rows: auto;
        }
        /* Override prose p { margin } inside the colored band */
        .enroute-offers-listing .enroute-card-wrap p {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }
        /* Theme sets img { height: auto } — override for card images */
        .enroute-offers-listing .enroute-card-wrap img {
            height: 100% !important;
            max-height: none !important;
            max-width: none !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        /* Guide listing: override theme img styles to prevent grey gap */
        .enroute-guides-listing .enroute-guide-card img {
            height: 100% !important;
            max-height: none !important;
            max-width: none !important;
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
            vertical-align: top !important;
            display: block !important;
        }
        /* Name bar: slides up from below on hover */
        .enroute-guides-listing .enroute-guide-name-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem 0.5rem;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #111;
            line-height: 1.2;
            transform: translateY(100%);
            transition: transform 0.2s ease;
        }
        .enroute-guides-listing .enroute-guide-card:hover .enroute-guide-name-bar {
            transform: translateY(0);
        }
    ' );
});

// Admin
add_action( 'admin_enqueue_scripts', function() {
    $screen = get_current_screen();
    if ( ! $screen ) return;
    if ( in_array( $screen->post_type, [ 'offer', 'station', 'resource' ] ) ) {
        wp_enqueue_style(
            'enroute-admin',
            ENROUTE_OFFERS_URL . 'admin/admin.css',
            [],
            ENROUTE_OFFERS_VERSION
        );
        wp_enqueue_media();
    }
});

// Prevent wptexturize from mangling quotes in shortcode output.
// This is the root cause of x-data="..." attribute corruption.
add_filter( 'no_texturize_shortcodes', function( $shortcodes ) {
    $shortcodes[] = 'enroute_offers_listing';
    $shortcodes[] = 'enroute_resources_listing';
    $shortcodes[] = 'enroute_guides_listing';
    return $shortcodes;
});

register_activation_hook( __FILE__, function() {
    require_once ENROUTE_OFFERS_PATH . 'includes/post-types.php';
    enroute_register_post_types();
    flush_rewrite_rules();
});
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
