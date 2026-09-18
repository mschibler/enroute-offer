<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$subjects      = get_terms([ 'taxonomy' => 'offer_subject',      'hide_empty' => true ]);
$target_groups = get_terms([ 'taxonomy' => 'offer_target_group', 'hide_empty' => true ]);
$offer_types   = get_terms([ 'taxonomy' => 'offer_type',         'hide_empty' => true ]);
$languages     = enroute_get_languages();
$weekdays      = enroute_get_weekdays();

$offers = get_posts([
    'post_type'      => 'offer',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

$offers_data = [];
foreach ( $offers as $i => $offer ) {
    // Image priority: 1) offer photo, 2) station activities photo, 3) station main photo
    $image_id  = get_post_meta( $offer->ID, '_offer_photo_id', true );
    $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
    if ( ! $image_url ) {
        $station_id = get_post_meta( $offer->ID, '_offer_station', true );
        if ( $station_id ) {
            $act_photo_id = get_post_meta( $station_id, '_station_activities_photo_id', true );
            if ( $act_photo_id ) {
                $image_url = wp_get_attachment_image_url( $act_photo_id, 'large' ) ?: '';
            }
            if ( ! $image_url ) {
                $main_photo_id = get_post_meta( $station_id, '_station_photo_id', true );
                if ( $main_photo_id ) {
                    $image_url = wp_get_attachment_image_url( $main_photo_id, 'large' ) ?: '';
                }
            }
        }
    }

    $ot_terms = get_the_terms( $offer->ID, 'offer_type' );
    $ot_names = $ot_ids = [];
    if ( $ot_terms && ! is_wp_error( $ot_terms ) ) {
        $ot_names = wp_list_pluck( $ot_terms, 'name' );
        $ot_ids   = array_map( 'intval', wp_list_pluck( $ot_terms, 'term_id' ) );
    }

    $subj_ids = [];
    $subj_terms = get_the_terms( $offer->ID, 'offer_subject' );
    if ( $subj_terms && ! is_wp_error( $subj_terms ) ) {
        $subj_ids = array_map( 'intval', wp_list_pluck( $subj_terms, 'term_id' ) );
    }

    $tg_ids = [];
    $tg_terms = get_the_terms( $offer->ID, 'offer_target_group' );
    if ( $tg_terms && ! is_wp_error( $tg_terms ) ) {
        $tg_ids = array_map( 'intval', wp_list_pluck( $tg_terms, 'term_id' ) );
    }

    $offers_data[] = [
        'id'               => $offer->ID,
        'title'            => get_the_title( $offer->ID ),
        'subtitle'         => get_post_meta( $offer->ID, '_offer_subtitle', true ) ?: '',
        'description'      => wp_strip_all_tags( get_post_meta( $offer->ID, '_offer_description', true ) ?: '' ),
        'permalink'        => get_permalink( $offer->ID ),
        'image'            => $image_url ?: '',
        'color'            => enroute_get_offer_color( $offer->ID, $i ),
        'offer_type_name'  => implode( ', ', $ot_names ),
        'offer_type_ids'   => $ot_ids,
        'subject_ids'      => $subj_ids,
        'target_group_ids' => $tg_ids,
        'weekdays'         => get_post_meta( $offer->ID, '_offer_weekdays',     true ) ?: [],
        'times_of_day'     => get_post_meta( $offer->ID, '_offer_times_of_day', true ) ?: [],
        'language'         => get_post_meta( $offer->ID, '_offer_language', true ) ?: '',
    ];
}

$uid          = 'eol_' . uniqid();
$json         = wp_json_encode( $offers_data );
$current_lang = function_exists( 'pll_current_language' ) ? pll_current_language() : substr( get_locale(), 0, 2 );
$current_lang = substr( $current_lang, 0, 2 ); // normalise de_CH → de

// Whether to show the filter button + drawer (shortcode attr "filter", default "yes")
$show_filter = ! isset( $args['filter'] ) || ! in_array( strtolower( (string) $args['filter'] ), [ 'no', 'false', '0' ], true );
?>
<script>window['<?php echo esc_js( $uid ); ?>'] = <?php echo $json; ?>;</script>

<div
    x-data="enrouteOffersListing(window['<?php echo esc_js( $uid ); ?>'], '<?php echo esc_js( $current_lang ); ?>')"
    id="<?php echo esc_attr( $uid ); ?>-wrap"
    class="enroute-offers-listing relative"
    x-init="
        const obs = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting && hasMore) loadMore();
        }, { rootMargin: '200px' });
        $nextTick(() => { if ($refs.sentinel) obs.observe($refs.sentinel); });
    "
