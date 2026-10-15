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
        'templates/partials/form-auth.php'    => 'Enroute – Formulare (Buchung & Anmeldung)',
        'templates/partials/form-contact.php' => 'Enroute – Formulare (Buchung & Anmeldung)',
        'templates/partials/form-group.php'   => 'Enroute – Formulare (Buchung & Anmeldung)',
        'templates/events-listing.php'    => 'Enroute – Veranstaltungen',
        'templates/event-detail.php'      => 'Enroute – Veranstaltungen',
        'includes/event-registration.php' => 'Enroute – Veranstaltungen',
        'includes/event-post-type.php'    => 'Enroute – Veranstaltungen',
    ];
}

/**
 * All PHP files of the plugin with their Polylang group.
 * Files not listed above (admin screens, settings, editor boxes) go to
 * "Enroute – Backend", so every text of the plugin is translatable —
 * including files added in the future.
 */
function enroute_pll_all_files(): array {
    $files  = enroute_pll_string_groups();
    $found  = array_merge(
        [ 'enroute_offers.php' ],
        array_map( fn( $f ) => 'includes/' . basename( $f ), glob( ENROUTE_OFFERS_PATH . 'includes/*.php' ) ?: [] ),
        array_map( fn( $f ) => 'templates/' . basename( $f ), glob( ENROUTE_OFFERS_PATH . 'templates/*.php' ) ?: [] ),
        array_map( fn( $f ) => 'templates/partials/' . basename( $f ), glob( ENROUTE_OFFERS_PATH . 'templates/partials/*.php' ) ?: [] )
    );
    foreach ( $found as $rel ) {
        if ( ! isset( $files[ $rel ] ) ) $files[ $rel ] = 'Enroute – Backend';
    }
    return $files;
}

/**
 * Extracts the texts of __(), _e(), esc_html__() … calls with the
 * 'enroute_offers' text domain from all plugin files.
 * Returns [ text => group ]; cached until a file changes.
 */
function enroute_pll_collect_strings(): array {
    $groups = enroute_pll_all_files();

    $stamp = '';
    foreach ( array_keys( $groups ) as $rel ) {
        $file   = ENROUTE_OFFERS_PATH . $rel;
        $stamp .= $rel . ( file_exists( $file ) ? filemtime( $file ) : 0 );
    }
    $cache_key = 'enroute_pll_strings_' . md5( $stamp );
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) ) return $cached;

    $funcs   = [ '__', '_e', 'esc_html__', 'esc_html_e', 'esc_attr__', 'esc_attr_e', '_x', '_ex', 'esc_html_x', 'esc_attr_x' ];
    $ctx_funcs = [ '_x', '_ex', 'esc_html_x', 'esc_attr_x' ];
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
            // With context (_x): own Polylang string "Text (Context)", so the same word
            // can be translated differently, e.g. "Anmelden (Veranstaltung)"
            if ( in_array( $t[1], $ctx_funcs, true ) && $tokens[ $i + 3 ] === ',' && is_array( $tokens[ $i + 4 ] ) && $tokens[ $i + 4 ][0] === T_CONSTANT_ENCAPSED_STRING ) {
                $text = enroute_pll_context_key( $text, enroute_pll_unquote( $tokens[ $i + 4 ][1] ) );
            }
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
    // Looked up as "Text (Context)"; not saved → the .mo translation (never the
    // translation of the plain text, which may mean something else)
    $key = enroute_pll_context_key( $text, $context );
    $out = enroute_pll_gettext( $translation, $key, $domain );
    return $out === $key ? $translation : $out;
}, 20, 4 );

/** Polylang string for a text with context. */
function enroute_pll_context_key( string $text, string $context ): string {
    return $context !== '' ? $text . ' (' . $context . ')' : $text;
}

