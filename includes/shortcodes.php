<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'enroute_offers_listing',    'enroute_offers_listing_sc'    );
add_shortcode( 'enroute_resources_listing', 'enroute_resources_listing_sc' );
add_shortcode( 'enroute_offer_detail',      'enroute_offer_detail_sc'      );
add_shortcode( 'enroute_guides_listing',    'enroute_guides_listing_sc'    );

// Auto-inject detail template on single offer posts (replaces/prepends content)
add_filter( 'the_content', 'enroute_offer_detail_content_filter' );

// ══════════════════════════════════════════════════════════════════════════════
// OFFER LISTING
// ══════════════════════════════════════════════════════════════════════════════

function enroute_offers_listing_sc( array $atts ): string {
    $args = shortcode_atts( [ 'filter' => 'yes' ], $atts, 'enroute_offers_listing' );
    ob_start();
    include ENROUTE_OFFERS_PATH . 'templates/offers-listing.php';
    return ob_get_clean();
}

// ══════════════════════════════════════════════════════════════════════════════
// RESOURCE LISTING
// ══════════════════════════════════════════════════════════════════════════════

function enroute_resources_listing_sc(): string {
    ob_start();
    include ENROUTE_OFFERS_PATH . 'templates/resources-listing.php';
    return ob_get_clean();
}

// ══════════════════════════════════════════════════════════════════════════════
// OFFER DETAIL
// ══════════════════════════════════════════════════════════════════════════════

function enroute_offer_detail_sc(): string {
    ob_start();
    include ENROUTE_OFFERS_PATH . 'templates/offer-detail.php';
    return ob_get_clean();
}

// ══════════════════════════════════════════════════════════════════════════════
// GUIDE LISTING
// ══════════════════════════════════════════════════════════════════════════════

function enroute_guides_listing_sc( array $atts ): string {
    $args = shortcode_atts( [ 'order' => 'alpha' ], $atts, 'enroute_guides_listing' );
    ob_start();
    include ENROUTE_OFFERS_PATH . 'templates/guides-listing.php';
    return ob_get_clean();
}

/**
 * Auto-inject offer detail template on single offer post pages.
 * Uses a static flag to prevent infinite recursion from apply_filters('the_content')
 * inside the template itself.
 */
function enroute_offer_detail_content_filter( string $content ): string {
    static $rendering = false;
    if ( $rendering ) return $content;
    if ( ! is_singular( 'offer' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }
    $rendering = true;
    ob_start();
    include ENROUTE_OFFERS_PATH . 'templates/offer-detail.php';
    $output = ob_get_clean();
    $rendering = false;
    return $output;
}
