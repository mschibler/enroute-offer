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

// ══════════════════════════════════════════════════════════════════════════════
// BLOG CATEGORY — image field
// Allows setting a fallback image per category shown in blog listing
// ══════════════════════════════════════════════════════════════════════════════

// Add image field to category add/edit forms
add_action( 'category_add_form_fields', function(): void {
    wp_nonce_field( 'enroute_cat_image_save', 'enroute_cat_image_nonce' );
    ?>
    <div class="form-field">
        <label><?php esc_html_e( 'Category Image', 'enroute_offers' ); ?></label>
        <div id="enroute-cat-image-wrap">
            <input type="hidden" id="enroute_cat_image_id" name="enroute_cat_image_id" value="">
            <div id="enroute-cat-image-preview" style="margin-bottom:0.5rem;"></div>
            <button type="button" class="button" id="enroute-cat-image-btn">
                <?php esc_html_e( 'Select Image', 'enroute_offers' ); ?>
            </button>
            <button type="button" class="button" id="enroute-cat-image-remove" style="display:none;">
                <?php esc_html_e( 'Remove', 'enroute_offers' ); ?>
            </button>
        </div>
        <p class="description"><?php esc_html_e( 'Shown as fallback when a blog post in this category has no featured image.', 'enroute_offers' ); ?></p>
    </div>
    <?php enroute_cat_image_script( 0 ); ?>
    <?php
} );

add_action( 'category_edit_form_fields', function( WP_Term $term ): void {
    wp_nonce_field( 'enroute_cat_image_save', 'enroute_cat_image_nonce' );
    $image_id  = get_term_meta( $term->term_id, 'category_image_id', true );
    $image_url = $image_id ? wp_get_attachment_image_url( (int) $image_id, 'thumbnail' ) : '';
    ?>
    <tr class="form-field">
        <th><label><?php esc_html_e( 'Category Image', 'enroute_offers' ); ?></label></th>
        <td>
            <input type="hidden" id="enroute_cat_image_id" name="enroute_cat_image_id" value="<?php echo esc_attr( $image_id ); ?>">
            <div id="enroute-cat-image-preview" style="margin-bottom:0.5rem;">
                <?php if ( $image_url ) : ?>
                <img src="<?php echo esc_url( $image_url ); ?>" style="max-width:150px; height:auto;">
                <?php endif; ?>
            </div>
            <button type="button" class="button" id="enroute-cat-image-btn">
                <?php esc_html_e( 'Select Image', 'enroute_offers' ); ?>
            </button>
            <button type="button" class="button" id="enroute-cat-image-remove" style="<?php echo $image_id ? '' : 'display:none;'; ?>">
                <?php esc_html_e( 'Remove', 'enroute_offers' ); ?>
            </button>
            <p class="description"><?php esc_html_e( 'Shown as fallback when a blog post in this category has no featured image.', 'enroute_offers' ); ?></p>
        </td>
    </tr>
    <?php enroute_cat_image_script( (int) $image_id ); ?>
    <?php
} );

function enroute_cat_image_script( int $image_id ): void { ?>
    <script>
    jQuery(function($) {
        var frame;
        $('#enroute-cat-image-btn').on('click', function(e) {
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({ title: '<?php esc_html_e( 'Select Category Image', 'enroute_offers' ); ?>', button: { text: '<?php esc_html_e( 'Use this image', 'enroute_offers' ); ?>' }, multiple: false });
            frame.on('select', function() {
                var att = frame.state().get('selection').first().toJSON();
                $('#enroute_cat_image_id').val(att.id);
                $('#enroute-cat-image-preview').html('<img src="' + (att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url) + '" style="max-width:150px;height:auto;">');
                $('#enroute-cat-image-remove').show();
            });
            frame.open();
        });
        $('#enroute-cat-image-remove').on('click', function() {
            $('#enroute_cat_image_id').val('');
            $('#enroute-cat-image-preview').html('');
            $(this).hide();
        });
    });
    </script>
    <?php
}

// Enqueue media uploader on category pages
add_action( 'admin_enqueue_scripts', function( string $hook ): void {
    if ( ! in_array( $hook, [ 'edit-tags.php', 'term.php' ], true ) ) return;
    if ( ( $_GET['taxonomy'] ?? '' ) !== 'category' ) return;
    wp_enqueue_media();
} );

// Save category image
add_action( 'created_category', 'enroute_save_cat_image' );
add_action( 'edited_category',  'enroute_save_cat_image' );

function enroute_save_cat_image( int $term_id ): void {
    if ( ! isset( $_POST['enroute_cat_image_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['enroute_cat_image_nonce'], 'enroute_cat_image_save' ) ) return;
    $image_id = absint( $_POST['enroute_cat_image_id'] ?? 0 );
    if ( $image_id ) {
        update_term_meta( $term_id, 'category_image_id', $image_id );
    } else {
        delete_term_meta( $term_id, 'category_image_id' );
    }
}
