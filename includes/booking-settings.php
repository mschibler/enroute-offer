<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Offers Settings — admin menu page under Offers, organised in tabs:
 *  - General:     page URLs, default colours
 *  - Contacts:    contact e-mail and phone per language ([enroute_contact] shortcode)
 *  - Emails:      admin address, booking and user pass e-mails (DE / FR / IT)
 *  - Text blocks: texts per language for the theme ([enroute_text] shortcode)
 */

/** Tabs of the settings page. */
function enroute_settings_tabs(): array {
    return [
        'general'  => __( 'General', 'enroute_offers' ),
        'contacts' => __( 'Contacts', 'enroute_offers' ),
        'emails'   => __( 'Emails', 'enroute_offers' ),
        'events'   => __( 'Events', 'enroute_offers' ),
        'texts'    => __( 'Text blocks', 'enroute_offers' ),
    ];
}

/** Languages for per-language settings. */
function enroute_settings_languages(): array {
    return [ 'de' => 'Deutsch', 'fr' => 'Français', 'it' => 'Italiano' ];
}

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
        // Profile page URL
        if ( isset( $_POST['enroute_profile_page_url'] ) ) {
            update_option( 'enroute_profile_page_url', enroute_normalize_url( wp_unslash( $_POST['enroute_profile_page_url'] ) ) );
        }
        // Userpass page URL
        if ( isset( $_POST['enroute_userpass_page_url'] ) ) {
            update_option( 'enroute_userpass_page_url', enroute_normalize_url( wp_unslash( $_POST['enroute_userpass_page_url'] ) ) );
        }
        if ( isset( $_POST['enroute_guides_page_url'] ) ) {
            update_option( 'enroute_guides_page_url', enroute_normalize_url( wp_unslash( $_POST['enroute_guides_page_url'] ) ) );
        }
        if ( isset( $_POST['enroute_events_page_url'] ) ) {
            update_option( 'enroute_events_page_url', enroute_normalize_url( wp_unslash( $_POST['enroute_events_page_url'] ) ) );
        }
        // POI map: optional Mapbox style (empty = standard OpenStreetMap)
        if ( isset( $_POST['enroute_mapbox_token'] ) ) {
            update_option( 'enroute_mapbox_token', sanitize_text_field( wp_unslash( $_POST['enroute_mapbox_token'] ) ) );
        }
        if ( isset( $_POST['enroute_mapbox_style'] ) ) {
            update_option( 'enroute_mapbox_style', sanitize_text_field( wp_unslash( $_POST['enroute_mapbox_style'] ) ) );
        }

        // Text blocks (per language); simple HTML (links, bold) allowed
        foreach ( array_keys( enroute_text_blocks() ) as $block ) {
            foreach ( array_keys( enroute_settings_languages() ) as $lang ) {
                $key = "enroute_text_{$block}_{$lang}";
                if ( isset( $_POST[ $key ] ) ) update_option( $key, wp_kses_post( wp_unslash( $_POST[ $key ] ) ) );
            }
        }

        // Events: per-language admin e-mail and templates
        foreach ( array_keys( enroute_settings_languages() ) as $lang ) {
            if ( isset( $_POST[ "enroute_event_admin_email_$lang" ] ) ) {
                update_option( "enroute_event_admin_email_$lang", sanitize_email( wp_unslash( $_POST[ "enroute_event_admin_email_$lang" ] ) ) );
            }
            foreach ( [ 'admin_subject', 'customer_subject' ] as $k ) {
                if ( isset( $_POST[ "enroute_event_{$k}_$lang" ] ) ) update_option( "enroute_event_{$k}_$lang", sanitize_text_field( wp_unslash( $_POST[ "enroute_event_{$k}_$lang" ] ) ) );
            }
            foreach ( [ 'admin_body', 'customer_body' ] as $k ) {
                if ( isset( $_POST[ "enroute_event_{$k}_$lang" ] ) ) update_option( "enroute_event_{$k}_$lang", sanitize_textarea_field( wp_unslash( $_POST[ "enroute_event_{$k}_$lang" ] ) ) );
            }
        }

        // Reset colours → delete saved values, so the defaults apply
        if ( isset( $_POST['enroute_reset_colors'] ) ) {
            for ( $n = 1; $n <= 5; $n++ ) delete_option( "enroute_featured_color_$n" );
            unset( $_POST['enroute_reset_colors'] );
            for ( $n = 1; $n <= 5; $n++ ) unset( $_POST[ "enroute_featured_color_$n" ] );
        }

        // Save featured colours
        for ( $n = 1; $n <= 5; $n++ ) {
            $key = "enroute_featured_color_$n";
            if ( isset( $_POST[ $key ] ) && preg_match( '/^#[0-9A-Fa-f]{6}$/', $_POST[ $key ] ) ) {
                update_option( $key, sanitize_hex_color( $_POST[ $key ] ) );
            }
        }

        // Contacts (per language)
        foreach ( array_keys( enroute_settings_languages() ) as $lang ) {
            if ( isset( $_POST[ "enroute_contact_email_$lang" ] ) ) {
                update_option( "enroute_contact_email_$lang", sanitize_email( wp_unslash( $_POST[ "enroute_contact_email_$lang" ] ) ) );
            }
            if ( isset( $_POST[ "enroute_contact_phone_$lang" ] ) ) {
                update_option( "enroute_contact_phone_$lang", sanitize_text_field( wp_unslash( $_POST[ "enroute_contact_phone_$lang" ] ) ) );
            }
        }

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
            // Userpass emails
            'enroute_userpass_customer_subject_de',
            'enroute_userpass_customer_body_de',
            'enroute_userpass_customer_subject_fr',
            'enroute_userpass_customer_body_fr',
            'enroute_userpass_customer_subject_it',
            'enroute_userpass_customer_body_it',
            'enroute_userpass_admin_subject',
            'enroute_userpass_admin_body',
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
        // Userpass email defaults
        'enroute_userpass_admin_subject'        => 'Neue User Pass Buchung: {pass}',
        'enroute_userpass_admin_body'           => 'Neue User Pass Buchung eingegangen.' . "

