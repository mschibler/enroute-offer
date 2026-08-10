<?php
/**
 * Featured offer template.
 * Used by shortcode [enroute_featured_offer number="1"]
 * Renders a single offer in the same card style as the offers listing.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$featured_number = isset( $args['number'] ) ? absint( $args['number'] ) : 1;

$offers = get_posts( [
    'post_type'   => 'offer',
    'post_status' => 'publish',
    'numberposts' => 1,
    'meta_query'  => [
        [
            'key'   => '_offer_featured',
            'value' => (string) $featured_number,
        ],
    ],
] );

if ( empty( $offers ) ) {
    if ( current_user_can( 'edit_posts' ) ) {
        echo '<p style="color:#999; font-style:italic;">';
        printf( esc_html__( '[No offer assigned to Featured %d]', 'enroute_offers' ), $featured_number );
        echo '</p>';
    }
    return;
}

$offer   = $offers[0];
$post_id = $offer->ID;

$enroute_palette = [ '#dbe442', '#fce300', '#fed141', '#ff6a39', '#ef4a81' ];
$color           = $enroute_palette[ $post_id % count( $enroute_palette ) ];

// Image: offer photo first, station photo as fallback
$image_id  = get_post_meta( $post_id, '_offer_photo_id', true );
$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
if ( ! $image_url ) {
    $station_id = get_post_meta( $post_id, '_offer_station', true );
    if ( $station_id ) {
        $station_photo_id = get_post_meta( $station_id, '_station_photo_id', true );
        if ( $station_photo_id ) {
            $image_url = wp_get_attachment_image_url( $station_photo_id, 'large' ) ?: '';
        }
    }
}

$subtitle  = get_post_meta( $post_id, '_offer_subtitle', true );
$ot_terms  = get_the_terms( $post_id, 'offer_type' );
$ot_name   = ( $ot_terms && ! is_wp_error( $ot_terms ) ) ? $ot_terms[0]->name : '';
$permalink = get_permalink( $post_id );
?>

<div class="enroute-featured-offer">
    <a
        href="<?php echo esc_url( $permalink ); ?>"
        class="enroute-card-wrap"
        style="display:grid; grid-row:span 2; grid-template-rows:subgrid; row-gap:0; padding:0; margin:0; font-size:0; line-height:0; overflow:hidden; text-decoration:none; color:inherit;"
    >
        <!-- Image -->
        <div style="display:block; width:100%; aspect-ratio:434/280; overflow:hidden; font-size:0; line-height:0; background:<?php echo $image_url ? 'transparent' : '#c2dcef'; ?>;">
            <?php if ( $image_url ) : ?>
            <img
                src="<?php echo esc_url( $image_url ); ?>"
                alt="<?php echo esc_attr( $offer->post_title ); ?>"
                style="width:100%; height:100%; object-fit:cover; display:block; margin:0; padding:0; border:none;"
            >
            <?php endif; ?>
        </div>

        <!-- Band -->
        <div style="display:block; font-size:1rem; line-height:1.5; padding:0.6rem 1rem 0.75rem; background:<?php echo esc_attr( $color ); ?>;">
            <?php if ( $ot_name ) : ?>
            <span style="display:block; font-size:0.75rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; opacity:0.75; margin-bottom:0.1rem;">
                <?php echo esc_html( $ot_name ); ?>
            </span>
            <?php endif; ?>
            <p style="margin:0; font-weight:700; font-size:1rem; line-height:1.3;">
                <?php echo esc_html( $offer->post_title ); ?>
            </p>
            <?php if ( $subtitle ) : ?>
            <p style="margin:0.25rem 0 0; font-size:0.85rem; line-height:1.3; opacity:0.85;">
                <?php echo esc_html( $subtitle ); ?>
            </p>
            <?php endif; ?>
        </div>
    </a>
</div>
