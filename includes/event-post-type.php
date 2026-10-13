<?php
/**
 * Events: post type, backend fields, helpers.
 *
 * Event language is a field of the event (like offers); each language version
 * of the site only lists events of its own language. Event URLs carry the
 * event's language (/fr/events/…), see offer-language.php.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ══════════════════════════════════════════════════════════════════════════════
// POST TYPES
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'init', function() {
    register_post_type( 'event', [
        'labels' => [
            'name'               => __( 'Veranstaltungen',                     'enroute_offers' ),
            'singular_name'      => __( 'Veranstaltung',                       'enroute_offers' ),
            'add_new'            => __( 'Neu',                                 'enroute_offers' ),
            'add_new_item'       => __( 'Neue Veranstaltung',                  'enroute_offers' ),
            'edit_item'          => __( 'Veranstaltung bearbeiten',            'enroute_offers' ),
            'new_item'           => __( 'Neue Veranstaltung',                  'enroute_offers' ),
            'view_item'          => __( 'Veranstaltung ansehen',               'enroute_offers' ),
            'search_items'       => __( 'Veranstaltungen suchen',              'enroute_offers' ),
            'not_found'          => __( 'Keine Veranstaltungen gefunden',      'enroute_offers' ),
            'not_found_in_trash' => __( 'Keine Veranstaltungen im Papierkorb', 'enroute_offers' ),
            'menu_name'          => __( 'Veranstaltungen',                     'enroute_offers' ),
            'all_items'          => __( 'Alle Veranstaltungen',                'enroute_offers' ),
            'featured_image'     => __( 'Bild',                                'enroute_offers' ),
            'set_featured_image' => __( 'Bild festlegen',                      'enroute_offers' ),
            'remove_featured_image' => __( 'Bild entfernen',                   'enroute_offers' ),
            'use_featured_image' => __( 'Als Bild verwenden',                  'enroute_offers' ),
        ],
        'public'          => true,
        'show_in_menu'    => true,
        'menu_icon'       => 'dashicons-calendar-alt',
        'supports'        => [ 'title', 'editor', 'thumbnail' ], // editor = Description (classic editor)
        'show_ui'         => true,
        'show_in_rest'    => false,
        'has_archive'     => false,
        'rewrite'         => [ 'slug' => 'events' ],
        'capability_type' => 'post',
    ] );

    // Registrations — created by the registration form, listed under Events
    register_post_type( 'enroute_event_reg', [
        'labels' => [
            'name'               => __( 'Anmeldungen',              'enroute_offers' ),
            'singular_name'      => __( 'Anmeldung',                'enroute_offers' ),
            'edit_item'          => __( 'Anmeldung bearbeiten',     'enroute_offers' ),
            'view_item'          => __( 'Anmeldung ansehen',        'enroute_offers' ),
            'search_items'       => __( 'Anmeldungen suchen',       'enroute_offers' ),
            'not_found'          => __( 'Keine Anmeldungen',        'enroute_offers' ),
            'not_found_in_trash' => __( 'Keine Anmeldungen im Papierkorb', 'enroute_offers' ),
            'menu_name'          => __( 'Anmeldungen',              'enroute_offers' ),
            'all_items'          => __( 'Anmeldungen',              'enroute_offers' ),
        ],
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => 'edit.php?post_type=event',
        'supports'        => [ 'title' ],
        'capability_type' => 'post',
        'capabilities'    => [ 'create_posts' => 'do_not_allow' ], // only via the form
        'map_meta_cap'    => true,
        'show_in_rest'    => false,
    ] );

}, 5 ); // early: before the URL rules for /fr/events/… are built (offer-language.php, priority 20)

// Share target groups and tags with offers (after the taxonomies are registered)
add_action( 'init', function() {
    register_taxonomy_for_object_type( 'offer_target_group', 'event' );
    register_taxonomy_for_object_type( 'offer_tag', 'event' );
}, 15 );

// Polylang must not manage events (language is the event's own field)
add_filter( 'pll_get_post_types', function( array $post_types ): array {
    unset( $post_types['event'], $post_types['enroute_event_reg'] );
    return $post_types;
}, 20 );

// Admin styles + media on event screens
add_action( 'admin_enqueue_scripts', function() {
    $screen = get_current_screen();
    if ( ! $screen || ! in_array( $screen->post_type, [ 'event', 'enroute_event_reg' ], true ) ) return;
    wp_enqueue_style( 'enroute-admin', ENROUTE_OFFERS_URL . 'admin/admin.css', [], ENROUTE_OFFERS_VERSION );
} );

// ══════════════════════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════════════════════

/** Place options. */
function enroute_get_event_place_types(): array {
    return [
        'station' => __( 'Station',        'enroute_offers' ),
        'other'   => __( 'Andere Adresse', 'enroute_offers' ),
    ];
}

