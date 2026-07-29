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
