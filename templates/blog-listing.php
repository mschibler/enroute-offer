<?php
/**
 * Blog listing template.
 * Shortcode: [enroute_blog_listing]
 * Shows posts for current Polylang language, filterable by category.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Current language
$current_lang = function_exists( 'pll_current_language' ) ? pll_current_language() : substr( get_locale(), 0, 2 );
$current_lang = substr( $current_lang, 0, 2 );

// Get all categories that have posts in current language
// When Polylang is active, query with lang parameter
$query_args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
    // Exclude uncategorised default WP posts by requiring at least one category
    'cat'            => implode( ',', array_map( fn($t) => $t->term_id, get_terms( [ 'taxonomy' => 'category', 'hide_empty' => true, 'fields' => 'all' ] ) ) ) ?: '-1',
];
if ( function_exists( 'pll_current_language' ) ) {
    $query_args['lang'] = $current_lang;
}

$all_posts = get_posts( $query_args );

// Get all categories that have at least one published post
$categories = get_categories( [ 'hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC' ] );

// Build category image map: cat_id => image_url (from term meta or fallback)
$cat_images = [];
foreach ( $categories as $cat ) {
    $img_id  = get_term_meta( $cat->term_id, 'category_image_id', true );
    $img_url = $img_id ? wp_get_attachment_image_url( (int) $img_id, 'large' ) : '';
    $cat_images[ $cat->term_id ] = $img_url ?: '';
}

// Build data for Alpine
$uid       = 'ebl_' . uniqid();
$posts_data = [];
foreach ( $all_posts as $p ) {
    $cats     = wp_get_post_categories( $p->ID, [ 'fields' => 'all' ] );
    $cat_ids  = array_map( fn($c) => (int) $c->term_id, $cats );
    $cat_name = $cats ? $cats[0]->name : '';

    // a) Post's own featured image
    $thumb_url = get_the_post_thumbnail_url( $p->ID, 'large' ) ?: '';

    // b) Guide author's photo if no post image
    if ( ! $thumb_url ) {
        $guide_id = get_post_meta( $p->ID, '_blog_guide_author_id', true );
        if ( $guide_id ) {
            $guide_photo_id = get_post_meta( (int) $guide_id, '_guide_photo_id', true );
            if ( $guide_photo_id ) {
                $thumb_url = wp_get_attachment_image_url( (int) $guide_photo_id, 'large' ) ?: '';
            }
        }
    }

    // c) Category default image
    if ( ! $thumb_url && $cat_ids ) {
        foreach ( $cat_ids as $cid ) {
            if ( ! empty( $cat_images[ $cid ] ) ) {
                $thumb_url = $cat_images[ $cid ];
                break;
            }
        }
    }

    // Determine image type for display style
    $has_post_image  = (bool) get_the_post_thumbnail_url( $p->ID, 'large' );
    $guide_id_check  = get_post_meta( $p->ID, '_blog_guide_author_id', true );
    $is_guide_photo  = ! $has_post_image && $guide_id_check && get_post_meta( (int) $guide_id_check, '_guide_photo_id', true );

    $posts_data[] = [
        'id'           => $p->ID,
        'title'        => html_entity_decode( get_the_title( $p->ID ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
        'excerpt'      => html_entity_decode( wp_trim_words( $p->post_excerpt ?: wp_strip_all_tags( $p->post_content ), 20, '…' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
        'permalink'    => get_permalink( $p->ID ),
        'image'        => $thumb_url,
        'is_guide_photo' => (bool) $is_guide_photo,
        'cat_ids'      => $cat_ids,
        'cat_name'     => $cat_name,
        'date'         => get_the_date( 'd.m.Y', $p->ID ),
    ];
}
?>

<script>window['<?php echo esc_js( $uid ); ?>'] = <?php echo wp_json_encode( $posts_data ); ?>;</script>

<div
    id="<?php echo esc_attr( $uid ); ?>-wrap"
    x-data="enrouteBlogListing(window['<?php echo esc_js( $uid ); ?>'])"
    x-init="
        const obs = new IntersectionObserver(entries => {
            if (entries[0].isIntersecting && hasMore) loadMore();
        }, { rootMargin: '200px' });
        $nextTick(() => { if ($refs.sentinel) obs.observe($refs.sentinel); });
    "
>

    <!-- ── Category filter ── -->
    <?php if ( $categories ) : ?>
    <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1.5rem;">
        <button
            @click="activeCategory = null; visibleCount = perPage"
            :style="activeCategory === null ? 'background:#111; color:#fff;' : 'background:#fff; color:#111;'"
            style="padding:0.4rem 1rem; border:1px solid #111; font-size:0.85rem; cursor:pointer; transition:all 0.15s;"
        ><?php esc_html_e( 'Alle', 'enroute_offers' ); ?></button>
        <?php foreach ( $categories as $cat ) : ?>
        <button
            @click="activeCategory = <?php echo (int) $cat->term_id; ?>; visibleCount = perPage"
            :style="activeCategory === <?php echo (int) $cat->term_id; ?> ? 'background:#111; color:#fff;' : 'background:#fff; color:#111;'"
            style="padding:0.4rem 1rem; border:1px solid #111; font-size:0.85rem; cursor:pointer; transition:all 0.15s;"
        ><?php echo esc_html( $cat->name ); ?></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ── Grid ── -->
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:1.5rem;">

        <template x-for="post in visible" :key="post.id">
            <a
                :href="post.permalink"
                style="display:block; text-decoration:none; color:inherit; background:#fff; margin:0; padding:0; box-sizing:border-box; overflow:hidden;"
            >
                <!-- Image -->
                <div style="width:100%; padding-top:56.25%; overflow:hidden; background:#e5e7eb; position:relative; display:block; margin:0; padding-left:0; padding-right:0; padding-bottom:0;">
                    <template x-if="post.image">
                        <img
                            :src="post.image"
                            :alt="post.title"
                            :style="post.is_guide_photo
                                ? 'position:absolute; top:0; left:0; width:100%; height:100%; object-fit:contain; object-position:center; display:block; margin:0; padding:0; border:none; vertical-align:top;'
                                : 'position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; object-position:center; display:block; margin:0; padding:0; border:none; vertical-align:top;'"
                        >
                    </template>
                    <template x-if="!post.image">
                        <div style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#9ca3af;">
                            <svg style="width:2rem;height:2rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </template>
                </div>

                <!-- Text -->
                <div style="padding:0.875rem 1rem 1rem; line-height:1.4;">
                    <p
                        x-show="post.cat_name"
                        x-text="post.cat_name"
                        style="margin:0 0 0.4rem; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:#6b7280;"
                    ></p>
                    <p
                        x-text="post.title"
                        style="margin:0 0 0.35rem; font-weight:700; font-size:1rem; line-height:1.3;"
                    ></p>
                    <p
                        x-show="post.excerpt"
                        x-text="post.excerpt"
                        style="margin:0; font-size:0.82rem; color:#4b5563; line-height:1.4;"
                    ></p>
                </div>
            </a>
        </template>

        <!-- Infinite scroll sentinel -->
        <div x-ref="sentinel" x-show="hasMore" style="height:1px; grid-column:1/-1;"></div>

        <!-- No results -->
        <template x-if="filtered.length === 0">
            <div style="grid-column:1/-1; text-align:center; padding:3rem 0; color:#9ca3af;">
                <p><?php esc_html_e( 'Keine Beiträge gefunden.', 'enroute_offers' ); ?></p>
            </div>
        </template>

    </div>

</div>
