<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── AJAX: submit userpass booking ─────────────────────────────────────────────

add_action( 'wp_ajax_enroute_book_userpass',        'enroute_handle_userpass_booking' );
add_action( 'wp_ajax_nopriv_enroute_book_userpass', 'enroute_handle_userpass_booking' );

function enroute_handle_userpass_booking(): void {
    if ( ! check_ajax_referer( 'enroute_user_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => __( 'Ungültige Anfrage.', 'enroute_offers' ) ], 403 );
    }

    $pass_type_id = absint( $_POST['pass_type_id'] ?? 0 );
    $salutation   = sanitize_text_field( $_POST['salutation']  ?? '' );
    $first_name   = sanitize_text_field( $_POST['first_name']  ?? '' );
    $last_name    = sanitize_text_field( $_POST['last_name']   ?? '' );
    $institution  = sanitize_text_field( $_POST['institution'] ?? '' );
    $street       = sanitize_text_field( $_POST['street']      ?? '' );
    $zip          = sanitize_text_field( $_POST['zip']         ?? '' );
    $place        = sanitize_text_field( $_POST['place']       ?? '' );
    $email        = sanitize_email(      $_POST['email']       ?? '' );
    $phone        = sanitize_text_field( $_POST['phone']       ?? '' );
    $remarks      = sanitize_textarea_field( $_POST['remarks'] ?? '' );

    if ( ! $pass_type_id || ! $first_name || ! $last_name || ! $email ) {
        wp_send_json_error( [ 'message' => __( 'Bitte alle Pflichtfelder ausfüllen.', 'enroute_offers' ) ] );
    }
    if ( ! is_email( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'Ungültige E-Mail-Adresse.', 'enroute_offers' ) ] );
    }

    $pass_type = get_post( $pass_type_id );
    if ( ! $pass_type || $pass_type->post_type !== 'enroute_userpass' ) {
        wp_send_json_error( [ 'message' => __( 'User Pass nicht gefunden.', 'enroute_offers' ) ] );
    }

    // Calculate valid_till from validity
    $validity   = get_post_meta( $pass_type_id, '_userpass_validity', true ) ?: '1year';
    $valid_till = enroute_calculate_valid_till( $validity );
    $credit     = get_post_meta( $pass_type_id, '_userpass_credit',   true );
    $user_id    = get_current_user_id(); // 0 if not logged in

    // Create booked pass post
    $title      = $pass_type->post_title . ' — ' . trim( "$first_name $last_name" ) . ' (' . date_i18n( 'd.m.Y' ) . ')';
    $booked_id  = wp_insert_post([
        'post_type'   => 'enroute_booked_pass',
        'post_title'  => $title,
        'post_status' => 'publish',
    ], true );

    if ( is_wp_error( $booked_id ) ) {
        wp_send_json_error( [ 'message' => __( 'Fehler beim Speichern.', 'enroute_offers' ) ] );
    }

    update_post_meta( $booked_id, '_booked_pass_type_id', $pass_type_id );
    update_post_meta( $booked_id, '_booked_pass_user_id', $user_id );
    update_post_meta( $booked_id, '_booked_pass_status',  'new' );

    // If user already had a previous pass, link them and set old pass to inactive
    if ( $user_id ) {
        $prev = enroute_get_user_pass( $user_id );
        if ( $prev ) {
            update_post_meta( $booked_id, '_booked_pass_previous_id', $prev['id'] );
            update_post_meta( $prev['id'], '_booked_pass_renewed_by', $booked_id );
            update_post_meta( $prev['id'], '_booked_pass_status',     'inactive' );
        }
    }
    update_post_meta( $booked_id, '_booked_pass_email',    $email );
    update_post_meta( $booked_id, '_booked_pass_valid_till', $valid_till );
    update_post_meta( $booked_id, '_booked_pass_credit',   $credit );
    update_post_meta( $booked_id, '_booked_pass_salutation',  $salutation );
    update_post_meta( $booked_id, '_booked_pass_first_name',  $first_name );
    update_post_meta( $booked_id, '_booked_pass_last_name',   $last_name );
    update_post_meta( $booked_id, '_booked_pass_institution', $institution );
    update_post_meta( $booked_id, '_booked_pass_street',      $street );
    update_post_meta( $booked_id, '_booked_pass_zip',         $zip );
    update_post_meta( $booked_id, '_booked_pass_place',       $place );
    update_post_meta( $booked_id, '_booked_pass_phone',       $phone );
    update_post_meta( $booked_id, '_booked_pass_remarks',     $remarks );
    update_post_meta( $booked_id, '_booked_pass_submitted',   date_i18n( 'd.m.Y H:i:s' ) );

    // Save profile if user is logged in
    if ( $user_id ) {
        foreach ([
            'enroute_salutation'  => $salutation,
            'enroute_first_name'  => $first_name,
            'enroute_last_name'   => $last_name,
            'enroute_institution' => $institution,
            'enroute_street'      => $street,
            'enroute_zip'         => $zip,
            'enroute_place'       => $place,
            'enroute_phone'       => $phone,
        ] as $key => $val ) {
            if ( $val ) update_user_meta( $user_id, $key, $val );
        }
    }

    // Detect language from pass type meta
    $pass_lang = get_post_meta( $pass_type_id, '_userpass_language', true ) ?: 'de';

    // Placeholder replacements
    $placeholders = [
        '{pass}'        => $pass_type->post_title,
        '{vorname}'     => $first_name,
        '{nachname}'    => $last_name,
        '{email}'       => $email,
        '{telefon}'     => $phone,
        '{institution}' => $institution,
        '{strasse}'     => $street,
        '{plz}'         => $zip,
        '{ort}'         => $place,
        '{bemerkungen}' => $remarks,
    ];

    // Send admin notification using settings template
    $admin_email = get_option( 'enroute_booking_admin_email', get_option( 'admin_email' ) );
    $admin_url   = admin_url( 'post.php?post=' . $booked_id . '&action=edit' );
    $placeholders['{admin_url}'] = $admin_url;

    $default_admin_subject = 'Neue User Pass Buchung: {pass}';
    $default_admin_body    = 'Neue User Pass Buchung eingegangen.' . "

