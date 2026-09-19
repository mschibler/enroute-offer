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
// AJAX — GET PASS SECTION HTML (called after login to refresh the pass UI)
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'wp_ajax_enroute_get_pass_section', 'enroute_handle_get_pass_section' );

function enroute_handle_get_pass_section(): void {
    $post_id      = absint( $_POST['offer_id'] ?? 0 );
    $current_lang = sanitize_key( $_POST['lang'] ?? 'de' );

    // Get user's pass
    $user_pass = is_user_logged_in() ? enroute_get_user_pass( get_current_user_id() ) : null;

    // Get available passes for language
    $pass_args = [
        'post_type'   => 'enroute_userpass',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
    ];
    if ( function_exists( 'pll_get_post_language' ) ) {
        $pass_args['lang'] = $current_lang;
    } else {
        $pass_args['meta_query'] = [[ 'key' => '_userpass_language', 'value' => $current_lang, 'compare' => '=' ]];
    }
    $available_passes = get_posts( $pass_args );
    $userpass_url     = get_option( 'enroute_userpass_page_url', '' );

    ob_start();
    $pass_ok        = $user_pass && $user_pass['is_valid'] && $user_pass['has_credit'];
    $pass_expired   = $user_pass && ! $user_pass['is_valid'];
    $pass_no_credit = $user_pass && $user_pass['is_valid'] && ! $user_pass['has_credit'];
    $pass_bad       = $pass_expired || $pass_no_credit;
    $vp_opts        = enroute_userpass_validity_options();

    if ( $pass_ok ) : ?>
        <div style="margin-bottom:1rem; padding:0.75rem 1rem; background:rgba(0,0,0,0.05); border-left:3px solid #2271b1;">
            <label style="display:flex; align-items:flex-start; gap:0.5rem; cursor:pointer;">
                <input type="checkbox" x-model="form.use_userpass" style="margin-top:0.2rem; flex-shrink:0;">
                <span style="font-size:0.875rem;">
                    <?php echo esc_html( sprintf(
                        __( 'User Pass verwenden (%s%s)', 'enroute_offers' ),
                        $user_pass['pass_type'] ?: $user_pass['name'],
                        $user_pass['valid_till_f'] ? ', ' . __( 'gültig bis', 'enroute_offers' ) . ' ' . $user_pass['valid_till_f'] : ''
                    ) ); ?>
                </span>
            </label>
        </div>
    <?php elseif ( $pass_bad || ! empty( $available_passes ) ) : ?>
        <div style="margin-bottom:1rem; padding:0.75rem 1rem; background:rgba(0,0,0,0.05); border-left:3px solid #e5a00d;">
            <?php if ( $pass_no_credit ) : ?>
                <p style="margin:0 0 0.6rem; font-size:0.875rem; font-weight:600; color:#991b1b;">
                    <?php esc_html_e( 'Ihr User Pass hat kein Guthaben mehr. Möchten Sie einen neuen Pass buchen?', 'enroute_offers' ); ?>
                </p>
            <?php elseif ( $pass_expired ) : ?>
                <p style="margin:0 0 0.6rem; font-size:0.875rem; font-weight:600; color:#991b1b;">
                    <?php esc_html_e( 'Ihr User Pass ist nicht mehr gültig. Möchten Sie einen neuen Pass buchen?', 'enroute_offers' ); ?>
                </p>
            <?php else : ?>
                <p style="margin:0 0 0.6rem; font-size:0.875rem; font-weight:600;">
                    <?php esc_html_e( 'Möchten Sie einen User Pass hinzufügen?', 'enroute_offers' ); ?>
                </p>
            <?php endif; ?>
            <?php foreach ( $available_passes as $bp ) :
                $bp_validity = get_post_meta( $bp->ID, '_userpass_validity', true ) ?: '1year';
                $bp_credit   = get_post_meta( $bp->ID, '_userpass_credit',   true );
                $bp_desc     = get_post_meta( $bp->ID, '_userpass_description', true );
                $vp_label    = $vp_opts[ $bp_validity ] ?? $bp_validity;
                $is_same     = $user_pass && (int) $user_pass['pass_type_id'] === (int) $bp->ID;
                $is_old_bad  = $is_same && $pass_bad;
            ?>
            <label style="display:flex; align-items:flex-start; gap:0.5rem; cursor:pointer; margin-bottom:0.5rem;">
                <input type="radio" name="booking_pass_type" x-model="form.booking_pass_type_id"
                       value="<?php echo (int) $bp->ID; ?>" style="margin-top:0.2rem; flex-shrink:0;">
                <span style="font-size:0.85rem;">
                    <strong style="<?php echo $is_old_bad ? 'color:#991b1b;' : ''; ?>">
                        <?php echo esc_html( $bp->post_title ); ?>
                        <?php if ( $is_old_bad ) : ?>
                        <span style="font-size:0.78rem; color:#991b1b;">
                            (<?php echo $pass_no_credit ? esc_html__( 'kein Guthaben mehr', 'enroute_offers' ) : esc_html__( 'abgelaufen', 'enroute_offers' ); ?>)
                        </span>
                        <?php endif; ?>
                        <?php if ( $is_same ) : ?>
                        <span style="font-size:0.78rem; color:#6b7280; font-weight:400;">
                            — <?php esc_html_e( 'gleiche Auswahl wie letztes Mal', 'enroute_offers' ); ?>
                        </span>
                        <?php endif; ?>
                    </strong>
                    <span style="color:#6b7280; font-size:0.8rem;">
                        — <?php echo esc_html( $vp_label ); ?>
                        <?php if ( $bp_credit ) echo ' | ' . esc_html( $bp_credit ); ?>
                    </span>
                    <?php if ( $bp_desc ) : ?>
                    <br><span style="color:#4b5563; font-size:0.8rem;"><?php echo esc_html( $bp_desc ); ?></span>
                    <?php endif; ?>
                </span>
            </label>
            <?php endforeach; ?>
            <label style="display:flex; align-items:flex-start; gap:0.5rem; cursor:pointer; margin-top:0.25rem;">
                <input type="radio" name="booking_pass_type" x-model="form.booking_pass_type_id"
                       value="" style="margin-top:0.2rem; flex-shrink:0;">
                <span style="font-size:0.85rem; color:#6b7280;"><?php esc_html_e( 'Kein User Pass', 'enroute_offers' ); ?></span>
            </label>
        </div>
    <?php endif;

    $html = ob_get_clean();
    wp_send_json_success( [ 'html' => $html ] );
}

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
