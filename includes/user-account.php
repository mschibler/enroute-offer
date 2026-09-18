<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ══════════════════════════════════════════════════════════════════════════════
// USER META HELPERS
// ══════════════════════════════════════════════════════════════════════════════

/**
 * User profile meta fields mapping.
 */
function enroute_user_meta_fields(): array {
    return [
        'enroute_salutation'  => 'sanitize_text_field',
        'enroute_first_name'  => 'sanitize_text_field',
        'enroute_last_name'   => 'sanitize_text_field',
        'enroute_institution' => 'sanitize_text_field',
        'enroute_street'      => 'sanitize_text_field',
        'enroute_zip'         => 'sanitize_text_field',
        'enroute_place'       => 'sanitize_text_field',
        'enroute_phone'       => 'sanitize_text_field',
    ];
}

/**
 * Get all profile meta for a user as array.
 */
function enroute_get_user_profile( int $user_id ): array {
    $data = [ 'email' => get_userdata( $user_id )->user_email ?? '' ];
    foreach ( enroute_user_meta_fields() as $key => $_ ) {
        $data[ $key ] = get_user_meta( $user_id, $key, true ) ?: '';
    }
    return $data;
}

// ══════════════════════════════════════════════════════════════════════════════
// AJAX — LOGIN
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_nopriv_enroute_login', 'enroute_handle_login' );

function enroute_handle_login(): void {
    check_ajax_referer( 'enroute_user_nonce', 'nonce' );

    $email    = sanitize_email( $_POST['email']    ?? '' );
    $password = $_POST['password'] ?? '';

    if ( ! $email || ! $password ) {
        wp_send_json_error( [ 'message' => __( 'Bitte E-Mail und Passwort eingeben.', 'enroute_offers' ) ] );
    }

    $user = get_user_by( 'email', $email );
    if ( ! $user ) {
        wp_send_json_error( [ 'message' => __( 'Kein Konto mit dieser E-Mail-Adresse gefunden.', 'enroute_offers' ) ] );
    }

    $result = wp_signon( [
        'user_login'    => $user->user_login,
        'user_password' => $password,
        'remember'      => true,
    ], false );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( [ 'message' => __( 'Falsches Passwort.', 'enroute_offers' ) ] );
    }

    wp_send_json_success( [
        'message' => __( 'Angemeldet!', 'enroute_offers' ),
        'profile' => enroute_get_user_profile( $result->ID ),
    ] );
}

// ══════════════════════════════════════════════════════════════════════════════
// AJAX — REGISTER
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_nopriv_enroute_register', 'enroute_handle_register' );

function enroute_handle_register(): void {
    check_ajax_referer( 'enroute_user_nonce', 'nonce' );

    $email      = sanitize_email( $_POST['email']      ?? '' );
    $password   = $_POST['password']                   ?? '';
    $password2  = $_POST['password2']                  ?? '';
    $first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
    $last_name  = sanitize_text_field( $_POST['last_name']  ?? '' );

    if ( ! $email || ! $password || ! $first_name || ! $last_name ) {
        wp_send_json_error( [ 'message' => __( 'Bitte alle Pflichtfelder ausfüllen.', 'enroute_offers' ) ] );
    }

    if ( ! is_email( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'Ungültige E-Mail-Adresse.', 'enroute_offers' ) ] );
    }

    if ( strlen( $password ) < 8 ) {
        wp_send_json_error( [ 'message' => __( 'Passwort muss mindestens 8 Zeichen lang sein.', 'enroute_offers' ) ] );
    }

    if ( $password !== $password2 ) {
        wp_send_json_error( [ 'message' => __( 'Passwörter stimmen nicht überein.', 'enroute_offers' ) ] );
    }

    if ( email_exists( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'Diese E-Mail-Adresse ist bereits registriert.', 'enroute_offers' ) ] );
    }

    if ( ! get_option( 'users_can_register' ) ) {
        wp_send_json_error( [ 'message' => __( 'Registrierung ist deaktiviert.', 'enroute_offers' ) ] );
    }

    $user_id = wp_create_user( $email, $password, $email );

    if ( is_wp_error( $user_id ) ) {
        wp_send_json_error( [ 'message' => $user_id->get_error_message() ] );
    }

    // Set role to subscriber
    $user = new WP_User( $user_id );
    $user->set_role( 'subscriber' );

    // Save name
    wp_update_user( [
        'ID'           => $user_id,
        'first_name'   => $first_name,
        'last_name'    => $last_name,
        'display_name' => trim( "$first_name $last_name" ),
    ] );

    // Save profile meta from registration form
    foreach ( enroute_user_meta_fields() as $key => $sanitizer ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_user_meta( $user_id, $key, $sanitizer( $_POST[ $key ] ) );
        }
    }
    // Sync first/last name to meta too
    update_user_meta( $user_id, 'enroute_first_name', $first_name );
    update_user_meta( $user_id, 'enroute_last_name',  $last_name );

    // Auto-login
    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );

    // Send notification email
    wp_new_user_notification( $user_id, null, 'user' );

    wp_send_json_success( [
        'message' => __( 'Konto erstellt und angemeldet!', 'enroute_offers' ),
        'profile' => enroute_get_user_profile( $user_id ),
    ] );
}

