<?php
/**
 * Event registration: AJAX handler and e-mails.
 *
 * - Admin notification → admin e-mail of the EVENT's language (Settings → Events),
 *   falling back to the general offers admin e-mail.
 * - Confirmation → the person registering, in the language they registered in.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Default e-mail templates per language (used until edited in the settings). */
function enroute_event_email_defaults(): array {
    return [
        'de' => [
            'admin_subject'    => 'Neue Anmeldung: {veranstaltung}',
            'admin_body'       => "Neue Anmeldung eingegangen.\n\nVeranstaltung: {veranstaltung}\nDatum: {datum}\nOrt: {veranstaltungsort}\n\nName: {anrede} {vorname} {nachname}\nInstitution: {institution}\nE-Mail: {email}\nTelefon: {telefon}\nAdresse: {strasse}, {plz} {ort}\nAnzahl Personen: {personen}\nWeitere Angaben: {bemerkungen}\n\nIm Backend ansehen: {admin_url}",
            'customer_subject' => 'Ihre Anmeldung: {veranstaltung}',
            'customer_body'    => "Guten Tag {vorname} {nachname}\n\nVielen Dank für Ihre Anmeldung zur Veranstaltung «{veranstaltung}».\n\nDatum: {datum}\nOrt: {veranstaltungsort}\nAnzahl Personen: {personen}\n\nMit freundlichen Grüssen\nIhr Enroute-Team",
        ],
        'fr' => [
            'admin_subject'    => 'Nouvelle inscription : {veranstaltung}',
            'admin_body'       => "Nouvelle inscription reçue.\n\nÉvénement : {veranstaltung}\nDate : {datum}\nLieu : {veranstaltungsort}\n\nNom : {anrede} {vorname} {nachname}\nInstitution : {institution}\nE-mail : {email}\nTéléphone : {telefon}\nAdresse : {strasse}, {plz} {ort}\nNombre de personnes : {personen}\nRemarques : {bemerkungen}\n\nVoir dans le backend : {admin_url}",
            'customer_subject' => 'Votre inscription : {veranstaltung}',
            'customer_body'    => "Bonjour {vorname} {nachname}\n\nMerci pour votre inscription à l'événement « {veranstaltung} ».\n\nDate : {datum}\nLieu : {veranstaltungsort}\nNombre de personnes : {personen}\n\nMeilleures salutations\nL'équipe Enroute",
        ],
        'it' => [
            'admin_subject'    => 'Nuova iscrizione: {veranstaltung}',
            'admin_body'       => "Nuova iscrizione ricevuta.\n\nEvento: {veranstaltung}\nData: {datum}\nLuogo: {veranstaltungsort}\n\nNome: {anrede} {vorname} {nachname}\nIstituzione: {institution}\nE-mail: {email}\nTelefono: {telefon}\nIndirizzo: {strasse}, {plz} {ort}\nNumero di persone: {personen}\nOsservazioni: {bemerkungen}\n\nVedi nel backend: {admin_url}",
            'customer_subject' => 'La Sua iscrizione: {veranstaltung}',
            'customer_body'    => "Buongiorno {vorname} {nachname}\n\nGrazie per la Sua iscrizione all'evento «{veranstaltung}».\n\nData: {datum}\nLuogo: {veranstaltungsort}\nNumero di persone: {personen}\n\nCordiali saluti\nIl team Enroute",
        ],
    ];
}

/** Setting value for a language, with default. $key: admin_email|admin_subject|admin_body|customer_subject|customer_body */
function enroute_event_email_setting( string $key, string $lang ): string {
    $lang     = array_key_exists( $lang, enroute_event_email_defaults() ) ? $lang : 'de';
    $value    = (string) get_option( "enroute_event_{$key}_{$lang}", '' );
    if ( $value !== '' ) return $value;
    if ( $key === 'admin_email' ) {
        return (string) get_option( 'enroute_booking_admin_email', get_option( 'admin_email' ) );
    }
    return enroute_event_email_defaults()[ $lang ][ $key ] ?? '';
}

add_action( 'wp_ajax_enroute_submit_event_registration',        'enroute_handle_event_registration' );
add_action( 'wp_ajax_nopriv_enroute_submit_event_registration', 'enroute_handle_event_registration' );

