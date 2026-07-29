<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register the 'booking' custom post type.
 * Bookings are created programmatically via AJAX — no public front-end.
 */
function enroute_register_booking_post_type() {
    register_post_type( 'enroute_booking', [
        'labels' => [
            'name'               => __( 'Buchungsanfragen',        'enroute_offers' ),
            'singular_name'      => __( 'Buchungsanfrage',         'enroute_offers' ),
            'add_new'            => __( 'Neu',                     'enroute_offers' ),
            'add_new_item'       => __( 'Neue Buchungsanfrage',     'enroute_offers' ),
            'edit_item'          => __( 'Buchungsanfrage bearbeiten','enroute_offers' ),
            'view_item'          => __( 'Buchungsanfrage ansehen',  'enroute_offers' ),
            'search_items'       => __( 'Buchungsanfragen suchen',  'enroute_offers' ),
            'not_found'          => __( 'Keine Buchungsanfragen',   'enroute_offers' ),
            'not_found_in_trash' => __( 'Keine Buchungsanfragen im Papierkorb', 'enroute_offers' ),
            'menu_name'          => __( 'Buchungsanfragen',         'enroute_offers' ),
            'all_items'          => __( 'Alle Buchungsanfragen',    'enroute_offers' ),
        ],
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => 'edit.php?post_type=offer',  // submenu under Offers
        'supports'          => [ 'title' ],
        'capability_type'   => 'post',
        'map_meta_cap'      => true,
        'show_in_rest'      => false,
    ]);
}
add_action( 'init', 'enroute_register_booking_post_type' );

/**
 * Add meta boxes to display booking details in admin.
 */
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'enroute_booking_details',
        __( 'Buchungsdetails', 'enroute_offers' ),
        'enroute_booking_details_meta_box',
        'enroute_booking',
        'normal',
        'high'
    );
});

function enroute_booking_details_meta_box( $post ) {
    $fields = [
        '_booking_offer_id'       => __( 'Angebot (ID)',      'enroute_offers' ),
        '_booking_offer_title'    => __( 'Angebot',           'enroute_offers' ),
        '_booking_salutation'     => __( 'Anrede',            'enroute_offers' ),
        '_booking_institution'    => __( 'Institution',       'enroute_offers' ),
        '_booking_first_name'     => __( 'Vorname',           'enroute_offers' ),
        '_booking_last_name'      => __( 'Nachname',          'enroute_offers' ),
        '_booking_street'         => __( 'Strasse',           'enroute_offers' ),
        '_booking_zip'            => __( 'PLZ',               'enroute_offers' ),
        '_booking_place'          => __( 'Ort',               'enroute_offers' ),
        '_booking_email'          => __( 'E-Mail',            'enroute_offers' ),
        '_booking_phone'          => __( 'Telefon',           'enroute_offers' ),
        '_booking_date_1'         => __( 'Wunschdatum',       'enroute_offers' ),
        '_booking_time_1'         => __( 'Wunschzeit',        'enroute_offers' ),
        '_booking_date_2'         => __( 'Ersatzdatum',       'enroute_offers' ),
        '_booking_time_2'         => __( 'Ersatzzeit',        'enroute_offers' ),
        '_booking_persons'        => __( 'Anzahl Personen',   'enroute_offers' ),
        '_booking_remarks'        => __( 'Weitere Angaben',   'enroute_offers' ),
        '_booking_submitted'      => __( 'Eingereicht am',    'enroute_offers' ),
    ];

    echo '<table class="form-table" style="margin:0;">';
    foreach ( $fields as $key => $label ) {
        $value = get_post_meta( $post->ID, $key, true );
        if ( $value === '' || $value === false ) continue;
        echo '<tr>';
        echo '<th style="width:160px;padding:6px 10px;font-weight:600;">' . esc_html( $label ) . '</th>';
        echo '<td style="padding:6px 10px;">' . nl2br( esc_html( $value ) ) . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    // Link to the offer
    $offer_id = get_post_meta( $post->ID, '_booking_offer_id', true );
    if ( $offer_id ) {
        $edit_url = get_edit_post_link( $offer_id );
        $view_url = get_permalink( $offer_id );
        echo '<p style="margin-top:1em;">';
        if ( $edit_url ) echo '<a href="' . esc_url( $edit_url ) . '" class="button button-small">' . esc_html__( 'Angebot bearbeiten', 'enroute_offers' ) . '</a> ';
        if ( $view_url ) echo '<a href="' . esc_url( $view_url ) . '" class="button button-small" target="_blank">' . esc_html__( 'Angebot ansehen', 'enroute_offers' ) . '</a>';
        echo '</p>';
    }
}

/**
 * Customise the booking list table columns.
 */
add_filter( 'manage_enroute_booking_posts_columns', function( $cols ) {
    return [
        'cb'                   => $cols['cb'],
        'title'                => __( 'Buchungsanfrage',   'enroute_offers' ),
        'booking_offer'        => __( 'Angebot',           'enroute_offers' ),
        'booking_contact'      => __( 'Kontakt',           'enroute_offers' ),
        'booking_date1'        => __( 'Wunschdatum',       'enroute_offers' ),
        'booking_persons'      => __( 'Personen',          'enroute_offers' ),
        'date'                 => __( 'Eingereicht',       'enroute_offers' ),
    ];
});

add_action( 'manage_enroute_booking_posts_custom_column', function( $col, $post_id ) {
    switch ( $col ) {
        case 'booking_offer':
            echo esc_html( get_post_meta( $post_id, '_booking_offer_title', true ) );
            break;
        case 'booking_contact':
            $first = get_post_meta( $post_id, '_booking_first_name', true );
            $last  = get_post_meta( $post_id, '_booking_last_name', true );
            $email = get_post_meta( $post_id, '_booking_email', true );
            echo esc_html( trim( "$first $last" ) );
            if ( $email ) echo '<br><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
            break;
        case 'booking_date1':
            $d = get_post_meta( $post_id, '_booking_date_1', true );
            $t = get_post_meta( $post_id, '_booking_time_1', true );
            echo esc_html( trim( "$d $t" ) );
            break;
        case 'booking_persons':
            echo esc_html( get_post_meta( $post_id, '_booking_persons', true ) );
            break;
    }
}, 10, 2 );

// Make bookings read-only in admin (no editing of data directly)
add_action( 'edit_form_top', function( $post ) {
    if ( $post->post_type !== 'enroute_booking' ) return;
    echo '<div class="notice notice-info inline" style="margin:10px 0;"><p>'
        . esc_html__( 'Buchungsanfragen sind schreibgeschützt. Daten werden über das Formular gespeichert.', 'enroute_offers' )
        . '</p></div>';
});