/** Event is shown in listings (checkbox "Active"; never set = active). */
function enroute_event_is_active( int $event_id ): bool {
    return get_post_meta( $event_id, '_event_active', true ) !== '0';
}

/** Event language code (de/fr/it/en), '' if none. */
function enroute_event_language( int $event_id ): string {
    return (string) get_post_meta( $event_id, '_event_language', true );
}

/** Start / end as 'Y-m-d H:i' ('' if not set). */
function enroute_event_start( int $event_id ): string { return (string) get_post_meta( $event_id, '_event_start', true ); }
function enroute_event_end( int $event_id ): string   { return (string) get_post_meta( $event_id, '_event_end', true ); }

/** Event is over: end (or start, if no end) lies in the past. Events without date are never over. */
function enroute_event_is_past( int $event_id ): bool {
    $until = enroute_event_end( $event_id ) ?: enroute_event_start( $event_id );
    if ( ! $until ) return false;
    // Start-only events and dates without time count until the end of their day
    if ( ! enroute_event_end( $event_id ) || substr( $until, 11, 5 ) === '00:00' ) $until = substr( $until, 0, 10 ) . ' 23:59';
    return $until < current_time( 'Y-m-d H:i' );
}

/**
 * Human-readable date(s), e.g.
 *   24.10.2026, 14:00–17:00
 *   24.10.2026, 14:00 – 26.10.2026, 12:00
 */
function enroute_event_date_text( int $event_id ): string {
    $start = enroute_event_start( $event_id );
    $end   = enroute_event_end( $event_id );
    if ( ! $start ) return '';

    $fmt = function( string $dt, bool $with_date = true ): string {
        $ts   = strtotime( $dt );
        $time = date( 'H:i', $ts );
        $out  = $with_date ? date( 'd.m.Y', $ts ) : '';
        if ( $time !== '00:00' ) $out .= ( $out ? ', ' : '' ) . $time;
        return $out;
    };

    if ( ! $end || $end === $start ) return $fmt( $start );
    if ( substr( $start, 0, 10 ) === substr( $end, 0, 10 ) ) {
        if ( substr( $end, 11, 5 ) === '00:00' ) return $fmt( $start ); // same day, no end time
        return $fmt( $start ) . '–' . $fmt( $end, false );
    }
    return $fmt( $start ) . ' – ' . $fmt( $end );
}

/** Lines for the event place: station (name + address) or other address. */
function enroute_event_place_lines( int $event_id ): array {
    $type = get_post_meta( $event_id, '_event_place_type', true ) ?: 'station';

    if ( $type === 'other' ) {
        $name   = get_post_meta( $event_id, '_event_place_name',   true );
        $street = get_post_meta( $event_id, '_event_place_street', true );
        $city   = trim( get_post_meta( $event_id, '_event_place_zip', true ) . ' ' . get_post_meta( $event_id, '_event_place_place', true ) );
    } else {
        $station_id = (int) get_post_meta( $event_id, '_event_station', true );
        if ( ! $station_id ) return [];
        $name   = get_the_title( $station_id );
        $street = get_post_meta( $station_id, '_station_address', true );
        $city   = trim( get_post_meta( $station_id, '_station_plz', true ) . ' ' . get_post_meta( $station_id, '_station_place', true ) );
    }
    return array_values( array_filter( [ $name, $street, $city ], fn( $l ) => trim( (string) $l ) !== '' ) );
}

/** Short place text for cards: name, else place. */
function enroute_event_place_short( int $event_id ): string {
    $type = get_post_meta( $event_id, '_event_place_type', true ) ?: 'station';
    if ( $type === 'other' ) {
        return (string) ( get_post_meta( $event_id, '_event_place_name', true ) ?: get_post_meta( $event_id, '_event_place_place', true ) );
    }
    $station_id = (int) get_post_meta( $event_id, '_event_station', true );
    return $station_id ? get_the_title( $station_id ) : '';
}