function enroute_handle_event_registration() {
    if ( ! check_ajax_referer( 'enroute_booking_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => __( 'Ungültige Anfrage.', 'enroute_offers' ) ], 403 );
    }

    $event_id = absint( $_POST['event_id'] ?? 0 );
    $event    = $event_id ? get_post( $event_id ) : null;
    if ( ! $event || $event->post_type !== 'event' || $event->post_status !== 'publish' ) {
        wp_send_json_error( [ 'message' => __( 'Veranstaltung nicht gefunden.', 'enroute_offers' ) ] );
    }
    if ( enroute_event_is_past( $event_id ) ) {
        wp_send_json_error( [ 'message' => __( 'Für diese Veranstaltung ist keine Anmeldung mehr möglich.', 'enroute_offers' ) ] );
    }

    $f = fn( string $k ) => sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) );
    $salutation  = $f( 'salutation' );
    $institution = $f( 'institution' );
    $first_name  = $f( 'first_name' );
    $last_name   = $f( 'last_name' );
    $street      = $f( 'street' );
    $zip         = $f( 'zip' );
    $place       = $f( 'place' );
    $email       = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $phone       = $f( 'phone' );
    $remarks     = sanitize_textarea_field( wp_unslash( $_POST['remarks'] ?? '' ) );

    $num = fn( string $k ) => ( isset( $_POST[ $k ] ) && $_POST[ $k ] !== '' ) ? absint( $_POST[ $k ] ) : '';
    $under16    = $num( 'persons_under16' );
    $over16     = $num( 'persons_over16' );
    $companions = $num( 'persons_companions' );
    $persons    = [ $under16, $over16, $companions ] !== [ '', '', '' ] ? (int) $under16 + (int) $over16 + (int) $companions : '';

    if ( ! $first_name || ! $last_name || ! $email ) {
        wp_send_json_error( [ 'message' => __( 'Bitte füllen Sie alle Pflichtfelder aus.', 'enroute_offers' ) ] );
    }
    if ( ! is_email( $email ) ) {
        wp_send_json_error( [ 'message' => __( 'Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'enroute_offers' ) ] );
    }

    $event_title = html_entity_decode( get_the_title( $event_id ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $event_dates = enroute_event_date_text( $event_id );
    $event_place = implode( ', ', enroute_event_place_lines( $event_id ) );

    $reg_id = wp_insert_post( [
        'post_type'   => 'enroute_event_reg',
        'post_status' => 'publish',
        'post_title'  => sprintf( '%s – %s %s', $event_title, $first_name, $last_name ),
    ] );
    if ( is_wp_error( $reg_id ) || ! $reg_id ) {
        wp_send_json_error( [ 'message' => __( 'Fehler beim Speichern der Anmeldung.', 'enroute_offers' ) ] );
    }

    $visitor_lang = sanitize_key( $_POST['lang'] ?? '' );
    $event_lang   = enroute_event_language( $event_id ) ?: 'de';
    $meta = [
        '_reg_event_id'           => $event_id,
        '_reg_event_title'        => $event_title,
        '_reg_event_dates'        => $event_dates,
        '_reg_salutation'         => $salutation,
        '_reg_institution'        => $institution,
        '_reg_first_name'         => $first_name,
        '_reg_last_name'          => $last_name,
        '_reg_street'             => $street,
        '_reg_zip'                => $zip,
        '_reg_place'              => $place,
        '_reg_email'              => $email,
        '_reg_phone'              => $phone,
        '_reg_persons'            => $persons,
        '_reg_persons_under16'    => $under16,
        '_reg_persons_over16'     => $over16,
        '_reg_persons_companions' => $companions,
        '_reg_remarks'            => $remarks,
        '_reg_language'           => $visitor_lang ?: $event_lang,
        '_reg_submitted'          => date_i18n( 'd.m.Y H:i:s' ),
        '_reg_user_id'            => get_current_user_id(),
    ];
    foreach ( $meta as $key => $value ) update_post_meta( $reg_id, $key, $value );

    $placeholders = [
        '{veranstaltung}'     => $event_title,
        '{datum}'             => $event_dates,
        '{veranstaltungsort}' => $event_place,
        '{anrede}'            => $salutation,
        '{vorname}'           => $first_name,
        '{nachname}'          => $last_name,
        '{institution}'       => $institution,
        '{email}'             => $email,
        '{telefon}'           => $phone,
        '{strasse}'           => $street,
        '{plz}'               => $zip,
        '{ort}'               => $place,
        '{personen}'          => '', // set per e-mail language below
        '{personen_unter16}'  => (string) $under16,
        '{personen_ueber16}'  => (string) $over16,
        '{begleitpersonen}'   => (string) $companions,
        '{bemerkungen}'       => $remarks,
        '{admin_url}'         => admin_url( 'post.php?post=' . $reg_id . '&action=edit' ),
    ];
    $headers = [ 'Content-Type: text/plain; charset=UTF-8' ];

    $conf_lang = array_key_exists( $visitor_lang, enroute_event_email_defaults() ) ? $visitor_lang : $event_lang;

    // Admin of the event's language
    $admin_email = enroute_event_email_setting( 'admin_email', $event_lang );
    $placeholders['{personen}'] = enroute_booking_persons_summary( $persons, $under16, $over16, $companions, $event_lang );
    if ( $admin_email && is_email( $admin_email ) ) {
        $reply = $headers;
        $reply[] = 'Reply-To: ' . trim( "$first_name $last_name" ) . ' <' . $email . '>';
        wp_mail(
            $admin_email,
            strtr( enroute_event_email_setting( 'admin_subject', $event_lang ), $placeholders ),
            strtr( enroute_event_email_setting( 'admin_body', $event_lang ), $placeholders ),
            $reply
        );
    }

    // Confirmation to the person registering, in their language
    $placeholders['{personen}'] = enroute_booking_persons_summary( $persons, $under16, $over16, $companions, $conf_lang );
    wp_mail(
        $email,
        strtr( enroute_event_email_setting( 'customer_subject', $conf_lang ), $placeholders ),
        strtr( enroute_event_email_setting( 'customer_body', $conf_lang ), $placeholders ),
        $headers
    );

    wp_send_json_success( [ 'message' => __( 'Vielen Dank für Ihre Anmeldung. Sie erhalten in Kürze eine Bestätigungs-E-Mail.', 'enroute_offers' ) ] );
}
