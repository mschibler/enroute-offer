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

// ══════════════════════════════════════════════════════════════════════════════
// BLOG POST — Guide Author meta box
// Only registered on this specific site in multisite
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'add_meta_boxes', function() {
    // Only add on standard posts
    add_meta_box(
        'enroute_blog_guide_author',
        __( 'Guide Author', 'enroute_offers' ),
        'enroute_blog_guide_author_cb',
        'post',
        'side',
        'default'
    );
} );

function enroute_blog_guide_author_cb( WP_Post $post ): void {
    wp_nonce_field( 'enroute_blog_guide_author_save', 'enroute_blog_guide_author_nonce' );
    $guide_id = get_post_meta( $post->ID, '_blog_guide_author_id', true );

    // Get all guides
    $guides = get_posts( [
        'post_type'   => 'guide',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
    ] );
    ?>
    <p>
        <label for="blog_guide_author_id"><?php esc_html_e( 'Assign a guide as author:', 'enroute_offers' ); ?></label>
        <select id="blog_guide_author_id" name="blog_guide_author_id" style="width:100%; margin-top:4px;">
            <option value=""><?php esc_html_e( '— None —', 'enroute_offers' ); ?></option>
            <?php foreach ( $guides as $g ) : ?>
            <option value="<?php echo $g->ID; ?>" <?php selected( $guide_id, $g->ID ); ?>>
                <?php echo esc_html( $g->post_title ); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}

add_action( 'save_post_post', function( int $post_id ): void {
    if ( ! isset( $_POST['enroute_blog_guide_author_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['enroute_blog_guide_author_nonce'], 'enroute_blog_guide_author_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $guide_id = absint( $_POST['blog_guide_author_id'] ?? 0 );
    update_post_meta( $post_id, '_blog_guide_author_id', $guide_id ?: '' );
} );
