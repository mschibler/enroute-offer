<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ══════════════════════════════════════════════════════════════════════════════
// POST TYPE
// ══════════════════════════════════════════════════════════════════════════════

function enroute_register_guide_post_type() {
    register_post_type( 'guide', [
        'labels' => [
            'name'               => __( 'Guides',              'enroute_offers' ),
            'singular_name'      => __( 'Guide',               'enroute_offers' ),
            'add_new'            => __( 'Add New',             'enroute_offers' ),
            'add_new_item'       => __( 'Add New Guide',       'enroute_offers' ),
            'edit_item'          => __( 'Edit Guide',          'enroute_offers' ),
            'new_item'           => __( 'New Guide',           'enroute_offers' ),
            'view_item'          => __( 'View Guide',          'enroute_offers' ),
            'search_items'       => __( 'Search Guides',       'enroute_offers' ),
            'not_found'          => __( 'No guides found',     'enroute_offers' ),
            'not_found_in_trash' => __( 'No guides in trash',  'enroute_offers' ),
            'menu_name'          => __( 'Guides',              'enroute_offers' ),
            'all_items'          => __( 'All Guides',          'enroute_offers' ),
        ],
        'public'            => false,   // no front-end single pages
        'publicly_queryable'=> false,
        'show_ui'           => true,
        'show_in_menu'      => true,
        'menu_icon'         => 'dashicons-groups',
        'supports'          => [ 'title' ],  // name only; photo/quote/text via meta boxes
        'show_in_rest'      => false,        // force classic editor
        'has_archive'       => false,
        'rewrite'           => false,
        'capability_type'   => 'post',
    ]);
}
add_action( 'init', 'enroute_register_guide_post_type' );

// ══════════════════════════════════════════════════════════════════════════════
// META BOXES
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'guide_details',
        __( 'Guide Details', 'enroute_offers' ),
        'enroute_guide_details_cb',
        'guide',
        'normal',
        'high'
    );
});

