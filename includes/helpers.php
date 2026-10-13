<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Returns supported languages for the language field.
 */
function enroute_get_languages(): array {
    return [
        'de' => __( 'German',  'enroute_offers' ),
        'fr' => __( 'French',  'enroute_offers' ),
        'it' => __( 'Italian', 'enroute_offers' ),
        'en' => __( 'English', 'enroute_offers' ),
    ];
}

/**
 * Returns weekday options.
 */
function enroute_get_weekdays(): array {
    return [
        'monday'    => __( 'Monday',    'enroute_offers' ),
        'tuesday'   => __( 'Tuesday',   'enroute_offers' ),
        'wednesday' => __( 'Wednesday', 'enroute_offers' ),
        'thursday'  => __( 'Thursday',  'enroute_offers' ),
        'friday'    => __( 'Friday',    'enroute_offers' ),
        'saturday'  => __( 'Saturday',  'enroute_offers' ),
        'sunday'    => __( 'Sunday',    'enroute_offers' ),
    ];
}

/**
 * Returns time of day options.
 */
function enroute_get_times_of_day(): array {
    return [
        'morning'   => __( 'Morning',   'enroute_offers' ),
        'afternoon' => __( 'Afternoon', 'enroute_offers' ),
        'evening'   => __( 'Evening',   'enroute_offers' ),
    ];
}

/**
 * Returns allowed MIME types for the resource file field.
 */
function enroute_allowed_mime_types(): array {
    return [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];
}

/**
 * Returns a file-type icon SVG/emoji by mime type.
 */
function enroute_filetype_icon( string $mime ): string {
    $icons = [
        'application/pdf'  => '<svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-red-600" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 17v-1h8v1H8zm0-3v-1h8v1H8zm0-3V10h5v1H8z"/></svg>',
        'word'             => '<svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-blue-700" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM9 17l-2-7h1.5l1.25 5 1.25-5H12l1.25 5 1.25-5H16l-2 7H9z"/></svg>',
        'excel'            => '<svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-green-700" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 11h2l1.5 2.5L13 11h2l-2.5 4 2.5 4h-2l-1.5-2.5L10 19H8l2.5-4L8 11z"/></svg>',
        'powerpoint'       => '<svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-orange-600" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM9 11h3.5a2.5 2.5 0 010 5H10.5V19H9V11zm1.5 4H12a1 1 0 100-2h-1.5v2z"/></svg>',
        'default'          => '<svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-500" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5z"/></svg>',
    ];

    if ( $mime === 'application/pdf' ) return $icons['application/pdf'];
    if ( str_contains( $mime, 'word' ) )        return $icons['word'];
    if ( str_contains( $mime, 'excel' ) || str_contains( $mime, 'spreadsheet' ) ) return $icons['excel'];
    if ( str_contains( $mime, 'powerpoint' ) || str_contains( $mime, 'presentation' ) ) return $icons['powerpoint'];
    return $icons['default'];
}

// ── Offer status: bookable / active ──────────────────────────────────────────

/** Offer can be booked (checkbox "Bookable"). Unticked or never set = not bookable. */
function enroute_offer_is_bookable( int $offer_id ): bool {
    return get_post_meta( $offer_id, '_offer_bookable', true ) === '1';
}

/** Offer is shown in listings (checkbox "Active"). Never set = active, so existing offers stay visible. */
function enroute_offer_is_active( int $offer_id ): bool {
    return get_post_meta( $offer_id, '_offer_active', true ) !== '0';
}

/** meta_query clause that keeps only active offers. */
function enroute_offer_active_meta_query(): array {
    return [
        'relation' => 'OR',
        [ 'key' => '_offer_active', 'compare' => 'NOT EXISTS' ],
        [ 'key' => '_offer_active', 'value' => '0', 'compare' => '!=' ],
    ];
}

// ── Durchführungsort (offer location) ───────────────────────────────────────

