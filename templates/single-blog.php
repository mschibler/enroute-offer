<?php
/**
 * Blog single post template.
 * Loaded via template_include filter in shortcodes.php for post type 'post'.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$post_id    = get_the_ID();
$title      = get_the_title();
$date       = get_the_date( 'd.m.Y' );
$content    = apply_filters( 'the_content', get_the_content() );
$img_url    = get_the_post_thumbnail_url( $post_id, 'full' ) ?: '';
$cats       = wp_get_post_categories( $post_id, [ 'fields' => 'all' ] );
$cat_name   = $cats ? $cats[0]->name : '';

// Color: from first category's image meta color, or default yellow
$color = '#EBD84A';
if ( $cats ) {
    $cat_img = get_term_meta( $cats[0]->term_id, 'category_image_id', true );
    // Just use yellow as default — admin can extend later
}

// Guide author
$guide_id    = get_post_meta( $post_id, '_blog_guide_author_id', true );
$guide_name  = $guide_id ? get_the_title( (int) $guide_id ) : '';
$guide_photo = '';
if ( $guide_id ) {
    $guide_photo_id = get_post_meta( (int) $guide_id, '_guide_photo_id', true );
    if ( $guide_photo_id ) $guide_photo = wp_get_attachment_image_url( (int) $guide_photo_id, 'thumbnail' ) ?: '';
}

// Sidebar: all posts in current language, same category
$current_lang = function_exists( 'pll_current_language' ) ? pll_current_language() : substr( get_locale(), 0, 2 );
$sidebar_args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'post__not_in'   => [ $post_id ],
];
if ( function_exists( 'pll_current_language' ) ) $sidebar_args['lang'] = $current_lang;
$sidebar_posts = get_posts( $sidebar_args );

// Build sidebar post data
$all_cats   = [];
$sidebar_data = [];
foreach ( $sidebar_posts as $sp ) {
    $sp_cats    = wp_get_post_categories( $sp->ID, [ 'fields' => 'all' ] );
    $sp_cat_ids = array_map( fn($c) => (int) $c->term_id, $sp_cats );
    $sp_cat_name = $sp_cats ? $sp_cats[0]->name : '';
    $sp_img     = get_the_post_thumbnail_url( $sp->ID, 'medium' ) ?: '';

    // Guide photo fallback
    if ( ! $sp_img ) {
        $sp_guide = get_post_meta( $sp->ID, '_blog_guide_author_id', true );
        if ( $sp_guide ) {
            $sp_gphoto = get_post_meta( (int) $sp_guide, '_guide_photo_id', true );
            if ( $sp_gphoto ) $sp_img = wp_get_attachment_image_url( (int) $sp_gphoto, 'enroute-guide-square' ) ?: wp_get_attachment_image_url( (int) $sp_gphoto, 'medium' ) ?: '';
        }
    }
    // Category image fallback
    if ( ! $sp_img && $sp_cats ) {
        foreach ( $sp_cat_ids as $sp_cid ) {
            $sp_img = enroute_get_cat_image_url( $sp_cid, 'medium' );
            if ( $sp_img ) break;
        }
    }

    foreach ( $sp_cat_ids as $cid ) $all_cats[$cid] = $sp_cat_name;

    $sidebar_data[] = [
        'id'        => $sp->ID,
        'title'     => html_entity_decode( get_the_title( $sp->ID ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
        'excerpt'   => wp_trim_words( $sp->post_excerpt ?: wp_strip_all_tags( $sp->post_content ), 15, '…' ),
        'permalink' => get_permalink( $sp->ID ),
        'image'     => $sp_img,
        'cat_ids'   => $sp_cat_ids,
        'cat_name'  => $sp_cat_name,
    ];
}

$sidebar_cats = [];
foreach ( $all_cats as $cid => $cname ) {
    if ( $cname ) $sidebar_cats[] = [ 'id' => $cid, 'name' => $cname ];
}

$uid = 'ebs_' . uniqid();
?>

<script>window['<?php echo esc_js( $uid ); ?>'] = <?php echo wp_json_encode( $sidebar_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;</script>

<div id="blog-single-wrap" style="max-width:1350px; margin:0 auto; padding:0 1rem;">
    <div style="display:grid; grid-template-columns:1fr 380px; gap:2.5rem; align-items:start;">

        <!-- ── LEFT: Article ───────────────────────────────────────────────── -->
        <article style="min-width:0;">

            <!-- Image + color bar overlay -->
            <div style="position:relative; width:100%; overflow:hidden; margin-bottom:2rem;">
                <?php if ( $img_url ) : ?>
                <img src="<?php echo esc_url( $img_url ); ?>"
                     alt="<?php echo esc_attr( $title ); ?>"
                     style="width:100%; display:block; max-height:500px; object-fit:cover; object-position:center; margin:0;">
                <?php endif; ?>
                <!-- Color bar with date + title over image bottom -->
                <div style="position:<?php echo $img_url ? 'absolute' : 'relative'; ?>; bottom:0; left:0; right:0;
                            background:<?php echo esc_attr( $color ); ?>; padding:1rem 1.25rem 1.25rem;">
                    <p style="margin:0 0 0.25rem; font-size:0.8rem; font-weight:600; color:rgba(0,0,0,0.6); font-family:Montserrat,sans-serif;">
                        <?php echo esc_html( $date ); ?>
                    </p>
                    <h1 style="margin:0; font-size:1.4rem; font-weight:700; line-height:1.25; color:#111; font-family:Montserrat,sans-serif;">
                        <?php echo esc_html( $title ); ?>
                    </h1>
                </div>
            </div>

            <!-- Guide author -->
            <?php if ( $guide_name ) : ?>
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.5rem;">
                <?php if ( $guide_photo ) : ?>
                <img src="<?php echo esc_url( $guide_photo ); ?>" alt="<?php echo esc_attr( $guide_name ); ?>"
                     style="width:3rem; height:3rem; border-radius:50%; object-fit:cover; flex-shrink:0;">
                <?php endif; ?>
                <span style="font-size:0.875rem; font-family:Montserrat,sans-serif; color:#4b5563;">
                    <?php echo esc_html( $guide_name ); ?>
                </span>
            </div>
            <?php endif; ?>

            <!-- Content -->
            <div style="font-size:1rem; line-height:1.7; color:#1f2937; font-family:Montserrat,sans-serif; max-width:72ch;">
                <?php echo wp_kses_post( $content ); ?>
            </div>

        </article>

        <!-- ── RIGHT: Sidebar listing ─────────────────────────────────────── -->
        <aside
            x-data="enrouteBlogSidebar(window['<?php echo esc_js( $uid ); ?>'])"
            style="position:sticky; top:1rem;"
        >
            <!-- Category filter -->
            <?php if ( $sidebar_cats ) : ?>
            <div style="display:flex; gap:0.4rem; flex-wrap:wrap; margin-bottom:1rem;">
                <button @click="activeCategory = null"
                        :style="activeCategory === null ? 'background:#111; color:#fff;' : 'background:#fff; color:#111;'"
                        style="padding:0.3rem 0.75rem; border:1px solid #111; font-size:0.8rem; cursor:pointer;">
                    <?php esc_html_e( 'Alle', 'enroute_offers' ); ?>
                </button>
                <?php foreach ( $sidebar_cats as $cat ) : ?>
                <button @click="activeCategory = <?php echo (int) $cat['id']; ?>"
                        :style="activeCategory === <?php echo (int) $cat['id']; ?> ? 'background:#111; color:#fff;' : 'background:#fff; color:#111;'"
                        style="padding:0.3rem 0.75rem; border:1px solid #111; font-size:0.8rem; cursor:pointer;">
                    <?php echo esc_html( $cat['name'] ); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Posts list -->
            <div style="display:flex; flex-direction:column; gap:1rem;">
                <template x-for="post in filtered" :key="post.id">
                    <a :href="post.permalink"
                       style="display:block; text-decoration:none; color:inherit; font-size:0; line-height:0; overflow:hidden;">
                        <!-- Image -->
                        <div style="width:100%; padding-top:56.25%; position:relative; overflow:hidden; background:#e5e7eb;">
                            <template x-if="post.image">
                                <img :src="post.image" :alt="post.title"
                                     style="position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover; display:block;">
                            </template>
                        </div>
                        <!-- Text -->
                        <div style="padding:0.5rem 0.75rem 0.75rem; background:#f9f9f9; font-size:1rem; line-height:1.4;">
                            <span x-show="post.cat_name" x-text="post.cat_name"
                                  style="display:block; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:rgba(0,0,0,0.5); font-family:Montserrat,sans-serif;"></span>
                            <span x-text="post.title"
                                  style="display:block; font-size:0.9rem; font-weight:700; padding-top:0.25rem; font-family:Montserrat,sans-serif; color:#111;"></span>
                            <span x-show="post.excerpt" x-text="post.excerpt"
                                  style="display:block; font-size:0.75rem; color:#6b7280; padding-top:0.25rem; font-family:Montserrat,sans-serif;"></span>
                        </div>
                    </a>
                </template>
            </div>

        </aside>

    </div>
</div>

<!-- Mobile: sidebar below article -->
<style>
@media (max-width: 768px) {
    #blog-single-wrap > div { grid-template-columns: 1fr !important; }
    #blog-single-wrap aside { position: static !important; }
}
</style>

<?php get_footer(); ?>
