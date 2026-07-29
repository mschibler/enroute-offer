<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', 'enroute_register_meta_boxes' );

function enroute_register_meta_boxes() {

    // ── OFFER meta boxes ────────────────────────────────────────────────────
    add_meta_box(
        'offer_details',
        __( 'Offer Details', 'enroute_offers' ),
        'enroute_offer_details_cb',
        'offer',
        'normal',
        'high'
    );
    add_meta_box(
        'offer_dates',
        __( 'Dates', 'enroute_offers' ),
        'enroute_offer_dates_cb',
        'offer',
        'normal',
        'default'
    );
    add_meta_box(
        'offer_resources',
        __( 'Resources & Links', 'enroute_offers' ),
        'enroute_offer_resources_cb',
        'offer',
        'normal',
        'default'
    );

    // ── STATION meta boxes ──────────────────────────────────────────────────
    add_meta_box(
        'station_details',
        __( 'Station Details', 'enroute_offers' ),
        'enroute_station_details_cb',
        'station',
        'normal',
        'high'
    );

    // ── RESOURCE meta boxes ─────────────────────────────────────────────────
    add_meta_box(
        'resource_details',
        __( 'Resource Details', 'enroute_offers' ),
        'enroute_resource_details_cb',
        'resource',
        'normal',
        'high'
    );
}

// ══════════════════════════════════════════════════════════════════════════════
// OFFER CALLBACKS
// ══════════════════════════════════════════════════════════════════════════════

