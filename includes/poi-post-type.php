<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ══════════════════════════════════════════════════════════════════════════════
// REGISTER POST TYPE
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'init', function() {
    register_post_type( 'poi', [
        'labels' => [
            'name'          => __( 'Points of Interest', 'enroute_offers' ),
            'singular_name' => __( 'Point of Interest',  'enroute_offers' ),
            'add_new_item'  => __( 'Add New POI',        'enroute_offers' ),
            'edit_item'     => __( 'Edit POI',           'enroute_offers' ),
            'all_items'     => __( 'All POIs',           'enroute_offers' ),
            'menu_name'     => __( 'Points of Interest', 'enroute_offers' ),
        ],
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'menu_position'     => 6,
        'menu_icon'         => 'dashicons-location-alt',
        'supports'          => [ 'title' ],
        'show_in_rest'      => false,
        'capability_type'   => 'post',
    ]);
} );

// ══════════════════════════════════════════════════════════════════════════════
// META BOX
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'add_meta_boxes', function() {
    add_meta_box( 'enroute_poi_details', __( 'POI Details', 'enroute_offers' ),
        'enroute_poi_meta_box_cb', 'poi', 'normal', 'high' );
} );

function enroute_poi_meta_box_cb( WP_Post $post ): void {
    wp_nonce_field( 'enroute_poi_save', 'enroute_poi_nonce' );
    $coordinates = get_post_meta( $post->ID, '_poi_coordinates',  true );
    $description = get_post_meta( $post->ID, '_poi_description',  true );
    $photo_id    = get_post_meta( $post->ID, '_poi_photo_id',     true );
    $language    = get_post_meta( $post->ID, '_poi_language',     true );
    $old_cms_id  = get_post_meta( $post->ID, '_old_cms_id',       true );
    $photo_url   = $photo_id ? wp_get_attachment_image_url( (int) $photo_id, 'thumbnail' ) : '';
    $languages   = [ 'de' => 'Deutsch', 'fr' => 'Français', 'it' => 'Italiano' ];
    ?>
    <div class="enroute-meta-wrap">

        <div class="enroute-field-group">
            <div class="enroute-field">
                <label for="poi_coordinates"><?php esc_html_e( 'Coordinates', 'enroute_offers' ); ?></label>
                <input type="text" id="poi_coordinates" name="poi_coordinates"
                       value="<?php echo esc_attr( $coordinates ); ?>"
                       placeholder="47.3769,8.5417" class="widefat">
                <p class="description"><?php esc_html_e( 'Latitude,Longitude — leave empty to use a random Swiss location.', 'enroute_offers' ); ?></p>
            </div>
            <div class="enroute-field">
                <label for="poi_language"><?php esc_html_e( 'Language', 'enroute_offers' ); ?></label>
                <select id="poi_language" name="poi_language">
                    <option value=""><?php esc_html_e( '— Select —', 'enroute_offers' ); ?></option>
                    <?php foreach ( $languages as $code => $label ) : ?>
                    <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $language, $code ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="enroute-field">
            <label for="poi_description"><?php esc_html_e( 'Description', 'enroute_offers' ); ?></label>
            <textarea id="poi_description" name="poi_description" rows="6" class="widefat"><?php echo esc_textarea( $description ); ?></textarea>
        </div>

        <div class="enroute-field">
            <label><?php esc_html_e( 'Photo', 'enroute_offers' ); ?></label>
            <div id="enroute-poi-photo-preview" style="margin-bottom:0.5rem;">
                <?php if ( $photo_url ) : ?>
                <img src="<?php echo esc_url( $photo_url ); ?>" style="max-width:200px; height:auto;">
                <?php endif; ?>
            </div>
            <input type="hidden" id="poi_photo_id" name="poi_photo_id" value="<?php echo esc_attr( $photo_id ); ?>">
            <button type="button" class="button" id="enroute-poi-photo-btn"><?php esc_html_e( 'Select Photo', 'enroute_offers' ); ?></button>
            <button type="button" class="button" id="enroute-poi-photo-remove" <?php echo $photo_id ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'enroute_offers' ); ?></button>
            <script>
            jQuery(function($){
                var frame;
                $('#enroute-poi-photo-btn').on('click',function(e){
                    e.preventDefault();
                    if(frame){frame.open();return;}
                    frame=wp.media({title:'<?php esc_html_e("Select Photo","enroute_offers"); ?>',button:{text:'<?php esc_html_e("Use this photo","enroute_offers"); ?>'},multiple:false});
                    frame.on('select',function(){
                        var att=frame.state().get('selection').first().toJSON();
                        $('#poi_photo_id').val(att.id);
                        var url=att.sizes&&att.sizes.thumbnail?att.sizes.thumbnail.url:att.url;
                        $('#enroute-poi-photo-preview').html('<img src="'+url+'" style="max-width:200px;height:auto;">');
                        $('#enroute-poi-photo-remove').show();
                    });
                    frame.open();
                });
                $('#enroute-poi-photo-remove').on('click',function(){
                    $('#poi_photo_id').val('');
                    $('#enroute-poi-photo-preview').html('');
                    $(this).hide();
                });
            });
            </script>
        </div>

        <div class="enroute-field">
            <label for="poi_old_cms_id"><?php esc_html_e( 'Old CMS ID', 'enroute_offers' ); ?></label>
            <input type="text" id="poi_old_cms_id" name="poi_old_cms_id"
                   value="<?php echo esc_attr( $old_cms_id ); ?>" class="small-text">
        </div>

    </div>
    <?php
}

