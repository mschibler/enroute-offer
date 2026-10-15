<?php
/**
 * Featured offers as one ordered list.
 *
 * - Offer form: "Featured" yes / no
 * - Offers → Featured Angebote: all featured offers, drag & drop order,
 *   add (goes to position 1, the others move back) and remove
 * - Position 1 of the list = "Featured #1" in the blocks / shortcode, etc.
 *   Blocks offer #1–12, the list shows all.
 *
 * Storage: option enroute_featured_offers = ordered offer IDs (the order),
 * post meta _offer_featured = '1' for featured offers (the yes/no field).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

const ENROUTE_FEATURED_OPTION = 'enroute_featured_offers';
const ENROUTE_FEATURED_SLOTS  = 12; // positions selectable in the blocks

/** Ordered list as stored (IDs). */
function enroute_featured_list_raw(): array {
    $list = get_option( ENROUTE_FEATURED_OPTION, null );
    if ( ! is_array( $list ) ) $list = enroute_featured_migrate();
    return array_values( array_unique( array_filter( array_map( 'intval', $list ) ) ) );
}

function enroute_featured_list_save( array $ids ): void {
    update_option( ENROUTE_FEATURED_OPTION, array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) ), false );
}

/**
 * One-time migration from the old "Featured 1–12" field: offers sorted by
 * their old number (then newest first), meta set to yes.
 */
function enroute_featured_migrate(): array {
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT p.ID, pm.meta_value FROM {$wpdb->posts} p
         JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_offer_featured'
         WHERE p.post_type = 'offer' AND p.post_status NOT IN ('trash','auto-draft') AND pm.meta_value <> ''
         ORDER BY CAST(pm.meta_value AS UNSIGNED) ASC, p.post_date DESC"
    );
    $ids = [];
    foreach ( $rows as $r ) {
        $ids[] = (int) $r->ID;
        update_post_meta( (int) $r->ID, '_offer_featured', '1' );
    }
    enroute_featured_list_save( $ids );
    return $ids;
}

/** Featured offers in order (offers that no longer exist or are in the trash are left out). */
function enroute_featured_offer_ids(): array {
    return array_values( array_filter( enroute_featured_list_raw(), function( $id ) {
        $p = get_post( $id );
        return $p && $p->post_type === 'offer' && ! in_array( $p->post_status, [ 'trash', 'auto-draft' ], true );
    } ) );
}

/** Offer at featured position $n (1-based), 0 if none. */
function enroute_featured_offer_id( int $n ): int {
    $ids = enroute_featured_offer_ids();
    return $n >= 1 ? (int) ( $ids[ $n - 1 ] ?? 0 ) : 0;
}

/** Offer is featured (yes / no). */
function enroute_offer_is_featured( int $offer_id ): bool {
    return in_array( $offer_id, enroute_featured_list_raw(), true );
}

/** Add an offer at position 1 (others move back one). */
function enroute_featured_add( int $offer_id ): void {
    $list = array_values( array_diff( enroute_featured_list_raw(), [ $offer_id ] ) );
    array_unshift( $list, $offer_id );
    enroute_featured_list_save( $list );
    update_post_meta( $offer_id, '_offer_featured', '1' );
}

function enroute_featured_remove( int $offer_id ): void {
    enroute_featured_list_save( array_diff( enroute_featured_list_raw(), [ $offer_id ] ) );
    update_post_meta( $offer_id, '_offer_featured', '' );
}

// Deleted offers leave the list
add_action( 'before_delete_post', function( $post_id ) {
    if ( get_post_type( $post_id ) === 'offer' && enroute_offer_is_featured( (int) $post_id ) ) {
        enroute_featured_list_save( array_diff( enroute_featured_list_raw(), [ (int) $post_id ] ) );
    }
} );

// ── Admin page: Offers → Featured Angebote ──────────────────────────────────
add_action( 'admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=offer',
        __( 'Featured Angebote', 'enroute_offers' ),
        __( 'Featured Angebote', 'enroute_offers' ),
        'edit_posts',
        'enroute-featured-offers',
        'enroute_featured_offers_page'
    );
} );

