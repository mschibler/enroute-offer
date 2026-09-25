<?php
/**
 * One-time fix: clear enroute-guide-square from all attachment metadata
 * so the blog listing regenerates the crops fresh.
 * 
 * Usage: place in WP root, run once via browser or WP-CLI, then delete.
 * Or run via WP-CLI: wp eval-file fix-guide-crops.php
 */
define( 'ABSPATH', dirname(__FILE__) . '/wp/' );
require_once dirname(__FILE__) . '/wp-load.php';

$guides = get_posts([
    'post_type'   => 'guide',
    'post_status' => 'any',
    'numberposts' => -1,
    'fields'      => 'ids',
]);

$cleared = 0;
foreach ( $guides as $guide_id ) {
    $photo_id = (int) get_post_meta( $guide_id, '_guide_photo_id', true );
    if ( ! $photo_id ) continue;
    $meta = wp_get_attachment_metadata( $photo_id );
    if ( isset( $meta['sizes']['enroute-guide-square'] ) ) {
        unset( $meta['sizes']['enroute-guide-square'] );
        wp_update_attachment_metadata( $photo_id, $meta );
        $cleared++;
        echo "Cleared photo_id=$photo_id\n";
    }
}

// Also clear the migration flags so migration runs again
delete_option( 'enroute_guide_square_regenerated_v2' );
delete_option( 'enroute_guide_square_regenerated_v3' );

echo "Done. Cleared $cleared entries. Now reload the blog listing.\n";