/** Image URL: featured image, else the station's photo. */
function enroute_event_image_url( int $event_id, string $size = 'large' ): string {
    $url = get_the_post_thumbnail_url( $event_id, $size ) ?: '';
    if ( ! $url && ( get_post_meta( $event_id, '_event_place_type', true ) ?: 'station' ) === 'station' ) {
        $station_id = (int) get_post_meta( $event_id, '_event_station', true );
        if ( $station_id ) {
            foreach ( [ '_station_activities_photo_id', '_station_photo_id' ] as $key ) {
                $photo = (int) get_post_meta( $station_id, $key, true );
                if ( $photo && ( $url = wp_get_attachment_image_url( $photo, $size ) ?: '' ) ) break;
            }
        }
    }
    return $url;
}

/**
 * Upcoming, active events of a language, sorted by start date (soonest first).
 * $lang '' = current language.
 */
function enroute_get_upcoming_events( string $lang = '', int $limit = -1 ): array {
    if ( ! $lang ) {
        $lang = function_exists( 'pll_current_language' ) ? (string) pll_current_language() : '';
        $lang = $lang ?: substr( get_locale(), 0, 2 );
    }
    $events = get_posts( [
        'post_type'   => 'event',
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_query'  => [
            'relation' => 'AND',
            [ 'key' => '_event_language', 'value' => $lang ],
            [
                'relation' => 'OR',
                [ 'key' => '_event_active', 'compare' => 'NOT EXISTS' ],
                [ 'key' => '_event_active', 'value' => '0', 'compare' => '!=' ],
            ],
        ],
    ] );

    $events = array_values( array_filter( $events, fn( $e ) => ! enroute_event_is_past( $e->ID ) ) );
    usort( $events, function( $a, $b ) {
        // Events without date last
        $sa = enroute_event_start( $a->ID ) ?: '9999';
        $sb = enroute_event_start( $b->ID ) ?: '9999';
        return strcmp( $sa, $sb ) ?: strcmp( $a->post_title, $b->post_title );
    } );
    return $limit > 0 ? array_slice( $events, 0, $limit ) : $events;
}

// ══════════════════════════════════════════════════════════════════════════════
// BACKEND FIELDS
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'add_meta_boxes', function() {
    add_meta_box( 'event_general', __( 'Allgemein', 'enroute_offers' ),        'enroute_event_general_cb', 'event', 'normal', 'high' );
    add_meta_box( 'event_date',    __( 'Datum', 'enroute_offers' ),            'enroute_event_date_cb',    'event', 'normal', 'high' );
    add_meta_box( 'event_place',   __( 'Veranstaltungsort', 'enroute_offers' ), 'enroute_event_place_cb',   'event', 'normal', 'default' );
} );

