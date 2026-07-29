<?php
// Sicherheitsabfrage: Verhindert direkten Aufruf der Datei
if (!defined('ABSPATH')) {
    exit;
}

// HIER PLATZIEREN: Der Hook für WordPress
add_action('init', 'enroute_polylang_register');

// Die eigentliche Funktion zur Registrierung
function enroute_polylang_register() {
    if (function_exists('pll_register_string')) {
        pll_register_string('enrouteOffersStrings', 'Benutzerpässe für Lehrpersonen', 'Enroute Offer', false);
    }
}

?>