<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handle booking form AJAX submission (logged-in + non-logged-in users).
 */
add_action( 'wp_ajax_enroute_submit_booking',        'enroute_handle_booking_submission' );
add_action( 'wp_ajax_nopriv_enroute_submit_booking', 'enroute_handle_booking_submission' );

function enroute_handle_booking_submission() {

    // ── Security ────────────────────────────────────────────────────────────────
    if ( ! check_ajax_referer( 'enroute_booking_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => __( 'Ungültige Anfrage.', 'enroute_offers' ) ], 403 );
    }

    // ── Sanitise input ──────────────────────────────────────────────────────────
    $offer_id    = absint( $_POST['offer_id']    ?? 0 );
    $salutation  = sanitize_text_field( $_POST['salutation']  ?? '' );
    $institution = sanitize_text_field( $_POST['institution'] ?? '' );
    $first_name  = sanitize_text_field( $_POST['first_name']  ?? '' );
    $last_name   = sanitize_text_field( $_POST['last_name']   ?? '' );
    $street      = sanitize_text_field( $_POST['street']      ?? '' );
    $zip         = sanitize_text_field( $_POST['zip']         ?? '' );
    $place       = sanitize_text_field( $_POST['place']       ?? '' );
    $email       = sanitize_email(      $_POST['email']       ?? '' );
    $phone       = sanitize_text_field( $_POST['phone']       ?? '' );
    $date_1      = sanitize_text_field( $_POST['date_1']      ?? '' );
    $time_1      = sanitize_text_field( $_POST['time_1']      ?? '' );
    $date_2      = sanitize_text_field( $_POST['date_2']      ?? '' );
    $time_2      = sanitize_text_field( $_POST['time_2']      ?? '' );
    // Group: three numbers; the total is kept in _booking_persons (old bookings, lists)
    $persons_under16    = isset( $_POST['persons_under16'] )    && $_POST['persons_under16']    !== '' ? absint( $_POST['persons_under16'] )    : '';
    $persons_over16     = isset( $_POST['persons_over16'] )     && $_POST['persons_over16']     !== '' ? absint( $_POST['persons_over16'] )     : '';
    $persons_companions = isset( $_POST['persons_companions'] ) && $_POST['persons_companions'] !== '' ? absint( $_POST['persons_companions'] ) : '';
    $persons_given      = [ $persons_under16, $persons_over16, $persons_companions ] !== [ '', '', '' ];
    $persons            = $persons_given ? (int) $persons_under16 + (int) $persons_over16 + (int) $persons_companions : '';
    $remarks     = sanitize_textarea_field( $_POST['remarks'] ?? '' );

    // ── Required fields ─────────────────────────────────────────────────────────
    if ( ! $offer_id || ! $first_name || ! $last_name || ! $email || ! $date_1 ) {
        wp_send_json_error( [ 'message' => __( 'Bitte füllen Sie alle Pflichtfelder aus.', 'enroute_offers' ) ] );
    }
    if ( ! is_email( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'enroute_offers' ) ] );
    }

    $offer = get_post( $offer_id );
    if ( ! $offer || $offer->post_type !== 'offer' ) {
        wp_send_json_error( [ 'message' => __( 'Angebot nicht gefunden.', 'enroute_offers' ) ] );
    }
    if ( ! enroute_offer_is_bookable( $offer_id ) ) {
        wp_send_json_error( [ 'message' => __( 'Dieses Angebot kann nicht gebucht werden.', 'enroute_offers' ) ] );
    }

    $offer_title    = $offer->post_title;
    $offer_subtitle = get_post_meta( $offer_id, '_offer_subtitle', true );
    $offer_email    = get_post_meta( $offer_id, '_offer_contact_email', true );

    // ── Create booking CPT entry ─────────────────────────────────────────────────
    $booking_title = sprintf(
        '%s — %s %s (%s)',
        $offer_title,
        $first_name,
        $last_name,
        date_i18n( 'd.m.Y H:i' )
    );

    $booking_id = wp_insert_post( [
        'post_type'   => 'enroute_booking',
        'post_title'  => $booking_title,
        'post_status' => 'publish',
    ], true );

    if ( is_wp_error( $booking_id ) ) {
        wp_send_json_error( [ 'message' => __( 'Fehler beim Speichern der Buchungsanfrage.', 'enroute_offers' ) ] );
    }

    // Save meta
    $meta = [
        '_booking_offer_id'    => $offer_id,
        '_booking_offer_title' => $offer_title,
        '_booking_salutation'  => $salutation,
        '_booking_institution' => $institution,
        '_booking_first_name'  => $first_name,
        '_booking_last_name'   => $last_name,
        '_booking_street'      => $street,
        '_booking_zip'         => $zip,
        '_booking_place'       => $place,
        '_booking_email'       => $email,
        '_booking_phone'       => $phone,
        '_booking_date_1'      => $date_1,
        '_booking_time_1'      => $time_1,
        '_booking_date_2'      => $date_2,
        '_booking_time_2'      => $time_2,
        '_booking_persons'            => $persons,
        '_booking_persons_under16'    => $persons_under16,
        '_booking_persons_over16'     => $persons_over16,
        '_booking_persons_companions' => $persons_companions,
        '_booking_remarks'     => $remarks,
        '_booking_submitted'   => date_i18n( 'd.m.Y H:i:s' ),
        '_booking_user_id'     => get_current_user_id(), // 0 if not logged in
    ];
    foreach ( $meta as $key => $value ) {
        update_post_meta( $booking_id, $key, $value );
    }

    // ── Build placeholder map ────────────────────────────────────────────────────
    $admin_url = admin_url( 'post.php?post=' . $booking_id . '&action=edit' );
    $placeholders = [
        '{angebot}'     => $offer_title,
        '{vorname}'     => $first_name,
        '{nachname}'    => $last_name,
        '{email}'       => $email,
        '{telefon}'     => $phone,
        '{institution}' => $institution,
        '{strasse}'     => $street,
        '{plz}'         => $zip,
        '{ort}'         => $place,
        '{datum1}'      => $date_1,
        '{uhrzeit1}'    => $time_1,
        '{datum2}'      => $date_2,
        '{uhrzeit2}'    => $time_2,
        '{personen}'          => enroute_booking_persons_summary( $persons, $persons_under16, $persons_over16, $persons_companions ),
        '{personen_unter16}'  => (string) $persons_under16,
        '{personen_ueber16}'  => (string) $persons_over16,
        '{begleitpersonen}'   => (string) $persons_companions,
        '{bemerkungen}' => $remarks,
        '{admin_url}'   => $admin_url,
    ];

    $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

    // ── Detect language: visitor's language, then offer language, fall back to DE ─
    $lang_code = sanitize_key( $_POST['lang'] ?? '' ) ?: ( get_post_meta( $offer_id, '_offer_language', true ) ?: 'de' );
    if ( ! in_array( $lang_code, [ 'de', 'fr', 'it' ] ) ) $lang_code = 'de';

    // ── Customer confirmation ────────────────────────────────────────────────────
    if ( is_email( $email ) ) {
        $defaults_subj = [
            'de' => 'Bestätigung Ihrer Buchungsanfrage',
            'fr' => 'Confirmation de votre demande de réservation',
            'it' => 'Conferma della tua richiesta di prenotazione',
        ];
        $cust_subject = get_option( "enroute_booking_customer_subject_$lang_code", $defaults_subj[ $lang_code ] );
        $cust_body    = get_option( "enroute_booking_customer_body_$lang_code", '' );
        if ( ! $cust_body ) {
            $cust_body = "Guten Tag {vorname} {nachname},\n\nVielen Dank für Ihre Buchungsanfrage für \"{angebot}\".\n\nMit freundlichen Grüssen\nIhr Enroute-Team";
        }
        $cust_subject = strtr( $cust_subject, $placeholders );
        $cust_body    = strtr( $cust_body,    $placeholders );

        wp_mail( $email, $cust_subject, $cust_body, $headers );
    }

    // ── Offer-contact confirmation ───────────────────────────────────────────────
    if ( $offer_email && is_email( $offer_email ) ) {
        $cont_subject = get_option( "enroute_booking_contact_subject_$lang_code", "Neue Buchungsanfrage: {angebot}" );
        $cont_body    = get_option( "enroute_booking_contact_body_$lang_code", '' );
        if ( ! $cont_body ) {
            $cont_body = "Angebot: {angebot}\nName: {vorname} {nachname}\nE-Mail: {email}\nWunschdatum: {datum1} {uhrzeit1}";
        }
        $cont_subject = strtr( $cont_subject, $placeholders );
        $cont_body    = strtr( $cont_body,    $placeholders );

        wp_mail( $offer_email, $cont_subject, $cont_body, $headers );
    }

    // ── Admin notification ───────────────────────────────────────────────────────
    $admin_email = get_option( 'enroute_booking_admin_email', get_option( 'admin_email' ) );
    $adm_subject = get_option( "enroute_booking_admin_subject_$lang_code", "Neue Buchungsanfrage: {angebot}" );
    $adm_body    = get_option( "enroute_booking_admin_body_$lang_code", '' );
    if ( ! $adm_body ) {
        $adm_body = "Angebot: {angebot}\nName: {vorname} {nachname}\nE-Mail: {email}\n\n{admin_url}";
    }
    $adm_subject = strtr( $adm_subject, $placeholders );
    $adm_body    = strtr( $adm_body,    $placeholders );
    if ( $admin_email && is_email( $admin_email ) ) {
        wp_mail( $admin_email, $adm_subject, $adm_body, $headers );
    }

    wp_send_json_success( [ 'message' => __( 'Ihre Buchungsanfrage wurde erfolgreich übermittelt. Sie erhalten in Kürze eine Bestätigungs-E-Mail.', 'enroute_offers' ) ] );
}