function enroute_featured_offers_page() {
    $ids      = enroute_featured_offer_ids();
    $others   = get_posts( [
        'post_type'    => 'offer',
        'post_status'  => [ 'publish', 'draft', 'pending', 'private', 'future' ],
        'numberposts'  => -1,
        'orderby'      => 'title',
        'order'        => 'ASC',
        'post__not_in' => $ids ?: [ 0 ],
        'lang'         => '', // offers are language-independent
    ] );
    $nonce    = wp_create_nonce( 'enroute_featured' );
    ?>
    <div class="wrap enroute-featured">
        <h1><?php esc_html_e( 'Featured Angebote', 'enroute_offers' ); ?></h1>
        <p class="description" style="max-width:760px;">
            <?php printf(
                esc_html__( 'Reihenfolge per Drag & Drop ändern. Position 1 ist «Featured #1» in den Blöcken der Startseite usw. In den Blöcken wählbar sind die Positionen 1–%d; weiter unten stehende Angebote bleiben featured und können jederzeit nach oben gezogen werden. Neu hinzugefügte Angebote kommen auf Position 1.', 'enroute_offers' ),
                ENROUTE_FEATURED_SLOTS
            ); ?>
        </p>

        <?php if ( isset( $_GET['updated'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Gespeichert.', 'enroute_offers' ); ?></p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:1.25rem 0; display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
            <input type="hidden" name="action" value="enroute_featured_add">
            <?php wp_nonce_field( 'enroute_featured' ); ?>
            <label for="enroute-featured-add"><strong><?php esc_html_e( 'Angebot hinzufügen:', 'enroute_offers' ); ?></strong></label>
            <select id="enroute-featured-add" name="offer_id" style="min-width:320px; max-width:100%;">
                <option value=""><?php esc_html_e( '— Angebot wählen —', 'enroute_offers' ); ?></option>
                <?php foreach ( $others as $o ) : ?>
                <option value="<?php echo (int) $o->ID; ?>"><?php echo esc_html( html_entity_decode( get_the_title( $o ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) . ( $o->post_status !== 'publish' ? ' (' . get_post_status_object( $o->post_status )->label . ')' : '' ) ); ?></option>
                <?php endforeach; ?>
            </select>
            <?php submit_button( __( 'Hinzufügen (auf Position 1)', 'enroute_offers' ), 'primary', 'submit', false ); ?>
        </form>

        <?php if ( ! $ids ) : ?>
            <p><em><?php esc_html_e( 'Noch keine Featured Angebote.', 'enroute_offers' ); ?></em></p>
        <?php else : ?>
        <table class="widefat striped enroute-featured-table" style="max-width:900px;">
            <thead>
                <tr>
                    <th style="width:28px;"></th>
                    <th style="width:70px;"><?php esc_html_e( 'Position', 'enroute_offers' ); ?></th>
                    <th style="width:90px;"><?php esc_html_e( 'Bild', 'enroute_offers' ); ?></th>
                    <th><?php esc_html_e( 'Angebot', 'enroute_offers' ); ?></th>
                    <th style="width:110px;"></th>
                </tr>
            </thead>
            <tbody id="enroute-featured-list">
            <?php foreach ( $ids as $id ) :
                $p        = get_post( $id );
                $warnings = [];
                if ( $p->post_status !== 'publish' ) $warnings[] = get_post_status_object( $p->post_status )->label;
                if ( function_exists( 'enroute_offer_is_active' ) && ! enroute_offer_is_active( $id ) ) $warnings[] = __( 'Inaktiv', 'enroute_offers' );
                $remove_url = wp_nonce_url( admin_url( 'admin-post.php?action=enroute_featured_remove&offer_id=' . $id ), 'enroute_featured' );
            ?>
                <tr data-id="<?php echo (int) $id; ?>">
                    <td class="enroute-featured-handle" style="cursor:move; color:#8c8f94;"><span class="dashicons dashicons-menu"></span></td>
                    <td><strong class="enroute-featured-pos"></strong></td>
                    <td><?php echo get_the_post_thumbnail( $id, [ 80, 50 ], [ 'style' => 'width:80px; height:50px; object-fit:cover; display:block;' ] ); ?></td>
                    <td>
                        <a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><strong><?php echo esc_html( html_entity_decode( get_the_title( $id ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ); ?></strong></a>
                        <?php if ( $warnings ) : ?>
                        <br><span style="color:#b32d2e;">⚠ <?php echo esc_html( implode( ', ', $warnings ) ); ?> – <?php esc_html_e( 'wird auf der Website nicht angezeigt', 'enroute_offers' ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;"><a href="<?php echo esc_url( $remove_url ); ?>" class="button button-small"><?php esc_html_e( 'Entfernen', 'enroute_offers' ); ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="enroute-featured-saved" style="display:none; color:#2e7d32;">✓ <?php esc_html_e( 'Reihenfolge gespeichert.', 'enroute_offers' ); ?></p>
        <?php endif; ?>
    </div>

    <style>
        .enroute-featured-table tr.ui-sortable-helper { background:#fff; box-shadow:0 2px 8px rgba(0,0,0,.15); display:table; }
        .enroute-featured-table tr.is-beyond td { opacity:.6; }
        .enroute-featured-table tr.enroute-featured-cut td { border-top:3px solid #2271b1; }
    </style>
    <script>
    jQuery(function ($) {
        var $list = $('#enroute-featured-list'), slots = <?php echo (int) ENROUTE_FEATURED_SLOTS; ?>;
        function renumber() {
            $list.children('tr').each(function (i) {
                $(this).find('.enroute-featured-pos').text('#' + (i + 1));
                $(this).toggleClass('is-beyond', i >= slots).toggleClass('enroute-featured-cut', i === slots);
            });
        }
        renumber();
        $list.sortable({
            items: 'tr', axis: 'y', handle: '.enroute-featured-handle', cursor: 'move',
            helper: function (e, tr) { tr.children().each(function () { $(this).width($(this).width()); }); return tr; },
            update: function () {
                renumber();
                var ids = $list.children('tr').map(function () { return $(this).data('id'); }).get();
                $.post(ajaxurl, { action: 'enroute_featured_sort', nonce: <?php echo wp_json_encode( $nonce ); ?>, ids: ids }, function (r) {
                    if (r && r.success) $('.enroute-featured-saved').stop(true).show().delay(1500).fadeOut();
                });
            }
        });
    });
    </script>
    <?php
}

add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'enroute-featured-offers' ) wp_enqueue_script( 'jquery-ui-sortable' );
} );

add_action( 'wp_ajax_enroute_featured_sort', function() {
    check_ajax_referer( 'enroute_featured', 'nonce' );
    if ( ! current_user_can( 'edit_posts' ) ) wp_send_json_error( null, 403 );
    $new     = array_map( 'absint', (array) ( $_POST['ids'] ?? [] ) );
    $current = enroute_featured_list_raw();
    // keep only offers that are in the list; entries not sent (e.g. trashed) stay at the end
    $new = array_values( array_intersect( $new, $current ) );
    enroute_featured_list_save( array_merge( $new, array_diff( $current, $new ) ) );
    wp_send_json_success();
} );

add_action( 'admin_post_enroute_featured_add', function() {
    check_admin_referer( 'enroute_featured' );
    if ( ! current_user_can( 'edit_posts' ) ) wp_die( esc_html__( 'Keine Berechtigung.', 'enroute_offers' ) );
    $id = absint( $_POST['offer_id'] ?? 0 );
    if ( $id && get_post_type( $id ) === 'offer' ) enroute_featured_add( $id );
    wp_safe_redirect( admin_url( 'edit.php?post_type=offer&page=enroute-featured-offers&updated=1' ) );
    exit;
} );

add_action( 'admin_post_enroute_featured_remove', function() {
    check_admin_referer( 'enroute_featured' );
    if ( ! current_user_can( 'edit_posts' ) ) wp_die( esc_html__( 'Keine Berechtigung.', 'enroute_offers' ) );
    $id = absint( $_GET['offer_id'] ?? 0 );
    if ( $id ) enroute_featured_remove( $id );
    wp_safe_redirect( admin_url( 'edit.php?post_type=offer&page=enroute-featured-offers&updated=1' ) );
    exit;
} );
