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

// Enable Polylang translation for userpass post type
add_filter( 'pll_get_post_types', function( $post_types ) {
    $post_types['enroute_userpass'] = 'enroute_userpass';
    return $post_types;
});

// Helper: credit type options
function enroute_userpass_credit_type_options(): array {
    return [
        'flat_rate' => __( 'Flat Rate', 'enroute_offers' ),
        'credits'   => __( 'Credits',   'enroute_offers' ),
    ];
}

// Helper: get validity options
function enroute_userpass_validity_options(): array {
    return [
        '1year'  => __( '1 Year',    'enroute_offers' ),
        '2years' => __( '2 Years',   'enroute_offers' ),
        '6months'=> __( '6 Months',  'enroute_offers' ),
    ];
}

// Helper: calculate valid_till date from validity key
function enroute_calculate_valid_till( string $validity ): string {
    switch ( $validity ) {
        case '6months': return date( 'Y-m-d', strtotime( '+6 months' ) );
        case '2years':  return date( 'Y-m-d', strtotime( '+2 years' ) );
        case '1year':
        default:        return date( 'Y-m-d', strtotime( '+1 year' ) );
    }
}

// ── Meta box ──────────────────────────────────────────────────────────────────

add_action( 'add_meta_boxes', function() {
    add_meta_box( 'enroute_userpass_details', __( 'Pass Details', 'enroute_offers' ),
        'enroute_userpass_details_cb', 'enroute_userpass', 'normal', 'high' );
});