// ══════════════════════════════════════════════════════════════════════════════
// AJAX — SAVE PROFILE
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_enroute_save_profile', 'enroute_handle_save_profile' );

function enroute_handle_save_profile(): void {
    check_ajax_referer( 'enroute_user_nonce', 'nonce' );

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => __( 'Nicht angemeldet.', 'enroute_offers' ) ] );
    }

    $user_id = get_current_user_id();

    foreach ( enroute_user_meta_fields() as $key => $sanitizer ) {
        if ( isset( $_POST[ $key ] ) ) {
            update_user_meta( $user_id, $key, $sanitizer( wp_unslash( $_POST[ $key ] ) ) );
        }
    }

    // Sync name to WP core fields too
    $first = get_user_meta( $user_id, 'enroute_first_name', true );
    $last  = get_user_meta( $user_id, 'enroute_last_name',  true );
    wp_update_user( [
        'ID'           => $user_id,
        'first_name'   => $first,
        'last_name'    => $last,
        'display_name' => trim( "$first $last" ),
    ] );

    wp_send_json_success( [
        'message' => __( 'Profil gespeichert.', 'enroute_offers' ),
        'profile' => enroute_get_user_profile( $user_id ),
    ] );
}

// ══════════════════════════════════════════════════════════════════════════════
// AJAX — LOGOUT
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_enroute_logout', 'enroute_handle_logout' );

function enroute_handle_logout(): void {
    check_ajax_referer( 'enroute_user_nonce', 'nonce' );
    wp_logout();
    wp_send_json_success( [ 'message' => __( 'Abgemeldet.', 'enroute_offers' ) ] );
}

// ══════════════════════════════════════════════════════════════════════════════
// Pass user state to frontend
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'wp_enqueue_scripts', function() {
    if ( ! wp_script_is( 'enroute-offers-front', 'enqueued' ) ) return;

    $user_data = [ 'loggedIn' => false, 'profile' => [] ];
    if ( is_user_logged_in() ) {
        $user_data = [
            'loggedIn' => true,
            'profile'  => enroute_get_user_profile( get_current_user_id() ),
        ];
    }

    wp_localize_script( 'enroute-offers-front', 'enrouteUserVars', [
        'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
        'nonce'        => wp_create_nonce( 'enroute_user_nonce' ),
        'loggedIn'     => is_user_logged_in(),
        'profile'      => $user_data['profile'],
        'profileUrl'   => get_option( 'enroute_profile_page_url', '' ),
        'userpassUrl'  => get_option( 'enroute_userpass_page_url', '' ),
        'bookingNonce' => wp_create_nonce( 'enroute_booking_nonce' ),
    ] );
    // Also expose userpass URL globally for booking form
    wp_add_inline_script( 'enroute-offers-front',
        'window.enrouteUserpassUrl = ' . wp_json_encode( get_option( 'enroute_userpass_page_url', '' ) ) . ';',
        'before'
    );
}, 20 );

// ══════════════════════════════════════════════════════════════════════════════
// AJAX — REFRESH NONCES (called after login to get user-specific nonces)
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_enroute_refresh_nonces',        'enroute_handle_refresh_nonces' );
add_action( 'wp_ajax_nopriv_enroute_refresh_nonces', 'enroute_handle_refresh_nonces' );

function enroute_handle_refresh_nonces(): void {
    // No nonce check — this endpoint is called right after login when old nonces are stale.
    // It only returns fresh nonces, so there's no security risk.
    wp_send_json_success( [
        'bookingNonce' => wp_create_nonce( 'enroute_booking_nonce' ),
        'userNonce'    => wp_create_nonce( 'enroute_user_nonce' ),
    ] );
}

// ══════════════════════════════════════════════════════════════════════════════
// REDIRECT after WP login to profile page
// ══════════════════════════════════════════════════════════════════════════════

add_filter( 'login_redirect', function( string $redirect_to, string $requested_redirect_to, $user ): string {
    $profile_url = get_option( 'enroute_profile_page_url', '' );
    if ( $profile_url && $user instanceof WP_User && ! $user->has_cap( 'edit_posts' ) ) {
        return $profile_url;
    }
    return $redirect_to;
}, 10, 3 );

// ══════════════════════════════════════════════════════════════════════════════
// Protect profile page — redirect to home if not logged in
// (shortcode handles this too but this catches direct URL access)
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'template_redirect', function(): void {
    $profile_url = get_option( 'enroute_profile_page_url', '' );
    if ( ! $profile_url ) return;
    if ( is_user_logged_in() ) return;

    // Check if current page is the profile page
    $current_url = ( is_ssl() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $current_url = strtok( $current_url, '?' );
    $profile_url_clean = strtok( $profile_url, '?' );

    if ( rtrim( $current_url, '/' ) === rtrim( $profile_url_clean, '/' ) ) {
        // Not logged in on profile page — show page but shortcode will handle the message
        // (we let the page render so any custom content above/below the shortcode is visible)
    }
});
