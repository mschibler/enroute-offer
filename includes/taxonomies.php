<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function enroute_register_taxonomies() {

    // ── SUBJECT (offer + resource) ──────────────────────────────────────────
    register_taxonomy( 'offer_subject', [ 'offer', 'resource' ], [
        'labels' => [
            'name'              => __( 'Subjects',        'enroute_offers' ),
            'singular_name'     => __( 'Subject',         'enroute_offers' ),
            'search_items'      => __( 'Search Subjects', 'enroute_offers' ),
            'all_items'         => __( 'All Subjects',    'enroute_offers' ),
            'edit_item'         => __( 'Edit Subject',    'enroute_offers' ),
            'add_new_item'      => __( 'Add New Subject', 'enroute_offers' ),
            'menu_name'         => __( 'Subjects',        'enroute_offers' ),
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => false,
        'show_admin_column' => true,
        'rewrite'           => [ 'slug' => 'subject' ],
    ]);

    // ── TARGET GROUP (offer + resource) ────────────────────────────────────
    register_taxonomy( 'offer_target_group', [ 'offer', 'resource' ], [
        'labels' => [
            'name'              => __( 'Target Groups',        'enroute_offers' ),
            'singular_name'     => __( 'Target Group',         'enroute_offers' ),
            'search_items'      => __( 'Search Target Groups', 'enroute_offers' ),
            'all_items'         => __( 'All Target Groups',    'enroute_offers' ),
            'edit_item'         => __( 'Edit Target Group',    'enroute_offers' ),
            'add_new_item'      => __( 'Add New Target Group', 'enroute_offers' ),
            'menu_name'         => __( 'Target Groups',        'enroute_offers' ),
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => false,
        'show_admin_column' => true,
        'rewrite'           => [ 'slug' => 'target-group' ],
    ]);

    // ── OFFER TYPE (offer only) ─────────────────────────────────────────────
    register_taxonomy( 'offer_type', [ 'offer' ], [
        'labels' => [
            'name'              => __( 'Offer Types',        'enroute_offers' ),
            'singular_name'     => __( 'Offer Type',         'enroute_offers' ),
            'search_items'      => __( 'Search Offer Types', 'enroute_offers' ),
            'all_items'         => __( 'All Offer Types',    'enroute_offers' ),
            'edit_item'         => __( 'Edit Offer Type',    'enroute_offers' ),
            'add_new_item'      => __( 'Add New Offer Type', 'enroute_offers' ),
            'menu_name'         => __( 'Offer Types',        'enroute_offers' ),
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => false,
        'show_admin_column' => true,
        'rewrite'           => [ 'slug' => 'offer-type' ],
    ]);

    // ── RESOURCE TYPE (resource only) ──────────────────────────────────────
    register_taxonomy( 'resource_type', [ 'resource' ], [
        'labels' => [
            'name'              => __( 'Resource Types',        'enroute_offers' ),
            'singular_name'     => __( 'Resource Type',         'enroute_offers' ),
            'search_items'      => __( 'Search Resource Types', 'enroute_offers' ),
            'all_items'         => __( 'All Resource Types',    'enroute_offers' ),
            'edit_item'         => __( 'Edit Resource Type',    'enroute_offers' ),
            'add_new_item'      => __( 'Add New Resource Type', 'enroute_offers' ),
            'menu_name'         => __( 'Resource Types',        'enroute_offers' ),
        ],
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => false,
        'show_admin_column' => true,
        'rewrite'           => [ 'slug' => 'resource-type' ],
    ]);

    // ── TAGS (all post types) ───────────────────────────────────────────────
    register_taxonomy( 'offer_tag', [ 'offer', 'station', 'resource' ], [
        'labels' => [
            'name'              => __( 'Tags',        'enroute_offers' ),
            'singular_name'     => __( 'Tag',         'enroute_offers' ),
            'search_items'      => __( 'Search Tags', 'enroute_offers' ),
            'all_items'         => __( 'All Tags',    'enroute_offers' ),
            'edit_item'         => __( 'Edit Tag',    'enroute_offers' ),
            'add_new_item'      => __( 'Add New Tag', 'enroute_offers' ),
            'menu_name'         => __( 'Tags',        'enroute_offers' ),
        ],
        'hierarchical'      => false,
        'show_ui'           => true,
        'show_in_rest'      => false,
        'show_admin_column' => true,
        'rewrite'           => [ 'slug' => 'offer-tag' ],
    ]);
}
add_action( 'init', 'enroute_register_taxonomies' );