function enroute_pll_gettext( $translation, $text, $domain ) {
    static $busy = false;
    if ( $domain !== 'enroute_offers' || $busy ) return $translation;
    if ( ! function_exists( 'pll_translate_string' ) || ! function_exists( 'pll_current_language' ) ) return $translation;

    if ( is_admin() && ! wp_doing_ajax() ) {
        // Backend: the language of the logged-in admin's own WordPress language setting
        $lang = enroute_pll_admin_language();
    } else {
        $lang = pll_current_language();
    }
    if ( wp_doing_ajax() ) {
        // AJAX from the front end: language sent with the request, else current / cookie language
        $lang = sanitize_key( $_REQUEST['lang'] ?? '' ) ?: ( $lang ?: sanitize_key( $_COOKIE['pll_language'] ?? '' ) );
    }
    if ( ! $lang ) return $translation;

    $busy  = true;
    $saved = enroute_pll_saved_translation( $text, $lang );
    $busy  = false;

    // Saved in Polylang → use it (even if identical to the original); otherwise the .mo translation
    return $saved ?? $translation;
}

/**
 * Translation saved in Polylang for $text in $lang, or null if none was saved.
 * Unlike pll_translate_string(), a translation that is identical to the
 * original also counts as saved, so an admin's choice always wins.
 */
function enroute_pll_saved_translation( string $text, string $lang ): ?string {
    static $tables = [];
    if ( ! isset( $tables[ $lang ] ) ) {
        $tables[ $lang ] = [];
        if ( function_exists( 'PLL' ) && class_exists( 'PLL_MO' ) ) {
            $language = PLL()->model->get_language( $lang );
            if ( $language ) {
                $mo = new PLL_MO();
                $mo->import_from_db( $language );
                foreach ( $mo->entries as $entry ) {
                    if ( $entry->singular !== '' && ! empty( $entry->translations[0] ) ) {
                        $tables[ $lang ][ $entry->singular ] = $entry->translations[0];
                    }
                }
            }
        }
    }
    return $tables[ $lang ][ $text ] ?? null;
}

/**
 * Translates a plugin text into a specific language, independent of the
 * current request (e.g. an admin e-mail in the event's language).
 * Order: translation saved in Polylang → plugin .mo file → original text.
 */
function enroute_t( string $text, string $lang ): string {
    if ( function_exists( 'enroute_pll_saved_translation' ) && function_exists( 'PLL' ) ) {
        $saved = enroute_pll_saved_translation( $text, $lang );
        if ( $saved !== null ) return $saved;
    }
    static $mos = [];
    if ( ! array_key_exists( $lang, $mos ) ) {
        $mos[ $lang ] = null;
        $locales = [ 'de' => 'de_DE', 'fr' => 'fr_FR', 'it' => 'it_IT' ];
        $file    = ENROUTE_OFFERS_PATH . 'languages/enroute_offers-' . ( $locales[ $lang ] ?? '' ) . '.mo';
        if ( isset( $locales[ $lang ] ) && class_exists( 'MO' ) && file_exists( $file ) ) {
            $mo = new MO();
            if ( $mo->import_from_file( $file ) ) $mos[ $lang ] = $mo;
        }
    }
    return $mos[ $lang ] ? $mos[ $lang ]->translate( $text ) : $text;
}

/** Polylang language slug matching the current admin user's language (de_CH → de). */
function enroute_pll_admin_language(): string {
    static $slug = null;
    if ( $slug !== null ) return $slug;
    $slug = '';
    if ( ! function_exists( 'PLL' ) || ! function_exists( 'get_user_locale' ) || ! did_action( 'init' ) ) return $slug;

    $locale = get_user_locale();
    foreach ( PLL()->model->get_languages_list() as $language ) {
        if ( $language->locale === $locale ) return $slug = $language->slug;
    }
    foreach ( PLL()->model->get_languages_list() as $language ) {
        if ( substr( $language->locale, 0, 2 ) === substr( $locale, 0, 2 ) ) return $slug = $language->slug;
    }
    return $slug;
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
                if ( isset( $pll_mo->entries[ $text ] ) ) continue; // saved in Polylang (by an admin or earlier import) — never overwrite

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
