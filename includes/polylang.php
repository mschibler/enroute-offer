<?php
// Sicherheitsabfrage: Verhindert direkten Aufruf der Datei
if (!defined('ABSPATH')) {
    exit;
}

// HIER PLATZIEREN: Der Hook für WordPress
add_action('init', 'enroute_polylang_register');

// Die eigentliche Funktion zur Registrierung
function enroute_polylang_register() {
    // Strings are registered automatically from all plugin files — see polylang-strings.php
}

// Exclude offer, resource, station from Polylang language filtering.
// These post types show all languages in listings — language sorting is handled
// client-side in Alpine. Polylang filtering would hide entries from other languages.
add_filter( 'pll_get_post_types', function( array $post_types ): array {
    unset( $post_types['offer'] );
    unset( $post_types['resource'] );
    unset( $post_types['station'] );
    return $post_types;
}, 20 );

?>