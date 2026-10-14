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

// No featured image → use the category image (same lookup as the blog listing, works in all languages)
if ( ! $img_url && $cats ) {
    foreach ( $cats as $c ) {
        $img_url = enroute_get_cat_image_url( (int) $c->term_id, 'full' );
        if ( $img_url ) break;
    }
}

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

// Only posts of the current language (get_posts() ignores Polylang's 'lang' argument here).
// Posts without an assigned language are kept.
if ( function_exists( 'pll_get_post_language' ) && $current_lang ) {
    $sidebar_posts = array_values( array_filter( $sidebar_posts, function( $sp ) use ( $current_lang ) {
        $sp_lang = pll_get_post_language( $sp->ID );
        return ! $sp_lang || $sp_lang === $current_lang;
    } ) );
}

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

<script>window['<?php echo esc_js( $uid ); ?>'] = <?php echo wp_json_encode( enroute_js_data( $sidebar_data ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;</script>

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
            <!-- Same typography as the offer detail and regular pages (Tailwind "prose") -->
            <div class="enroute-blog-body prose max-w-none text-gray-800 font-montserrat text-lg">
                <?php echo wp_kses_post( $content ); ?>
            </div>

            <?php if ( $guide_id && get_post_status( (int) $guide_id ) === 'publish' ) :
                // ── Guide box: photo, name, short text + other posts by this guide ──
                $gb_photo_id = (int) get_post_meta( (int) $guide_id, '_guide_photo_id', true );
                $gb_photo    = $gb_photo_id ? ( wp_get_attachment_image_url( $gb_photo_id, 'enroute-guide-square' ) ?: wp_get_attachment_image_url( $gb_photo_id, 'medium' ) ) : '';
                $gb_text     = get_post_meta( (int) $guide_id, '_guide_blog_text', true );

                $gb_args = [
                    'post_type'      => 'post',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'post__not_in'   => [ $post_id ],
                    'meta_query'     => [ [ 'key' => '_blog_guide_author_id', 'value' => (int) $guide_id ] ],
                ];
                $gb_posts = get_posts( $gb_args );
                // Only posts in the current language (posts without a language are kept)
                if ( function_exists( 'pll_get_post_language' ) && $current_lang ) {
                    $gb_posts = array_values( array_filter( $gb_posts, function( $p ) use ( $current_lang ) {
                        $l = pll_get_post_language( $p->ID );
                        return ! $l || $l === $current_lang;
                    } ) );
                }
            ?>
            <section class="enroute-blog-guide font-montserrat" style="margin-top:4.5rem; width:100%;">
                <!-- Guide box: grey, full width; photo left, name + short text right -->
                <div style="display:flex; gap:2rem; align-items:flex-start; width:100%; box-sizing:border-box; background:#efefef; padding:20px;">
                    <?php if ( $gb_photo ) : ?>
                    <img src="<?php echo esc_url( $gb_photo ); ?>" alt="<?php echo esc_attr( $guide_name ); ?>"
                         style="width:9rem; height:9rem; object-fit:cover; object-position:center top; flex-shrink:0; margin:0; border-radius:7px;">
                    <?php endif; ?>
                    <div style="min-width:0;">
                        <h2 style="margin:0 0 0.75rem; font-size:1.5rem; font-weight:500; line-height:1.3; color:#111;"><?php echo esc_html( $guide_name ); ?></h2>
                        <?php if ( $gb_text ) : ?>
                        <p style="margin:0; font-size:1rem; line-height:1.6; color:#1f2937;"><?php echo nl2br( esc_html( $gb_text ) ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ( $gb_posts ) : ?>
                <div style="margin-top:2rem;">
                    <h3 style="margin:0 0 0.75rem; font-size:1rem; font-weight:700; color:#111;">
                        <?php echo esc_html( sprintf( __( 'Weitere Beiträge von %s', 'enroute_offers' ), $guide_name ) ); ?>
                    </h3>
                    <ul style="margin:0; padding:0; list-style:none;">
                        <?php foreach ( $gb_posts as $gp ) : ?>
                        <li style="margin:0; padding:0.6rem 0; border-bottom:1px solid #e5e7eb;">
                            <a href="<?php echo esc_url( get_permalink( $gp->ID ) ); ?>" style="color:#111; text-decoration:underline;"><?php echo esc_html( get_the_title( $gp->ID ) ); ?></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

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
