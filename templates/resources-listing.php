<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$subjects       = get_terms([ 'taxonomy' => 'offer_subject',      'hide_empty' => true ]);
$target_groups  = get_terms([ 'taxonomy' => 'offer_target_group', 'hide_empty' => true ]);
$resource_types = get_terms([ 'taxonomy' => 'resource_type',      'hide_empty' => true ]);
$languages      = enroute_get_languages();

$resources = get_posts([
    'post_type'      => 'resource',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
]);

if ( ! function_exists( 'enroute_mime_to_icon_key' ) ) {
    function enroute_mime_to_icon_key( string $mime ): string {
        if ( $mime === 'application/pdf' ) return 'pdf';
        if ( str_contains( $mime, 'word' ) ) return 'word';
        if ( str_contains( $mime, 'excel' ) || str_contains( $mime, 'spreadsheet' ) ) return 'excel';
        if ( str_contains( $mime, 'powerpoint' ) || str_contains( $mime, 'presentation' ) ) return 'ppt';
        return 'file';
    }
}

$resources_data = [];
foreach ( $resources as $res ) {
    $file_id  = get_post_meta( $res->ID, '_resource_file_id', true );
    $mime     = $file_id ? (string) get_post_mime_type( $file_id ) : '';
    $file_url = $file_id ? wp_get_attachment_url( $file_id ) : '';

    $rt_terms = get_the_terms( $res->ID, 'resource_type' );
    $rt_names = $rt_ids = [];
    if ( $rt_terms && ! is_wp_error( $rt_terms ) ) {
        $rt_names = wp_list_pluck( $rt_terms, 'name' );
        $rt_ids   = array_map( 'intval', wp_list_pluck( $rt_terms, 'term_id' ) );
    }

    $subj_ids = [];
    $subj_terms = get_the_terms( $res->ID, 'offer_subject' );
    if ( $subj_terms && ! is_wp_error( $subj_terms ) ) {
        $subj_ids = array_map( 'intval', wp_list_pluck( $subj_terms, 'term_id' ) );
    }

    $tg_ids = [];
    $tg_terms = get_the_terms( $res->ID, 'offer_target_group' );
    if ( $tg_terms && ! is_wp_error( $tg_terms ) ) {
        $tg_ids = array_map( 'intval', wp_list_pluck( $tg_terms, 'term_id' ) );
    }

    $resources_data[] = [
        'id'                 => $res->ID,
        'title'              => get_the_title( $res->ID ),
        'permalink'          => get_permalink( $res->ID ),
        'file_url'           => $file_url ?: '',
        'icon_key'           => enroute_mime_to_icon_key( $mime ),
        'resource_type_name' => implode( ', ', $rt_names ),
        'resource_type_ids'  => $rt_ids,
        'subject_ids'        => $subj_ids,
        'target_group_ids'   => $tg_ids,
        'language'           => get_post_meta( $res->ID, '_resource_language', true ) ?: '',
    ];
}

$uid  = 'erl_' . uniqid();
$json = wp_json_encode( $resources_data );
?>

<!-- ── SVG sprites ── -->
<div id="enroute-icons" style="display:none" aria-hidden="true">
    <svg id="enroute-icon-pdf"   xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 17v-1h8v1H8zm0-3v-1h8v1H8zm0-3V10h5v1H8z"/></svg>
    <svg id="enroute-icon-word"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM9 17l-2-7h1.5l1.25 5 1.25-5H12l1.25 5 1.25-5H16l-2 7H9z"/></svg>
    <svg id="enroute-icon-excel" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 11h2l1.5 2.5L13 11h2l-2.5 4 2.5 4h-2l-1.5-2.5L10 19H8l2.5-4L8 11z"/></svg>
    <svg id="enroute-icon-ppt"   xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM9 11h3.5a2.5 2.5 0 010 5H10.5V19H9V11zm1.5 4H12a1 1 0 100-2h-1.5v2z"/></svg>
    <svg id="enroute-icon-file"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5z"/></svg>
    <!-- extra icon types matching screenshot -->
    <svg id="enroute-icon-audio" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v10.55A4 4 0 1014 17V7h4V3h-6z"/></svg>
    <svg id="enroute-icon-video" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 10.5V7a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h12a1 1 0 001-1v-3.5l4 4v-11l-4 4z"/></svg>
    <svg id="enroute-icon-image" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2zM8.5 13.5l2.5 3 3.5-4.5 4.5 6H5l3.5-4.5z"/></svg>
</div>

<script>window['<?php echo esc_js( $uid ); ?>'] = <?php echo $json; ?>;</script>

<div
    x-data="enrouteResourcesListing(window['<?php echo esc_js( $uid ); ?>'])"
    id="<?php echo esc_attr( $uid ); ?>-wrap"
    class="enroute-resources-listing relative"