// ══════════════════════════════════════════════════════════════════════════════
// SAVE
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'save_post_poi', function( int $post_id ): void {
    if ( ! isset( $_POST['enroute_poi_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['enroute_poi_nonce'], 'enroute_poi_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    update_post_meta( $post_id, '_poi_coordinates', sanitize_text_field( $_POST['poi_coordinates'] ?? '' ) );
    update_post_meta( $post_id, '_poi_description', wp_kses_post( $_POST['poi_description']  ?? '' ) );
    update_post_meta( $post_id, '_poi_photo_id',    absint( $_POST['poi_photo_id']            ?? 0  ) ?: '' );
    $allowed_langs = [ 'de', 'fr', 'it' ];
    $lang = isset( $_POST['poi_language'] ) && in_array( $_POST['poi_language'], $allowed_langs, true )
        ? sanitize_key( $_POST['poi_language'] ) : '';
    update_post_meta( $post_id, '_poi_language', $lang );
    if ( isset( $_POST['poi_old_cms_id'] ) ) {
        update_post_meta( $post_id, '_old_cms_id', sanitize_text_field( $_POST['poi_old_cms_id'] ) );
    }
} );

// ══════════════════════════════════════════════════════════════════════════════
// ADMIN COLUMNS
// ══════════════════════════════════════════════════════════════════════════════

add_filter( 'manage_poi_posts_columns', function( array $cols ): array {
    return array_merge(
        array_slice( $cols, 0, 2 ),
        [ 'poi_language' => __( 'Language', 'enroute_offers' ), 'poi_coords' => __( 'Coordinates', 'enroute_offers' ) ],
        array_slice( $cols, 2 )
    );
} );

add_action( 'manage_poi_posts_custom_column', function( string $col, int $post_id ): void {
    if ( $col === 'poi_language' )
        echo esc_html( strtoupper( get_post_meta( $post_id, '_poi_language', true ) ?: '—' ) );
    if ( $col === 'poi_coords' )
        echo esc_html( get_post_meta( $post_id, '_poi_coordinates', true ) ?: '<span style="color:#9ca3af;">random</span>' );
}, 10, 2 );

// Enqueue media uploader on POI edit screens
add_action( 'admin_enqueue_scripts', function( string $hook ) {
    global $post;
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
    if ( isset( $post ) && $post->post_type === 'poi' ) wp_enqueue_media();
} );
