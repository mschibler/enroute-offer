<?php
/**
 * Polylang string translations for the plugin's front-end texts.
 *
 * - Registers every text used in the front-end files (booking form, listings,
 *   profile, messages …) under Languages → Translations, grouped by area.
 *   The list is read directly from the source files, so new texts show up
 *   automatically after an update.
 * - A translation entered in Polylang takes precedence; if none is entered,
 *   the plugin's .mo translation files are used as before.
 * - Existing .mo translations are copied into Polylang once, so admins see
 *   and edit the current texts instead of empty fields.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Front-end files and the group they appear under in Polylang. */
function enroute_pll_string_groups(): array {
    return [
        'templates/offer-detail.php'      => 'Enroute – Angebot & Buchungsformular',
        'includes/booking-ajax.php'       => 'Enroute – Angebot & Buchungsformular',
        'templates/offers-listing.php'    => 'Enroute – Angebotsliste',
        'templates/featured-offer.php'    => 'Enroute – Angebotsliste',
        'templates/resources-listing.php' => 'Enroute – Materialien',
        'templates/guides-listing.php'    => 'Enroute – Guides',
        'templates/blog-listing.php'      => 'Enroute – Blog',
        'templates/single-blog.php'       => 'Enroute – Blog',
        'templates/poi-map.php'           => 'Enroute – Karte',
        'templates/user-profile.php'      => 'Enroute – Benutzerprofil & Login',
        'includes/user-account.php'       => 'Enroute – Benutzerprofil & Login',
        'templates/userpass-booking.php'  => 'Enroute – User Pass Buchung',
        'includes/userpass-ajax.php'      => 'Enroute – User Pass Buchung',
        'includes/helpers.php'            => 'Enroute – Allgemein',
    ];
}

/**
 * Extracts the texts of __(), _e(), esc_html__() … calls with the
 * 'enroute_offers' text domain from the front-end files.
 * Returns [ text => group ]; cached until a file changes.
 */
function enroute_pll_collect_strings(): array {
    $groups = enroute_pll_string_groups();

    $stamp = '';
    foreach ( array_keys( $groups ) as $rel ) {
        $file   = ENROUTE_OFFERS_PATH . $rel;
        $stamp .= $rel . ( file_exists( $file ) ? filemtime( $file ) : 0 );
    }
    $cache_key = 'enroute_pll_strings_' . md5( $stamp );
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) ) return $cached;

    $funcs   = [ '__', '_e', 'esc_html__', 'esc_html_e', 'esc_attr__', 'esc_attr_e', '_x', '_ex' ];
    $strings = [];

    foreach ( $groups as $rel => $group ) {
        $file = ENROUTE_OFFERS_PATH . $rel;
        if ( ! is_readable( $file ) ) continue;

        // Keep only meaningful tokens
        $tokens = array_values( array_filter(
            token_get_all( file_get_contents( $file ) ),
            fn( $t ) => ! is_array( $t ) || ! in_array( $t[0], [ T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ], true )
        ) );

        $count = count( $tokens );
        for ( $i = 0; $i < $count - 3; $i++ ) {
            $t = $tokens[ $i ];
            if ( ! is_array( $t ) || $t[0] !== T_STRING || ! in_array( $t[1], $funcs, true ) ) continue;
            if ( $tokens[ $i + 1 ] !== '(' ) continue;

            $arg = $tokens[ $i + 2 ];
            if ( ! is_array( $arg ) || $arg[0] !== T_CONSTANT_ENCAPSED_STRING ) continue;

            // Text domain = last literal before the closing parenthesis
            $domain = null;
            for ( $j = $i + 3; $j < min( $count, $i + 12 ); $j++ ) {
                if ( $tokens[ $j ] === ')' ) break;
                if ( is_array( $tokens[ $j ] ) && $tokens[ $j ][0] === T_CONSTANT_ENCAPSED_STRING ) {
                    $domain = enroute_pll_unquote( $tokens[ $j ][1] );
                }
            }
            if ( $domain !== 'enroute_offers' ) continue;

            $text = enroute_pll_unquote( $arg[1] );
            if ( $text !== '' && ! isset( $strings[ $text ] ) ) {
                $strings[ $text ] = $group;
            }
        }
    }

    set_transient( $cache_key, $strings, WEEK_IN_SECONDS );
    return $strings;
}

