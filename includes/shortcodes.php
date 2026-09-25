<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'enroute_offers_listing',    'enroute_offers_listing_sc'    );
add_shortcode( 'enroute_resources_listing', 'enroute_resources_listing_sc' );
add_shortcode( 'enroute_offer_detail',      'enroute_offer_detail_sc'      );
add_shortcode( 'enroute_guides_listing',    'enroute_guides_listing_sc'    );
add_shortcode( 'enroute_featured_offer',    'enroute_featured_offer_sc'    );
add_shortcode( 'enroute_user_profile',      'enroute_user_profile_sc'      );
add_shortcode( 'enroute_userpass_booking',  'enroute_userpass_booking_sc'  );
add_shortcode( 'enroute_blog_listing',      'enroute_blog_listing_sc'      );
add_shortcode( 'enroute_poi_map',           'enroute_poi_map_sc'           );

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


// ══════════════════════════════════════════════════════════════════════════════
// FEATURED OFFER
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Get a featured offer colour by number (1-5).
 * Falls back to defaults if not set in options.
 */
function enroute_get_featured_color( int $n ): string {
    $defaults = [
        1 => '#F8ED84',
        2 => '#E8AB58',
        3 => '#C9D56B',
        4 => '#D0687B',
        5 => '#D4735D',
    ];
    $n = max( 1, min( 5, $n ) ); // clamp to 1-5
    return get_option( "enroute_featured_color_$n", $defaults[ $n ] );
}

function enroute_featured_offer_sc( array $atts ): string {
    $args = shortcode_atts( [ 'number' => '1', 'color' => '1' ], $atts, 'enroute_featured_offer' );
    ob_start();
    include ENROUTE_OFFERS_PATH . 'templates/featured-offer.php';
    return ob_get_clean();
}


// ══════════════════════════════════════════════════════════════════════════════
// USER PROFILE
// ══════════════════════════════════════════════════════════════════════════════

function enroute_user_profile_sc(): string {
    ob_start();
    include ENROUTE_OFFERS_PATH . 'templates/user-profile.php';
    return ob_get_clean();
}


// ══════════════════════════════════════════════════════════════════════════════
// USERPASS BOOKING PAGE
// ══════════════════════════════════════════════════════════════════════════════

function enroute_userpass_booking_sc( array $atts ): string {
    ob_start();
    include ENROUTE_OFFERS_PATH . 'templates/userpass-booking.php';
    return ob_get_clean();
}


// ══════════════════════════════════════════════════════════════════════════════
// BLOG LISTING
// ══════════════════════════════════════════════════════════════════════════════

function enroute_blog_listing_sc( array $atts ): string {
    ob_start();
    include ENROUTE_OFFERS_PATH . 'templates/blog-listing.php';
    return ob_get_clean();
}


// ══════════════════════════════════════════════════════════════════════════════
// POI MAP
// ══════════════════════════════════════════════════════════════════════════════

function enroute_poi_map_sc(): string {
    ob_start();
    include ENROUTE_OFFERS_PATH . 'templates/poi-map.php';
    return ob_get_clean();
}