>

    <!-- ── Toolbar ── -->
    <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1rem; flex-wrap:wrap;">
        <!-- Search -->
        <div style="position:relative; flex:1; min-width:200px;">
            <input
                type="text"
                x-model.debounce.300ms="searchQuery"
                @input="visibleCount = perPage"
                placeholder="<?php esc_attr_e( 'Suchen…', 'enroute_offers' ); ?>"
                style="width:100%; padding:0.5rem 2rem 0.5rem 2.25rem; border:1px solid #000; font-size:0.875rem; box-sizing:border-box; outline:none;"
            >
            <svg style="position:absolute; left:0.625rem; top:0.625rem; width:1rem; height:1rem; color:#9ca3af; pointer-events:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <button
                x-show="searchQuery"
                @click="searchQuery = ''; visibleCount = perPage"
                style="position:absolute; right:0.5rem; top:0.4rem; background:none; border:none; cursor:pointer; color:#9ca3af; font-size:1rem; line-height:1; padding:2px;"
                aria-label="<?php esc_attr_e( 'Suche löschen', 'enroute_offers' ); ?>"
            >✕</button>
        </div>
        <?php if ( $show_filter ) : ?>
            <button
                @click="filterOpen = true"
                class="inline-flex items-center gap-2 px-4 py-2 border border-black text-sm font-medium hover:bg-black hover:text-white transition-colors bg-[#B5DFFC] whitespace-nowrap"
            >
                <?php esc_html_e( 'Filter', 'enroute_offers' ); ?>
                <span
                    x-show="activeFilterCount > 0"
                    x-text="'(' + activeFilterCount + ')'"
                    class="text-xs"
                ></span>
            </button>
        <?php endif; ?>
    </div>

    <!-- ── Grid ──
         subgrid on rows: row 0 = image (aspect-ratio box), row 1 = colored band.
         Every card in the same grid column shares identical row heights automatically. -->
    <div class="enroute-offers-grid" style="display:grid; grid-template-columns:repeat(3,1fr); column-gap:1rem; row-gap:0;">
        <template x-for="offer in visible" :key="offer.id">
            <a :href="offer.permalink"
               class="enroute-card-wrap"
               style="display:grid; grid-row:span 2; grid-template-rows:subgrid; row-gap:0; text-decoration:none; color:inherit; border:none; outline:none; box-shadow:none; padding:0; margin:0 0 1rem 0; font-size:0; line-height:0; overflow:hidden;"
            >
                <!-- Image: aspect-ratio box -->
                <div :style="'display:block; width:100%; aspect-ratio:434/280; overflow:hidden; font-size:0; line-height:0; background:' + (offer.image ? 'transparent' : '#c2dcef') + ';'">
                    <img
                        :src="offer.image || ''"
                        :alt="offer.title"
                        :style="'width:100%; height:100%; object-fit:cover; object-position:center; transition:transform 0.3s ease; border:none; margin:0; padding:0; display:' + (offer.image ? 'block' : 'none') + ';'"
                        @mouseenter="$el.style.transform='scale(1.05)'"
                        @mouseleave="$el.style.transform='scale(1)'"
                    >
                    <div x-show="!offer.image" style="width:100%; height:100%; display:flex; align-items:center; justify-content:center;">
                        <svg style="width:3rem;height:3rem;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <!-- Band: directly flush under image via subgrid -->
                <div :style="'display:block; font-size:1rem; line-height:1.5; padding:0.6rem 1rem 0.75rem; background:' + offer.color + ';'">
                    <span x-show="offer.offer_type_name" x-text="offer.offer_type_name" class="text-xs leading-5 tracking-widest"
                          style="display:block; font-weight:700; text-transform:uppercase; color:rgba(0,0,0,0.6);"></span>
                    <p x-text="offer.title" class="text-lg leading-5 pt-3"
                       style="margin:0; font-weight:700; color:#111827;"></p>
                    <p x-show="offer.subtitle" x-text="offer.subtitle" class="text-xs leading-4 pt-2 pb-3"
                       style="margin:0.15rem 0 0; color:#1f2937"></p>
                </div>
            </a>
        </template>

        <!-- Load more sentinel — watched by IntersectionObserver -->
        <div
            x-ref="sentinel"
            x-show="hasMore"
            style="height:1px; grid-column:1/-1;"
        ></div>

        <template x-if="filtered.length === 0">
            <div style="grid-column:1/-1; text-align:center; padding:4rem 0; color:#9ca3af;">
                <p><?php esc_html_e( 'Keine Angebote gefunden.', 'enroute_offers' ); ?></p>
                <button @click="resetFilters()" style="margin-top:0.75rem; font-size:0.875rem; text-decoration:underline; background:none; border:none; cursor:pointer;">
                    <?php esc_html_e( 'Filter zurücksetzen', 'enroute_offers' ); ?>
                </button>
            </div>
        </template>
    </div>

    <?php if ( $show_filter ) : ?>
        <!-- ── Filter Drawer (teleported to body to avoid stacking context clipping) ── -->

        <!-- Backdrop -->
        <template x-teleport="body">
        <div
            x-show="filterOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="filterOpen = false"
            style="display:none; position:fixed; inset:0; top: var(--wp-admin--admin-bar--height, 0px); z-index:9998; background: rgba(0,0,0,0.5);"
        ></div>
        </template>

        <!-- Drawer panel -->
        <template x-teleport="body">
        <div
            x-show="filterOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            style="display:none; position:fixed; right:0; top: var(--wp-admin--admin-bar--height, 0px); bottom:0; width:24rem; background:#d6e8f5; z-index:9999; overflow-y:auto; box-shadow: -4px 0 24px rgba(0,0,0,0.18);"
            @click.stop
        >
            <!-- Header row with close button -->
            <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #9ca3af; background:#d6e8f5; position:sticky; top:0; z-index:1;">
                <span style="font-weight:600; font-size:0.95rem;">Filter</span>
                <button
                    @click="filterOpen = false"
                    style="background:none; border:none; cursor:pointer; padding:4px; color:#374151;"
                    aria-label="<?php esc_attr_e( 'Filter schließen', 'enroute_offers' ); ?>"
                >
                    <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Accordion -->
            <div>

                <?php
                $sections = [];
                if ( $weekdays ) {
                    $sections[] = [ 'key' => 'weekday',      'label' => __( 'Wochentag', 'enroute_offers' ), 'items' => $weekdays,      'type' => 'kv' ];
                }
                if ( $subjects && ! is_wp_error( $subjects ) ) {
                    $sections[] = [ 'key' => 'subject',      'label' => __( 'Thema', 'enroute_offers' ),     'terms' => $subjects,      'type' => 'term' ];
                }
                if ( $target_groups && ! is_wp_error( $target_groups ) ) {
                    $sections[] = [ 'key' => 'target_group', 'label' => __( 'Zielgruppe', 'enroute_offers' ),'terms' => $target_groups, 'type' => 'term' ];
                }
                if ( $offer_types && ! is_wp_error( $offer_types ) ) {
                    $sections[] = [ 'key' => 'offer_type',   'label' => __( 'Angebot', 'enroute_offers' ),   'terms' => $offer_types,   'type' => 'term' ];
                }
                $sections[] = [ 'key' => 'language', 'label' => __( 'Sprache', 'enroute_offers' ), 'items' => $languages, 'type' => 'kv' ];
                ?>

                <?php foreach ( $sections as $sec ) : ?>
                <div x-data="{ open: false }" class="border-b border-gray-400">
                    <button
                        type="button"
                        @click="open = !open"
                        class="w-full flex items-center justify-between px-5 py-4 text-left font-semibold text-gray-900 hover:bg-[#c2dcef] transition-colors"
                    >
                        <span><?php echo esc_html( $sec['label'] ); ?></span>
                        <span x-show="!open" class="text-xl leading-none">+</span>
                        <span x-show="open"  class="text-xl leading-none">&times;</span>
                    </button>
                    <div x-show="open" class="px-5 pb-4">
                        <?php if ( $sec['type'] === 'term' ) : ?>
                            <div class="columns-2 gap-x-4">
                            <?php foreach ( $sec['terms'] as $term ) : ?>
                                <label class="flex items-center gap-2 py-0.5 cursor-pointer text-sm">
                                    <input type="checkbox"
                                        :checked="pending.<?php echo esc_js( $sec['key'] ); ?>.includes(<?php echo (int) $term->term_id; ?>)"
                                        @change="toggleMultiFilter('<?php echo esc_js( $sec['key'] ); ?>', <?php echo (int) $term->term_id; ?>)"
                                        class="accent-gray-900">
                                    <?php echo esc_html( $term->name ); ?>
                                </label>
                            <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <?php foreach ( $sec['items'] as $code => $label ) : ?>
                                <label class="flex items-center gap-2 py-0.5 cursor-pointer text-sm">
                                    <input type="checkbox"
                                        :checked="pending.<?php echo esc_js( $sec['key'] ); ?>.includes('<?php echo esc_js( $code ); ?>')"
                                        @change="toggleMultiFilter('<?php echo esc_js( $sec['key'] ); ?>', '<?php echo esc_js( $code ); ?>')"
                                        class="accent-gray-900">
                                    <?php echo esc_html( $label ); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>

            <!-- Buttons -->
            <div class="flex gap-3 px-5 py-4 border-t border-gray-400">
                <button
                    @click="applyFilters(); filterOpen = false"
                    class="flex-1 px-4 py-2 border border-black bg-white text-black text-sm font-medium hover:bg-black hover:text-white transition-colors"
                ><?php esc_html_e( 'Anwenden', 'enroute_offers' ); ?></button>
                <button
                    @click="resetFilters()"
                    class="flex-1 px-4 py-2 bg-black text-white text-sm font-medium hover:bg-gray-800 transition-colors"
                ><?php esc_html_e( 'Zurücksetzen', 'enroute_offers' ); ?></button>
            </div>
        </div>
        </template>
    <?php endif; ?>

</div>
