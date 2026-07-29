<?php
/**
 * Offer detail page template.
 * Included via shortcode [enroute_offer_detail] or auto-injected on single offer posts.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = get_the_ID();
if ( ! $post_id || get_post_type( $post_id ) !== 'offer' ) return;

// ── Meta values ────────────────────────────────────────────────────────────────
$subtitle      = get_post_meta( $post_id, '_offer_subtitle',       true );
$description   = get_post_meta( $post_id, '_offer_description',    true );
$price         = get_post_meta( $post_id, '_offer_price',          true );
$email         = get_post_meta( $post_id, '_offer_contact_email',  true );
$date_info     = get_post_meta( $post_id, '_offer_date_info',      true );
$fixed_date    = get_post_meta( $post_id, '_offer_fixed_date',     true );
$weekdays      = get_post_meta( $post_id, '_offer_weekdays',       true ) ?: [];
$times_of_day  = get_post_meta( $post_id, '_offer_times_of_day',  true ) ?: [];
$station_id    = get_post_meta( $post_id, '_offer_station',        true );
$language_code = get_post_meta( $post_id, '_offer_language',       true );
$resources_ids = get_post_meta( $post_id, '_offer_resources',      true ) ?: [];
$ext_links     = get_post_meta( $post_id, '_offer_external_links', true ) ?: [];

// ── Taxonomy terms ──────────────────────────────────────────────────────────────
$target_groups = get_the_terms( $post_id, 'offer_target_group' );
$subjects      = get_the_terms( $post_id, 'offer_subject' );
$offer_types   = get_the_terms( $post_id, 'offer_type' );

// ── Station ────────────────────────────────────────────────────────────────────
$station_name = $station_id ? get_the_title( $station_id ) : '';

// ── Language label ──────────────────────────────────────────────────────────────
$languages     = enroute_get_languages();
$language_label = $language_code && isset( $languages[ $language_code ] ) ? $languages[ $language_code ] : '';

// ── Weekday labels ─────────────────────────────────────────────────────────────
$all_weekdays = enroute_get_weekdays();
$weekday_labels = array_map( fn($d) => $all_weekdays[$d] ?? $d, $weekdays );

// ── Time of day labels ─────────────────────────────────────────────────────────
$all_times = enroute_get_times_of_day();
$time_labels = array_map( fn($t) => $all_times[$t] ?? $t, $times_of_day );

// ── Featured image ─────────────────────────────────────────────────────────────
$image_id  = get_post_thumbnail_id( $post_id );
$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
$image_alt = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : get_the_title( $post_id );

// ── Resources ─────────────────────────────────────────────────────────────────
$resources = [];
if ( ! empty( $resources_ids ) ) {
    $res_posts = get_posts([
        'post_type'   => 'resource',
        'post__in'    => array_map( 'intval', $resources_ids ),
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
    ]);
    foreach ( $res_posts as $r ) {
        $file_id  = get_post_meta( $r->ID, '_resource_file_id', true );
        $file_url = $file_id ? wp_get_attachment_url( $file_id ) : get_permalink( $r->ID );
        $resources[] = [ 'title' => $r->post_title, 'url' => $file_url ];
    }
}

// ── Offer type label (for header band) ─────────────────────────────────────────
$offer_type_label = '';
if ( $offer_types && ! is_wp_error( $offer_types ) ) {
    $offer_type_label = implode( ', ', wp_list_pluck( $offer_types, 'name' ) );
}

// ── Brand color palette (cycles across offers) ─────────────────────────────────
$enroute_palette    = [ '#dbe442', '#fce300', '#fed141', '#ff6a39', '#ef4a81' ];
$offer_color        = $enroute_palette[ $post_id % count( $enroute_palette ) ];
$button_color       = '#fce300'; // Pantone 102 — bright yellow for action buttons
$button_color_green = '#c9d56b'; // green button
?>

<article class="enroute-offer-detail">

    <?php if ( $image_url ) : ?>
    <!-- Hero image with title band overlaid at bottom -->
    <div class="relative w-full overflow-hidden" style="max-height:480px;">
        <img
            src="<?php echo esc_url( $image_url ); ?>"
            alt="<?php echo esc_attr( $image_alt ); ?>"
            class="w-full object-cover"
            style="max-height:480px;"
        >
        <div class="absolute bottom-0 left-0 p-6" style="background: <?php echo esc_attr( $offer_color ); ?>; max-width:75%;">
            <h1 class="text-4xl font-black font-montserrat font-semibold leading-tight mb-0"><?php the_title(); ?></h1>
        </div>
    </div>
    <?php else : ?>
    <div class="px-0 py-6">
        <h1 class="text-4xl font-black font-montserrat font-semibold leading-tight"><?php the_title(); ?></h1>
    </div>
    <?php endif; ?>

    <?php if ( $subtitle ) : ?>

    <div class="enroute-detail-layout" style="display:grid; grid-template-columns:1fr 340px; gap:2rem; align-items:start;">

        <div class="mt-0 mb-0 pl-6 pr-6">
            <p class="text-3xl font-montserrat font-normal leading-snug"><?php echo esc_html( $subtitle ); ?></p>
        </div>
        <div></div>
    </div>
    <?php endif; ?>

    <!-- hr class="border-gray-300 my-6"  -->

    <!-- Two-column layout: description left, sidebar right -->
    <div class="enroute-detail-layout" style="display:grid; grid-template-columns:1fr 340px; gap:2rem; align-items:start;">

        <!-- Left: main description (WP post content) -->
        <div class="enroute-detail-body prose max-w-none text-gray-800 font-montserrat text-lg">
            <hr class="border-gray-800 mt-0 mb-6">
            <!--hr class="border-gray-300 my-6" -->
            <div class="pl-6 pr-6">
            <?php
            $raw_content = get_post_field( 'post_content', $post_id, 'raw' );
            if ( $raw_content ) {
                echo wp_kses_post( wpautop( $raw_content ) );
            } elseif ( $description ) {
                echo wp_kses_post( wpautop( $description ) );
            }
            ?>
            </div>
        </div>

        <!-- Right: sidebar info box + action buttons -->
        <div class="enroute-detail-sidebar">
            <hr class="border-gray-800 mt-0 mb-6">
            <!-- Info box -->
            <div style="margin-bottom:1.5rem;" class="font-montserrat text-sm pb-12">

                <?php if ( $target_groups && ! is_wp_error( $target_groups ) ) : ?>
                <div class="enroute-detail-row" style="margin-bottom:1rem;">
                    <p style="font-weight:700; margin:0;"><?php esc_html_e( 'Zielgruppe', 'enroute_offers' ); ?></p>
                    <?php foreach ( $target_groups as $tg ) : ?>
                        <p style="margin:0; line-height:1.4;"><?php echo esc_html( $tg->name ); ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ( $weekday_labels || $time_labels || $date_info || $fixed_date ) : ?>
                <div class="enroute-detail-row" style="margin-bottom:1rem;">
                    <p style="font-weight:700; margin:0;"><?php esc_html_e( 'Daten', 'enroute_offers' ); ?></p>
                    <?php if ( $weekday_labels ) : ?>
                        <p style="margin:0; line-height:1.4;"><?php echo esc_html( implode( ', ', $weekday_labels ) ); ?></p>
                    <?php endif; ?>
                    <?php if ( $time_labels ) : ?>
                        <p style="margin:0; line-height:1.4;"><?php echo esc_html( implode( ', ', $time_labels ) ); ?></p>
                    <?php endif; ?>
                    <?php if ( $fixed_date ) : ?>
                        <p style="margin:0; line-height:1.4;"><?php echo esc_html( date_i18n( get_option('date_format'), strtotime( $fixed_date ) ) ); ?></p>
                    <?php endif; ?>
                    <?php if ( $date_info ) : ?>
                        <p style="margin:0; line-height:1.4;"><?php echo esc_html( $date_info ); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ( $price ) : ?>
                <div class="enroute-detail-row" style="margin-bottom:1rem;">
                    <p style="font-weight:700; margin:0;"><?php esc_html_e( 'Preis', 'enroute_offers' ); ?></p>
                    <p style="margin:0; line-height:1.4;"><?php echo esc_html( $price ); ?></p>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $resources ) ) : ?>
                <div class="enroute-detail-row" style="margin-bottom:1rem;">
                    <p style="font-weight:700; margin:0;"><?php esc_html_e( 'Lehrmittel', 'enroute_offers' ); ?></p>
                    <?php foreach ( $resources as $res ) : ?>
                        <p style="margin:0; line-height:1.4;">
                            <a href="<?php echo esc_url( $res['url'] ); ?>"
                               target="_blank" rel="noopener noreferrer"
                               style="color:inherit; text-decoration:underline;">
                                <?php echo esc_html( $res['title'] ); ?>
                            </a>
                        </p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ( ! empty( $ext_links ) ) : ?>
                <div class="enroute-detail-row" style="margin-bottom:1rem;">
                    <p style="font-weight:700; margin:0;"><?php esc_html_e( 'Links', 'enroute_offers' ); ?></p>
                    <?php foreach ( $ext_links as $link ) : ?>
                        <p style="margin:0; line-height:1.4;">
                            <a href="<?php echo esc_url( $link ); ?>"
                               target="_blank" rel="noopener noreferrer"
                               style="color:inherit; text-decoration:underline;">
                                <?php echo esc_html( $link ); ?>
                            </a>
                        </p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ( $station_name ) : ?>
                <div class="enroute-detail-row" style="margin-bottom:0;">
                    <p style="font-weight:700; margin:0;"><?php esc_html_e( 'Station', 'enroute_offers' ); ?></p>
                    <p style="margin:0; line-height:1.4;"><?php echo esc_html( $station_name ); ?></p>
                </div>
                <?php endif; ?>

            </div><!-- /info box -->

            <!-- Action buttons -->
            <div x-data="{ bookingOpen: false }" style="display:flex; flex-direction:column; gap:0.75rem;">
                <button
                    type="button"
                    onclick="window.print()"
                    style="width:100%; padding:0.5rem 1rem; background:<?php echo esc_attr( $button_color ); ?>; border:none; font-weight:600; font-size:1.25rem; cursor:pointer; text-align:center;"
                >
                    <?php esc_html_e( 'Drucken', 'enroute_offers' ); ?>
                </button>
                <button
                    type="button"
                    @click="bookingOpen = true"
                    style="width:100%; padding:0.5rem 1rem; background:<?php echo esc_attr( $button_color_green ); ?>; border:none; font-weight:600; font-size:1.25rem; cursor:pointer; text-align:center;"
                >
                    <?php esc_html_e( 'Buchen', 'enroute_offers' ); ?>
                </button>

                <!-- Backdrop -->
                <template x-teleport="body">
                <div
                    x-show="bookingOpen"
                    @click="bookingOpen = false"
                    style="display:none; position:fixed; inset:0; top:var(--wp-admin--admin-bar--height,0px); z-index:9998; background:rgba(0,0,0,0.5);"
                ></div>
                </template>

                <!-- Booking drawer -->
                <template x-teleport="body">
                <div
                    x-show="bookingOpen"
                    @click.stop
                    style="display:none; position:fixed; right:0; top:var(--wp-admin--admin-bar--height,0px); bottom:0; width:28rem; max-width:100vw; background:#dbe442; z-index:9999; overflow-y:auto; box-shadow:-4px 0 24px rgba(0,0,0,0.18);"
                    x-data="enrouteBookingForm()"
                >
                    <!-- Sticky header -->
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:2px solid rgba(0,0,0,0.15); background:#dbe442; position:sticky; top:0; z-index:1;">
                        <span style="font-weight:700; font-size:1.05rem;"><?php esc_html_e( 'Buchungsanfrage', 'enroute_offers' ); ?></span>
                        <button @click="bookingOpen = false" style="background:none; border:none; cursor:pointer; padding:4px;">
                            <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Offer title block -->
                    <div style="padding:1rem 1.25rem; border-bottom:1px solid rgba(0,0,0,0.15);">
                        <p style="font-weight:700; margin:0; font-size:0.95rem;"><?php echo esc_html( get_the_title( $post_id ) ); ?></p>
                        <?php if ( $subtitle ) : ?>
                        <p style="margin:0.2rem 0 0; font-size:0.85rem; color:#374151;"><?php echo esc_html( $subtitle ); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Success message -->
                    <div x-show="submitted" style="display:none; padding:2rem 1.25rem; text-align:center;">
                        <svg style="width:3rem;height:3rem;color:#166534;margin:0 auto 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p style="font-weight:700; font-size:1rem; margin:0 0 0.5rem;"><?php esc_html_e( 'Anfrage übermittelt!', 'enroute_offers' ); ?></p>
                        <p x-text="successMsg" style="font-size:0.9rem; margin:0; color:#374151;"></p>
                        <button @click="bookingOpen = false; submitted = false" style="margin-top:1.5rem; padding:0.75rem 1.5rem; background:#111; color:#fff; border:none; font-weight:600; cursor:pointer; font-size:0.9rem;">
                            <?php esc_html_e( 'Schliessen', 'enroute_offers' ); ?>
                        </button>
                    </div>

                    <!-- Form -->
                    <div x-show="!submitted" style="padding:1rem 1.25rem 2rem;">

                        <!-- Error message -->
                        <div x-show="errorMsg" style="display:none; margin-bottom:1rem; padding:0.75rem 1rem; background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; font-size:0.875rem;" x-text="errorMsg"></div>

                        <?php
                        // Input style helper
                        $inp = 'style="width:100%; padding:0.5rem 0.6rem; border:1px solid rgba(0,0,0,0.3); background:#fff; font-size:0.9rem; box-sizing:border-box;"';
                        $lbl = 'style="display:block; font-size:0.8rem; font-weight:600; margin-bottom:0.2rem; color:#374151;"';
                        ?>

                        <!-- Kontaktperson -->
                        <h3 style="font-size:0.95rem; font-weight:700; margin:1rem 0 0.75rem; padding-bottom:0.4rem; border-bottom:1px solid rgba(0,0,0,0.2);">
                            <?php esc_html_e( 'Kontaktperson', 'enroute_offers' ); ?>
                        </h3>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.6rem; margin-bottom:0.6rem;">
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

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.6rem; margin-bottom:0.6rem;">
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Vorname', 'enroute_offers' ); ?> *</label>
                                <input type="text" x-model="form.first_name" <?php echo $inp; ?>>
                            </div>
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Nachname', 'enroute_offers' ); ?> *</label>
                                <input type="text" x-model="form.last_name" <?php echo $inp; ?>>
                            </div>
                        </div>

                        <div style="margin-bottom:0.6rem;">
                            <label <?php echo $lbl; ?>><?php esc_html_e( 'Strasse', 'enroute_offers' ); ?></label>
                            <input type="text" x-model="form.street" <?php echo $inp; ?>>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 2fr; gap:0.6rem; margin-bottom:0.6rem;">
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'PLZ', 'enroute_offers' ); ?></label>
                                <input type="text" x-model="form.zip" <?php echo $inp; ?>>
                            </div>
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Ort', 'enroute_offers' ); ?></label>
                                <input type="text" x-model="form.place" <?php echo $inp; ?>>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.6rem; margin-bottom:0.6rem;">
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'E-Mail', 'enroute_offers' ); ?> *</label>
                                <input type="email" x-model="form.email" <?php echo $inp; ?>>
                            </div>
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Telefon', 'enroute_offers' ); ?></label>
                                <input type="tel" x-model="form.phone" <?php echo $inp; ?>>
                            </div>
                        </div>

                        <!-- Wunschdatum -->
                        <h3 style="font-size:0.95rem; font-weight:700; margin:1rem 0 0.75rem; padding-bottom:0.4rem; border-bottom:1px solid rgba(0,0,0,0.2);">
                            <?php esc_html_e( 'Wunschdatum', 'enroute_offers' ); ?>
                        </h3>
                        <div style="display:grid; grid-template-columns:2fr 1fr; gap:0.6rem; margin-bottom:0.6rem;">
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Datum', 'enroute_offers' ); ?> *</label>
                                <input type="date" x-model="form.date_1" <?php echo $inp; ?>>
                            </div>
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Uhrzeit', 'enroute_offers' ); ?></label>
                                <input type="time" x-model="form.time_1" <?php echo $inp; ?>>
                            </div>
                        </div>

                        <!-- Ersatzdatum -->
                        <h3 style="font-size:0.95rem; font-weight:700; margin:1rem 0 0.75rem; padding-bottom:0.4rem; border-bottom:1px solid rgba(0,0,0,0.2);">
                            <?php esc_html_e( 'Ersatzdatum', 'enroute_offers' ); ?>
                        </h3>
                        <div style="display:grid; grid-template-columns:2fr 1fr; gap:0.6rem; margin-bottom:0.6rem;">
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Datum', 'enroute_offers' ); ?></label>
                                <input type="date" x-model="form.date_2" <?php echo $inp; ?>>
                            </div>
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Uhrzeit', 'enroute_offers' ); ?></label>
                                <input type="time" x-model="form.time_2" <?php echo $inp; ?>>
                            </div>
                        </div>

                        <!-- Gruppe -->
                        <h3 style="font-size:0.95rem; font-weight:700; margin:1rem 0 0.75rem; padding-bottom:0.4rem; border-bottom:1px solid rgba(0,0,0,0.2);">
                            <?php esc_html_e( 'Gruppe', 'enroute_offers' ); ?>
                        </h3>
                        <div style="margin-bottom:0.6rem;">
                            <label <?php echo $lbl; ?>><?php esc_html_e( 'Anzahl Personen', 'enroute_offers' ); ?></label>
                            <input type="number" min="1" x-model="form.persons" <?php echo $inp; ?>>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label <?php echo $lbl; ?>><?php esc_html_e( 'Weitere Angaben', 'enroute_offers' ); ?></label>
                            <textarea x-model="form.remarks" rows="4" <?php echo $inp; ?> style="width:100%; padding:0.5rem 0.6rem; border:1px solid rgba(0,0,0,0.3); background:#fff; font-size:0.9rem; box-sizing:border-box; resize:vertical;"></textarea>
                        </div>

                        <!-- Submit -->
                        <button
                            type="button"
                            @click="submitBooking(<?php echo (int) $post_id; ?>)"
                            :disabled="loading"
                            style="width:100%; padding:0.875rem 1rem; background:#111; color:#fff; border:none; font-weight:700; font-size:1rem; cursor:pointer;"
                            :style="loading ? 'opacity:0.6; cursor:not-allowed;' : ''"
                        >
                            <span x-show="!loading"><?php esc_html_e( 'Anfrage Senden', 'enroute_offers' ); ?></span>
                            <span x-show="loading"><?php esc_html_e( 'Wird gesendet…', 'enroute_offers' ); ?></span>
                        </button>
                        <p style="font-size:0.75rem; color:#555; margin:0.5rem 0 0;">* <?php esc_html_e( 'Pflichtfelder', 'enroute_offers' ); ?></p>

                    </div><!-- /form -->
                </div>
                </template>

            </div><!-- /x-data bookingOpen -->

        </div><!-- /sidebar -->
    </div><!-- /layout -->


   <div class="grid grid-cols-3 gap-8 font-[montserrat] bg-[#B5DFFC] pt-8 pb-8 border-t border-b border-black mt-6">
       <div class="font-semibold text-2xl pl-6 pr-6">
           Benutzerpässe für Lehrpersonen
       </div>
       <div class="text-sm pl-6 pr-6">
           EduPass Mini: 250 CHF zahlen - für 350 CHF nutzen<br>
           EduPass Midi: 500 CHF zahlen - für 750 CHF nutzen<br>
           EduPass Maxi: 1000 CHF zahlen - für 1550 CHF nutzen<br>
           Das Guthaben ist ein Jahr ab Bestelldatum gültig.
       </div>
       <div class="text-sm pl-6 pr-6">Ein Upgrade auf einen höheren Pass ist jederzeit möglich, das Ablaufdatum bleibt dabei unverändert. Nicht genutztes Guthaben verfällt nach Ablauf und wird nicht rückerstattet. Bei Überschreitung wird der Differenzbetrag in Rechnung gestellt.
       </div>
   </div>

</article>