function enroute_userpass_details_cb( WP_Post $post ): void {
    wp_nonce_field( 'enroute_userpass_save', 'enroute_userpass_nonce' );
    $description = get_post_meta( $post->ID, '_userpass_description', true );
    $validity    = get_post_meta( $post->ID, '_userpass_validity',    true ) ?: '1year';
    $credit_type = get_post_meta( $post->ID, '_userpass_credit_type', true ) ?: 'flat_rate';
    $credit      = get_post_meta( $post->ID, '_userpass_credit',      true );
    ?>
    <div class="enroute-meta-wrap">
        <div class="enroute-field">
            <label for="userpass_description"><?php esc_html_e( 'Description', 'enroute_offers' ); ?></label>
            <textarea id="userpass_description" name="userpass_description" rows="4" class="widefat"><?php echo esc_textarea( $description ); ?></textarea>
        </div>
        <div class="enroute-field-group">
            <div class="enroute-field">
                <label for="userpass_validity"><?php esc_html_e( 'Validity', 'enroute_offers' ); ?></label>
                <select id="userpass_validity" name="userpass_validity" class="widefat">
                    <?php foreach ( enroute_userpass_validity_options() as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $validity, $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e( 'How long the pass is valid from the booking date.', 'enroute_offers' ); ?></p>
            </div>
            <div class="enroute-field">
                <label for="userpass_credit_type"><?php esc_html_e( 'Credit System', 'enroute_offers' ); ?></label>
                <select id="userpass_credit_type" name="userpass_credit_type"
                        onchange="document.getElementById('userpass_credit_wrap').style.display = this.value === 'credits' ? 'block' : 'none';">
                    <?php foreach ( enroute_userpass_credit_type_options() as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $credit_type, $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="enroute-field" id="userpass_credit_wrap" style="<?php echo $credit_type === 'credits' ? '' : 'display:none;'; ?>">
                <label for="userpass_credit"><?php esc_html_e( 'Credit Value', 'enroute_offers' ); ?></label>
                <input type="text" id="userpass_credit" name="userpass_credit" value="<?php echo esc_attr( $credit ); ?>" placeholder="<?php esc_attr_e( 'e.g. 350 CHF', 'enroute_offers' ); ?>">
                <p class="description"><?php esc_html_e( 'Starting credit when a pass is booked.', 'enroute_offers' ); ?></p>
            </div>
        </div>
        <div class="enroute-field">
            <p class="description" style="margin:0; padding:0.5rem; background:#f0f0f0; border-left:3px solid #2271b1;">
                <?php esc_html_e( 'Language is managed by Polylang — set it using the Language meta box on the right.', 'enroute_offers' ); ?>
            </p>
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
    $validity_options = array_keys( enroute_userpass_validity_options() );
    $validity = isset( $_POST['userpass_validity'] ) && in_array( $_POST['userpass_validity'], $validity_options, true )
        ? $_POST['userpass_validity'] : '1year';
    update_post_meta( $post_id, '_userpass_validity', $validity );

    $credit_type_options = array_keys( enroute_userpass_credit_type_options() );
    $credit_type = isset( $_POST['userpass_credit_type'] ) && in_array( $_POST['userpass_credit_type'], $credit_type_options, true )
        ? $_POST['userpass_credit_type'] : 'flat_rate';
    update_post_meta( $post_id, '_userpass_credit_type', $credit_type );
    // Only save credit value if credit system is selected
    $credit = $credit_type === 'credits' ? sanitize_text_field( $_POST['userpass_credit'] ?? '' ) : '';
    update_post_meta( $post_id, '_userpass_credit', $credit );
    // Language is managed by Polylang
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
            $valid_till  = get_post_meta( $post_id, '_booked_pass_valid_till', true );
            $credit      = get_post_meta( $post_id, '_booked_pass_credit',     true );
            $pass_type_id_col = get_post_meta( $post_id, '_booked_pass_type_id', true );
            $col_credit_type  = $pass_type_id_col ? get_post_meta( (int) $pass_type_id_col, '_userpass_credit_type', true ) : 'flat_rate';
            $credit_num  = (float) preg_replace( '/[^0-9.]/', '', $credit );
            $no_credit   = $col_credit_type === 'credits' && $credit_num === 0.0 && $credit !== '';
            $renewed_by  = get_post_meta( $post_id, '_booked_pass_renewed_by',  true );
            $previous_id = get_post_meta( $post_id, '_booked_pass_previous_id', true );

            if ( ! $valid_till ) {
                echo '<span style="color:#6b7280;">—</span>';
            } elseif ( strtotime( $valid_till ) < time() ) {
                echo '<span style="color:#991b1b; font-weight:600;">' . esc_html__( 'Expired', 'enroute_offers' ) . '</span>';
            } elseif ( $no_credit ) {
                echo '<span style="color:#991b1b; font-weight:600;">' . esc_html__( 'No Credit', 'enroute_offers' ) . '</span>';
            } else {
                echo '<span style="color:#166534; font-weight:600;">' . esc_html__( 'Valid', 'enroute_offers' ) . '</span>';
            }

            // Show renewal links
            if ( $renewed_by ) {
                $new_url = get_edit_post_link( $renewed_by );
                echo '<br><small><a href="' . esc_url( $new_url ) . '" style="color:#2271b1;">' . esc_html__( '→ Renewed', 'enroute_offers' ) . '</a></small>';
            }
            if ( $previous_id ) {
                $old_url = get_edit_post_link( $previous_id );
                echo '<br><small><a href="' . esc_url( $old_url ) . '" style="color:#6b7280;">' . esc_html__( '← Previous pass', 'enroute_offers' ) . '</a></small>';
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

    // Get credit type from the pass type definition
    $pass_type_meta  = $pass_id ? get_post_meta( (int) $pass_id, '_userpass_credit_type', true ) : '';
    $credit_type     = $pass_type_meta ?: ( $credit !== '' ? 'credits' : 'flat_rate' ); // fallback for old data
    $uses_credits    = $credit_type === 'credits';
    $credit_num      = (float) preg_replace( '/[^0-9.]/', '', $credit );
    $has_credit      = ! $uses_credits || $credit === '' || $credit_num > 0;

    return [
        'id'          => $p->ID,
        'name'        => get_the_title( $p->ID ),
        'pass_type'   => $pass_id ? get_the_title( $pass_id ) : '',
        'pass_type_id'=> (int) $pass_id,
        'valid_till'  => $valid_till,
        'valid_till_f'=> $valid_till ? date_i18n( 'd.m.Y', strtotime( $valid_till ) ) : '',
        'credit'      => $credit,
        'credit_type' => $credit_type,
        'uses_credits'=> $uses_credits,
        'has_credit'  => $has_credit,
        'is_valid'    => $valid_till && strtotime( $valid_till ) >= time(),
        'edit_url'    => get_edit_post_link( $p->ID ),
    ];
}