function enroute_event_general_cb( WP_Post $post ): void {
    wp_nonce_field( 'enroute_event_save', 'enroute_event_nonce' );
    $short    = get_post_meta( $post->ID, '_event_short_description', true );
    $price    = get_post_meta( $post->ID, '_event_price',             true );
    $email    = get_post_meta( $post->ID, '_event_contact_email',     true );
    $website  = get_post_meta( $post->ID, '_event_website',           true );
    $language = get_post_meta( $post->ID, '_event_language',          true );
    $active   = get_post_meta( $post->ID, '_event_active',            true ) !== '0';
    if ( $post->post_status === 'auto-draft' && ! $language && function_exists( 'pll_current_language' ) ) {
        $language = (string) pll_current_language(); // new event: admin's current Polylang language
    }
    ?>
    <div class="enroute-meta-wrap">
        <p class="description"><?php esc_html_e( 'Die Beschreibung wird im grossen Textfeld oben erfasst.', 'enroute_offers' ); ?></p>

        <div class="enroute-field">
            <label for="event_short_description"><?php esc_html_e( 'Kurzbeschreibung', 'enroute_offers' ); ?></label>
            <textarea id="event_short_description" name="event_short_description" rows="3" class="widefat"><?php echo esc_textarea( $short ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Wird in der Liste und im Block auf der Startseite angezeigt.', 'enroute_offers' ); ?></p>
        </div>

        <div class="enroute-field">
            <label for="event_price"><?php esc_html_e( 'Preis', 'enroute_offers' ); ?></label>
            <input type="text" id="event_price" name="event_price" value="<?php echo esc_attr( $price ); ?>">
            <p class="description"><?php esc_html_e( 'Freier Text, z. B. «CHF 20.–» oder «kostenlos».', 'enroute_offers' ); ?></p>
        </div>

        <div class="enroute-field">
            <label for="event_contact_email"><?php esc_html_e( 'Kontakt-E-Mail', 'enroute_offers' ); ?></label>
            <input type="email" id="event_contact_email" name="event_contact_email" value="<?php echo esc_attr( $email ); ?>">
        </div>

        <div class="enroute-field">
            <label for="event_website"><?php esc_html_e( 'Website der Veranstaltung', 'enroute_offers' ); ?></label>
            <input type="text" id="event_website" name="event_website" value="<?php echo esc_attr( $website ); ?>" placeholder="www.example.ch">
        </div>

        <div class="enroute-field">
            <label for="event_language"><?php esc_html_e( 'Sprache', 'enroute_offers' ); ?></label>
            <select id="event_language" name="event_language">
                <option value=""><?php esc_html_e( '— Sprache wählen —', 'enroute_offers' ); ?></option>
                <?php foreach ( enroute_get_languages() as $code => $label ) : ?>
                    <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $language, $code ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e( 'Die Veranstaltung erscheint nur auf der Website in dieser Sprache. Anmeldungen gehen an die Admin-E-Mail dieser Sprache.', 'enroute_offers' ); ?></p>
        </div>

        <div class="enroute-field">
            <label class="enroute-checkbox-label">
                <input type="checkbox" name="event_active" value="1" <?php checked( $active ); ?>>
                <?php esc_html_e( 'Aktiv', 'enroute_offers' ); ?>
            </label>
            <p class="description"><?php esc_html_e( 'Deaktivieren, um die Veranstaltung in Listen und auf der Startseite auszublenden.', 'enroute_offers' ); ?></p>
        </div>
    </div>
    <?php
}

function enroute_event_date_cb( WP_Post $post ): void {
    // Stored as 'Y-m-d H:i'; shown as separate date and time fields (time optional)
    $split = function( string $v ): array {
        if ( ! $v ) return [ '', '' ];
        $time = substr( $v, 11, 5 );
        return [ substr( $v, 0, 10 ), $time === '00:00' ? '' : $time ];
    };
    [ $start_date, $start_time ] = $split( enroute_event_start( $post->ID ) );
    [ $end_date, $end_time ]     = $split( enroute_event_end( $post->ID ) );
    ?>
    <div class="enroute-meta-wrap">
        <div class="enroute-field">
            <label for="event_start_date"><?php esc_html_e( 'Beginn (Datum / Zeit)', 'enroute_offers' ); ?></label>
            <input type="date" id="event_start_date" name="event_start_date" value="<?php echo esc_attr( $start_date ); ?>">
            <input type="time" id="event_start_time" name="event_start_time" value="<?php echo esc_attr( $start_time ); ?>" aria-label="<?php esc_attr_e( 'Zeit', 'enroute_offers' ); ?>">
        </div>
        <div class="enroute-field">
            <label for="event_end_date"><?php esc_html_e( 'Ende (Datum / Zeit)', 'enroute_offers' ); ?></label>
            <input type="date" id="event_end_date" name="event_end_date" value="<?php echo esc_attr( $end_date ); ?>">
            <input type="time" id="event_end_time" name="event_end_time" value="<?php echo esc_attr( $end_time ); ?>" aria-label="<?php esc_attr_e( 'Zeit', 'enroute_offers' ); ?>">
            <p class="description"><?php esc_html_e( 'Zeiten sind optional. Ist beim Ende nur eine Zeit angegeben, gilt das Datum des Beginns.', 'enroute_offers' ); ?></p>
            <p class="description"><?php esc_html_e( 'Nach dem Ende wird die Veranstaltung automatisch ausgeblendet und die Anmeldung geschlossen.', 'enroute_offers' ); ?></p>
        </div>
    </div>
    <?php
}

function enroute_event_place_cb( WP_Post $post ): void {
    $type    = get_post_meta( $post->ID, '_event_place_type', true ) ?: 'station';
    $station = (int) get_post_meta( $post->ID, '_event_station', true );
    $name    = get_post_meta( $post->ID, '_event_place_name',   true );
    $street  = get_post_meta( $post->ID, '_event_place_street', true );
    $zip     = get_post_meta( $post->ID, '_event_place_zip',    true );
    $place   = get_post_meta( $post->ID, '_event_place_place',  true );
    $stations = get_posts( [ 'post_type' => 'station', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish' ] );
    ?>
    <div class="enroute-meta-wrap">
        <div class="enroute-field">
            <label for="event_place_type"><?php esc_html_e( 'Veranstaltungsort', 'enroute_offers' ); ?></label>
            <select id="event_place_type" name="event_place_type">
                <?php foreach ( enroute_get_event_place_types() as $val => $label ) : ?>
                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $type, $val ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="event_place_station">
            <div class="enroute-field">
                <label for="event_station"><?php esc_html_e( 'Station', 'enroute_offers' ); ?></label>
                <select id="event_station" name="event_station">
                    <option value=""><?php esc_html_e( '— Station wählen —', 'enroute_offers' ); ?></option>
                    <?php foreach ( $stations as $s ) : ?>
                        <option value="<?php echo esc_attr( $s->ID ); ?>" <?php selected( $station, $s->ID ); ?>><?php echo esc_html( $s->post_title ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div id="event_place_other">
            <div class="enroute-field">
                <label for="event_place_name"><?php esc_html_e( 'Name des Ortes', 'enroute_offers' ); ?></label>
                <input type="text" id="event_place_name" name="event_place_name" value="<?php echo esc_attr( $name ); ?>">
            </div>
            <div class="enroute-field">
                <label for="event_place_street"><?php esc_html_e( 'Strasse / Nr.', 'enroute_offers' ); ?></label>
                <input type="text" id="event_place_street" name="event_place_street" value="<?php echo esc_attr( $street ); ?>">
            </div>
            <div class="enroute-field">
                <label for="event_place_zip"><?php esc_html_e( 'PLZ', 'enroute_offers' ); ?></label>
                <input type="text" id="event_place_zip" name="event_place_zip" value="<?php echo esc_attr( $zip ); ?>" style="max-width:8rem;">
            </div>
            <div class="enroute-field">
                <label for="event_place_place"><?php esc_html_e( 'Ort', 'enroute_offers' ); ?></label>
                <input type="text" id="event_place_place" name="event_place_place" value="<?php echo esc_attr( $place ); ?>">
            </div>
        </div>
    </div>
    <script>
    (function () {
        var select = document.getElementById('event_place_type');
        function toggle() {
            document.getElementById('event_place_station').style.display = select.value === 'station' ? '' : 'none';
            document.getElementById('event_place_other').style.display   = select.value === 'other'   ? '' : 'none';
        }
        select.addEventListener('change', toggle);
        toggle();
    })();
    </script>
    <?php
}

add_action( 'save_post_event', function( int $post_id ): void {
    if ( ! isset( $_POST['enroute_event_nonce'] ) || ! wp_verify_nonce( $_POST['enroute_event_nonce'], 'enroute_event_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    update_post_meta( $post_id, '_event_short_description', sanitize_textarea_field( wp_unslash( $_POST['event_short_description'] ?? '' ) ) );
    update_post_meta( $post_id, '_event_price',             sanitize_text_field( wp_unslash( $_POST['event_price'] ?? '' ) ) );
    update_post_meta( $post_id, '_event_contact_email',     sanitize_email( wp_unslash( $_POST['event_contact_email'] ?? '' ) ) );
    update_post_meta( $post_id, '_event_website',           enroute_normalize_url( wp_unslash( $_POST['event_website'] ?? '' ) ) );

    $lang = sanitize_key( $_POST['event_language'] ?? '' );
    update_post_meta( $post_id, '_event_language', array_key_exists( $lang, enroute_get_languages() ) ? $lang : '' );
    update_post_meta( $post_id, '_event_active', isset( $_POST['event_active'] ) ? '1' : '0' );

    // Dates: separate date + time fields → 'Y-m-d H:i' (no time = 00:00)
    $date = fn( string $k ) => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v = sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) ) ) ? $v : '';
    $time = fn( string $k ) => preg_match( '/^(\d{2}:\d{2})/', sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) ), $m ) ? $m[1] : '';
    $start_date = $date( 'event_start_date' );
    $start_time = $time( 'event_start_time' );
    $end_date   = $date( 'event_end_date' );
    $end_time   = $time( 'event_end_time' );
    if ( ! $end_date && $end_time ) $end_date = $start_date; // only an end time → same day as the start

    update_post_meta( $post_id, '_event_start', $start_date ? $start_date . ' ' . ( $start_time ?: '00:00' ) : '' );
    update_post_meta( $post_id, '_event_end',   $end_date   ? $end_date   . ' ' . ( $end_time   ?: '00:00' ) : '' );

    $type = sanitize_key( $_POST['event_place_type'] ?? 'station' );
    update_post_meta( $post_id, '_event_place_type', array_key_exists( $type, enroute_get_event_place_types() ) ? $type : 'station' );
    update_post_meta( $post_id, '_event_station', absint( $_POST['event_station'] ?? 0 ) );
    foreach ( [ 'name', 'street', 'zip', 'place' ] as $f ) {
        update_post_meta( $post_id, "_event_place_$f", sanitize_text_field( wp_unslash( $_POST[ "event_place_$f" ] ?? '' ) ) );
    }
} );

// ── Event list columns ──────────────────────────────────────────────────────
add_filter( 'manage_event_posts_columns', function( $cols ) {
    $new = [];
    foreach ( $cols as $k => $v ) {
        $new[ $k ] = $v;
        if ( $k === 'title' ) {
            $new['event_dates']    = __( 'Datum', 'enroute_offers' );
            $new['event_language'] = __( 'Sprache', 'enroute_offers' );
            $new['event_status']   = __( 'Status', 'enroute_offers' );
        }
    }
    return $new;
} );
add_action( 'manage_event_posts_custom_column', function( $col, $post_id ) {
    if ( $col === 'event_dates' )    echo esc_html( enroute_event_date_text( $post_id ) );
    if ( $col === 'event_language' ) echo esc_html( enroute_get_languages()[ enroute_event_language( $post_id ) ] ?? '' );
    if ( $col === 'event_status' ) {
        if ( ! enroute_event_is_active( $post_id ) )   esc_html_e( 'Inaktiv', 'enroute_offers' );
        elseif ( enroute_event_is_past( $post_id ) )   esc_html_e( 'Vorbei', 'enroute_offers' );
        else                                           esc_html_e( 'Aktiv', 'enroute_offers' );
    }
}, 10, 2 );

// ══════════════════════════════════════════════════════════════════════════════
// REGISTRATIONS — backend view
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'add_meta_boxes', function() {
    add_meta_box( 'enroute_event_reg_details', __( 'Anmeldung', 'enroute_offers' ), function( $post ) {
        $fields = [
            '_reg_event_title'        => __( 'Veranstaltung', 'enroute_offers' ),
            '_reg_event_dates'        => __( 'Datum', 'enroute_offers' ),
            '_reg_salutation'         => __( 'Anrede', 'enroute_offers' ),
            '_reg_institution'        => __( 'Institution', 'enroute_offers' ),
            '_reg_first_name'         => __( 'Vorname', 'enroute_offers' ),
            '_reg_last_name'          => __( 'Nachname', 'enroute_offers' ),
            '_reg_street'             => __( 'Strasse', 'enroute_offers' ),
            '_reg_zip'                => __( 'PLZ', 'enroute_offers' ),
            '_reg_place'              => __( 'Ort', 'enroute_offers' ),
            '_reg_email'              => __( 'E-Mail', 'enroute_offers' ),
            '_reg_phone'              => __( 'Telefon', 'enroute_offers' ),
            '_reg_persons_under16'    => __( 'Anzahl Personen unter 16 Jahren', 'enroute_offers' ),
            '_reg_persons_over16'     => __( 'Anzahl Personen über 16 Jahren', 'enroute_offers' ),
            '_reg_persons_companions' => __( 'Anzahl Begleitpersonen', 'enroute_offers' ),
            '_reg_persons'            => __( 'Anzahl Personen (total)', 'enroute_offers' ),
            '_reg_remarks'            => __( 'Weitere Angaben', 'enroute_offers' ),
            '_reg_submitted'          => __( 'Eingereicht am', 'enroute_offers' ),
        ];
        echo '<table class="form-table" style="margin:0;">';
        foreach ( $fields as $key => $label ) {
            $value = get_post_meta( $post->ID, $key, true );
            if ( $value === '' || $value === false ) continue;
            echo '<tr><th style="width:200px;padding:6px 10px;font-weight:600;">' . esc_html( $label ) . '</th>';
            echo '<td style="padding:6px 10px;">' . nl2br( esc_html( $value ) ) . '</td></tr>';
        }
        echo '</table>';
        $event_id = (int) get_post_meta( $post->ID, '_reg_event_id', true );
        if ( $event_id && ( $edit = get_edit_post_link( $event_id ) ) ) {
            echo '<p style="margin-top:1em;"><a class="button button-small" href="' . esc_url( $edit ) . '">' . esc_html__( 'Veranstaltung bearbeiten', 'enroute_offers' ) . '</a></p>';
        }
    }, 'enroute_event_reg', 'normal', 'high' );
} );

