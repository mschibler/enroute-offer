<?php
/**
 * Guide listing template.
 * Used by shortcode [enroute_guides_listing order="alpha|random"]
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Shortcode attribute: order ─────────────────────────────────────────────
// Passed in via $args variable set by the shortcode wrapper.
$order_mode = isset( $args['order'] ) ? $args['order'] : 'alpha';

// ── Query all published guides ─────────────────────────────────────────────
$query_args = [
    'post_type'      => 'guide',
    'post_status'    => 'publish',
    'numberposts'    => -1,
    'orderby'        => $order_mode === 'random' ? 'rand' : 'title',
    'order'          => 'ASC',
];
$guide_posts = get_posts( $query_args );

if ( empty( $guide_posts ) ) {
    echo '<p>' . esc_html__( 'No guides found.', 'enroute_offers' ) . '</p>';
    return;
}

// ── Build data array for Alpine ────────────────────────────────────────────
$enroute_palette = [ '#dbe442', '#fce300', '#fed141', '#ff6a39', '#ef4a81' ];
$guides_data     = [];

foreach ( $guide_posts as $i => $g ) {
    $photo_id  = get_post_meta( $g->ID, '_guide_photo_id', true );
    $photo_url = $photo_id ? wp_get_attachment_image_url( $photo_id, 'large' ) : '';
    $photo_src = $photo_url ?: '';

    $guides_data[] = [
        'id'    => $g->ID,
        'name'  => $g->post_title,
        'quote' => get_post_meta( $g->ID, '_guide_quote',       true ),
        'text'  => wpautop(get_post_meta( $g->ID, '_guide_description', true )),
        'photo' => $photo_src,
        'color' => $enroute_palette[ $g->ID % count( $enroute_palette ) ],
    ];
    //echo wpautop(get_post_meta( $g->ID, '_guide_description', true ));
}


// ── Pass data to Alpine via window (avoid wptexturize mangling) ────────────
// Use single-quoted window key so it is safe inside an HTML attribute value.
$uid = 'egl_' . uniqid();
?>
<script>window['<?php echo esc_js( $uid ); ?>'] = <?php echo wp_json_encode( $guides_data ); ?>;</script>

<div
    class="enroute-guides-listing"
    x-data="enrouteGuidesListing(window['<?php echo esc_js( $uid ); ?>'])"
    @keydown.escape.window="closeModal()"
>

    <!-- ── Photo grid ─────────────────────────────────────────────────────── -->
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem;">

        <template x-for="guide in guides" :key="guide.id">
            <div
                @click="openModal(guide)"
                class="enroute-guide-card"
                style="position:relative; cursor:pointer; overflow:hidden; aspect-ratio:1/1; background:#d1d5db; display:block;"
            >
                <!-- Photo: absolutely fills the card, no implicit inline space -->
                <img
                    :src="guide.photo"
                    :alt="guide.name"
                    x-show="guide.photo"
                    style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover; object-position:top center; display:block; filter:grayscale(100%); margin:0; padding:0; border:none;"
                >

                <!-- Placeholder when no photo -->
                <div
                    x-show="!guide.photo"
                    style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center;"
                >
                    <svg style="width:3rem;height:3rem;color:#9ca3af;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                    </svg>
                </div>

                <!-- Name bar: hidden, slides up on hover via CSS -->
                <div
                    class="enroute-guide-name-bar"
                    :style="'background:' + guide.color + ';'"
                    x-text="guide.name"
                ></div>

            </div>
        </template>

    </div>

    <!-- ── Modal backdrop ─────────────────────────────────────────────────── -->
    <template x-teleport="body">
        <div
            x-show="modalOpen"
            @click="closeModal()"
            style="display:none; position:fixed; inset:0; top:var(--wp-admin--admin-bar--height,0px); z-index:9998; background:rgba(0,0,0,0.65);"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>
    </template>

    <!-- ── Modal panel ────────────────────────────────────────────────────── -->
    <template x-teleport="body">
        <div
            x-show="modalOpen && active"
            @click.stop
            style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:9999; width:min(860px,95vw); max-height:90vh; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.4);"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            <template x-if="active">
            <div style="display:grid; grid-template-columns:1fr 1fr; height:min(540px,80vh);">

                <!-- Left: photo -->
                <div style="overflow:hidden; background:#d1d5db; position:relative;">
                    <img
                        :src="active.photo"
                        :alt="active.name"
                        x-show="active.photo"
                        style="width:100%; height:100%; object-fit:cover; object-position:top center; filter:grayscale(100%);"
                    >
                    <div
                        x-show="!active.photo"
                        style="width:100%; height:100%; display:flex; align-items:center; justify-content:center;"
                    >
                        <svg style="width:5rem;height:5rem;color:#9ca3af;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                        </svg>
                    </div>
                </div>

                <!-- Right: info panel -->
                <div
                    :style="'background:' + active.color + '; padding:2rem 1.75rem; overflow-y:auto; position:relative; display:flex; flex-direction:column; justify-content:flex-start;'"
                >
                    <!-- Close button -->
                    <button
                        @click="closeModal()"
                        style="position:absolute; top:0.75rem; right:0.75rem; background:none; border:none; cursor:pointer; padding:4px; line-height:1;"
                        aria-label="<?php esc_attr_e( 'Close', 'enroute_offers' ); ?>"
                    >
                        <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <!-- Name -->
                    <p
                        x-text="active.name"
                        style="font-weight:900; letter-spacing:0.04em; text-transform:uppercase; margin:0 0 0.75rem; line-height:1.1; padding-right:2rem;"
                        class="font-montserrat text-4xl text-black pb-3"
                    ></p>

                    <!-- Quote -->
                    <p
                        x-show="active.quote"
                        x-text="active.quote"
                        style="font-weight:400; line-height:1.4; margin:0; color:#111;"
                        class="font-montserrat text-2xl text-black"
                    ></p>

                    <!-- Text -->
                    <p
                        x-show="active.text"
                        x-html="active.text.replaceAll('<p>', '<br>')"
                        style="line-height:1.6; margin:0; color:#222;"
                        class="font-montserrat text-lg text-black"
                    ></p>

                </div>

            </div>
            </template>
        </div>
    </template>

</div><!-- /.enroute-guides-listing -->
