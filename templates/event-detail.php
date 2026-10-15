<?php
/**
 * Event detail page — auto-injected on single event pages (like offers).
 * Sidebar order: Dates, Place, Price, Target group (+ website, contact).
 * Registration drawer: login, contact person, group.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = get_the_ID();
if ( ! $post_id || get_post_type( $post_id ) !== 'event' ) return;

$short         = get_post_meta( $post_id, '_event_short_description', true );
$price         = get_post_meta( $post_id, '_event_price',             true );
$contact_email = get_post_meta( $post_id, '_event_contact_email',     true );
$website       = get_post_meta( $post_id, '_event_website',           true );
$dates         = enroute_event_date_text( $post_id );
$place_lines   = enroute_event_place_lines( $post_id );
$target_groups = get_the_terms( $post_id, 'offer_target_group' );
$is_past       = enroute_event_is_past( $post_id );
$image_url     = enroute_event_image_url( $post_id, 'full' );
$thumb_id      = get_post_thumbnail_id( $post_id );
$image_alt     = $thumb_id ? ( get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) ?: get_the_title( $post_id ) ) : get_the_title( $post_id );

$event_color        = enroute_get_featured_color( 1 );
$button_color       = '#fce300';
$button_color_green = '#c9d56b';
?>

<article class="enroute-offer-detail enroute-event-detail">

    <?php if ( $image_url ) : ?>
    <div class="relative w-full overflow-hidden" style="max-height:480px;">
        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" class="w-full object-cover" style="max-height:480px;">
        <div class="absolute bottom-0 left-0 p-6" style="background:<?php echo esc_attr( $event_color ); ?>; max-width:75%;">
            <h1 class="text-4xl font-black font-montserrat font-semibold leading-tight mb-0"><?php the_title(); ?></h1>
        </div>
    </div>
    <?php else : ?>
    <div class="px-0 py-6">
        <h1 class="text-4xl font-black font-montserrat font-semibold leading-tight"><?php the_title(); ?></h1>
    </div>
    <?php endif; ?>

    <?php if ( $short ) : ?>
    <div class="enroute-detail-layout" style="display:grid; grid-template-columns:1fr 340px; gap:2rem; align-items:start;">
        <div class="mt-0 mb-0 pl-6 pr-6">
            <p class="text-3xl font-montserrat font-normal leading-snug"><?php echo esc_html( $short ); ?></p>
        </div>
        <div></div>
    </div>
    <?php endif; ?>

    <div class="enroute-detail-layout" style="display:grid; grid-template-columns:1fr 340px; gap:2rem; align-items:start;">

        <!-- Left: description -->
        <div class="enroute-detail-body prose max-w-none text-gray-800 font-montserrat text-lg">
            <hr class="border-gray-800 mt-0 mb-6">
            <div class="pl-6 pr-6">
                <?php
                $raw_content = get_post_field( 'post_content', $post_id, 'raw' );
                if ( $raw_content ) echo wp_kses_post( wpautop( $raw_content ) );
                ?>
            </div>
        </div>

        <!-- Right: info + actions -->
        <div class="enroute-detail-sidebar">
            <hr class="border-gray-800 mt-0 mb-6">
            <div style="margin-bottom:1.5rem;" class="font-montserrat text-sm pb-12">

                <?php if ( $dates ) : ?>
                <div class="enroute-detail-row" style="margin-bottom:1rem;">
                    <p style="font-weight:700; margin:0;"><?php esc_html_e( 'Daten', 'enroute_offers' ); ?></p>
                    <p style="margin:0; line-height:1.4;"><?php echo esc_html( $dates ); ?></p>
                </div>
                <?php endif; ?>

                <?php if ( $place_lines ) : ?>
                <div class="enroute-detail-row" style="margin-bottom:1rem;">
                    <p style="font-weight:700; margin:0;"><?php esc_html_e( 'Veranstaltungsort', 'enroute_offers' ); ?></p>
                    <?php foreach ( $place_lines as $line ) : ?>
                        <p style="margin:0; line-height:1.4;"><?php echo esc_html( $line ); ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ( $price ) : ?>
                <div class="enroute-detail-row" style="margin-bottom:1rem;">
                    <p style="font-weight:700; margin:0;"><?php esc_html_e( 'Preis', 'enroute_offers' ); ?></p>
                    <p style="margin:0; line-height:1.4;"><?php echo esc_html( $price ); ?></p>
                </div>
                <?php endif; ?>

                <?php if ( $target_groups && ! is_wp_error( $target_groups ) ) : ?>
                <div class="enroute-detail-row" style="margin-bottom:1rem;">
                    <p style="font-weight:700; margin:0;"><?php esc_html_e( 'Zielgruppe', 'enroute_offers' ); ?></p>
                    <?php foreach ( $target_groups as $tg ) : ?>
                        <p style="margin:0; line-height:1.4;"><?php echo esc_html( $tg->name ); ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ( $website ) : ?>
                <div class="enroute-detail-row" style="margin-bottom:1rem;">
                    <p style="font-weight:700; margin:0;"><?php esc_html_e( 'Website', 'enroute_offers' ); ?></p>
                    <p style="margin:0; line-height:1.4; word-break:break-word;">
                        <a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer" style="color:inherit; text-decoration:underline;">
                            <?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( $website ) ) ); ?>
                        </a>
                    </p>
                </div>
                <?php endif; ?>

                <?php if ( $contact_email ) : ?>
                <div class="enroute-detail-row" style="margin-bottom:0;">
                    <p style="font-weight:700; margin:0;"><?php esc_html_e( 'Kontakt', 'enroute_offers' ); ?></p>
                    <p style="margin:0; line-height:1.4;"><a href="mailto:<?php echo esc_attr( antispambot( $contact_email ) ); ?>" style="color:inherit; text-decoration:underline;"><?php echo esc_html( antispambot( $contact_email ) ); ?></a></p>
                </div>
                <?php endif; ?>

            </div>

            <div x-data="{ registrationOpen: false }" style="display:flex; flex-direction:column; gap:0.75rem;">
                <button type="button" onclick="window.print()"
                    style="width:100%; padding:0.5rem 1rem; background:<?php echo esc_attr( $button_color ); ?>; border:none; font-weight:600; font-size:1.25rem; cursor:pointer; text-align:center;">
                    <?php esc_html_e( 'Drucken', 'enroute_offers' ); ?>
                </button>

                <?php if ( $is_past ) : ?>
                <p style="margin:0; font-size:0.9rem; color:#6b7280;"><?php esc_html_e( 'Diese Veranstaltung hat bereits stattgefunden.', 'enroute_offers' ); ?></p>
                <?php elseif ( enroute_event_registration_enabled( $post_id ) ) : // checkbox "Anmeldung per Formular aktivieren" ?>
                <button type="button" @click="registrationOpen = true"
                    style="width:100%; padding:0.5rem 1rem; background:<?php echo esc_attr( $button_color_green ); ?>; border:none; font-weight:600; font-size:1.25rem; cursor:pointer; text-align:center;">
                    <?php esc_html_e( 'Anmelden', 'enroute_offers' ); ?>
                </button>

                <!-- Backdrop -->
                <template x-teleport="body">
                <div x-show="registrationOpen" @click="registrationOpen = false"
                     style="display:none; position:fixed; inset:0; top:var(--wp-admin--admin-bar--height,0px); z-index:9998; background:rgba(0,0,0,0.5);"></div>
                </template>

                <!-- Registration drawer -->
                <template x-teleport="body">
                <div x-show="registrationOpen" @click.stop
                     style="display:none; position:fixed; right:0; top:var(--wp-admin--admin-bar--height,0px); bottom:0; width:28rem; max-width:100vw; background:#dbe442; z-index:9999; overflow-y:auto; box-shadow:-4px 0 24px rgba(0,0,0,0.18);"
                     x-data="enrouteEventRegistration(<?php echo (int) $post_id; ?>)"
                     @enroute:loggedin.window="prefillFromProfile($event.detail)">

                    <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:2px solid rgba(0,0,0,0.15); background:#dbe442; position:sticky; top:0; z-index:1;">
                        <span style="font-weight:700; font-size:1.05rem;"><?php esc_html_e( 'Anmeldung', 'enroute_offers' ); ?></span>
                        <button @click="registrationOpen = false" style="background:none; border:none; cursor:pointer; padding:4px;" aria-label="<?php esc_attr_e( 'Schliessen', 'enroute_offers' ); ?>">
                            <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div style="padding:1rem 1.25rem; border-bottom:1px solid rgba(0,0,0,0.15);">
                        <p style="font-weight:700; margin:0; font-size:0.95rem;"><?php echo esc_html( get_the_title( $post_id ) ); ?></p>
                        <?php if ( $dates ) : ?><p style="margin:0.2rem 0 0; font-size:0.85rem; color:#374151;"><?php echo esc_html( $dates ); ?></p><?php endif; ?>
                    </div>

                    <!-- Success -->
                    <div x-show="submitted" style="display:none; padding:2rem 1.25rem; text-align:center;">
                        <svg style="width:3rem;height:3rem;color:#166534;margin:0 auto 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <p style="font-weight:700; font-size:1rem; margin:0 0 0.5rem;"><?php esc_html_e( 'Anmeldung übermittelt!', 'enroute_offers' ); ?></p>
                        <p x-text="successMsg" style="font-size:0.9rem; margin:0; color:#374151;"></p>
                        <button @click="registrationOpen = false; submitted = false" style="margin-top:1.5rem; padding:0.75rem 1.5rem; background:#111; color:#fff; border:none; font-weight:600; cursor:pointer; font-size:0.9rem;">
                            <?php esc_html_e( 'Schliessen', 'enroute_offers' ); ?>
                        </button>
                    </div>

                    <!-- Form -->
                    <div x-show="!submitted" style="padding:1rem 1.25rem 2rem;">
                        <div x-show="errorMsg" style="display:none; margin-bottom:1rem; padding:0.75rem 1rem; background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; font-size:0.875rem;" x-text="errorMsg"></div>

                        <?php
                        $inp       = 'style="width:100%; padding:0.5rem 0.6rem; border:1px solid rgba(0,0,0,0.3); background:#fff; font-size:0.9rem; box-sizing:border-box;"';
                        $lbl       = 'style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.2rem; color:#374151;"';
                        $auth_hint = __( 'Optional — Sie können sich auch ohne Konto anmelden.', 'enroute_offers' );
                        include ENROUTE_OFFERS_PATH . 'templates/partials/form-auth.php';
                        include ENROUTE_OFFERS_PATH . 'templates/partials/form-contact.php';
                        include ENROUTE_OFFERS_PATH . 'templates/partials/form-group.php';
                        ?>

                        <button type="button" @click="submit()" :disabled="loading"
                            style="width:100%; padding:0.875rem 1rem; background:#111; color:#fff; border:none; font-weight:700; font-size:1rem; cursor:pointer;"
                            :style="loading ? { opacity: '0.6', cursor: 'not-allowed' } : {}">
                            <span x-show="!loading"><?php esc_html_e( 'Anmeldung senden', 'enroute_offers' ); ?></span>
                            <span x-show="loading"><?php esc_html_e( 'Wird gesendet…', 'enroute_offers' ); ?></span>
                        </button>
                        <p style="font-size:0.75rem; color:#555; margin:0.5rem 0 0;">* <?php esc_html_e( 'Pflichtfelder', 'enroute_offers' ); ?></p>
                    </div>
                </div>
                </template>
                <?php endif; ?>
            </div>

        </div><!-- /sidebar -->
    </div><!-- /layout -->
</article>
