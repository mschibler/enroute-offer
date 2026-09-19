<?php
/**
 * User profile page template.
 * Shortcode: [enroute_user_profile]
 * - Redirects to login (via booking drawer) if not logged in
 * - Shows address data with inline edit
 * - Shows list of user's bookings
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! is_user_logged_in() ) {
    // Redirect to profile page after login — store current URL
    $current_url = get_permalink();
    wp_safe_redirect( add_query_arg( 'enroute_redirect', urlencode( $current_url ), $current_url ) );
    exit;
}

$user_id = get_current_user_id();
$profile = enroute_get_user_profile( $user_id );

// Get user's bookings (by user ID first, fall back to email)
$bookings = get_posts( [
    'post_type'   => 'enroute_booking',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby'     => 'date',
    'order'       => 'DESC',
    'meta_query'  => [
        'relation' => 'OR',
        [ 'key' => '_booking_user_id', 'value' => $user_id, 'compare' => '=' ],
        [ 'key' => '_booking_email',   'value' => $profile['email'], 'compare' => '=' ],
    ],
] );

$inp = 'style="width:100%; padding:0.5rem 0.6rem; border:1px solid #ccc; font-size:0.9rem; box-sizing:border-box;"';
$lbl = 'style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.2rem; color:#374151;"';
?>

<div
    class="enroute-user-profile"
    x-data="enrouteUserProfile()"
    style="max-width:800px;"
>

    <!-- ── Header ─────────────────────────────────────────────────────────── -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <h2 style="margin:0; font-size:1.25rem; font-weight:700;">
            <?php echo esc_html( trim( $profile['enroute_first_name'] . ' ' . $profile['enroute_last_name'] ) ?: $profile['email'] ); ?>
        </h2>
        <button
            @click="logout()"
            style="padding:0.4rem 1rem; border:1px solid #ccc; background:#fff; font-size:0.85rem; cursor:pointer;"
        ><?php esc_html_e( 'Abmelden', 'enroute_offers' ); ?></button>
    </div>

    <!-- ── Success / Error message ────────────────────────────────────────── -->
    <div x-show="message" style="display:none; margin-bottom:1rem; padding:0.75rem 1rem; background:#d4edda; border:1px solid #c3e6cb; color:#155724; font-size:0.9rem;" x-text="message"></div>
    <div x-show="errorMsg" style="display:none; margin-bottom:1rem; padding:0.75rem 1rem; background:#f8d7da; border:1px solid #f5c6cb; color:#721c24; font-size:0.9rem;" x-text="errorMsg"></div>

    <!-- ── Address section ────────────────────────────────────────────────── -->
    <div style="background:#f9f9f9; border:1px solid #e5e7eb; padding:1.5rem; margin-bottom:2rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
            <h3 style="margin:0; font-size:1rem; font-weight:700;"><?php esc_html_e( 'Kontaktdaten', 'enroute_offers' ); ?></h3>
            <button
                @click="editing = !editing"
                x-text="editing ? '<?php esc_html_e( 'Abbrechen', 'enroute_offers' ); ?>' : '<?php esc_html_e( 'Bearbeiten', 'enroute_offers' ); ?>'"
                style="padding:0.4rem 1rem; border:1px solid #000; background:#fff; font-size:0.85rem; cursor:pointer;"
            ></button>
        </div>

        <!-- View mode -->
        <div x-show="!editing">
            <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                <?php
                $view_fields = [
                    __( 'Anrede',      'enroute_offers' ) => $profile['enroute_salutation'],
                    __( 'Name',        'enroute_offers' ) => trim( $profile['enroute_first_name'] . ' ' . $profile['enroute_last_name'] ),
                    __( 'Institution', 'enroute_offers' ) => $profile['enroute_institution'],
                    __( 'Adresse',     'enroute_offers' ) => $profile['enroute_street'],
                    __( 'PLZ / Ort',   'enroute_offers' ) => trim( $profile['enroute_zip'] . ' ' . $profile['enroute_place'] ),
                    __( 'E-Mail',      'enroute_offers' ) => $profile['email'],
                    __( 'Telefon',     'enroute_offers' ) => $profile['enroute_phone'],
                ];
                foreach ( $view_fields as $label => $value ) :
                    if ( ! $value ) continue;
                ?>
                <tr>
                    <td style="padding:0.4rem 1rem 0.4rem 0; color:#6b7280; width:130px; vertical-align:top;"><?php echo esc_html( $label ); ?></td>
                    <td style="padding:0.4rem 0;"><?php echo esc_html( $value ); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Edit mode -->
        <div x-show="editing" style="display:none;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
                <div>
                    <label <?php echo $lbl; ?>><?php esc_html_e( 'Anrede', 'enroute_offers' ); ?></label>
                    <select x-model="form.enroute_salutation" <?php echo $inp; ?>>
                        <option value=""><?php esc_html_e( '— Bitte wählen —', 'enroute_offers' ); ?></option>
                        <option value="Herr"><?php esc_html_e( 'Herr', 'enroute_offers' ); ?></option>
                        <option value="Frau"><?php esc_html_e( 'Frau', 'enroute_offers' ); ?></option>
                        <option value="Divers"><?php esc_html_e( 'Divers', 'enroute_offers' ); ?></option>
                    </select>
                </div>
                <div>
                    <label <?php echo $lbl; ?>><?php esc_html_e( 'Institution', 'enroute_offers' ); ?></label>
                    <input type="text" x-model="form.enroute_institution" <?php echo $inp; ?>>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
                <div>
                    <label <?php echo $lbl; ?>><?php esc_html_e( 'Vorname', 'enroute_offers' ); ?> *</label>
                    <input type="text" x-model="form.enroute_first_name" <?php echo $inp; ?>>
                </div>
                <div>
                    <label <?php echo $lbl; ?>><?php esc_html_e( 'Nachname', 'enroute_offers' ); ?> *</label>
                    <input type="text" x-model="form.enroute_last_name" <?php echo $inp; ?>>
                </div>
            </div>
            <div style="margin-bottom:0.75rem;">
                <label <?php echo $lbl; ?>><?php esc_html_e( 'Strasse', 'enroute_offers' ); ?></label>
                <input type="text" x-model="form.enroute_street" <?php echo $inp; ?>>
            </div>
            <div style="display:grid; grid-template-columns:1fr 2fr; gap:0.75rem; margin-bottom:0.75rem;">
                <div>
                    <label <?php echo $lbl; ?>><?php esc_html_e( 'PLZ', 'enroute_offers' ); ?></label>
                    <input type="text" x-model="form.enroute_zip" <?php echo $inp; ?>>
                </div>
                <div>
                    <label <?php echo $lbl; ?>><?php esc_html_e( 'Ort', 'enroute_offers' ); ?></label>
                    <input type="text" x-model="form.enroute_place" <?php echo $inp; ?>>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:1rem;">
                <div>
                    <label <?php echo $lbl; ?>><?php esc_html_e( 'E-Mail', 'enroute_offers' ); ?></label>
                    <input type="email" value="<?php echo esc_attr( $profile['email'] ); ?>" <?php echo $inp; ?> disabled style="<?php echo $inp; ?> background:#f3f4f6; cursor:not-allowed;">
                    <p style="font-size:0.75rem; color:#9ca3af; margin:0.2rem 0 0;"><?php esc_html_e( 'E-Mail kann nicht geändert werden.', 'enroute_offers' ); ?></p>
                </div>
                <div>
                    <label <?php echo $lbl; ?>><?php esc_html_e( 'Telefon', 'enroute_offers' ); ?></label>
                    <input type="tel" x-model="form.enroute_phone" <?php echo $inp; ?>>
                </div>
            </div>
            <button
                @click="saveProfile()"
                :disabled="saving"
                style="padding:0.75rem 2rem; background:#111; color:#fff; border:none; font-weight:700; font-size:0.95rem; cursor:pointer;"
            >
                <span x-show="!saving"><?php esc_html_e( 'Speichern', 'enroute_offers' ); ?></span>
                <span x-show="saving"><?php esc_html_e( 'Wird gespeichert…', 'enroute_offers' ); ?></span>
            </button>
        </div>
    </div>

    <!-- ── User Pass section ────────────────────────────────────────────────── -->
    <?php
    $user_pass = enroute_get_user_pass( $user_id );
    $userpass_url = get_option( 'enroute_userpass_page_url', '' );
    ?>
    <div style="background:#f9f9f9; border:1px solid #e5e7eb; padding:1.5rem; margin-bottom:2rem;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; gap:0.5rem;">
            <h3 style="margin:0; font-size:1rem; font-weight:700;"><?php esc_html_e( 'Mein User Pass', 'enroute_offers' ); ?></h3>
            <?php if ( $userpass_url ) : ?>
            <a href="<?php echo esc_url( $userpass_url ); ?>"
               style="padding:0.4rem 1rem; border:1px solid #000; background:#fff; font-size:0.85rem; text-decoration:none; color:#111; display:inline-block;">
                <?php esc_html_e( 'User Pass anfragen', 'enroute_offers' ); ?>
            </a>
            <?php endif; ?>
        </div>

        <?php if ( $user_pass ) : ?>
        <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
            <tr>
                <td style="padding:0.4rem 1rem 0.4rem 0; color:#6b7280; width:130px;"><?php esc_html_e( 'Pass', 'enroute_offers' ); ?></td>
                <td style="padding:0.4rem 0; font-weight:600;"><?php echo esc_html( $user_pass['pass_type'] ?: $user_pass['name'] ); ?></td>
            </tr>
            <tr>
                <td style="padding:0.4rem 1rem 0.4rem 0; color:#6b7280;"><?php esc_html_e( 'Gültig bis', 'enroute_offers' ); ?></td>
                <td style="padding:0.4rem 0;">
                    <?php echo esc_html( $user_pass['valid_till_f'] ?: '—' ); ?>
                    <?php if ( $user_pass['valid_till'] ) : ?>
                    <span style="margin-left:0.5rem; font-size:0.8rem; font-weight:600; color:<?php echo $user_pass['is_valid'] ? '#166534' : '#991b1b'; ?>;">
                        (<?php echo $user_pass['is_valid'] ? esc_html__( 'Gültig', 'enroute_offers' ) : esc_html__( 'Abgelaufen', 'enroute_offers' ); ?>)
                    </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ( $user_pass['uses_credits'] ) : ?>
            <tr>
                <td style="padding:0.4rem 1rem 0.4rem 0; color:#6b7280;"><?php esc_html_e( 'Guthaben', 'enroute_offers' ); ?></td>
                <td style="padding:0.4rem 0;">
                    <?php if ( $user_pass['has_credit'] ) : ?>
                        <?php echo esc_html( $user_pass['credit'] ); ?>
                    <?php else : ?>
                        <span style="color:#991b1b; font-weight:600;"><?php esc_html_e( 'Kein Guthaben mehr', 'enroute_offers' ); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php else : ?>
        <p style="color:#6b7280; font-size:0.9rem; margin:0;"><?php esc_html_e( 'Noch kein User Pass.', 'enroute_offers' ); ?></p>
        <?php endif; ?>
    </div>

    <!-- ── Bookings section ───────────────────────────────────────────────── -->
    <h3 style="font-size:1rem; font-weight:700; margin-bottom:1rem;"><?php esc_html_e( 'Meine Buchungsanfragen', 'enroute_offers' ); ?></h3>

    <?php if ( empty( $bookings ) ) : ?>
        <p style="color:#6b7280; font-size:0.9rem;"><?php esc_html_e( 'Noch keine Buchungsanfragen.', 'enroute_offers' ); ?></p>
    <?php else : ?>
    <div style="display:flex; flex-direction:column; gap:0.75rem;">
        <?php foreach ( $bookings as $booking ) :
            $offer_title = get_post_meta( $booking->ID, '_booking_offer_title', true );
            $date_1      = get_post_meta( $booking->ID, '_booking_date_1', true );
            $time_1      = get_post_meta( $booking->ID, '_booking_time_1', true );
            $date_2      = get_post_meta( $booking->ID, '_booking_date_2', true );
            $persons     = get_post_meta( $booking->ID, '_booking_persons', true );
            $submitted   = get_post_meta( $booking->ID, '_booking_submitted', true ) ?: get_the_date( 'd.m.Y', $booking->ID );
        ?>
        <div style="border:1px solid #e5e7eb; padding:1rem 1.25rem; background:#fff;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:0.5rem;">
                <div>
                    <p style="margin:0; font-weight:700; font-size:0.95rem;"><?php echo esc_html( $offer_title ); ?></p>
                    <p style="margin:0.25rem 0 0; font-size:0.85rem; color:#6b7280;">
                        <?php esc_html_e( 'Wunschdatum:', 'enroute_offers' ); ?>
                        <?php echo esc_html( trim( "$date_1 $time_1" ) ); ?>
                        <?php if ( $date_2 ) : ?>
                            &nbsp;|&nbsp;<?php esc_html_e( 'Ersatz:', 'enroute_offers' ); ?> <?php echo esc_html( $date_2 ); ?>
                        <?php endif; ?>
                        <?php if ( $persons ) : ?>
                            &nbsp;|&nbsp;<?php echo esc_html( $persons ); ?> <?php esc_html_e( 'Personen', 'enroute_offers' ); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <p style="margin:0; font-size:0.8rem; color:#9ca3af; white-space:nowrap;">
                    <?php echo esc_html( $submitted ); ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<script>
// Pass profile data to Alpine component
window.enrouteInitialProfile = <?php echo wp_json_encode( $profile ); ?>;
</script>
