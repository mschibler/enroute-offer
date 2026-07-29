<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'save_post_offer',    'enroute_save_offer_meta'    );
add_action( 'save_post_station',  'enroute_save_station_meta'  );
add_action( 'save_post_resource', 'enroute_save_resource_meta' );

// ══════════════════════════════════════════════════════════════════════════════
// OFFER
// ══════════════════════════════════════════════════════════════════════════════

function enroute_save_offer_meta( int $post_id ): void {
    if ( ! isset( $_POST['enroute_offer_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['enroute_offer_nonce'], 'enroute_offer_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $text_fields = [
        '_offer_subtitle'    => 'offer_subtitle',
        '_offer_description' => 'offer_description',
        '_offer_price'       => 'offer_price',
        '_offer_date_info'   => 'offer_date_info',
    ];
    foreach ( $text_fields as $meta_key => $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $meta_key, sanitize_textarea_field( $_POST[ $field ] ) );
        }
    }

    // Contact email
    $email = isset( $_POST['offer_contact_email'] ) ? sanitize_email( $_POST['offer_contact_email'] ) : '';
    update_post_meta( $post_id, '_offer_contact_email', $email );

    // Station
    $station = isset( $_POST['offer_station'] ) ? absint( $_POST['offer_station'] ) : 0;
    update_post_meta( $post_id, '_offer_station', $station );

    // Language
    $allowed_langs = array_keys( enroute_get_languages() );
    $language = isset( $_POST['offer_language'] ) && in_array( $_POST['offer_language'], $allowed_langs, true )
        ? sanitize_key( $_POST['offer_language'] ) : '';
    update_post_meta( $post_id, '_offer_language', $language );

    // Weekdays (multi)
    $allowed_days = array_keys( enroute_get_weekdays() );
    $weekdays = [];
    if ( isset( $_POST['offer_weekdays'] ) && is_array( $_POST['offer_weekdays'] ) ) {
        foreach ( $_POST['offer_weekdays'] as $d ) {
            if ( in_array( $d, $allowed_days, true ) ) $weekdays[] = sanitize_key( $d );
        }
    }
    update_post_meta( $post_id, '_offer_weekdays', $weekdays );

    // Times of day (multi)
    $allowed_times = array_keys( enroute_get_times_of_day() );
    $times_of_day = [];
    if ( isset( $_POST['offer_times_of_day'] ) && is_array( $_POST['offer_times_of_day'] ) ) {
        foreach ( $_POST['offer_times_of_day'] as $t ) {
            if ( in_array( $t, $allowed_times, true ) ) $times_of_day[] = sanitize_key( $t );
        }
    }
    update_post_meta( $post_id, '_offer_times_of_day', $times_of_day );

    // Fixed date
    $fixed_date = isset( $_POST['offer_fixed_date'] ) ? sanitize_text_field( $_POST['offer_fixed_date'] ) : '';
    update_post_meta( $post_id, '_offer_fixed_date', $fixed_date );

    // Resources
    $resources = isset( $_POST['offer_resources'] ) ? array_map( 'absint', (array) $_POST['offer_resources'] ) : [];
    update_post_meta( $post_id, '_offer_resources', $resources );

    // External links
    $links = [];
    if ( isset( $_POST['offer_external_links'] ) && is_array( $_POST['offer_external_links'] ) ) {
        foreach ( $_POST['offer_external_links'] as $link ) {
            $clean = esc_url_raw( trim( $link ) );
            if ( $clean ) $links[] = $clean;
        }
    }
    update_post_meta( $post_id, '_offer_external_links', $links );
}

// ══════════════════════════════════════════════════════════════════════════════
// STATION
// ══════════════════════════════════════════════════════════════════════════════

function enroute_save_station_meta( int $post_id ): void {
    if ( ! isset( $_POST['enroute_station_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['enroute_station_nonce'], 'enroute_station_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $fields = [
        '_station_portrait' => 'station_portrait',
        '_station_address'  => 'station_address',
        '_station_plz'      => 'station_plz',
        '_station_place'    => 'station_place',
    ];
    foreach ( $fields as $meta_key => $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $meta_key, sanitize_textarea_field( $_POST[ $field ] ) );
        }
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// RESOURCE
// ══════════════════════════════════════════════════════════════════════════════

function enroute_save_resource_meta( int $post_id ): void {
    if ( ! isset( $_POST['enroute_resource_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['enroute_resource_nonce'], 'enroute_resource_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $file_id = isset( $_POST['resource_file_id'] ) ? absint( $_POST['resource_file_id'] ) : 0;
    update_post_meta( $post_id, '_resource_file_id', $file_id );

    $allowed_langs = array_keys( enroute_get_languages() );
    $language = isset( $_POST['resource_language'] ) && in_array( $_POST['resource_language'], $allowed_langs, true )
        ? sanitize_key( $_POST['resource_language'] ) : '';
    update_post_meta( $post_id, '_resource_language', $language );
}

// ══════════════════════════════════════════════════════════════════════════════
// OLD CMS ID — save for offer / station / resource
// ══════════════════════════════════════════════════════════════════════════════

function enroute_save_old_cms_id( int $post_id ): void {
    if ( ! isset( $_POST['enroute_old_cms_id_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['enroute_old_cms_id_nonce'], 'enroute_old_cms_id_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $value = isset( $_POST['old_cms_id'] ) ? sanitize_text_field( $_POST['old_cms_id'] ) : '';
    update_post_meta( $post_id, '_old_cms_id', $value );
}
add_action( 'save_post_offer',    'enroute_save_old_cms_id' );
add_action( 'save_post_station',  'enroute_save_old_cms_id' );
add_action( 'save_post_resource', 'enroute_save_old_cms_id' );
