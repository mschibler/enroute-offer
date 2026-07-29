<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function enroute_register_post_types() {

    // ── OFFER ───────────────────────────────────────────────────────────────
    register_post_type( 'offer', [
        'labels' => [
            'name'               => __( 'Offers',           'enroute_offers' ),
            'singular_name'      => __( 'Offer',            'enroute_offers' ),
            'add_new'            => __( 'Add New',          'enroute_offers' ),
            'add_new_item'       => __( 'Add New Offer',    'enroute_offers' ),
            'edit_item'          => __( 'Edit Offer',       'enroute_offers' ),
            'new_item'           => __( 'New Offer',        'enroute_offers' ),
            'view_item'          => __( 'View Offer',       'enroute_offers' ),
            'search_items'       => __( 'Search Offers',    'enroute_offers' ),
            'not_found'          => __( 'No offers found',  'enroute_offers' ),
            'not_found_in_trash' => __( 'No offers in trash', 'enroute_offers' ),
            'menu_name'          => __( 'Offers',           'enroute_offers' ),
        ],
        'public'            => true,
        'show_in_menu'      => true,
        'menu_icon'         => 'dashicons-tickets-alt',
        'supports'          => [ 'title', 'thumbnail' ],
        'show_ui'           => true,
        'show_in_rest'      => false,
        'has_archive'       => false,
        'rewrite'           => [ 'slug' => 'offers' ],
        'capability_type'   => 'post',
    ]);

    // ── STATION ─────────────────────────────────────────────────────────────
    register_post_type( 'station', [
        'labels' => [
            'name'               => __( 'Stations',            'enroute_offers' ),
            'singular_name'      => __( 'Station',             'enroute_offers' ),
            'add_new'            => __( 'Add New',             'enroute_offers' ),
            'add_new_item'       => __( 'Add New Station',     'enroute_offers' ),
            'edit_item'          => __( 'Edit Station',        'enroute_offers' ),
            'new_item'           => __( 'New Station',         'enroute_offers' ),
            'view_item'          => __( 'View Station',        'enroute_offers' ),
            'search_items'       => __( 'Search Stations',     'enroute_offers' ),
            'not_found'          => __( 'No stations found',   'enroute_offers' ),
            'not_found_in_trash' => __( 'No stations in trash','enroute_offers' ),
            'menu_name'          => __( 'Stations',            'enroute_offers' ),
        ],
        'public'            => true,
        'show_in_menu'      => true,
        'menu_icon'         => 'dashicons-location-alt',
        'supports'          => [ 'title' ],
        'show_ui'           => true,
        'show_in_rest'      => false,
        'has_archive'       => false,
        'rewrite'           => [ 'slug' => 'stations' ],
        'capability_type'   => 'post',
    ]);

    // ── RESOURCE ────────────────────────────────────────────────────────────
    register_post_type( 'resource', [
        'labels' => [
            'name'               => __( 'Resources',            'enroute_offers' ),
            'singular_name'      => __( 'Resource',             'enroute_offers' ),
            'add_new'            => __( 'Add New',              'enroute_offers' ),
            'add_new_item'       => __( 'Add New Resource',     'enroute_offers' ),
            'edit_item'          => __( 'Edit Resource',        'enroute_offers' ),
            'new_item'           => __( 'New Resource',         'enroute_offers' ),
            'view_item'          => __( 'View Resource',        'enroute_offers' ),
            'search_items'       => __( 'Search Resources',     'enroute_offers' ),
            'not_found'          => __( 'No resources found',   'enroute_offers' ),
            'not_found_in_trash' => __( 'No resources in trash','enroute_offers' ),
            'menu_name'          => __( 'Resources',            'enroute_offers' ),
        ],
        'public'            => true,
        'show_in_menu'      => true,
        'menu_icon'         => 'dashicons-media-document',
        'supports'          => [ 'title' ],
        'show_ui'           => true,
        'show_in_rest'      => false,
        'has_archive'       => false,
        'rewrite'           => [ 'slug' => 'resources' ],
        'capability_type'   => 'post',
    ]);
}
add_action( 'init', 'enroute_register_post_types' );