" . 'Pass: {pass}' . "
" . 'Name: {vorname} {nachname}' . "
" . 'E-Mail: {email}' . "
" . 'Telefon: {telefon}' . "
" . 'Institution: {institution}' . "
" . 'Adresse: {strasse}, {plz} {ort}' . "
" . 'Bemerkungen: {bemerkungen}' . "

" . 'Im Backend ansehen: {admin_url}',
        'enroute_userpass_customer_subject_de'  => 'Ihre User Pass Anfrage: {pass}',
        'enroute_userpass_customer_body_de'     => 'Guten Tag {vorname} {nachname},' . "

" . 'Vielen Dank für Ihre Anfrage für den User Pass "{pass}".' . "
" . 'Wir werden Ihre Anfrage prüfen und uns in Kürze bei Ihnen melden.' . "

" . 'Mit freundlichen Grüssen' . "
" . 'Ihr Enroute-Team',
        'enroute_userpass_customer_subject_fr'  => 'Votre demande de User Pass: {pass}',
        'enroute_userpass_customer_body_fr'     => 'Bonjour {vorname} {nachname},' . "

" . 'Merci pour votre demande de User Pass "{pass}".' . "
" . 'Nous examinerons votre demande et vous contacterons prochainement.' . "

" . 'Cordialement' . "
" . "L'équipe Enroute",
        'enroute_userpass_customer_subject_it'  => 'La sua richiesta di User Pass: {pass}',
        'enroute_userpass_customer_body_it'     => 'Buongiorno {vorname} {nachname},' . "

" . 'Grazie per la sua richiesta di User Pass "{pass}".' . "
" . 'Esamineremo la sua richiesta e la contatteremo a breve.' . "

