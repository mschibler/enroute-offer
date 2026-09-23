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
    // Only import posts (exclude default WP sample post etc.) by requiring _old_cms_id
    'meta_query'     => [
        [ 'key' => '_old_cms_id', 'compare' => 'EXISTS' ],
    ],
];
if ( function_exists( 'pll_current_language' ) ) {
    $query_args['lang'] = $current_lang;
}

$all_posts = get_posts( $query_args );

// Build category list from actual posts
$cat_ids_used = [];
foreach ( $all_posts as $p ) {
    $cats = wp_get_post_categories( $p->ID );
    foreach ( $cats as $cid ) $cat_ids_used[] = $cid;
}
$cat_ids_used = array_unique( $cat_ids_used );
$categories   = $cat_ids_used ? get_categories( [ 'include' => $cat_ids_used, 'orderby' => 'name', 'hide_empty' => true ] ) : [];

// Build data for Alpine
$uid       = 'ebl_' . uniqid();
$posts_data = [];
foreach ( $all_posts as $p ) {
    $cats     = wp_get_post_categories( $p->ID, [ 'fields' => 'all' ] );
    $cat_ids  = array_map( fn($c) => (int) $c->term_id, $cats );
    $cat_name = $cats ? $cats[0]->name : '';

    $thumb_url = get_the_post_thumbnail_url( $p->ID, 'large' ) ?: '';

    $posts_data[] = [
        'id'        => $p->ID,
        'title'     => html_entity_decode( get_the_title( $p->ID ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
        'excerpt'   => html_entity_decode( wp_trim_words( $p->post_excerpt ?: wp_strip_all_tags( $p->post_content ), 20, '…' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
        'permalink' => get_permalink( $p->ID ),
        'image'     => $thumb_url,
        'cat_ids'   => $cat_ids,
        'cat_name'  => $cat_name,
        'date'      => get_the_date( 'd.m.Y', $p->ID ),
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
                style="display:block; text-decoration:none; color:inherit; overflow:hidden; background:#fff;"
            >
                <!-- Image -->
                <div style="width:100%; aspect-ratio:16/10; overflow:hidden; background:#e5e7eb;">
                    <img
                        x-show="post.image"
                        :src="post.image"
                        :alt="post.title"
                        style="width:100%; height:100%; object-fit:cover; display:block;"
                    >
                    <div
                        x-show="!post.image"
                        style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#9ca3af;"
                    >
                        <svg style="width:2rem;height:2rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>

                <!-- Text -->
                <div style="padding:0.875rem 1rem 1rem;">
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
