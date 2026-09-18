<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ══════════════════════════════════════════════════════════════════════════════
// USERPASS TYPE CPT — the pass products available for booking
// ══════════════════════════════════════════════════════════════════════════════

function enroute_register_userpass_cpt() {
    register_post_type( 'enroute_userpass', [
        'labels' => [
            'name'               => __( 'User Passes',          'enroute_offers' ),
            'singular_name'      => __( 'User Pass',            'enroute_offers' ),
            'add_new_item'       => __( 'New User Pass',        'enroute_offers' ),
            'edit_item'          => __( 'Edit User Pass',       'enroute_offers' ),
            'all_items'          => __( 'All User Passes',      'enroute_offers' ),
            'menu_name'          => __( 'User Passes',          'enroute_offers' ),
        ],
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => 'edit.php?post_type=offer',
        'supports'          => [ 'title' ],
        'show_in_rest'      => false,
        'capability_type'   => 'post',
    ]);
}
add_action( 'init', 'enroute_register_userpass_cpt' );

// ── Meta box ──────────────────────────────────────────────────────────────────

add_action( 'add_meta_boxes', function() {
    add_meta_box( 'enroute_userpass_details', __( 'Pass Details', 'enroute_offers' ),
        'enroute_userpass_details_cb', 'enroute_userpass', 'normal', 'high' );
});

function enroute_userpass_details_cb( WP_Post $post ): void {
    wp_nonce_field( 'enroute_userpass_save', 'enroute_userpass_nonce' );
    $description = get_post_meta( $post->ID, '_userpass_description', true );
    $valid_till  = get_post_meta( $post->ID, '_userpass_valid_till',  true );
    $credit      = get_post_meta( $post->ID, '_userpass_credit',      true );
    $language    = get_post_meta( $post->ID, '_userpass_language',    true );
    ?>
    <div class="enroute-meta-wrap">
        <div class="enroute-field">
            <label for="userpass_description"><?php esc_html_e( 'Description', 'enroute_offers' ); ?></label>
            <textarea id="userpass_description" name="userpass_description" rows="4" class="widefat"><?php echo esc_textarea( $description ); ?></textarea>
        </div>
        <div class="enroute-field-group">
            <div class="enroute-field">
                <label for="userpass_valid_till"><?php esc_html_e( 'Valid Till', 'enroute_offers' ); ?></label>
                <input type="date" id="userpass_valid_till" name="userpass_valid_till" value="<?php echo esc_attr( $valid_till ); ?>">
                <p class="description"><?php esc_html_e( 'Default validity date when a pass is booked.', 'enroute_offers' ); ?></p>
            </div>
            <div class="enroute-field">
                <label for="userpass_credit"><?php esc_html_e( 'Credit', 'enroute_offers' ); ?></label>
                <input type="text" id="userpass_credit" name="userpass_credit" value="<?php echo esc_attr( $credit ); ?>" placeholder="<?php esc_attr_e( 'e.g. 350 CHF', 'enroute_offers' ); ?>">
            </div>
        </div>
        <div class="enroute-field">
            <label for="userpass_language"><?php esc_html_e( 'Language', 'enroute_offers' ); ?></label>
            <select id="userpass_language" name="userpass_language" class="widefat">
                <option value=""><?php esc_html_e( '— Select —', 'enroute_offers' ); ?></option>
                <?php foreach ( enroute_get_languages() as $code => $label ) : ?>
                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $language, $code ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php
}

