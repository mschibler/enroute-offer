<?php
/**
 * User Pass booking form page.
 * Shortcode: [enroute_userpass_booking]
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$current_lang = function_exists( 'pll_current_language' ) ? pll_current_language() : substr( get_locale(), 0, 2 );
$current_lang = substr( $current_lang, 0, 2 );

// Get available passes for current language (Polylang or meta fallback)
$pass_args = [
    'post_type'   => 'enroute_userpass',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby'     => 'title',
    'order'       => 'ASC',
];
if ( function_exists( 'pll_get_post_language' ) ) {
    $pass_args['lang'] = $current_lang;
} else {
    $pass_args['meta_query'] = [
        [ 'key' => '_userpass_language', 'value' => $current_lang, 'compare' => '=' ],
    ];
}
$passes = get_posts( $pass_args );

// Referer URL — where to go back after booking
$referer = isset( $_GET['referer'] ) ? esc_url_raw( wp_unslash( $_GET['referer'] ) ) : '';

$inp = 'style="width:100%; padding:0.5rem 0.6rem; border:1px solid #ccc; font-size:0.9rem; box-sizing:border-box;"';
$lbl = 'style="display:block; font-size:0.85rem; font-weight:600; margin-bottom:0.3rem; color:#374151;"';

// Pre-fill from profile if logged in
$profile = [];
if ( is_user_logged_in() ) {
    $profile = enroute_get_user_profile( get_current_user_id() );
}
?>

<div
    class="enroute-userpass-booking"
    x-data="enrouteUserpassBooking()"
    style="max-width:640px;"
>

    <?php if ( empty( $passes ) ) : ?>
        <p><?php esc_html_e( 'Keine User Passes verfügbar.', 'enroute_offers' ); ?></p>
    <?php else : ?>

    <!-- Success state -->
    <div x-show="submitted" style="display:none; text-align:center; padding:2rem 0;">
        <svg style="width:3rem;height:3rem;color:#166534;margin:0 auto 1rem;display:block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <p style="font-weight:700; font-size:1.1rem; margin:0 0 0.5rem;"><?php esc_html_e( 'Anfrage übermittelt!', 'enroute_offers' ); ?></p>
        <p style="color:#6b7280; margin:0 0 1.5rem;" x-text="successMsg"></p>
        <?php if ( $referer ) : ?>
        <a href="<?php echo esc_url( $referer ); ?>"
           style="display:inline-block; padding:0.75rem 2rem; background:#111; color:#fff; text-decoration:none; font-weight:600;">
            <?php esc_html_e( '← Zurück zum Angebot', 'enroute_offers' ); ?>
        </a>
        <?php endif; ?>
    </div>

    <!-- Form -->
    <div x-show="!submitted">

        <!-- Error -->
        <div x-show="errorMsg" style="display:none; margin-bottom:1rem; padding:0.75rem 1rem; background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; font-size:0.875rem;" x-text="errorMsg"></div>

        <!-- Pass selection -->
        <div style="margin-bottom:1.5rem;">
            <label <?php echo $lbl; ?>><?php esc_html_e( 'User Pass wählen', 'enroute_offers' ); ?> *</label>
            <?php foreach ( $passes as $pass ) :
                $desc   = get_post_meta( $pass->ID, '_userpass_description', true );
                $credit = get_post_meta( $pass->ID, '_userpass_credit',      true );
            ?>
            <label style="display:block; border:2px solid #e5e7eb; padding:1rem; margin-bottom:0.5rem; cursor:pointer;"
                   :style="form.pass_type_id == '<?php echo $pass->ID; ?>' ? 'border-color:#111; background:#f9f9f9;' : ''">
                <div style="display:flex; align-items:flex-start; gap:0.75rem;">
                    <input type="radio" name="pass_type_id" value="<?php echo $pass->ID; ?>"
                           x-model="form.pass_type_id" style="margin-top:0.2rem; flex-shrink:0;">
                    <div>
                        <p style="margin:0; font-weight:700; font-size:0.95rem;"><?php echo esc_html( $pass->post_title ); ?></p>
                        <?php if ( $desc ) : ?>
                        <p style="margin:0.3rem 0 0; font-size:0.85rem; color:#4b5563;"><?php echo esc_html( $desc ); ?></p>
                        <?php endif; ?>
                        <p style="margin:0.3rem 0 0; font-size:0.8rem; color:#6b7280;">
                            <?php
                            $validity      = get_post_meta( $pass->ID, '_userpass_validity', true ) ?: '1year';
                            $validity_opts = enroute_userpass_validity_options();
                            $validity_lbl  = $validity_opts[ $validity ] ?? $validity;
                            echo esc_html__( 'Validity:', 'enroute_offers' ) . ' ' . esc_html( $validity_lbl );
                            if ( $credit ) echo ' &nbsp;|&nbsp; ' . esc_html__( 'Guthaben:', 'enroute_offers' ) . ' ' . esc_html( $credit );
                            ?>
                        </p>
                    </div>
                </div>
            </label>
            <?php endforeach; ?>
        </div>

        <!-- Login / Register -->
        <?php if ( ! is_user_logged_in() ) : ?>
        <div x-data="enrouteUserAuth()" style="border:1px solid #e5e7eb; padding:1rem; margin-bottom:1.5rem; background:#f9f9f9;">
            <p style="margin:0 0 0.75rem; font-weight:600; font-size:0.9rem;"><?php esc_html_e( 'Anmelden oder Konto erstellen (optional)', 'enroute_offers' ); ?></p>

            <div x-show="loggedIn" style="font-size:0.9rem; color:#166534;">
                ✓ <span x-text="(profile.enroute_first_name||'') + ' ' + (profile.enroute_last_name||profile.email||'')"></span>
            </div>

            <div x-show="!loggedIn">
                <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem;">
                    <button @click="mode='login'; errorMsg=''" :style="mode==='login' ? 'background:#111; color:#fff;' : 'background:#fff;'"
                        style="flex:1; padding:0.4rem; border:1px solid #111; font-size:0.82rem; cursor:pointer;">
                        <?php esc_html_e( 'Anmelden', 'enroute_offers' ); ?>
                    </button>
                    <button @click="mode='register'; errorMsg=''" :style="mode==='register' ? 'background:#111; color:#fff;' : 'background:#fff;'"
                        style="flex:1; padding:0.4rem; border:1px solid #111; font-size:0.82rem; cursor:pointer;">
                        <?php esc_html_e( 'Registrieren', 'enroute_offers' ); ?>
                    </button>
                </div>
                <div x-show="errorMsg" style="display:none; margin-bottom:0.5rem; padding:0.5rem; background:#fef2f2; color:#991b1b; font-size:0.8rem;" x-text="errorMsg"></div>

                <div x-show="mode==='login'" style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                    <input type="email" x-model="form.email" placeholder="<?php esc_attr_e( 'E-Mail', 'enroute_offers' ); ?>"
                           style="flex:1; min-width:180px; padding:0.4rem 0.6rem; border:1px solid #ccc; font-size:0.85rem;">
                    <input type="password" x-model="form.password" placeholder="<?php esc_attr_e( 'Passwort', 'enroute_offers' ); ?>"
                           style="flex:1; min-width:150px; padding:0.4rem 0.6rem; border:1px solid #ccc; font-size:0.85rem;">
                    <button @click="submit()" :disabled="loading"
                        style="padding:0.4rem 1rem; background:#111; color:#fff; border:none; font-size:0.85rem; cursor:pointer; white-space:nowrap;">
                        <span x-show="!loading"><?php esc_html_e( 'Anmelden', 'enroute_offers' ); ?></span>
                        <span x-show="loading">…</span>
                    </button>
                </div>

                <div x-show="mode==='register'">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.4rem; margin-bottom:0.4rem;">
                        <input type="text" x-model="form.enroute_first_name" placeholder="<?php esc_attr_e( 'Vorname *', 'enroute_offers' ); ?>"
                               style="padding:0.4rem 0.6rem; border:1px solid #ccc; font-size:0.85rem;">
                        <input type="text" x-model="form.enroute_last_name" placeholder="<?php esc_attr_e( 'Nachname *', 'enroute_offers' ); ?>"
                               style="padding:0.4rem 0.6rem; border:1px solid #ccc; font-size:0.85rem;">
                    </div>
                    <div style="margin-bottom:0.4rem;">
                        <input type="email" x-model="form.email" placeholder="<?php esc_attr_e( 'E-Mail *', 'enroute_offers' ); ?>"
                               style="width:100%; padding:0.4rem 0.6rem; border:1px solid #ccc; font-size:0.85rem; box-sizing:border-box;">
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.4rem; margin-bottom:0.5rem;">
                        <input type="password" x-model="form.password" placeholder="<?php esc_attr_e( 'Passwort *', 'enroute_offers' ); ?>"
                               style="padding:0.4rem 0.6rem; border:1px solid #ccc; font-size:0.85rem;">
                        <input type="password" x-model="form.password2" placeholder="<?php esc_attr_e( 'Wiederholen *', 'enroute_offers' ); ?>"
                               style="padding:0.4rem 0.6rem; border:1px solid #ccc; font-size:0.85rem;">
                    </div>
                    <button @click="submit()" :disabled="loading"
                        style="width:100%; padding:0.5rem; background:#111; color:#fff; border:none; font-size:0.85rem; cursor:pointer;">
                        <span x-show="!loading"><?php esc_html_e( 'Konto erstellen', 'enroute_offers' ); ?></span>
                        <span x-show="loading">…</span>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contact data -->
        <h3 style="font-size:1rem; font-weight:700; margin:0 0 1rem; padding-bottom:0.5rem; border-bottom:1px solid #e5e7eb;">
            <?php esc_html_e( 'Kontaktdaten', 'enroute_offers' ); ?>
        </h3>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
            <div>
                <label <?php echo $lbl; ?>><?php esc_html_e( 'Anrede', 'enroute_offers' ); ?></label>
                <select x-model="form.salutation" <?php echo $inp; ?>>
                    <option value=""><?php esc_html_e( 'Bitte wählen', 'enroute_offers' ); ?></option>
                    <option value="Herr"><?php esc_html_e( 'Herr', 'enroute_offers' ); ?></option>
                    <option value="Frau"><?php esc_html_e( 'Frau', 'enroute_offers' ); ?></option>
                    <option value="Divers"><?php esc_html_e( 'Divers', 'enroute_offers' ); ?></option>
                </select>
            </div>
            <div>
                <label <?php echo $lbl; ?>><?php esc_html_e( 'Institution', 'enroute_offers' ); ?></label>
                <input type="text" x-model="form.institution" <?php echo $inp; ?>>
            </div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
            <div>
                <label <?php echo $lbl; ?>><?php esc_html_e( 'Vorname', 'enroute_offers' ); ?> *</label>
                <input type="text" x-model="form.first_name" <?php echo $inp; ?>>
            </div>
            <div>
                <label <?php echo $lbl; ?>><?php esc_html_e( 'Nachname', 'enroute_offers' ); ?> *</label>
                <input type="text" x-model="form.last_name" <?php echo $inp; ?>>
            </div>
        </div>
        <div style="margin-bottom:0.75rem;">
            <label <?php echo $lbl; ?>><?php esc_html_e( 'Strasse', 'enroute_offers' ); ?></label>
            <input type="text" x-model="form.street" <?php echo $inp; ?>>
        </div>
        <div style="display:grid; grid-template-columns:1fr 2fr; gap:0.75rem; margin-bottom:0.75rem;">
            <div>
                <label <?php echo $lbl; ?>><?php esc_html_e( 'PLZ', 'enroute_offers' ); ?></label>
                <input type="text" x-model="form.zip" <?php echo $inp; ?>>
            </div>
            <div>
                <label <?php echo $lbl; ?>><?php esc_html_e( 'Ort', 'enroute_offers' ); ?></label>
                <input type="text" x-model="form.place" <?php echo $inp; ?>>
            </div>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
            <div>
                <label <?php echo $lbl; ?>><?php esc_html_e( 'E-Mail', 'enroute_offers' ); ?> *</label>
                <input type="email" x-model="form.email" <?php echo $inp; ?>>
            </div>
            <div>
                <label <?php echo $lbl; ?>><?php esc_html_e( 'Telefon', 'enroute_offers' ); ?></label>
                <input type="tel" x-model="form.phone" <?php echo $inp; ?>>
            </div>
        </div>
        <div style="margin-bottom:1.5rem;">
            <label <?php echo $lbl; ?>><?php esc_html_e( 'Bemerkungen', 'enroute_offers' ); ?></label>
            <textarea x-model="form.remarks" rows="3" <?php echo $inp; ?>></textarea>
        </div>

        <button
            type="button"
            @click="submit()"
            :disabled="loading"
            style="width:100%; padding:0.875rem 1rem; background:#111; color:#fff; border:none; font-weight:700; font-size:1rem; cursor:pointer;"
            :style="loading ? 'opacity:0.6; cursor:not-allowed;' : ''"
        >
            <span x-show="!loading"><?php esc_html_e( 'User Pass anfragen', 'enroute_offers' ); ?></span>
            <span x-show="loading"><?php esc_html_e( 'Wird gesendet…', 'enroute_offers' ); ?></span>
        </button>
        <p style="font-size:0.75rem; color:#9ca3af; margin:0.5rem 0 0;">* <?php esc_html_e( 'Pflichtfelder', 'enroute_offers' ); ?></p>

    </div><!-- /form -->
    <?php endif; ?>

</div>

<script>
window.enrouteUserpassReferer = <?php echo wp_json_encode( $referer ); ?>;
window.enrouteUserpassProfile = <?php echo wp_json_encode( $profile ); ?>;
</script>
