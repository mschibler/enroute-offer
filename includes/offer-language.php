<?php
/**
 * Language-independent offers.
 *
 * Offers are not managed by Polylang (see polylang.php), so their URLs have no
 * language code and Polylang would show them in the default language. To keep
 * visitors in the language they are browsing in, offer links carry the visitor's
 * language prefix, e.g. /fr/offers/my-offer/ or /it/offers/my-offer/
 * (the default language keeps /offers/my-offer/ when Polylang hides it).
 *
 * Polylang reads the language from that prefix, so header, menus, booking form,
 * labels and messages are in the visitor's language, while the offer content
 * stays as entered. The language switcher on an offer page stays on the offer.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Polylang settings relevant here, or null if not applicable (only language-as-directory mode). */
function enroute_offer_lang_setup(): ?array {
    static $setup = false;
    if ( $setup !== false ) return $setup;

    $setup = null;
    if ( ! function_exists( 'PLL' ) || ! function_exists( 'pll_languages_list' ) || ! function_exists( 'pll_default_language' ) ) return $setup;

    $options = PLL()->options;
    if ( (int) ( $options['force_lang'] ?? 0 ) !== 1 ) return $setup; // only "language set from the directory name in pretty permalinks"

    $langs = (array) pll_languages_list( [ 'fields' => 'slug' ] );
    if ( ! empty( $options['hide_default'] ) ) {
        $langs = array_diff( $langs, [ pll_default_language() ] );
    }
    if ( ! $langs ) return $setup;

    $setup = [
        'langs' => array_values( $langs ),                         // languages that get a prefix
        'base'  => empty( $options['rewrite'] ) ? 'language/' : '', // Polylang option "remove /language/"
    ];
    return $setup;
}

function enroute_offer_rewrite_slug(): string {
    $pto = get_post_type_object( 'offer' );
    return ( $pto && ! empty( $pto->rewrite['slug'] ) ) ? trim( $pto->rewrite['slug'], '/' ) : 'offers';
}

// ── Rewrite rule for /fr/offers/slug/ (flushed automatically when languages change) ──
add_action( 'init', function() {
    $setup = enroute_offer_lang_setup();
    if ( ! $setup ) return;

    $slug  = enroute_offer_rewrite_slug();
    $langs = implode( '|', array_map( 'preg_quote', $setup['langs'] ) );

    add_rewrite_rule(
        '^' . $setup['base'] . '(' . $langs . ')/' . preg_quote( $slug, '/' ) . '/([^/]+)/?$',
        'index.php?post_type=offer&name=$matches[2]',
        'top'
    );

    $signature = md5( $setup['base'] . $langs . '|' . $slug );
    if ( get_option( 'enroute_offer_lang_rules' ) !== $signature ) {
        flush_rewrite_rules( false );
        update_option( 'enroute_offer_lang_rules', $signature, false );
    }
}, 20 );

/** Adds (or replaces) the language prefix in an offer URL. */
function enroute_offer_link_for_lang( string $link, string $lang ): string {
    $setup = enroute_offer_lang_setup();
    if ( ! $setup ) return $link;

    $home = untrailingslashit( get_option( 'home' ) );
    if ( strpos( $link, $home ) !== 0 ) return $link;

    // Path relative to home, without any existing language prefix
    $rel = substr( $link, strlen( $home ) );
    $rel = preg_replace( '#^/' . preg_quote( $setup['base'], '#' ) . '(' . implode( '|', array_map( 'preg_quote', $setup['langs'] ) ) . ')(?=/)#', '', $rel );

    if ( ! in_array( $lang, $setup['langs'], true ) ) {
        return $home . $rel; // default language without prefix
    }
    return $home . '/' . $setup['base'] . $lang . $rel;
}

// ── Offer links on the front end use the visitor's current language ──────────
add_filter( 'post_type_link', function( $link, $post ) {
    if ( ! $post instanceof WP_Post || $post->post_type !== 'offer' ) return $link;
    if ( is_admin() && ! wp_doing_ajax() ) return $link;
    if ( ! function_exists( 'pll_current_language' ) ) return $link;

    $lang = pll_current_language();
    return $lang ? enroute_offer_link_for_lang( $link, $lang ) : $link;
}, 20, 2 );

// ── Language switcher on an offer page: same offer, other language ───────────
add_filter( 'pll_translation_url', function( $url, $lang ) {
    if ( ! is_singular( 'offer' ) ) return $url;
    $link = get_permalink( get_queried_object_id() );
    return $link ? enroute_offer_link_for_lang( $link, $lang ) : $url;
}, 20, 2 );