/** Location options for offers. */
function enroute_get_location_types(): array {
    return [
        'station'   => __( 'Adresse der Station', 'enroute_offers' ),
        'other'     => __( 'Andere Adresse', 'enroute_offers' ),
        'classroom' => __( 'Im Schulzimmer', 'enroute_offers' ),
    ];
}

/**
 * Lines to display for an offer's location (Durchführungsort).
 * Station address, other address, or "Im Schulzimmer". Empty array if nothing to show.
 */
function enroute_offer_location_lines( int $offer_id ): array {
    $type = get_post_meta( $offer_id, '_offer_location_type', true ) ?: 'station';

    if ( $type === 'classroom' ) {
        return [ __( 'Im Schulzimmer', 'enroute_offers' ) ];
    }

    if ( $type === 'other' ) {
        $name   = get_post_meta( $offer_id, '_offer_location_name',   true );
        $street = get_post_meta( $offer_id, '_offer_location_street', true );
        $city   = trim( get_post_meta( $offer_id, '_offer_location_zip', true ) . ' ' . get_post_meta( $offer_id, '_offer_location_place', true ) );
    } else {
        $station_id = (int) get_post_meta( $offer_id, '_offer_station', true );
        if ( ! $station_id ) return [];
        $name   = get_the_title( $station_id );
        $street = get_post_meta( $station_id, '_station_address', true );
        $city   = trim( get_post_meta( $station_id, '_station_plz', true ) . ' ' . get_post_meta( $station_id, '_station_place', true ) );
    }

    return array_values( array_filter( [ $name, $street, $city ], fn( $l ) => trim( (string) $l ) !== '' ) );
}

// ── Default colours (Settings → General → Default colors) ────────────────────

/** Default colours 1–5, used when no colour has been saved in the settings. */
function enroute_get_default_colors(): array {
    return [
        1 => '#C9D56B',
        2 => '#F8ED84',
        3 => '#E8AB58',
        4 => '#D4735D',
        5 => '#D0687B',
    ];
}

// ── Texts for JavaScript (Alpine listings) ───────────────────────────────────

/**
 * Decodes HTML entities (e.g. &#8211; → –, &amp; → &) in all texts of a data
 * array before it is handed to JavaScript. WordPress stores typographic
 * characters in titles as entities; Alpine's x-text shows them literally.
 * Safe because x-text and :href/:src never interpret HTML.
 * Keys in $html_keys hold real HTML for x-html and are left unchanged.
 */
function enroute_js_data( $data, array $html_keys = [] ) {
    if ( is_array( $data ) ) {
        foreach ( $data as $key => $value ) {
            if ( is_string( $key ) && in_array( $key, $html_keys, true ) ) continue;
            $data[ $key ] = enroute_js_data( $value, $html_keys );
        }
        return $data;
    }
    return is_string( $data ) ? html_entity_decode( $data, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : $data;
}

// ── Booking: group size ──────────────────────────────────────────────────────

/**
 * Text for the {personen} e-mail placeholder and the backend, e.g.
 * "17 (unter 16 Jahren: 12, über 16 Jahren: 3, Begleitpersonen: 2)".
 * Old bookings with only a total show just the total.
 */
function enroute_booking_persons_summary( $total, $under16 = '', $over16 = '', $companions = '' ): string {
    $parts = [];
    if ( $under16 !== '' && $under16 !== null )       $parts[] = sprintf( __( 'unter 16 Jahren: %d', 'enroute_offers' ), (int) $under16 );
    if ( $over16 !== '' && $over16 !== null )         $parts[] = sprintf( __( 'über 16 Jahren: %d', 'enroute_offers' ), (int) $over16 );
    if ( $companions !== '' && $companions !== null ) $parts[] = sprintf( __( 'Begleitpersonen: %d', 'enroute_offers' ), (int) $companions );

    if ( $total === '' || $total === null ) return '';
    return $parts ? $total . ' (' . implode( ', ', $parts ) . ')' : (string) $total;
}