add_filter( 'manage_enroute_event_reg_posts_columns', function( $cols ) {
    return [
        'cb'          => $cols['cb'],
        'title'       => __( 'Anmeldung', 'enroute_offers' ),
        'reg_event'   => __( 'Veranstaltung', 'enroute_offers' ),
        'reg_contact' => __( 'Kontakt', 'enroute_offers' ),
        'reg_persons' => __( 'Personen', 'enroute_offers' ),
        'date'        => __( 'Eingereicht', 'enroute_offers' ),
    ];
} );
add_action( 'manage_enroute_event_reg_posts_custom_column', function( $col, $post_id ) {
    switch ( $col ) {
        case 'reg_event':
            echo esc_html( get_post_meta( $post_id, '_reg_event_title', true ) );
            echo '<br><small style="color:#666;">' . esc_html( get_post_meta( $post_id, '_reg_event_dates', true ) ) . '</small>';
            break;
        case 'reg_contact':
            $email = get_post_meta( $post_id, '_reg_email', true );
            echo esc_html( trim( get_post_meta( $post_id, '_reg_first_name', true ) . ' ' . get_post_meta( $post_id, '_reg_last_name', true ) ) );
            if ( $email ) echo '<br><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
            break;
        case 'reg_persons':
            echo esc_html( enroute_booking_persons_summary(
                get_post_meta( $post_id, '_reg_persons', true ),
                get_post_meta( $post_id, '_reg_persons_under16', true ),
                get_post_meta( $post_id, '_reg_persons_over16', true ),
                get_post_meta( $post_id, '_reg_persons_companions', true )
            ) );
            break;
    }
}, 10, 2 );