function enroute_guide_details_cb( WP_Post $post ): void {
    wp_nonce_field( 'enroute_guide_save', 'enroute_guide_nonce' );

    $quote    = get_post_meta( $post->ID, '_guide_quote',    true );
    $desc     = get_post_meta( $post->ID, '_guide_description', true );
    $photo_id = get_post_meta( $post->ID, '_guide_photo_id', true );
    $language = get_post_meta( $post->ID, '_guide_language', true );

    $photo_url = $photo_id ? wp_get_attachment_image_url( $photo_id, 'medium' ) : '';
    ?>
    <div class="enroute-meta-wrap font-montserrat">

        <!-- Quote -->
        <div class="enroute-field">
            <label for="guide_quote"><?php esc_html_e( 'Quote', 'enroute_offers' ); ?></label>
            <input
                type="text"
                id="guide_quote"
                name="guide_quote"
                value="<?php echo esc_attr( $quote ); ?>"
                class="widefat"
            >
        </div>

        <!-- Description / Text -->
        <div class="enroute-field">
            <label><?php esc_html_e( 'Text', 'enroute_offers' ); ?></label>
            <?php wp_editor( $desc, 'guide_description', [
                'textarea_name' => 'guide_description',
                'textarea_rows' => 8,
                'media_buttons' => false,
                'teeny'         => true,
                'quicktags'     => true,
            ] ); ?>
        </div>

        <!-- Language -->
        <div class="enroute-field">
            <label for="guide_language"><?php esc_html_e( 'Language', 'enroute_offers' ); ?></label>
            <select id="guide_language" name="guide_language" class="widefat">
                <option value=""><?php esc_html_e( '— Select —', 'enroute_offers' ); ?></option>
                <?php foreach ( enroute_get_languages() as $code => $label ) : ?>
                    <option value="<?php echo esc_attr( $code ); ?>"<?php selected( $language, $code ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Photo -->
        <div class="enroute-field">
            <label><?php esc_html_e( 'Photo', 'enroute_offers' ); ?></label>
            <div class="enroute-media-wrap" id="guide_photo_wrap">
                <?php if ( $photo_url ) : ?>
                    <img id="guide_photo_preview" src="<?php echo esc_url( $photo_url ); ?>"
                         style="max-width:200px; max-height:200px; display:block; margin-bottom:8px; object-fit:cover;">
                <?php else : ?>
                    <img id="guide_photo_preview" src="" style="max-width:200px; max-height:200px; display:none; margin-bottom:8px; object-fit:cover;">
                <?php endif; ?>
                <input type="hidden" id="guide_photo_id" name="guide_photo_id" value="<?php echo esc_attr( (string) $photo_id ); ?>">
                <button type="button" class="button" id="guide_photo_select">
                    <?php esc_html_e( $photo_id ? 'Change Photo' : 'Select Photo', 'enroute_offers' ); ?>
                </button>
                <?php if ( $photo_id ) : ?>
                <button type="button" class="button" id="guide_photo_remove" style="margin-left:4px;">
                    <?php esc_html_e( 'Remove', 'enroute_offers' ); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.enroute-meta-wrap -->

    <script>
    (function($){
        $(function(){
            var frame;
            $('#guide_photo_select').on('click', function(e){
                e.preventDefault();
                if ( frame ) { frame.open(); return; }
                frame = wp.media({ title: '<?php esc_html_e( 'Select Photo', 'enroute_offers' ); ?>', button: { text: '<?php esc_html_e( 'Use this photo', 'enroute_offers' ); ?>' }, multiple: false });
                frame.on('select', function(){
                    var att = frame.state().get('selection').first().toJSON();
                    $('#guide_photo_id').val(att.id);
                    var src = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
                    $('#guide_photo_preview').attr('src', src).show();
                    $('#guide_photo_select').text('<?php esc_html_e( 'Change Photo', 'enroute_offers' ); ?>');
                    if ( !$('#guide_photo_remove').length ) {
                        $('<button type="button" class="button" id="guide_photo_remove" style="margin-left:4px;"><?php esc_html_e( 'Remove', 'enroute_offers' ); ?></button>').insertAfter('#guide_photo_select').on('click', removePhoto);
                    }
                });
                frame.open();
            });
            function removePhoto(e){
                e.preventDefault();
                $('#guide_photo_id').val('');
                $('#guide_photo_preview').attr('src','').hide();
                $('#guide_photo_select').text('<?php esc_html_e( 'Select Photo', 'enroute_offers' ); ?>');
                $(this).remove();
            }
            $('#guide_photo_remove').on('click', removePhoto);
        });
    })(jQuery);
    </script>
    <?php
}

// ══════════════════════════════════════════════════════════════════════════════
// META SAVE
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'save_post_guide', function( int $post_id ): void {
    if ( ! isset( $_POST['enroute_guide_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['enroute_guide_nonce'], 'enroute_guide_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Quote
    $quote = isset( $_POST['guide_quote'] ) ? sanitize_text_field( $_POST['guide_quote'] ) : '';
    update_post_meta( $post_id, '_guide_quote', $quote );

    // Description (allow basic HTML from WYSIWYG editor)
    $desc = isset( $_POST['guide_description'] ) ? wp_kses_post( wp_unslash( $_POST['guide_description'] ) ) : '';
    update_post_meta( $post_id, '_guide_description', $desc );

    // Language
    $allowed_langs = array_keys( enroute_get_languages() );
    $language = isset( $_POST['guide_language'] ) && in_array( $_POST['guide_language'], $allowed_langs, true )
        ? sanitize_key( $_POST['guide_language'] ) : '';
    update_post_meta( $post_id, '_guide_language', $language );

    // Photo
    $photo_id = isset( $_POST['guide_photo_id'] ) ? absint( $_POST['guide_photo_id'] ) : 0;
    update_post_meta( $post_id, '_guide_photo_id', $photo_id );
});

// ══════════════════════════════════════════════════════════════════════════════
// ADMIN: enqueue media uploader on guide edit screens
// ══════════════════════════════════════════════════════════════════════════════

add_action( 'admin_enqueue_scripts', function( string $hook ) {
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'guide' ) return;
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) return;
    wp_enqueue_media();
    wp_enqueue_style( 'enroute-admin', ENROUTE_OFFERS_URL . 'admin/admin.css', [], ENROUTE_OFFERS_VERSION );
});