" . 'Pass: {pass}' . "
" . 'Name: {vorname} {nachname}' . "
" . 'E-Mail: {email}' . "
" . 'Telefon: {telefon}' . "
" . 'Institution: {institution}' . "
" . 'Adresse: {strasse}, {plz} {ort}' . "
" . 'Bemerkungen: {bemerkungen}' . "

" . 'Im Backend ansehen: {admin_url}';

    $admin_subject = get_option( 'enroute_userpass_admin_subject', $default_admin_subject );
    $admin_body    = get_option( 'enroute_userpass_admin_body',    $default_admin_body );
    $admin_subject = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $admin_subject );
    $admin_body    = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $admin_body );

    wp_mail( $admin_email, $admin_subject, $admin_body, [ 'Content-Type: text/plain; charset=UTF-8' ] );

    // Send confirmation to customer using language-specific template from settings
    $default_subjects = [
        'de' => 'Ihre User Pass Anfrage: {pass}',
        'fr' => 'Votre demande de User Pass: {pass}',
        'it' => 'La sua richiesta di User Pass: {pass}',
    ];
    $default_bodies = [
        'de' => 'Guten Tag {vorname} {nachname},' . "

" . 'Vielen Dank für Ihre Anfrage für den User Pass "{pass}".' . "
" . 'Wir werden Ihre Anfrage prüfen und uns in Kürze bei Ihnen melden.' . "

" . 'Mit freundlichen Grüssen' . "
" . 'Ihr Enroute-Team',
        'fr' => 'Bonjour {vorname} {nachname},' . "

" . 'Merci pour votre demande de User Pass "{pass}".' . "
" . 'Nous examinerons votre demande et vous contacterons prochainement.' . "

" . 'Cordialement' . "
" . "L'équipe Enroute",
        'it' => 'Buongiorno {vorname} {nachname},' . "

" . 'Grazie per la sua richiesta di User Pass "{pass}".' . "
" . 'Esamineremo la sua richiesta e la contatteremo a breve.' . "

" . 'Cordiali saluti' . "
" . 'Il team Enroute',
    ];

    $cust_subject = get_option( 'enroute_userpass_customer_subject_' . $pass_lang, $default_subjects[ $pass_lang ] ?? $default_subjects['de'] );
    $cust_body    = get_option( 'enroute_userpass_customer_body_'    . $pass_lang, $default_bodies[  $pass_lang ] ?? $default_bodies['de'] );

    // Replace placeholders
    $cust_subject = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $cust_subject );
    $cust_body    = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $cust_body );

    wp_mail( $email, $cust_subject, $cust_body, [ 'Content-Type: text/plain; charset=UTF-8' ] );

    wp_send_json_success( [
        'message'    => __( 'Ihre User Pass Anfrage wurde erfolgreich übermittelt. Sie erhalten in Kürze eine Bestätigungs-E-Mail.', 'enroute_offers' ),
        'booked_id'  => $booked_id,
    ] );
}

// ── AJAX: get passes for current language ─────────────────────────────────────

add_action( 'wp_ajax_enroute_get_userpasses',        'enroute_get_userpasses' );
add_action( 'wp_ajax_nopriv_enroute_get_userpasses', 'enroute_get_userpasses' );

function enroute_get_userpasses(): void {
    $lang = sanitize_key( $_POST['lang'] ?? 'de' );

    $args = [
        'post_type'   => 'enroute_userpass',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
    ];

    // Use Polylang to filter by language if available, otherwise fall back to meta
    // Always filter by language meta field
    $args['meta_query'] = [
        [ 'key' => '_userpass_language', 'value' => $lang, 'compare' => '=' ],
    ];

    $passes = get_posts( $args );

    $data = array_map( function( $p ) {
        $validity    = get_post_meta( $p->ID, '_userpass_validity',    true ) ?: '1year';
        $credit_type = get_post_meta( $p->ID, '_userpass_credit_type', true ) ?: 'flat_rate';
        $options     = enroute_userpass_validity_options();
        return [
            'id'             => $p->ID,
            'name'           => $p->post_title,
            'description'    => get_post_meta( $p->ID, '_userpass_description', true ),
            'validity'       => $validity,
            'validity_label' => $options[ $validity ] ?? $validity,
            'credit_type'    => $credit_type,
            'credit'         => $credit_type === 'credits' ? get_post_meta( $p->ID, '_userpass_credit', true ) : '',
        ];
    }, $passes );

    wp_send_json_success( $data );
}
