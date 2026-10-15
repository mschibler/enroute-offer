<?php
/**
 * Featured offers as one ordered list.
 *
 * - Offer form: "Featured" yes / no
 * - Offers → Featured Angebote: all featured offers, drag & drop order,
 *   add (goes to position 1, the others move back) and remove
 * - Per language (offer field "Language"): position 1 of that language =
 *   "Featured #1" on the pages of that language, etc. Blocks offer #1–12,
 *   the list shows all.
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

/** Language of an offer (field "Language"), '' if none. */
function enroute_featured_offer_lang( int $offer_id ): string {
    return (string) get_post_meta( $offer_id, '_offer_language', true );
}

/** Featured offers of one language, in order. */
function enroute_featured_offer_ids_for( string $lang ): array {
    return array_values( array_filter( enroute_featured_offer_ids(), fn( $id ) => enroute_featured_offer_lang( $id ) === $lang ) );
}

/** Language whose featured list is used: given, else current page language, else default. */
function enroute_featured_current_lang(): string {
    $lang = '';
    if ( function_exists( 'pll_current_language' ) ) $lang = (string) pll_current_language();
    // Block preview in the editor (REST): language of the edited page
    if ( ! $lang && ! empty( $_GET['post_id'] ) && function_exists( 'pll_get_post_language' ) ) $lang = (string) pll_get_post_language( absint( $_GET['post_id'] ) );
    if ( ! $lang && function_exists( 'pll_default_language' ) ) $lang = (string) pll_default_language();
    return $lang ?: substr( get_locale(), 0, 2 );
}

