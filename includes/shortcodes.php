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
add_shortcode( 'enroute_events_listing',    function(): string {
    ob_start();
    include ENROUTE_OFFERS_PATH . 'templates/events-listing.php';
    return ob_get_clean();
} );

// Auto-inject detail template on single offer posts (replaces/prepends content)
add_filter( 'the_content', 'enroute_offer_detail_content_filter' );

// Same for single event posts
add_filter( 'the_content', function( string $content ): string {
    static $rendering = false;
    if ( $rendering || ! is_singular( 'event' ) || ! in_the_loop() || ! is_main_query() ) return $content;
    $rendering = true;
    $GLOBALS['enroute_rendering_event_detail'] = true; // keeps the title in our template (see event-post-type.php)
    ob_start();
    include ENROUTE_OFFERS_PATH . 'templates/event-detail.php';
    $output = ob_get_clean();
    $GLOBALS['enroute_rendering_event_detail'] = false;
    $rendering = false;
    return $output;
} );

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
    $defaults = enroute_get_default_colors();
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

function enroute_blog_listing_sc( $atts ): string {
    // Optional page background colour: [enroute_blog_listing background="#E8AB58"]
    $atts = shortcode_atts( [ 'background' => '' ], (array) $atts, 'enroute_blog_listing' );
    $bg   = sanitize_hex_color( $atts['background'] );
    ob_start();
    if ( $bg ) {
        // Page background coloured, the theme's main content area (main#main) stays white
        echo '<style>body{background-color:' . esc_html( $bg ) . ' !important;} #main{background-color:#fff;}</style>';
    }
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


// ── Use custom template for single blog posts ─────────────────────────────────
add_filter( 'template_include', function( string $template ): string {
    if ( is_singular( 'post' ) ) {
        $custom = ENROUTE_OFFERS_PATH . 'templates/single-blog.php';
        if ( file_exists( $custom ) ) return $custom;
    }
    return $template;
} );


// ── Contact details (Settings → Contacts) ─────────────────────────────────────
// [enroute_contact]                  → e-mail and phone
// [enroute_contact field="email"]    → e-mail only (mailto link)
// [enroute_contact field="phone"]    → phone only (tel link)
// Optional: link="no" (plain text), lang="fr" (force a language)
// In theme files: echo enroute_contact( 'email' );

/** Contact value for the current language; falls back to German if empty. */
function enroute_get_contact( string $field, string $lang = '' ): string {
    if ( ! in_array( $field, [ 'email', 'phone' ], true ) ) return '';
    if ( ! $lang ) {
        $lang = function_exists( 'pll_current_language' ) ? (string) pll_current_language() : '';
        $lang = $lang ?: substr( get_locale(), 0, 2 );
    }
    $value = (string) get_option( "enroute_contact_{$field}_" . sanitize_key( $lang ), '' );
    if ( $value === '' && $lang !== 'de' ) {
        $value = (string) get_option( "enroute_contact_{$field}_de", '' );
    }
    return $value;
}

/** HTML for one or both contact fields. */
function enroute_contact( string $field = '', bool $link = true, string $lang = '' ): string {
    $fields = $field ? [ $field ] : [ 'email', 'phone' ];
    $out    = [];

    foreach ( $fields as $f ) {
        $value = enroute_get_contact( $f, $lang );
        if ( $value === '' ) continue;

        if ( $link && $f === 'email' ) {
            $html = '<a href="mailto:' . esc_attr( antispambot( $value ) ) . '">' . esc_html( antispambot( $value ) ) . '</a>';
        } elseif ( $link && $f === 'phone' ) {
            $tel  = preg_replace( '/[^0-9+]/', '', $value );
            $html = '<a href="tel:' . esc_attr( $tel ) . '">' . esc_html( $value ) . '</a>';
        } else {
            $html = esc_html( $value );
        }
        $out[] = '<span class="enroute-contact-' . esc_attr( $f ) . '">' . $html . '</span>';
    }

    if ( ! $out ) return '';
    return count( $out ) === 1 ? $out[0] : '<span class="enroute-contact">' . implode( '<br>', $out ) . '</span>';
}

add_shortcode( 'enroute_contact', function( $atts ): string {
    $atts = shortcode_atts( [ 'field' => '', 'link' => 'yes', 'lang' => '' ], $atts, 'enroute_contact' );
    return enroute_contact(
        sanitize_key( $atts['field'] ),
        ! in_array( strtolower( (string) $atts['link'] ), [ 'no', '0', 'false' ], true ),
        sanitize_key( $atts['lang'] )
    );
} );


// ── Text blocks (Settings → Text blocks) ──────────────────────────────────────
// [enroute_text block="footer_copyright"]     In theme files: echo enroute_text( 'footer_copyright' );
// Optional: lang="fr" (force a language)

/** Available text blocks: key => label in the settings. New blocks only need an entry here. */
function enroute_text_blocks(): array {
    return [
        'footer_copyright' => __( 'Footer Copyright text', 'enroute_offers' ),
        'userpass_info'    => __( 'Benutzerpässe – Text rechts (Angebotsseite unten)', 'enroute_offers' ),
    ];
}

/** Default texts per block and language (used until the field is saved in the settings). */
function enroute_text_block_defaults(): array {
    return [
        'userpass_info' => [
            'de' => 'Ein Upgrade auf einen höheren Pass ist jederzeit möglich, das Ablaufdatum bleibt dabei unverändert. Nicht genutztes Guthaben verfällt nach Ablauf und wird nicht rückerstattet. Bei Überschreitung wird der Differenzbetrag in Rechnung gestellt.',
            'fr' => "Un passage à un pass supérieur est possible à tout moment ; la date d'échéance reste inchangée. Le crédit non utilisé expire à l'échéance et n'est pas remboursé. En cas de dépassement, la différence est facturée.",
            'it' => 'Il passaggio a un pass superiore è possibile in qualsiasi momento; la data di scadenza rimane invariata. Il credito non utilizzato scade alla fine del periodo e non viene rimborsato. In caso di superamento, la differenza viene fatturata.',
        ],
    ];
}

/** Saved text of a block/language, else its default. */
function enroute_text_block_value( string $block, string $lang ): string {
    $default = enroute_text_block_defaults()[ $block ][ $lang ] ?? '';
    return (string) get_option( "enroute_text_{$block}_{$lang}", $default );
}

/** Text blocks edited as multi-line text (line breaks are kept). */
function enroute_text_blocks_multiline(): array {
    return [ 'userpass_info' ];
}

/** Text block for the current language (German if empty); {jahr}/{year} → current year. */
function enroute_text( string $block, string $lang = '' ): string {
    if ( ! array_key_exists( $block, enroute_text_blocks() ) ) return '';
    if ( ! $lang ) {
        $lang = function_exists( 'pll_current_language' ) ? (string) pll_current_language() : '';
        $lang = $lang ?: substr( get_locale(), 0, 2 );
    }
    $text = enroute_text_block_value( $block, sanitize_key( $lang ) );
    if ( $text === '' && $lang !== 'de' ) $text = enroute_text_block_value( $block, 'de' );
    if ( $text === '' ) return '';
    $text = str_replace( [ '{jahr}', '{year}' ], date_i18n( 'Y' ), $text );
    if ( in_array( $block, enroute_text_blocks_multiline(), true ) ) $text = nl2br( trim( $text ) );
    return '<span class="enroute-text enroute-text-' . esc_attr( str_replace( '_', '-', $block ) ) . '">' . wp_kses_post( $text ) . '</span>';
}

add_shortcode( 'enroute_text', function( $atts ): string {
    $atts = shortcode_atts( [ 'block' => '', 'lang' => '' ], $atts, 'enroute_text' );
    return enroute_text( sanitize_key( $atts['block'] ), sanitize_key( $atts['lang'] ) );
} );