add_action( 'save_post_enroute_userpass', function( int $post_id ): void {
    if ( ! isset( $_POST['enroute_userpass_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['enroute_userpass_nonce'], 'enroute_userpass_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    update_post_meta( $post_id, '_userpass_description', sanitize_textarea_field( $_POST['userpass_description'] ?? '' ) );
    update_post_meta( $post_id, '_userpass_valid_till',  sanitize_text_field( $_POST['userpass_valid_till']  ?? '' ) );
    update_post_meta( $post_id, '_userpass_credit',      sanitize_text_field( $_POST['userpass_credit']      ?? '' ) );
    $allowed_langs = array_keys( enroute_get_languages() );
    $lang = isset( $_POST['userpass_language'] ) && in_array( $_POST['userpass_language'], $allowed_langs, true )
        ? sanitize_key( $_POST['userpass_language'] ) : '';
    update_post_meta( $post_id, '_userpass_language', $lang );
});

// ══════════════════════════════════════════════════════════════════════════════
// BOOKED PASS CPT — individual passes booked by users
// ══════════════════════════════════════════════════════════════════════════════

function enroute_register_booked_pass_cpt() {
    register_post_type( 'enroute_booked_pass', [
        'labels' => [
            'name'               => __( 'Booked Passes',         'enroute_offers' ),
            'singular_name'      => __( 'Booked Pass',           'enroute_offers' ),
            'edit_item'          => __( 'Edit Booked Pass',      'enroute_offers' ),
            'all_items'          => __( 'All Booked Passes',     'enroute_offers' ),
            'menu_name'          => __( 'Booked Passes',         'enroute_offers' ),
            'search_items'       => __( 'Search Booked Passes',  'enroute_offers' ),
        ],
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => 'edit.php?post_type=offer',
        'supports'          => [ 'title' ],
        'show_in_rest'      => false,
        'capability_type'   => 'post',
    ]);
}
add_action( 'init', 'enroute_register_booked_pass_cpt' );

// ── Meta box for booked pass (admin editable: credit, valid_till) ──────────────

add_action( 'add_meta_boxes', function() {
    add_meta_box( 'enroute_booked_pass_details', __( 'Pass Details', 'enroute_offers' ),
        'enroute_booked_pass_details_cb', 'enroute_booked_pass', 'normal', 'high' );
});

function enroute_booked_pass_details_cb( WP_Post $post ): void {
    wp_nonce_field( 'enroute_booked_pass_save', 'enroute_booked_pass_nonce' );
    $pass_id     = get_post_meta( $post->ID, '_booked_pass_type_id',  true );
    $user_id     = get_post_meta( $post->ID, '_booked_pass_user_id',  true );
    $valid_till  = get_post_meta( $post->ID, '_booked_pass_valid_till', true );
    $credit      = get_post_meta( $post->ID, '_booked_pass_credit',   true );
    $email       = get_post_meta( $post->ID, '_booked_pass_email',    true );
    $submitted   = get_post_meta( $post->ID, '_booked_pass_submitted', true );

    $pass_name   = $pass_id ? get_the_title( $pass_id ) : '—';
    $user        = $user_id ? get_userdata( (int) $user_id ) : null;
    $user_name   = $user ? $user->display_name . ' (' . $user->user_email . ')' : ( $email ?: '—' );
    ?>
    <div class="enroute-meta-wrap">
        <table class="form-table" style="margin:0 0 1rem;">
            <tr><th><?php esc_html_e( 'Pass Type', 'enroute_offers' ); ?></th><td><?php echo esc_html( $pass_name ); ?></td></tr>
            <tr><th><?php esc_html_e( 'User', 'enroute_offers' ); ?></th><td><?php echo esc_html( $user_name ); ?></td></tr>
            <tr><th><?php esc_html_e( 'Booked', 'enroute_offers' ); ?></th><td><?php echo esc_html( $submitted ); ?></td></tr>
        </table>
        <div class="enroute-field-group">
            <div class="enroute-field">
                <label for="booked_pass_valid_till"><strong><?php esc_html_e( 'Valid Till', 'enroute_offers' ); ?></strong></label>
                <input type="date" id="booked_pass_valid_till" name="booked_pass_valid_till"
                       value="<?php echo esc_attr( $valid_till ); ?>" class="widefat">
            </div>
            <div class="enroute-field">
                <label for="booked_pass_credit"><strong><?php esc_html_e( 'Credit', 'enroute_offers' ); ?></strong></label>
                <input type="text" id="booked_pass_credit" name="booked_pass_credit"
                       value="<?php echo esc_attr( $credit ); ?>" class="widefat">
            </div>
        </div>
    </div>
    <?php
}

add_action( 'save_post_enroute_booked_pass', function( int $post_id ): void {
    if ( ! isset( $_POST['enroute_booked_pass_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['enroute_booked_pass_nonce'], 'enroute_booked_pass_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    update_post_meta( $post_id, '_booked_pass_valid_till', sanitize_text_field( $_POST['booked_pass_valid_till'] ?? '' ) );
    update_post_meta( $post_id, '_booked_pass_credit',     sanitize_text_field( $_POST['booked_pass_credit']     ?? '' ) );
});

// ── Custom columns for booked passes list ─────────────────────────────────────

add_filter( 'manage_enroute_booked_pass_posts_columns', function( $cols ) {
    return [
        'cb'                  => $cols['cb'],
        'title'               => __( 'Pass',         'enroute_offers' ),
        'booked_pass_user'    => __( 'User',          'enroute_offers' ),
        'booked_pass_valid'   => __( 'Valid Till',    'enroute_offers' ),
        'booked_pass_credit'  => __( 'Credit',        'enroute_offers' ),
        'booked_pass_status'  => __( 'Status',        'enroute_offers' ),
        'date'                => __( 'Booked',        'enroute_offers' ),
    ];
});

add_action( 'manage_enroute_booked_pass_posts_custom_column', function( $col, $post_id ) {
    switch ( $col ) {
        case 'booked_pass_user':
            $user_id = get_post_meta( $post_id, '_booked_pass_user_id', true );
            $email   = get_post_meta( $post_id, '_booked_pass_email',   true );
            if ( $user_id ) {
                $user = get_userdata( (int) $user_id );
                echo esc_html( $user ? $user->display_name : "User #$user_id" );
                echo '<br><small>' . esc_html( $user ? $user->user_email : $email ) . '</small>';
            } else {
                echo esc_html( $email ?: '—' );
            }
            break;
        case 'booked_pass_valid':
            $d = get_post_meta( $post_id, '_booked_pass_valid_till', true );
            echo esc_html( $d ? date_i18n( 'd.m.Y', strtotime( $d ) ) : '—' );
            break;
        case 'booked_pass_credit':
            echo esc_html( get_post_meta( $post_id, '_booked_pass_credit', true ) ?: '—' );
            break;
        case 'booked_pass_status':
            $valid_till = get_post_meta( $post_id, '_booked_pass_valid_till', true );
            if ( ! $valid_till ) {
                echo '<span style="color:#6b7280;">—</span>';
            } elseif ( strtotime( $valid_till ) >= time() ) {
                echo '<span style="color:#166534; font-weight:600;">' . esc_html__( 'Valid', 'enroute_offers' ) . '</span>';
            } else {
                echo '<span style="color:#991b1b;">' . esc_html__( 'Expired', 'enroute_offers' ) . '</span>';
            }
            break;
    }
}, 10, 2 );

// ── Sortable valid_till column ────────────────────────────────────────────────

add_filter( 'manage_edit-enroute_booked_pass_sortable_columns', function( $cols ) {
    $cols['booked_pass_valid'] = 'booked_pass_valid';
    return $cols;
});

add_action( 'pre_get_posts', function( WP_Query $query ) {
    if ( ! is_admin() || $query->get('post_type') !== 'enroute_booked_pass' ) return;
    if ( $query->get('orderby') === 'booked_pass_valid' ) {
        $query->set( 'meta_key', '_booked_pass_valid_till' );
        $query->set( 'orderby',  'meta_value' );
    }
});

// ── Admin search by user email / pass name ────────────────────────────────────

add_action( 'restrict_manage_posts', function() {
    global $typenow;
    if ( $typenow !== 'enroute_booked_pass' ) return;

    // Pass name filter
    $passes = get_posts([ 'post_type' => 'enroute_userpass', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ]);
    $selected = $_GET['filter_pass_id'] ?? '';
    echo '<select name="filter_pass_id"><option value="">' . esc_html__( 'All Pass Types', 'enroute_offers' ) . '</option>';
    foreach ( $passes as $p ) {
        echo '<option value="' . esc_attr( $p->ID ) . '"' . selected( $selected, $p->ID, false ) . '>' . esc_html( $p->post_title ) . '</option>';
    }
    echo '</select>';

    // Validity filter
    $filter_valid = $_GET['filter_valid'] ?? '';
    echo '<select name="filter_valid">';
    echo '<option value="">' . esc_html__( 'All Statuses', 'enroute_offers' ) . '</option>';
    echo '<option value="valid"'   . selected( $filter_valid, 'valid', false )   . '>' . esc_html__( 'Valid',   'enroute_offers' ) . '</option>';
    echo '<option value="expired"' . selected( $filter_valid, 'expired', false ) . '>' . esc_html__( 'Expired', 'enroute_offers' ) . '</option>';
    echo '</select>';
});

add_action( 'pre_get_posts', function( WP_Query $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) return;
    if ( $query->get('post_type') !== 'enroute_booked_pass' ) return;

    $meta_query = [];

    if ( ! empty( $_GET['filter_pass_id'] ) ) {
        $meta_query[] = [ 'key' => '_booked_pass_type_id', 'value' => absint( $_GET['filter_pass_id'] ), 'compare' => '=' ];
    }

    if ( ! empty( $_GET['filter_valid'] ) ) {
        $today = date('Y-m-d');
        if ( $_GET['filter_valid'] === 'valid' ) {
            $meta_query[] = [ 'key' => '_booked_pass_valid_till', 'value' => $today, 'compare' => '>=', 'type' => 'DATE' ];
        } else {
            $meta_query[] = [ 'key' => '_booked_pass_valid_till', 'value' => $today, 'compare' => '<',  'type' => 'DATE' ];
        }
    }

    if ( $meta_query ) {
        $query->set( 'meta_query', $meta_query );
    }
});

// ══════════════════════════════════════════════════════════════════════════════
// HELPER — get user's active booked pass
// ══════════════════════════════════════════════════════════════════════════════

function enroute_get_user_pass( int $user_id ): ?array {
    $posts = get_posts([
        'post_type'   => 'enroute_booked_pass',
        'post_status' => 'publish',
        'numberposts' => 1,
        'meta_query'  => [
            [ 'key' => '_booked_pass_user_id', 'value' => $user_id, 'compare' => '=' ],
        ],
        'orderby'     => 'date',
        'order'       => 'DESC',
    ]);

    if ( empty( $posts ) ) return null;

    $p          = $posts[0];
    $valid_till = get_post_meta( $p->ID, '_booked_pass_valid_till', true );
    $credit     = get_post_meta( $p->ID, '_booked_pass_credit',     true );
    $pass_id    = get_post_meta( $p->ID, '_booked_pass_type_id',    true );

    return [
        'id'          => $p->ID,
        'name'        => get_the_title( $p->ID ),
        'pass_type'   => $pass_id ? get_the_title( $pass_id ) : '',
        'valid_till'  => $valid_till,
        'valid_till_f'=> $valid_till ? date_i18n( 'd.m.Y', strtotime( $valid_till ) ) : '',
        'credit'      => $credit,
        'is_valid'    => $valid_till && strtotime( $valid_till ) >= time(),
        'edit_url'    => get_edit_post_link( $p->ID ),
    ];
}