/** Offer at featured position $n (1-based) of a language (current if empty), 0 if none. */
function enroute_featured_offer_id( int $n, string $lang = '' ): int {
    $ids = enroute_featured_offer_ids_for( $lang ?: enroute_featured_current_lang() );
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
    // Tabs: languages of the website (Polylang), so no tab for unused languages (e.g. English)
    $languages = enroute_get_languages();
    $site      = function_exists( 'pll_languages_list' ) ? (array) pll_languages_list( [ 'fields' => 'slug' ] ) : [ 'de', 'fr', 'it' ];
    $languages = array_intersect_key( $languages, array_flip( $site ) ) ?: $languages;
    $all_ids   = enroute_featured_offer_ids();
    $no_lang   = array_values( array_filter( $all_ids, fn( $id ) => ! array_key_exists( enroute_featured_offer_lang( $id ), $languages ) ) );
    $tabs      = $languages + ( $no_lang ? [ 'none' => __( 'Ohne Sprache', 'enroute_offers' ) ] : [] );
    $lang      = sanitize_key( $_GET['lang'] ?? '' );
    if ( ! isset( $tabs[ $lang ] ) ) $lang = (string) array_key_first( $languages );
    $ids       = $lang === 'none' ? $no_lang : enroute_featured_offer_ids_for( $lang );

    $others = $lang === 'none' ? [] : get_posts( [
        'post_type'    => 'offer',
        'post_status'  => [ 'publish', 'draft', 'pending', 'private', 'future' ],
        'numberposts'  => -1,
        'orderby'      => 'title',
        'order'        => 'ASC',
        'post__not_in' => $all_ids ?: [ 0 ],
        'meta_query'   => [ [ 'key' => '_offer_language', 'value' => $lang ] ],
        'lang'         => '', // offers are language-independent in Polylang
    ] );
    $nonce    = wp_create_nonce( 'enroute_featured' );
    $base_url = admin_url( 'edit.php?post_type=offer&page=enroute-featured-offers' );
    ?>
    <div class="wrap enroute-featured">
        <h1><?php esc_html_e( 'Featured Angebote', 'enroute_offers' ); ?></h1>
        <p class="description" style="max-width:760px;">
            <?php printf(
                esc_html__( 'Jede Sprache hat ihre eigene Liste (nach dem Feld «Sprache» des Angebots). Reihenfolge per Drag & Drop ändern. Position 1 ist «Featured #1» in den Blöcken der Seiten dieser Sprache usw. In den Blöcken wählbar sind die Positionen 1–%d; weiter unten stehende Angebote bleiben featured und können jederzeit nach oben gezogen werden. Neu hinzugefügte Angebote kommen auf Position 1.', 'enroute_offers' ),
                ENROUTE_FEATURED_SLOTS
            ); ?>
        </p>

        <nav class="nav-tab-wrapper" style="margin-top:1rem;">
            <?php foreach ( $tabs as $code => $label ) :
                $count = $code === 'none' ? count( $no_lang ) : count( enroute_featured_offer_ids_for( $code ) ); ?>
            <a href="<?php echo esc_url( add_query_arg( 'lang', $code, $base_url ) ); ?>" class="nav-tab<?php echo $code === $lang ? ' nav-tab-active' : ''; ?>">
                <?php echo esc_html( $label ); ?> <span style="opacity:.6;">(<?php echo (int) $count; ?>)</span>
            </a>
            <?php endforeach; ?>
        </nav>

        <?php if ( isset( $_GET['updated'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Gespeichert.', 'enroute_offers' ); ?></p></div>
        <?php endif; ?>

        <?php if ( $lang === 'none' ) : ?>
        <p style="color:#b32d2e; margin:1rem 0;"><?php esc_html_e( 'Diese Angebote sind featured, haben aber keine Sprache und werden deshalb nirgends angezeigt. Bitte im Angebot eine Sprache wählen oder hier entfernen.', 'enroute_offers' ); ?></p>
        <?php else : ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:1.25rem 0; display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
            <input type="hidden" name="action" value="enroute_featured_add">
            <input type="hidden" name="lang" value="<?php echo esc_attr( $lang ); ?>">
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
        <?php endif; ?>

        <?php if ( ! $ids ) : ?>
            <p><em><?php esc_html_e( 'Noch keine Featured Angebote in dieser Sprache.', 'enroute_offers' ); ?></em></p>
        <?php else : ?>
        <table class="widefat striped enroute-featured-table" style="max-width:900px;">
            <thead>
                <tr>
                    <th style="width:28px;"></th>
                    <th style="width:70px;"><?php esc_html_e( 'Position', 'enroute_offers' ); ?></th>
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
                $remove_url = wp_nonce_url( admin_url( 'admin-post.php?action=enroute_featured_remove&lang=' . $lang . '&offer_id=' . $id ), 'enroute_featured' );
            ?>
                <tr data-id="<?php echo (int) $id; ?>">
                    <td class="enroute-featured-handle" style="cursor:move; color:#8c8f94;"><span class="dashicons dashicons-menu"></span></td>
                    <td><strong class="enroute-featured-pos"></strong></td>
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
    $current = enroute_featured_list_raw();
    // The sent IDs (one language) get the new order in the places they already
    // take in the overall list; other languages stay where they are.
    $new     = array_values( array_intersect( array_map( 'absint', (array) ( $_POST['ids'] ?? [] ) ), $current ) );
    $queue   = $new;
    foreach ( $current as $i => $id ) {
        if ( in_array( $id, $new, true ) ) $current[ $i ] = array_shift( $queue );
    }
    enroute_featured_list_save( $current );
    wp_send_json_success();
} );

add_action( 'admin_post_enroute_featured_add', function() {
    check_admin_referer( 'enroute_featured' );
    if ( ! current_user_can( 'edit_posts' ) ) wp_die( esc_html__( 'Keine Berechtigung.', 'enroute_offers' ) );
    $id = absint( $_POST['offer_id'] ?? 0 );
    if ( $id && get_post_type( $id ) === 'offer' ) enroute_featured_add( $id );
    wp_safe_redirect( admin_url( 'edit.php?post_type=offer&page=enroute-featured-offers&updated=1&lang=' . sanitize_key( $_REQUEST['lang'] ?? '' ) ) );
    exit;
} );

add_action( 'admin_post_enroute_featured_remove', function() {
    check_admin_referer( 'enroute_featured' );
    if ( ! current_user_can( 'edit_posts' ) ) wp_die( esc_html__( 'Keine Berechtigung.', 'enroute_offers' ) );
    $id = absint( $_GET['offer_id'] ?? 0 );
    if ( $id ) enroute_featured_remove( $id );
    wp_safe_redirect( admin_url( 'edit.php?post_type=offer&page=enroute-featured-offers&updated=1&lang=' . sanitize_key( $_REQUEST['lang'] ?? '' ) ) );
    exit;
} );