// Filter registrations by event: ?reg_event=ID (link from the event list)
add_action( 'pre_get_posts', function( WP_Query $q ) {
    if ( ! is_admin() || ! $q->is_main_query() || $q->get( 'post_type' ) !== 'enroute_event_reg' ) return;
    $event = absint( $_GET['reg_event'] ?? 0 );
    if ( $event ) $q->set( 'meta_query', [ [ 'key' => '_reg_event_id', 'value' => $event ] ] );
} );
add_filter( 'post_row_actions', function( $actions, $post ) {
    if ( $post->post_type !== 'event' ) return $actions;
    $count = count( get_posts( [ 'post_type' => 'enroute_event_reg', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_reg_event_id', 'meta_value' => $post->ID ] ) );
    $actions['registrations'] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=enroute_event_reg&reg_event=' . $post->ID ) ) . '">'
        . esc_html( sprintf( __( 'Anmeldungen (%d)', 'enroute_offers' ), $count ) ) . '</a>';
    return $actions;
}, 10, 2 );

// ══════════════════════════════════════════════════════════════════════════════
// FRONT PAGE BLOCK SUPPORT (used by the enroute_blocks plugin)
// ══════════════════════════════════════════════════════════════════════════════

/** "Place, date(s)" line for cards. */
function enroute_event_card_meta( int $event_id ): string {
    return implode( ', ', array_filter( [ enroute_event_place_short( $event_id ), enroute_event_date_text( $event_id ) ] ) );
}

/** Text of the link to the events page. */
function enroute_events_more_link_text(): string {
    return __( 'Weitere Veranstaltungen', 'enroute_offers' );
}

/**
 * Events page URL (Settings → General) in the current language:
 * if the page has a Polylang translation, its URL is returned.
 */
function enroute_events_page_url(): string {
    $url = (string) get_option( 'enroute_events_page_url', '' );
    if ( ! $url ) return '';
    if ( function_exists( 'pll_get_post' ) && function_exists( 'pll_current_language' ) && pll_current_language() ) {
        $page_id = url_to_postid( $url );
        if ( $page_id ) {
            $translated = pll_get_post( $page_id, pll_current_language() );
            if ( $translated ) return (string) get_permalink( $translated );
        }
    }
    return $url;
}

// ══════════════════════════════════════════════════════════════════════════════
// SMALL FIXES
// ══════════════════════════════════════════════════════════════════════════════

/** URL from user input; adds https:// when the scheme is missing ("www.x.ch" → "https://www.x.ch"). */
function enroute_normalize_url( string $url ): string {
    $url = trim( $url );
    if ( $url === '' ) return '';
    if ( ! preg_match( '#^[a-z][a-z0-9+.\-]*:#i', $url ) ) $url = 'https://' . ltrim( $url, '/' );
    return esc_url_raw( $url );
}

// The event page shows the image itself (with the title band) — hide the
// theme's own featured image on single event pages, so it isn't shown twice.
add_filter( 'post_thumbnail_html', function( $html, $post_id ) {
    if ( is_admin() || ! is_singular( 'event' ) || (int) $post_id !== (int) get_queried_object_id() ) return $html;
    return '';
}, 10, 2 );
add_filter( 'has_post_thumbnail', function( $has, $post ) {
    if ( is_admin() || ! is_singular( 'event' ) ) return $has;
    $id = $post instanceof WP_Post ? $post->ID : (int) ( $post ?: get_the_ID() );
    return $id === (int) get_queried_object_id() ? false : $has;
}, 10, 2 );

/**
 * Event editor: target groups and tags only in one language (Polylang),
 * not every term with all its translations. Language = the event's language,
 * for a new event the admin's language.
 */
add_filter( 'get_terms_args', function( array $args, $taxonomies ) {
    if ( ! is_admin() || ! function_exists( 'pll_languages_list' ) ) return $args;
    if ( ! array_intersect( (array) $taxonomies, [ 'offer_target_group', 'offer_tag' ] ) ) return $args;
    if ( ! empty( $args['object_ids'] ) || isset( $args['lang'] ) ) return $args;

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || $screen->base !== 'post' || $screen->post_type !== 'event' ) return $args;

    $post_id = absint( $_GET['post'] ?? 0 );
    $lang    = $post_id ? enroute_event_language( $post_id ) : '';
    if ( ! $lang ) $lang = function_exists( 'pll_current_language' ) ? (string) pll_current_language() : '';
    if ( ! $lang && function_exists( 'enroute_pll_admin_language' ) ) $lang = enroute_pll_admin_language();

    if ( $lang && in_array( $lang, (array) pll_languages_list( [ 'fields' => 'slug' ] ), true ) ) $args['lang'] = $lang;
    return $args;
}, 10, 2 );

// The event page shows its title in the coloured band on the image — hide the
// theme's own page title and "Edit" link on event pages (like offer pages).
// Browser tab / search engine title (wp_get_document_title) is not affected.
function enroute_is_theme_event_title( $post_id ): bool {
    return ! is_admin()
        && is_singular( 'event' )
        && ! empty( $GLOBALS['enroute_head_done'] )                          // not in <head> (SEO/meta tags keep the title)
        && (int) $post_id === (int) get_queried_object_id()
        && empty( $GLOBALS['enroute_rendering_event_detail'] );
}
add_action( 'wp_head', function() { $GLOBALS['enroute_head_done'] = true; }, PHP_INT_MAX );
add_filter( 'the_title', function( $title, $post_id = 0 ) {
    return enroute_is_theme_event_title( $post_id ) ? '' : $title;
}, 10, 2 );
add_filter( 'edit_post_link', function( $link, $post_id = 0 ) {
    return enroute_is_theme_event_title( $post_id ) ? '' : $link;
}, 10, 2 );

