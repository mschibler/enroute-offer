<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Offers Settings — admin menu page under Offers.
 * Manages:
 *  - Admin notification email address
 *  - Customer confirmation email (DE / FR / IT)
 *  - Offer-contact confirmation email
 */

add_action( 'admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=offer',          // parent slug
        __( 'Einstellungen', 'enroute_offers' ),
        __( 'Einstellungen', 'enroute_offers' ),
        'manage_options',
        'enroute-offers-settings',
        'enroute_offers_settings_page'
    );
});

function enroute_offers_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    // Save
    if ( isset( $_POST['enroute_offers_settings_nonce'] )
        && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['enroute_offers_settings_nonce'] ) ), 'enroute_offers_settings_save' )
    ) {
        $fields = [
            'enroute_booking_admin_email',
            'enroute_booking_customer_subject_de',
            'enroute_booking_customer_body_de',
            'enroute_booking_customer_subject_fr',
            'enroute_booking_customer_body_fr',
            'enroute_booking_customer_subject_it',
            'enroute_booking_customer_body_it',
            'enroute_booking_contact_subject_de',
            'enroute_booking_contact_body_de',
            'enroute_booking_contact_subject_fr',
            'enroute_booking_contact_body_fr',
            'enroute_booking_contact_subject_it',
            'enroute_booking_contact_body_it',
            'enroute_booking_admin_subject_de',
            'enroute_booking_admin_body_de',
            'enroute_booking_admin_subject_fr',
            'enroute_booking_admin_body_fr',
            'enroute_booking_admin_subject_it',
            'enroute_booking_admin_body_it',
        ];
        foreach ( $fields as $f ) {
            if ( isset( $_POST[ $f ] ) ) {
                update_option( $f, wp_unslash( $_POST[ $f ] ) );
            }
        }
        echo '<div class="notice notice-success"><p>' . esc_html__( 'Einstellungen gespeichert.', 'enroute_offers' ) . '</p></div>';
    }

    // Default email bodies
    $defaults = [
        'enroute_booking_admin_email'           => get_option( 'admin_email' ),
        'enroute_booking_customer_subject_de'   => __( 'Bestätigung Ihrer Buchungsanfrage', 'enroute_offers' ),
        'enroute_booking_customer_body_de'      => "Guten Tag {vorname} {nachname},\n\nVielen Dank für Ihre Buchungsanfrage für das Angebot \"{angebot}\".\nWir werden uns in Kürze bei Ihnen melden.\n\nMit freundlichen Grüssen\nIhr Enroute-Team",
        'enroute_booking_customer_subject_fr'   => __( 'Confirmation de votre demande de réservation', 'enroute_offers' ),
        'enroute_booking_customer_body_fr'      => "Bonjour {vorname} {nachname},\n\nMerci pour votre demande de réservation pour l'offre \"{angebot}\".\nNous vous contacterons prochainement.\n\nCordialement\nL'équipe Enroute",
        'enroute_booking_customer_subject_it'   => __( 'Conferma della tua richiesta di prenotazione', 'enroute_offers' ),
        'enroute_booking_customer_body_it'      => "Buongiorno {vorname} {nachname},\n\nGrazie per la sua richiesta di prenotazione per l'offerta \"{angebot}\".\nLa contatteremo a breve.\n\nCordiali saluti\nIl team Enroute",
        'enroute_booking_contact_subject_de'    => 'Neue Buchungsanfrage: {angebot}',
        'enroute_booking_contact_body_de'       => "Guten Tag,\n\nSie haben eine neue Buchungsanfrage erhalten.\n\nAngebot: {angebot}\nName: {vorname} {nachname}\nE-Mail: {email}\nTelefon: {telefon}\nInstitution: {institution}\nWunschdatum: {datum1} {uhrzeit1}\nErsatzdatum: {datum2} {uhrzeit2}\nAnzahl Personen: {personen}\nBemerkungen: {bemerkungen}",
        'enroute_booking_contact_subject_fr'    => 'Nouvelle demande de réservation: {angebot}',
        'enroute_booking_contact_body_fr'       => "Bonjour,\n\nVous avez reçu une nouvelle demande de réservation.\n\nOffre: {angebot}\nNom: {vorname} {nachname}\nE-mail: {email}\nTéléphone: {telefon}\nInstitution: {institution}\nDate souhaitée: {datum1} {uhrzeit1}\nDate de remplacement: {datum2} {uhrzeit2}\nNombre de personnes: {personen}\nRemarques: {bemerkungen}",
        'enroute_booking_contact_subject_it'    => 'Nuova richiesta di prenotazione: {angebot}',
        'enroute_booking_contact_body_it'       => "Buongiorno,\n\nHa ricevuto una nuova richiesta di prenotazione.\n\nOfferta: {angebot}\nNome: {vorname} {nachname}\nE-mail: {email}\nTelefono: {telefon}\nIstituzione: {institution}\nData desiderata: {datum1} {uhrzeit1}\nData alternativa: {datum2} {uhrzeit2}\nNumero di persone: {personen}\nOsservazioni: {bemerkungen}",
        'enroute_booking_admin_subject_de'      => 'Neue Buchungsanfrage: {angebot}',
        'enroute_booking_admin_body_de'         => "Neue Buchungsanfrage eingegangen.\n\nAngebot: {angebot}\nName: {vorname} {nachname}\nE-Mail: {email}\nTelefon: {telefon}\nInstitution: {institution}\nAdresse: {strasse}, {plz} {ort}\nWunschdatum: {datum1} {uhrzeit1}\nErsatzdatum: {datum2} {uhrzeit2}\nAnzahl Personen: {personen}\nBemerkungen: {bemerkungen}\n\nIm Backend ansehen: {admin_url}",
        'enroute_booking_admin_subject_fr'      => 'Nouvelle demande de réservation: {angebot}',
        'enroute_booking_admin_body_fr'         => "Nouvelle demande de réservation reçue.\n\nOffre: {angebot}\nNom: {vorname} {nachname}\nE-mail: {email}\nTéléphone: {telefon}\nInstitution: {institution}\nAdresse: {strasse}, {plz} {ort}\nDate souhaitée: {datum1} {uhrzeit1}\nDate de remplacement: {datum2} {uhrzeit2}\nNombre de personnes: {personen}\nRemarques: {bemerkungen}\n\nVoir dans le backend: {admin_url}",
        'enroute_booking_admin_subject_it'      => 'Nuova richiesta di prenotazione: {angebot}',
        'enroute_booking_admin_body_it'         => "Nuova richiesta di prenotazione ricevuta.\n\nOfferta: {angebot}\nNome: {vorname} {nachname}\nE-mail: {email}\nTelefono: {telefon}\nIstituzione: {institution}\nIndirizzo: {strasse}, {plz} {ort}\nData desiderata: {datum1} {uhrzeit1}\nData alternativa: {datum2} {uhrzeit2}\nNumero di persone: {personen}\nOsservazioni: {bemerkungen}\n\nVedi nel backend: {admin_url}",
    ];

    $vals = [];
    foreach ( $defaults as $key => $def ) {
        $vals[ $key ] = get_option( $key, $def );
    }

    $hint = '<small style="color:#666;">' . esc_html__( 'Verfügbare Platzhalter: {angebot}, {vorname}, {nachname}, {email}, {telefon}, {institution}, {strasse}, {plz}, {ort}, {datum1}, {uhrzeit1}, {datum2}, {uhrzeit2}, {personen}, {bemerkungen}, {admin_url}', 'enroute_offers' ) . '</small>';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Angebote – Einstellungen', 'enroute_offers' ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'enroute_offers_settings_save', 'enroute_offers_settings_nonce' ); ?>

            <h2><?php esc_html_e( 'Admin-E-Mail-Adresse', 'enroute_offers' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="enroute_booking_admin_email"><?php esc_html_e( 'E-Mail Angebote-Admin', 'enroute_offers' ); ?></label></th>
                    <td><input type="email" id="enroute_booking_admin_email" name="enroute_booking_admin_email" value="<?php echo esc_attr( $vals['enroute_booking_admin_email'] ); ?>" class="regular-text"></td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'E-Mail an Kunden (Bestätigung)', 'enroute_offers' ); ?></h2>
            <p><?php echo $hint; ?></p>
            <?php foreach ( [ 'de' => 'Deutsch', 'fr' => 'Français', 'it' => 'Italiano' ] as $lang => $lang_label ) : ?>
            <h3><?php echo esc_html( $lang_label ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Betreff', 'enroute_offers' ); ?></label></th>
                    <td><input type="text" name="enroute_booking_customer_subject_<?php echo $lang; ?>" value="<?php echo esc_attr( $vals[ "enroute_booking_customer_subject_$lang" ] ); ?>" class="large-text"></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Text', 'enroute_offers' ); ?></label></th>
                    <td><textarea name="enroute_booking_customer_body_<?php echo $lang; ?>" rows="7" class="large-text"><?php echo esc_textarea( $vals[ "enroute_booking_customer_body_$lang" ] ); ?></textarea></td>
                </tr>
            </table>
            <?php endforeach; ?>

            <h2><?php esc_html_e( 'E-Mail an Angebots-Kontakt', 'enroute_offers' ); ?></h2>
            <p><?php echo $hint; ?></p>
            <?php foreach ( [ 'de' => 'Deutsch', 'fr' => 'Français', 'it' => 'Italiano' ] as $lang => $lang_label ) : ?>
            <h3><?php echo esc_html( $lang_label ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Betreff', 'enroute_offers' ); ?></label></th>
                    <td><input type="text" name="enroute_booking_contact_subject_<?php echo $lang; ?>" value="<?php echo esc_attr( $vals[ "enroute_booking_contact_subject_$lang" ] ); ?>" class="large-text"></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Text', 'enroute_offers' ); ?></label></th>
                    <td><textarea name="enroute_booking_contact_body_<?php echo $lang; ?>" rows="7" class="large-text"><?php echo esc_textarea( $vals[ "enroute_booking_contact_body_$lang" ] ); ?></textarea></td>
                </tr>
            </table>
            <?php endforeach; ?>

            <h2><?php esc_html_e( 'E-Mail an Angebote-Admin', 'enroute_offers' ); ?></h2>
            <p><?php echo $hint; ?></p>
            <?php foreach ( [ 'de' => 'Deutsch', 'fr' => 'Français', 'it' => 'Italiano' ] as $lang => $lang_label ) : ?>
            <h3><?php echo esc_html( $lang_label ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Betreff', 'enroute_offers' ); ?></label></th>
                    <td><input type="text" name="enroute_booking_admin_subject_<?php echo $lang; ?>" value="<?php echo esc_attr( $vals[ "enroute_booking_admin_subject_$lang" ] ); ?>" class="large-text"></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Text', 'enroute_offers' ); ?></label></th>
                    <td><textarea name="enroute_booking_admin_body_<?php echo $lang; ?>" rows="7" class="large-text"><?php echo esc_textarea( $vals[ "enroute_booking_admin_body_$lang" ] ); ?></textarea></td>
                </tr>
            </table>
            <?php endforeach; ?>

            <?php submit_button( __( 'Einstellungen speichern', 'enroute_offers' ) ); ?>
        </form>
    </div>
    <?php
}