function enroute_offer_details_cb( WP_Post $post ): void {
    wp_nonce_field( 'enroute_offer_save', 'enroute_offer_nonce' );

    $subtitle = get_post_meta( $post->ID, '_offer_subtitle',       true );
    $desc     = get_post_meta( $post->ID, '_offer_description',   true );
    $price    = get_post_meta( $post->ID, '_offer_price',         true );
    $email    = get_post_meta( $post->ID, '_offer_contact_email', true );
    $station  = get_post_meta( $post->ID, '_offer_station',       true );
    $language = get_post_meta( $post->ID, '_offer_language',      true );

    $stations = get_posts([
        'post_type'   => 'station',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
        'post_status' => 'publish',
    ]);
    ?>
    <div class="enroute-meta-wrap">

        <div class="enroute-field">
            <label for="offer_subtitle"><?php esc_html_e( 'Subtitle', 'enroute_offers' ); ?></label>
            <input type="text" id="offer_subtitle" name="offer_subtitle" value="<?php echo esc_attr( $subtitle ); ?>">
        </div>

        <div class="enroute-field">
            <label for="offer_description"><?php esc_html_e( 'Description', 'enroute_offers' ); ?></label>
            <textarea id="offer_description" name="offer_description" rows="5"><?php echo esc_textarea( $desc ); ?></textarea>
        </div>

        <div class="enroute-field">
            <label for="offer_price"><?php esc_html_e( 'Price', 'enroute_offers' ); ?></label>
            <input type="text" id="offer_price" name="offer_price" value="<?php echo esc_attr( $price ); ?>">
        </div>

        <div class="enroute-field">
            <label for="offer_contact_email"><?php esc_html_e( 'Contact Email', 'enroute_offers' ); ?></label>
            <input type="email" id="offer_contact_email" name="offer_contact_email" value="<?php echo esc_attr( $email ); ?>">
        </div>

        <div class="enroute-field">
            <label for="offer_station"><?php esc_html_e( 'Station', 'enroute_offers' ); ?></label>
            <select id="offer_station" name="offer_station">
                <option value=""><?php esc_html_e( '— Select Station —', 'enroute_offers' ); ?></option>
                <?php foreach ( $stations as $s ) : ?>
                    <option value="<?php echo esc_attr( $s->ID ); ?>"<?php selected( $station, $s->ID ); ?>>
                        <?php echo esc_html( $s->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="enroute-field">
            <label for="offer_language"><?php esc_html_e( 'Language', 'enroute_offers' ); ?></label>
            <select id="offer_language" name="offer_language">
                <option value=""><?php esc_html_e( '— Select Language —', 'enroute_offers' ); ?></option>
                <?php foreach ( enroute_get_languages() as $code => $label ) : ?>
                    <option value="<?php echo esc_attr( $code ); ?>"<?php selected( $language, $code ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

    </div>
    <?php
}

function enroute_offer_dates_cb( WP_Post $post ): void {
    $weekday      = get_post_meta( $post->ID, '_offer_weekdays',     true ) ?: [];
    $time_of_day  = get_post_meta( $post->ID, '_offer_times_of_day', true ) ?: [];
    $date_info    = get_post_meta( $post->ID, '_offer_date_info',    true );
    $fixed_date   = get_post_meta( $post->ID, '_offer_fixed_date',   true );
    ?>
    <div class="enroute-meta-wrap">

        <div class="enroute-field">
            <label><?php esc_html_e( 'Weekday', 'enroute_offers' ); ?></label>
            <div class="enroute-checkboxes">
                <?php foreach ( enroute_get_weekdays() as $val => $label ) : ?>
                    <label class="enroute-checkbox-label">
                        <input type="checkbox"
                               name="offer_weekdays[]"
                               value="<?php echo esc_attr( $val ); ?>"
                               <?php checked( in_array( $val, (array) $weekday, true ) ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="enroute-field">
            <label><?php esc_html_e( 'Time of Day', 'enroute_offers' ); ?></label>
            <div class="enroute-checkboxes">
                <?php foreach ( enroute_get_times_of_day() as $val => $label ) : ?>
                    <label class="enroute-checkbox-label">
                        <input type="checkbox"
                               name="offer_times_of_day[]"
                               value="<?php echo esc_attr( $val ); ?>"
                               <?php checked( in_array( $val, (array) $time_of_day, true ) ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="enroute-field">
            <label for="offer_date_info"><?php esc_html_e( 'Additional Date Information', 'enroute_offers' ); ?></label>
            <textarea id="offer_date_info" name="offer_date_info" rows="3"><?php echo esc_textarea( $date_info ); ?></textarea>
        </div>

        <div class="enroute-field">
            <label for="offer_fixed_date"><?php esc_html_e( 'Fixed Date (optional)', 'enroute_offers' ); ?></label>
            <input type="date" id="offer_fixed_date" name="offer_fixed_date" value="<?php echo esc_attr( $fixed_date ); ?>">
        </div>

    </div>
    <?php
}

function enroute_offer_resources_cb( WP_Post $post ): void {
    $selected_resources = get_post_meta( $post->ID, '_offer_resources',      true ) ?: [];
    $external_links     = get_post_meta( $post->ID, '_offer_external_links', true ) ?: [ '' ];

    $all_resources = get_posts([
        'post_type'   => 'resource',
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
        'post_status' => 'publish',
    ]);
    ?>
    <div class="enroute-meta-wrap">

        <div class="enroute-field">
            <label><?php esc_html_e( 'Resources', 'enroute_offers' ); ?></label>
            <div class="enroute-checkboxes">
                <?php foreach ( $all_resources as $r ) : ?>
                    <label class="enroute-checkbox-label">
                        <input type="checkbox"
                               name="offer_resources[]"
                               value="<?php echo esc_attr( $r->ID ); ?>"
                               <?php checked( in_array( $r->ID, array_map( 'intval', $selected_resources ) ) ); ?>>
                        <?php echo esc_html( $r->post_title ); ?>
                    </label>
                <?php endforeach; ?>
                <?php if ( empty( $all_resources ) ) : ?>
                    <p class="description"><?php esc_html_e( 'No resources found. Please add some resources first.', 'enroute_offers' ); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="enroute-field" id="enroute-external-links">
            <label><?php esc_html_e( 'External Links', 'enroute_offers' ); ?></label>
            <div id="external-links-container">
                <?php foreach ( $external_links as $i => $link ) : ?>
                    <div class="enroute-repeater-row">
                        <input type="url"
                               name="offer_external_links[]"
                               value="<?php echo esc_url( $link ); ?>"
                               placeholder="https://"
                               class="widefat">
                        <button type="button" class="button enroute-remove-row"
                                <?php echo ( count( $external_links ) <= 1 && $i === 0 ) ? 'style="display:none"' : ''; ?>>
                            <?php esc_html_e( 'Remove', 'enroute_offers' ); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button" id="enroute-add-link">
                + <?php esc_html_e( 'Add Link', 'enroute_offers' ); ?>
            </button>
        </div>

    </div>

    <script>
    (function(){
        document.getElementById('enroute-add-link').addEventListener('click', function(){
            const container = document.getElementById('external-links-container');
            const row = document.createElement('div');
            row.className = 'enroute-repeater-row';
            row.innerHTML = '<input type="url" name="offer_external_links[]" value="" placeholder="https://" class="widefat">'
                          + '<button type="button" class="button enroute-remove-row"><?php esc_html_e("Remove","enroute_offers"); ?></button>';
            container.appendChild(row);
            bindRemove();
        });
        function bindRemove(){
            document.querySelectorAll('.enroute-remove-row').forEach(function(btn){
                btn.onclick = function(){ btn.closest('.enroute-repeater-row').remove(); };
            });
        }
        bindRemove();
    })();
    </script>
    <?php
}

// ══════════════════════════════════════════════════════════════════════════════
// STATION CALLBACK
// ══════════════════════════════════════════════════════════════════════════════

function enroute_station_details_cb( WP_Post $post ): void {
    wp_nonce_field( 'enroute_station_save', 'enroute_station_nonce' );

    $portrait = get_post_meta( $post->ID, '_station_portrait', true );
    $address  = get_post_meta( $post->ID, '_station_address',  true );
    $plz      = get_post_meta( $post->ID, '_station_plz',      true );
    $place    = get_post_meta( $post->ID, '_station_place',    true );
    ?>
    <div class="enroute-meta-wrap">

        <div class="enroute-field">
            <label for="station_portrait"><?php esc_html_e( 'Portrait / Description', 'enroute_offers' ); ?></label>
            <textarea id="station_portrait" name="station_portrait" rows="6"><?php echo esc_textarea( $portrait ); ?></textarea>
        </div>

        <div class="enroute-field-group">
            <div class="enroute-field">
                <label for="station_address"><?php esc_html_e( 'Address (Street & Number)', 'enroute_offers' ); ?></label>
                <input type="text" id="station_address" name="station_address" value="<?php echo esc_attr( $address ); ?>">
            </div>
            <div class="enroute-field enroute-field--short">
                <label for="station_plz"><?php esc_html_e( 'Postal Code (PLZ)', 'enroute_offers' ); ?></label>
                <input type="text" id="station_plz" name="station_plz" value="<?php echo esc_attr( $plz ); ?>">
            </div>
            <div class="enroute-field">
                <label for="station_place"><?php esc_html_e( 'Place / City', 'enroute_offers' ); ?></label>
                <input type="text" id="station_place" name="station_place" value="<?php echo esc_attr( $place ); ?>">
            </div>
        </div>

    </div>
    <?php
}

// ══════════════════════════════════════════════════════════════════════════════
// RESOURCE CALLBACK
// ══════════════════════════════════════════════════════════════════════════════

function enroute_resource_details_cb( WP_Post $post ): void {
    wp_nonce_field( 'enroute_resource_save', 'enroute_resource_nonce' );

    $file_id   = get_post_meta( $post->ID, '_resource_file_id',  true );
    $file_url  = $file_id ? wp_get_attachment_url( $file_id ) : '';
    $file_name = $file_id ? basename( get_attached_file( $file_id ) ) : '';
    $language  = get_post_meta( $post->ID, '_resource_language', true );
    ?>
    <div class="enroute-meta-wrap">

        <div class="enroute-field">
            <label><?php esc_html_e( 'File (PDF, Word, Excel, PowerPoint)', 'enroute_offers' ); ?></label>
            <div class="enroute-file-wrap">
                <input type="hidden" id="resource_file_id" name="resource_file_id" value="<?php echo esc_attr( $file_id ); ?>">
                <span id="resource_file_name" style="margin-right:8px">
                    <?php echo $file_name ? esc_html( $file_name ) : esc_html__( 'No file selected', 'enroute_offers' ); ?>
                </span>
                <button type="button" class="button" id="resource_file_btn">
                    <?php esc_html_e( 'Select / Upload File', 'enroute_offers' ); ?>
                </button>
                <?php if ( $file_url ) : ?>
                    <a href="<?php echo esc_url( $file_url ); ?>" target="_blank" style="margin-left:8px">
                        <?php esc_html_e( 'View file', 'enroute_offers' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="enroute-field">
            <label for="resource_language"><?php esc_html_e( 'Language', 'enroute_offers' ); ?></label>
            <select id="resource_language" name="resource_language">
                <option value=""><?php esc_html_e( '— Select Language —', 'enroute_offers' ); ?></option>
                <?php foreach ( enroute_get_languages() as $code => $label ) : ?>
                    <option value="<?php echo esc_attr( $code ); ?>"<?php selected( $language, $code ); ?>>
                        <?php echo esc_html( $label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

    </div>

    <script>
    (function(){
        var frame;
        document.getElementById('resource_file_btn').addEventListener('click', function(e){
            e.preventDefault();
            if ( frame ) { frame.open(); return; }
            frame = wp.media({
                title: '<?php esc_html_e("Select or Upload File","enroute_offers"); ?>',
                button: { text: '<?php esc_html_e("Use this file","enroute_offers"); ?>' },
                multiple: false,
                library: {
                    type: [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    ]
                }
            });
            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                document.getElementById('resource_file_id').value   = attachment.id;
                document.getElementById('resource_file_name').textContent = attachment.filename;
            });
            frame.open();
        });
    })();
    </script>
    <?php
}