" . 'Cordiali saluti' . "
" . 'Il team Enroute',
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

    $hint = '<small style="color:#666;">' . esc_html__( 'Verfügbare Platzhalter: {angebot}, {vorname}, {nachname}, {email}, {telefon}, {institution}, {strasse}, {plz}, {ort}, {datum1}, {uhrzeit1}, {datum2}, {uhrzeit2}, {personen}, {personen_unter16}, {personen_ueber16}, {begleitpersonen}, {bemerkungen}, {admin_url}', 'enroute_offers' ) . '</small>';
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Angebote – Einstellungen', 'enroute_offers' ); ?></h1>

        <?php
        $tabs       = enroute_settings_tabs();
        $active_tab = sanitize_key( $_GET['tab'] ?? 'general' );
        if ( ! isset( $tabs[ $active_tab ] ) ) $active_tab = 'general';
        ?>
        <nav class="nav-tab-wrapper" style="margin-bottom:1rem;">
            <?php foreach ( $tabs as $slug => $label ) : ?>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=offer&page=enroute-offers-settings&tab=' . $slug ) ); ?>"
                   class="nav-tab <?php echo $slug === $active_tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $label ); ?></a>
            <?php endforeach; ?>
        </nav>

        <form method="post">
            <?php wp_nonce_field( 'enroute_offers_settings_save', 'enroute_offers_settings_nonce' ); ?>

            <?php if ( $active_tab === 'general' ) : // ═══ GENERAL ═══ ?>

            <h2><?php esc_html_e( 'Benutzerprofile', 'enroute_offers' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="enroute_profile_page_url"><?php esc_html_e( 'Profil-Seiten-URL', 'enroute_offers' ); ?></label></th>
                    <td>
                        <input type="text" id="enroute_profile_page_url" name="enroute_profile_page_url"
                               value="<?php echo esc_attr( get_option( 'enroute_profile_page_url', '' ) ); ?>"
                               class="regular-text" placeholder="https://...">
                        <p class="description"><?php esc_html_e( 'URL der Seite mit dem Shortcode [enroute_user_profile]. Benutzer werden nach dem Login hierher weitergeleitet.', 'enroute_offers' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="enroute_userpass_page_url"><?php esc_html_e( 'User Pass Seiten-URL', 'enroute_offers' ); ?></label></th>
                    <td>
                        <input type="text" id="enroute_userpass_page_url" name="enroute_userpass_page_url"
                               value="<?php echo esc_attr( get_option( 'enroute_userpass_page_url', '' ) ); ?>"
                               class="regular-text" placeholder="https://...">
                        <p class="description"><?php esc_html_e( 'URL der Seite mit dem Shortcode [enroute_userpass_booking].', 'enroute_offers' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="enroute_guides_page_url"><?php esc_html_e( 'Guides Seiten-URL', 'enroute_offers' ); ?></label></th>
                    <td>
                        <input type="text" id="enroute_guides_page_url" name="enroute_guides_page_url"
                               value="<?php echo esc_attr( get_option( 'enroute_guides_page_url', '' ) ); ?>"
                               class="regular-text" placeholder="https://...">
                        <p class="description"><?php esc_html_e( 'URL der Seite mit dem Shortcode [enroute_guides_listing]. Wird für den Guide-Block auf der Startseite verwendet.', 'enroute_offers' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="enroute_events_page_url"><?php esc_html_e( 'Veranstaltungen Seiten-URL', 'enroute_offers' ); ?></label></th>
                    <td>
                        <input type="text" id="enroute_events_page_url" name="enroute_events_page_url"
                               value="<?php echo esc_attr( get_option( 'enroute_events_page_url', '' ) ); ?>"
                               class="regular-text" placeholder="https://...">
                        <p class="description"><?php esc_html_e( 'URL der Seite mit dem Shortcode [enroute_events_listing]. Wird für den Link «Weitere Veranstaltungen» im Block auf der Startseite verwendet. Auf Französisch und Italienisch wird automatisch die übersetzte Seite verlinkt.', 'enroute_offers' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="enroute_mapbox_style"><?php esc_html_e( 'Karte: Mapbox-Stil (optional)', 'enroute_offers' ); ?></label></th>
                    <td>
                        <input type="text" id="enroute_mapbox_style" name="enroute_mapbox_style"
                               value="<?php echo esc_attr( get_option( 'enroute_mapbox_style', '' ) ); ?>"
                               class="regular-text" placeholder="mapbox://styles/benutzername/stil-id">
                        <p class="description"><?php esc_html_e( 'Für die Karte [enroute_poi_map]: Style-URL aus Mapbox Studio (Share → Style URL). Leer lassen für die Standard-OpenStreetMap-Karte.', 'enroute_offers' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="enroute_mapbox_token"><?php esc_html_e( 'Karte: Mapbox Access Token', 'enroute_offers' ); ?></label></th>
                    <td>
                        <input type="text" id="enroute_mapbox_token" name="enroute_mapbox_token"
                               value="<?php echo esc_attr( get_option( 'enroute_mapbox_token', '' ) ); ?>"
                               class="regular-text" placeholder="pk.…">
                        <p class="description"><?php esc_html_e( 'Öffentliches Token (beginnt mit pk.). Es ist auf der Website sichtbar – in Mapbox am besten auf die eigene Domain beschränken.', 'enroute_offers' ); ?></p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Default colors', 'enroute_offers' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Colours used for the text band in featured offer blocks. Use with shortcode: [enroute_featured_offer number="1" color="1"]', 'enroute_offers' ); ?></p>
            <table class="form-table">
                <?php
                $default_colors = enroute_get_default_colors();
                for ( $n = 1; $n <= 5; $n++ ) :
                    $val = get_option( "enroute_featured_color_$n", $default_colors[ $n ] );
                ?>
                <tr>
                    <th><label for="enroute_featured_color_<?php echo $n; ?>"><?php printf( esc_html__( 'Color %d', 'enroute_offers' ), $n ); ?></label></th>
                    <td style="display:flex; align-items:center; gap:0.75rem; padding-top:8px;">
                        <?php // Picker and text field stay in sync; only the text field is submitted ?>
                        <input
                            type="color"
                            id="enroute_featured_color_picker_<?php echo $n; ?>"
                            value="<?php echo esc_attr( $val ); ?>"
                            oninput="document.getElementById('enroute_featured_color_<?php echo $n; ?>').value=this.value; document.getElementById('enroute_featured_color_preview_<?php echo $n; ?>').style.background=this.value;"
                            style="width:48px; height:32px; padding:2px; border:1px solid #ccc; cursor:pointer;"
                        >
                        <input
                            type="text"
                            id="enroute_featured_color_<?php echo $n; ?>"
                            name="enroute_featured_color_<?php echo $n; ?>"
                            value="<?php echo esc_attr( $val ); ?>"
                            class="small-text"
                            maxlength="7"
                            pattern="#[0-9A-Fa-f]{6}"
                            placeholder="#000000"
                            oninput="if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) { document.getElementById('enroute_featured_color_picker_<?php echo $n; ?>').value=this.value; document.getElementById('enroute_featured_color_preview_<?php echo $n; ?>').style.background=this.value; }"
                            style="font-family:monospace;"
                        >
                        <span style="display:inline-block; width:32px; height:32px; background:<?php echo esc_attr( $val ); ?>; border:1px solid #ccc; vertical-align:middle;" id="enroute_featured_color_preview_<?php echo $n; ?>"></span>
                    </td>
                </tr>
                <?php endfor; ?>
            </table>
            <p>
                <button type="submit" name="enroute_reset_colors" value="1" class="button"
                        onclick="return confirm('<?php echo esc_js( __( 'Alle Farben auf die Standardfarben zurücksetzen?', 'enroute_offers' ) ); ?>');">
                    <?php esc_html_e( 'Auf Standardfarben zurücksetzen', 'enroute_offers' ); ?>
                </button>
            </p>

            <?php elseif ( $active_tab === 'contacts' ) : // ═══ CONTACTS ═══ ?>

            <h2><?php esc_html_e( 'Kontakt', 'enroute_offers' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Kontaktangaben pro Sprache, z. B. für den Footer. Auf der Website wird automatisch die Angabe der aktuellen Sprache angezeigt; ein leeres Feld wird nicht angezeigt.', 'enroute_offers' ); ?><br>
                <?php esc_html_e( 'Shortcodes:', 'enroute_offers' ); ?>
                <code>[enroute_contact]</code> <?php esc_html_e( '(E-Mail und Telefon)', 'enroute_offers' ); ?>,
                <code>[enroute_contact field="email"]</code>,
                <code>[enroute_contact field="phone"]</code>
            </p>
            <?php foreach ( enroute_settings_languages() as $lang => $lang_label ) : ?>
            <h3><?php echo esc_html( $lang_label ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="enroute_contact_email_<?php echo esc_attr( $lang ); ?>"><?php esc_html_e( 'E-Mail', 'enroute_offers' ); ?></label></th>
                    <td><input type="email" id="enroute_contact_email_<?php echo esc_attr( $lang ); ?>" name="enroute_contact_email_<?php echo esc_attr( $lang ); ?>" value="<?php echo esc_attr( get_option( "enroute_contact_email_$lang", '' ) ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="enroute_contact_phone_<?php echo esc_attr( $lang ); ?>"><?php esc_html_e( 'Telefon', 'enroute_offers' ); ?></label></th>
                    <td><input type="text" id="enroute_contact_phone_<?php echo esc_attr( $lang ); ?>" name="enroute_contact_phone_<?php echo esc_attr( $lang ); ?>" value="<?php echo esc_attr( get_option( "enroute_contact_phone_$lang", '' ) ); ?>" class="regular-text" placeholder="+41 ..."></td>
                </tr>
            </table>
            <?php endforeach; ?>

            <?php elseif ( $active_tab === 'emails' ) : // ═══ EMAILS ═══ ?>

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

            <h2><?php esc_html_e( 'User Pass E-Mails', 'enroute_offers' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Platzhalter: {pass}, {vorname}, {nachname}, {email}, {telefon}, {institution}, {strasse}, {plz}, {ort}, {bemerkungen}, {admin_url}', 'enroute_offers' ); ?></p>

            <h3><?php esc_html_e( 'Admin-Benachrichtigung (immer auf Deutsch)', 'enroute_offers' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Betreff (Admin)', 'enroute_offers' ); ?></label></th>
                    <td><input type="text" name="enroute_userpass_admin_subject"
                        value="<?php echo esc_attr( get_option( 'enroute_userpass_admin_subject', '' ) ); ?>"
                        class="large-text"></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Text (Admin)', 'enroute_offers' ); ?></label></th>
                    <td><textarea name="enroute_userpass_admin_body"
                        rows="6" class="large-text"><?php echo esc_textarea( get_option( 'enroute_userpass_admin_body', '' ) ); ?></textarea></td>
                </tr>
            </table>

            <h3><?php esc_html_e( 'Kunden-Bestätigung', 'enroute_offers' ); ?></h3>
            <?php foreach ( [ 'de' => 'Deutsch', 'fr' => 'Français', 'it' => 'Italiano' ] as $lang => $lang_label ) : ?>
            <h4><?php echo esc_html( $lang_label ); ?></h4>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Betreff (Kunde)', 'enroute_offers' ); ?></label></th>
                    <td><input type="text" name="enroute_userpass_customer_subject_<?php echo $lang; ?>"
                        value="<?php echo esc_attr( get_option( 'enroute_userpass_customer_subject_' . $lang, '' ) ); ?>"
                        class="large-text"></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Text (Kunde)', 'enroute_offers' ); ?></label></th>
                    <td><textarea name="enroute_userpass_customer_body_<?php echo $lang; ?>"
                        rows="6" class="large-text"><?php echo esc_textarea( get_option( 'enroute_userpass_customer_body_' . $lang, '' ) ); ?></textarea></td>
                </tr>
            </table>
            <?php endforeach; ?>

            <?php elseif ( $active_tab === 'events' ) : // ═══ EVENTS ═══ ?>

            <h2><?php esc_html_e( 'Anmeldungen zu Veranstaltungen', 'enroute_offers' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Die Admin-Benachrichtigung geht an die Admin-E-Mail der Sprache der Veranstaltung (leer = E-Mail Angebote-Admin aus dem Tab «Emails»). Die Bestätigung geht an die angemeldete Person, in der Sprache, in der sie sich angemeldet hat.', 'enroute_offers' ); ?><br>
                <?php esc_html_e( 'Platzhalter: {veranstaltung}, {datum}, {veranstaltungsort}, {anrede}, {vorname}, {nachname}, {institution}, {email}, {telefon}, {strasse}, {plz}, {ort}, {personen}, {personen_unter16}, {personen_ueber16}, {begleitpersonen}, {bemerkungen}, {admin_url}', 'enroute_offers' ); ?>
            </p>
            <?php foreach ( enroute_settings_languages() as $lang => $lang_label ) : ?>
            <h3><?php echo esc_html( $lang_label ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="enroute_event_admin_email_<?php echo esc_attr( $lang ); ?>"><?php esc_html_e( 'Admin-E-Mail', 'enroute_offers' ); ?></label></th>
                    <td><input type="email" id="enroute_event_admin_email_<?php echo esc_attr( $lang ); ?>" name="enroute_event_admin_email_<?php echo esc_attr( $lang ); ?>"
                               value="<?php echo esc_attr( get_option( "enroute_event_admin_email_$lang", '' ) ); ?>" class="regular-text"
                               placeholder="<?php echo esc_attr( get_option( 'enroute_booking_admin_email', get_option( 'admin_email' ) ) ); ?>"></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Betreff (Admin)', 'enroute_offers' ); ?></label></th>
                    <td><input type="text" name="enroute_event_admin_subject_<?php echo esc_attr( $lang ); ?>" value="<?php echo esc_attr( enroute_event_email_setting( 'admin_subject', $lang ) ); ?>" class="large-text"></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Text (Admin)', 'enroute_offers' ); ?></label></th>
                    <td><textarea name="enroute_event_admin_body_<?php echo esc_attr( $lang ); ?>" rows="8" class="large-text"><?php echo esc_textarea( enroute_event_email_setting( 'admin_body', $lang ) ); ?></textarea></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Betreff (Bestätigung)', 'enroute_offers' ); ?></label></th>
                    <td><input type="text" name="enroute_event_customer_subject_<?php echo esc_attr( $lang ); ?>" value="<?php echo esc_attr( enroute_event_email_setting( 'customer_subject', $lang ) ); ?>" class="large-text"></td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Text (Bestätigung)', 'enroute_offers' ); ?></label></th>
                    <td><textarea name="enroute_event_customer_body_<?php echo esc_attr( $lang ); ?>" rows="8" class="large-text"><?php echo esc_textarea( enroute_event_email_setting( 'customer_body', $lang ) ); ?></textarea></td>
                </tr>
            </table>
            <?php endforeach; ?>

            <?php elseif ( $active_tab === 'texts' ) : // ═══ TEXT BLOCKS ═══ ?>

            <p class="description">
                <?php esc_html_e( 'Texte pro Sprache für das Theme. Auf der Website wird automatisch der Text der aktuellen Sprache angezeigt (ist ein Feld leer, der deutsche). Einfaches HTML (z. B. Links) ist erlaubt; {jahr} wird durch das aktuelle Jahr ersetzt.', 'enroute_offers' ); ?>
            </p>
            <?php foreach ( enroute_text_blocks() as $block => $block_label ) : ?>
            <h2><?php echo esc_html( $block_label ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Shortcode:', 'enroute_offers' ); ?> <code>[enroute_text block="<?php echo esc_html( $block ); ?>"]</code>
                &nbsp;·&nbsp; <?php esc_html_e( 'Im Theme (PHP):', 'enroute_offers' ); ?> <code>&lt;?php echo enroute_text( '<?php echo esc_html( $block ); ?>' ); ?&gt;</code>
            </p>
            <table class="form-table">
                <?php foreach ( enroute_settings_languages() as $lang => $lang_label ) : $key = "enroute_text_{$block}_{$lang}"; ?>
                <tr>
                    <th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $lang_label ); ?></label></th>
                    <td><?php if ( in_array( $block, enroute_text_blocks_multiline(), true ) ) : ?>
                        <textarea id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" rows="4" class="large-text"><?php echo esc_textarea( enroute_text_block_value( $block, $lang ) ); ?></textarea>
                    <?php else : ?>
                        <input type="text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( enroute_text_block_value( $block, $lang ) ); ?>" class="large-text"
                               placeholder="<?php echo esc_attr( $block === 'footer_copyright' ? '© {jahr} Enroute' : '' ); ?>">
                    <?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endforeach; ?>

            <?php endif; ?>

            <?php submit_button( __( 'Einstellungen speichern', 'enroute_offers' ) ); ?>
        </form>
    </div>
    <?php
}
