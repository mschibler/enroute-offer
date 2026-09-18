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

// ══════════════════════════════════════════════════════════════════════════════
// OFFER TYPE — colour field
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Render colour field on Add Term screen.
 */
add_action( 'offer_type_add_form_fields', function() {
    ?>
    <div class="form-field">
        <label for="offer_type_color"><?php esc_html_e( 'Colour', 'enroute_offers' ); ?></label>
        <div style="display:flex; align-items:center; gap:0.75rem;">
            <input type="color" id="offer_type_color" name="offer_type_color" value="#dbe442"
                   style="width:48px; height:32px; padding:2px; border:1px solid #ccc; cursor:pointer;">
            <input type="text" name="offer_type_color_hex" value="#dbe442"
                   class="small-text" maxlength="7" placeholder="#000000" style="font-family:monospace;"
                   oninput="document.getElementById('offer_type_color').value=this.value">
        </div>
        <p><?php esc_html_e( 'Used as the background colour of the offer card band in listings and on the detail page.', 'enroute_offers' ); ?></p>
    </div>
    <?php
});

/**
 * Render colour field on Edit Term screen.
 */
add_action( 'offer_type_edit_form_fields', function( WP_Term $term ) {
    $color = get_term_meta( $term->term_id, 'offer_type_color', true ) ?: '#dbe442';
    ?>
    <tr class="form-field">
        <th scope="row">
            <label for="offer_type_color"><?php esc_html_e( 'Colour', 'enroute_offers' ); ?></label>
        </th>
        <td>
            <div style="display:flex; align-items:center; gap:0.75rem;">
                <input type="color" id="offer_type_color" name="offer_type_color" value="<?php echo esc_attr( $color ); ?>"
                       style="width:48px; height:32px; padding:2px; border:1px solid #ccc; cursor:pointer;">
                <input type="text" name="offer_type_color_hex" value="<?php echo esc_attr( $color ); ?>"
                       class="small-text" maxlength="7" placeholder="#000000" style="font-family:monospace;"
                       oninput="document.getElementById('offer_type_color').value=this.value">
                <span style="display:inline-block; width:32px; height:32px; background:<?php echo esc_attr( $color ); ?>; border:1px solid #ccc;" id="offer_type_color_preview"></span>
            </div>
            <p class="description"><?php esc_html_e( 'Used as the background colour of the offer card band in listings and on the detail page.', 'enroute_offers' ); ?></p>
        </td>
    </tr>
    <?php
});

/**
 * Save colour field when term is created or edited.
 */
add_action( 'created_offer_type', 'enroute_save_offer_type_color' );
add_action( 'edited_offer_type',  'enroute_save_offer_type_color' );

function enroute_save_offer_type_color( int $term_id ): void {
    // Prefer hex text field (more reliable than color picker on all browsers)
    $color = isset( $_POST['offer_type_color_hex'] )
        ? sanitize_hex_color( $_POST['offer_type_color_hex'] )
        : ( isset( $_POST['offer_type_color'] ) ? sanitize_hex_color( $_POST['offer_type_color'] ) : '' );

    if ( $color ) {
        update_term_meta( $term_id, 'offer_type_color', $color );
    }
}

/**
 * Show colour swatch in the offer_type term list table.
 */
add_filter( 'manage_edit-offer_type_columns', function( $columns ) {
    $columns['color'] = __( 'Colour', 'enroute_offers' );
    return $columns;
});

add_filter( 'manage_offer_type_custom_column', function( $content, $column, $term_id ) {
    if ( $column === 'color' ) {
        $color = get_term_meta( $term_id, 'offer_type_color', true ) ?: '#dbe442';
        return '<span style="display:inline-block; width:24px; height:24px; background:' . esc_attr( $color ) . '; border:1px solid #ccc; border-radius:3px; vertical-align:middle;"></span> <code>' . esc_html( $color ) . '</code>';
    }
    return $content;
}, 10, 3 );

/**
 * Helper: get offer_type colour for a given offer post ID.
 * Falls back to palette colour if no offer_type or no colour set.
 */
function enroute_get_offer_color( int $post_id, int $fallback_index = 0 ): string {
    $palette  = [ '#dbe442', '#fce300', '#fed141', '#ff6a39', '#ef4a81' ];
    $fallback = $palette[ $fallback_index % count( $palette ) ];

    $terms = get_the_terms( $post_id, 'offer_type' );
    if ( ! $terms || is_wp_error( $terms ) ) return $fallback;

    $color = get_term_meta( $terms[0]->term_id, 'offer_type_color', true );
    return $color ?: $fallback;
}
