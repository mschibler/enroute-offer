<?php
/**
 * POI Map template.
 * Shortcode: [enroute_poi_map]
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$pois = get_posts([
    'post_type'   => 'poi',
    'post_status' => 'publish',
    'numberposts' => -1,
]);

$markers = [];
foreach ( $pois as $poi ) {
    $coords    = get_post_meta( $poi->ID, '_poi_coordinates', true );
    $desc      = get_post_meta( $poi->ID, '_poi_description', true );
    $photo_id  = get_post_meta( $poi->ID, '_poi_photo_id',    true );
    $photo_url = $photo_id ? wp_get_attachment_image_url( (int) $photo_id, 'large' ) : '';

    if ( $coords && strpos( $coords, ',' ) !== false ) {
        [ $lat, $lng ] = array_map( 'floatval', explode( ',', $coords ) );
    } else {
        $lat = 45.8 + lcg_value() * ( 47.8 - 45.8 );
        $lng = 5.9  + lcg_value() * ( 10.5 - 5.9 );
    }

    $markers[] = [
        'title' => html_entity_decode( get_the_title( $poi->ID ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
        'desc'  => wp_kses_post( $desc ),
        'photo' => $photo_url,
        'lat'   => round( $lat, 6 ),
        'lng'   => round( $lng, 6 ),
    ];
}

// Tiles: Mapbox style (Settings → General) if style + token are set, else standard OpenStreetMap
$mapbox_token = trim( (string) get_option( 'enroute_mapbox_token', '' ) );
$mapbox_style = trim( (string) get_option( 'enroute_mapbox_style', '' ) );
$mapbox_path  = preg_match( '#^mapbox://styles/([^/\s]+/[^/\s?]+)#', $mapbox_style, $mm ) ? $mm[1]
              : ( preg_match( '~styles/v1/([^/\s]+/[^/\s?.#]+)~', $mapbox_style, $mm ) ? $mm[1]
              : ( preg_match( '#^[\w.-]+/[\w.-]+$#', $mapbox_style ) ? $mapbox_style : '' ) );
if ( $mapbox_token && $mapbox_path ) {
    $tiles = [
        'url'         => 'https://api.mapbox.com/styles/v1/' . $mapbox_path . '/tiles/512/{z}/{x}/{y}@2x?access_token=' . rawurlencode( $mapbox_token ),
        'tileSize'    => 512,
        'zoomOffset'  => -1,
        'maxZoom'     => 20,
        'attribution' => '© <a href="https://www.mapbox.com/about/maps/">Mapbox</a> © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> <a href="https://www.mapbox.com/map-feedback/" target="_blank">Improve this map</a>',
    ];
} else {
    $tiles = [
        'url'         => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        'tileSize'    => 256,
        'zoomOffset'  => 0,
        'maxZoom'     => 19,
        'attribution' => '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    ];
}

$map_id       = 'epm_' . uniqid();
$markers_json = wp_json_encode( enroute_js_data( $markers, [ 'desc' ] ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<style>
#<?php echo esc_attr( $map_id ); ?>-wrap {
    position: relative;
    width: 100%;
    height: 600px;
}
#<?php echo esc_attr( $map_id ); ?> {
    width: 100%;
    height: 100%;
    z-index: 0;
    background: #f2efe9;
}
/* Popup overlaid centered on map */
.epm-popup-<?php echo esc_attr( $map_id ); ?> {
    display: none;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-height: 92%;
    background: #fff;
    box-shadow: 0 8px 48px rgba(0,0,0,.3);
    z-index: 1000;
    overflow: hidden;
}
.epm-popup-inner {
    display: flex;
    min-height: 280px;
}
.epm-popup-img {
    flex: 0 0 44%;
    align-self: flex-start;
}
.epm-popup-img img {
    width: 100%;
    height: auto;
    display: block;
}
.epm-popup-body {
    flex: 1;
    padding: 2rem 1.75rem;
    overflow-y: auto;
    max-height: 480px;
}
.epm-popup-body h2 {
    margin: 0 0 .5rem;
    font-size: 1.25rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    line-height: 1.2;
    padding-right: 2rem;
}
.epm-popup-body .epm-desc {
    font-size: .875rem;
    line-height: 1.65;
    color: #374151;
    margin-top: .75rem;
}
.epm-close {
    position: absolute;
    top: .6rem;
    right: .75rem;
    background: none;
    border: none;
    font-size: 1.75rem;
    line-height: 1;
    cursor: pointer;
    color: #111;
    z-index: 10;
}
</style>

<div id="<?php echo esc_attr( $map_id ); ?>-wrap">
    <div id="<?php echo esc_attr( $map_id ); ?>"></div>

    <div class="epm-popup-<?php echo esc_attr( $map_id ); ?>"
         id="<?php echo esc_attr( $map_id ); ?>-popup">
        <button class="epm-close" id="<?php echo esc_attr( $map_id ); ?>-close">×</button>
        <div class="epm-popup-inner" id="<?php echo esc_attr( $map_id ); ?>-inner"></div>
    </div>
</div>

<script>
(function(){
    var mid     = '<?php echo esc_js( $map_id ); ?>';
    var markers = <?php echo $markers_json; ?>;
    var panel   = document.getElementById(mid+'-popup');
    var inner   = document.getElementById(mid+'-inner');
    var closeBtn= document.getElementById(mid+'-close');

    closeBtn.addEventListener('click', function(){ panel.style.display='none'; });

    // Close on click outside popup
    document.getElementById(mid).addEventListener('click', function(e){
        if (!panel.contains(e.target)) panel.style.display='none';
    });

    var map = L.map(mid, {
        zoomControl: true,
        scrollWheelZoom: true,
    }).setView([46.8, 8.2], 8);

    // Map tiles: standard OpenStreetMap, or the Mapbox style from the settings
    var tiles = <?php echo wp_json_encode( $tiles, JSON_UNESCAPED_SLASHES ); ?>;
    L.tileLayer(tiles.url, {
        attribution: tiles.attribution,
        tileSize:    tiles.tileSize,
        zoomOffset:  tiles.zoomOffset,
        maxZoom:     tiles.maxZoom,
    }).addTo(map);

    var icon = L.divIcon({
        className: '',
        html: '<svg width="20" height="28" viewBox="0 0 24 32" xmlns="http://www.w3.org/2000/svg">'
            + '<path d="M12 0C5.4 0 0 5.4 0 12c0 9 12 20 12 20S24 21 24 12C24 5.4 18.6 0 12 0z" fill="#111"/>'
            + '<circle cx="12" cy="12" r="5" fill="#fff"/></svg>',
        iconSize:   [20, 28],
        iconAnchor: [10, 28],
    });

    // Escape plain text before it goes into innerHTML (titles are plain text; desc is sanitised HTML)
    function epmEsc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

    markers.forEach(function(m){
        L.marker([m.lat, m.lng], {icon:icon}).addTo(map).on('click', function(e){
            L.DomEvent.stopPropagation(e);
            var hasImg = !!m.photo;
            var html = '';
            if (hasImg) {
                html += '<div class="epm-popup-img"><img src="'+epmEsc(m.photo)+'" alt="'+epmEsc(m.title)+'"></div>';
            }
            html += '<div class="epm-popup-body">';
            html += '<h2>'+epmEsc(m.title)+'</h2>';
            if (m.desc) html += '<div class="epm-desc">'+m.desc+'</div>';
            html += '</div>';
            inner.innerHTML = html;
            panel.style.display = 'block';
        });
    });
})();
</script>