>

    <!-- ── Toolbar ── -->
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-gray-500">
            <span x-text="filtered.length"></span> <?php esc_html_e( 'Ressourcen', 'enroute_offers' ); ?>
        </p>
        <button
            @click="filterOpen = true"
            class="inline-flex items-center gap-2 px-4 py-2 border border-black text-sm font-medium bg-[#B5DFFC] hover:bg-black hover:text-white transition-colors"
        >
            <?php esc_html_e( 'Filter', 'enroute_offers' ); ?>
            <span
                x-show="activeFilterCount > 0"
                x-text="'(' + activeFilterCount + ')'"
                class="text-xs"
            ></span>
        </button>
    </div>

    <!-- ── Grid ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <template x-for="res in filtered" :key="res.id">
            <a
                :href="res.file_url || res.permalink"
                :target="res.file_url ? '_blank' : '_self'"
                :rel="res.file_url ? 'noopener noreferrer' : ''"
                class="flex items-center gap-3 p-4 bg-[#d6e8f5] hover:bg-[#c2dcef] transition-colors"
            >
                <!-- Icon -->
                <div
                    class="flex-shrink-0 w-12 h-12 flex items-center justify-center text-gray-700"
                    x-init="
                        const tpl = document.getElementById('enroute-icon-' + res.icon_key);
                        if (tpl) { const svg = tpl.cloneNode(true); svg.removeAttribute('id'); svg.setAttribute('class','w-10 h-10'); $el.appendChild(svg); }
                    "
                ></div>
                <!-- Text -->
                <div class="min-w-0">
                    <span
                        x-show="res.resource_type_name"
                        x-text="res.resource_type_name"
                        class="block text-xs font-normal text-gray-600 mb-0.5"
                    ></span>
                    <p x-text="res.title" class="text-sm font-bold text-gray-900 leading-snug"></p>
                </div>
            </a>
        </template>

        <template x-if="filtered.length === 0">
            <div class="col-span-full text-center py-16 text-gray-400">
                <p><?php esc_html_e( 'Keine Ressourcen gefunden.', 'enroute_offers' ); ?></p>
                <button @click="resetFilters()" class="mt-3 text-sm underline">
                    <?php esc_html_e( 'Filter zurücksetzen', 'enroute_offers' ); ?>
                </button>
            </div>
        </template>
    </div>

    <!-- ── Filter Drawer (right side overlay) ── -->
    <!-- Teleport to body so fixed positioning is never clipped by stacking contexts -->

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
        <!-- Drawer header with close button -->
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; border-bottom:1px solid #9ca3af; background:#d6e8f5; position:sticky; top:0; z-index:1;">
            <span style="font-weight:700; font-size:1rem;"><?php esc_html_e( 'Filter', 'enroute_offers' ); ?></span>
            <button
                @click="filterOpen = false"
                style="background:none; border:none; cursor:pointer; padding:4px; line-height:1; color:#374151;"
                aria-label="<?php esc_attr_e( 'Filter schließen', 'enroute_offers' ); ?>"
            >
                <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Accordion sections -->
        <div>

            <?php
            // Build accordion sections config
            $sections = [];

            if ( $resource_types && ! is_wp_error( $resource_types ) ) {
                $sections[] = [
                    'key'    => 'resource_type',
                    'label'  => __( 'Medientyp', 'enroute_offers' ),
                    'terms'  => $resource_types,
                    'type'   => 'term',
                ];
            }
            if ( $subjects && ! is_wp_error( $subjects ) ) {
                $sections[] = [
                    'key'   => 'subject',
                    'label' => __( 'Thema', 'enroute_offers' ),
                    'terms' => $subjects,
                    'type'  => 'term',
                ];
            }
            if ( $target_groups && ! is_wp_error( $target_groups ) ) {
                $sections[] = [
                    'key'   => 'target_group',
                    'label' => __( 'Zielgruppe', 'enroute_offers' ),
                    'terms' => $target_groups,
                    'type'  => 'term',
                ];
            }
            $sections[] = [
                'key'   => 'language',
                'label' => __( 'Sprache', 'enroute_offers' ),
                'items' => $languages,
                'type'  => 'kv',
            ];
            ?>

            <?php foreach ( $sections as $i => $sec ) : ?>
            <div
                x-data="{ open: false }"
                class="border-b border-gray-400"
            >
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
                                <input
                                    type="checkbox"
                                    :checked="pending.<?php echo esc_js( $sec['key'] ); ?>.includes(<?php echo (int) $term->term_id; ?>)"
                                    @change="toggleMultiFilter('<?php echo esc_js( $sec['key'] ); ?>', <?php echo (int) $term->term_id; ?>)"
                                    class="accent-gray-900"
                                >
                                <?php echo esc_html( $term->name ); ?>
                            </label>
                        <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <?php foreach ( $sec['items'] as $code => $label ) : ?>
                            <label class="flex items-center gap-2 py-0.5 cursor-pointer text-sm">
                                <input
                                    type="checkbox"
                                    :checked="pending.<?php echo esc_js( $sec['key'] ); ?>.includes('<?php echo esc_js( $code ); ?>')"
                                    @change="toggleMultiFilter('<?php echo esc_js( $sec['key'] ); ?>', '<?php echo esc_js( $code ); ?>')"
                                    class="accent-gray-900"
                                >
                                <?php echo esc_html( $label ); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

        </div><!-- /accordion -->

        <!-- Buttons (flow naturally below filters) -->
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

    </div><!-- /drawer -->
    </template>

</div>