/** Converts a PHP string literal token into its value. */
function enroute_pll_unquote( string $literal ): string {
    $q    = $literal[0];
    $body = substr( $literal, 1, -1 );
    if ( $q === "'" ) {
        return str_replace( [ "\\\\", "\\'" ], [ "\\", "'" ], $body );
    }
    return stripcslashes( $body );
}

// ── Register the strings (only needed in the admin) ───────────────────────────
add_action( 'init', function() {
    if ( ! is_admin() || wp_doing_ajax() || ! function_exists( 'pll_register_string' ) ) return;

    foreach ( enroute_pll_collect_strings() as $text => $group ) {
        $multiline = strlen( $text ) > 80 || str_contains( $text, "\n" );
        pll_register_string( 'enroute: ' . mb_substr( $text, 0, 40 ), $text, $group, $multiline );
    }
}, 20 );

// ── Use the Polylang translation on the front end (and in front-end AJAX) ─────
add_filter( 'gettext', 'enroute_pll_gettext', 20, 3 );
add_filter( 'gettext_with_context', function( $translation, $text, $context, $domain ) {
    return enroute_pll_gettext( $translation, $text, $domain );
}, 20, 4 );

function enroute_pll_gettext( $translation, $text, $domain ) {
    static $busy = false;
    if ( $domain !== 'enroute_offers' || $busy ) return $translation;
    if ( ! function_exists( 'pll_translate_string' ) || ! function_exists( 'pll_current_language' ) ) return $translation;
    if ( is_admin() && ! wp_doing_ajax() ) return $translation;

    $lang = pll_current_language();
    if ( ! $lang && wp_doing_ajax() ) {
        // AJAX from the front end: use the language sent with the request or the visitor's language cookie
        $lang = sanitize_key( $_REQUEST['lang'] ?? ( $_COOKIE['pll_language'] ?? '' ) );
    }
    if ( ! $lang ) return $translation;

    $busy = true;
    $pll  = pll_translate_string( $text, $lang );
    $busy = false;

    return ( is_string( $pll ) && $pll !== '' && $pll !== $text ) ? $pll : $translation;
}

// ── Copy existing .mo translations into Polylang (once, and for new strings) ──
add_action( 'admin_init', function() {
    if ( ! function_exists( 'PLL' ) || ! class_exists( 'PLL_MO' ) || ! class_exists( 'MO' ) ) return;

    $strings = enroute_pll_collect_strings();
    $hash    = md5( implode( "\n", array_keys( $strings ) ) );
    if ( get_option( 'enroute_pll_seeded' ) === $hash ) return;

    try {
        $languages = PLL()->model->get_languages_list();
        foreach ( $languages as $language ) {
            $mo_file = ENROUTE_OFFERS_PATH . 'languages/enroute_offers-' . $language->locale . '.mo';
            if ( ! file_exists( $mo_file ) ) continue;

            $source = new MO();
            if ( ! $source->import_from_file( $mo_file ) ) continue;

            $pll_mo = new PLL_MO();
            $pll_mo->import_from_db( $language );
            $changed = false;

            foreach ( array_keys( $strings ) as $text ) {
                $existing = $pll_mo->translate( $text );
                if ( $existing !== '' && $existing !== $text ) continue; // already translated in Polylang

                $from_mo = $source->translate( $text );
                if ( $from_mo !== '' && $from_mo !== $text ) {
                    $pll_mo->add_entry( $pll_mo->make_entry( $text, $from_mo ) );
                    $changed = true;
                }
            }

            if ( $changed ) $pll_mo->export_to_db( $language );
        }
        update_option( 'enroute_pll_seeded', $hash, false );
    } catch ( Throwable $e ) {
        // Never break the admin because of the import; strings stay editable in Polylang.
        error_log( 'enroute_offers: Polylang import skipped — ' . $e->getMessage() );
    }
} );
