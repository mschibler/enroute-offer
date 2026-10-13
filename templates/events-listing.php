<?php
/**
 * Events listing — shortcode [enroute_events_listing]
 * Upcoming, active events of the current language, soonest first.
 * Card colours: default colours 1–5 from the settings, in that order.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$events = enroute_get_upcoming_events();
?>
<div class="enroute-events-listing">
    <?php if ( ! $events ) : ?>
        <p style="padding:2rem 0; color:#6b7280;"><?php esc_html_e( 'Zurzeit sind keine Veranstaltungen geplant.', 'enroute_offers' ); ?></p>
    <?php else : ?>
    <div class="enroute-offers-grid" style="display:grid; grid-template-columns:repeat(3,1fr); column-gap:1rem; row-gap:0;">
        <?php foreach ( $events as $i => $event ) :
            $image = enroute_event_image_url( $event->ID );
            $color = enroute_get_featured_color( ( $i % 5 ) + 1 );
            $dates = enroute_event_date_text( $event->ID );
            $short = get_post_meta( $event->ID, '_event_short_description', true );
        ?>
        <a href="<?php echo esc_url( get_permalink( $event->ID ) ); ?>"
           class="enroute-card-wrap"
           style="display:grid; grid-row:span 2; grid-template-rows:subgrid; row-gap:0; text-decoration:none; color:inherit; border:none; outline:none; box-shadow:none; padding:0; margin:0 0 1rem 0; overflow:hidden;">
            <div style="display:block; width:100%; aspect-ratio:434/280; overflow:hidden; background:<?php echo $image ? 'transparent' : '#c2dcef'; ?>;">
                <?php if ( $image ) : ?>
                <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( get_the_title( $event->ID ) ); ?>"
                     style="width:100%; height:100%; object-fit:cover; object-position:center; display:block; margin:0; padding:0; border:none; transition:transform 0.3s ease;"
                     onmouseenter="this.style.transform='scale(1.05)'" onmouseleave="this.style.transform='scale(1)'">
                <?php endif; ?>
            </div>
            <div style="display:block; font-size:1rem; line-height:1.5; padding:0.6rem 1rem 0.75rem; background:<?php echo esc_attr( $color ); ?>;">
                <?php if ( $dates ) : ?>
                <span style="display:block; font-size:0.75rem; line-height:1.25rem; letter-spacing:0.05em; font-weight:700; color:rgba(0,0,0,0.6);"><?php echo esc_html( $dates ); ?></span>
                <?php endif; ?>
                <span style="display:block; margin:0; padding-top:0.75rem; font-size:1.125rem; line-height:1.25rem; font-weight:700; color:#111827;"><?php echo esc_html( get_the_title( $event->ID ) ); ?></span>
                <?php if ( $short ) : ?>
                <p style="margin:0.15rem 0 0; padding-top:0.5rem; padding-bottom:0.75rem; font-size:0.75rem; line-height:1rem; color:#1f2937;"><?php echo esc_html( $short ); ?></p>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
