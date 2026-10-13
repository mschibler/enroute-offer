<?php
/**
 * Group fields: persons under/over 16, accompanying persons, remarks.
 * Shared by the offer booking form and the event registration form.
 * Expects $inp / $lbl (input + label style attributes) and an Alpine x-data with `form`.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!-- Gruppe -->
                        <h3 style="font-size:0.95rem; font-weight:700; margin:1rem 0 0.75rem; padding-bottom:0.4rem; border-bottom:1px solid rgba(0,0,0,0.2);">
                            <?php esc_html_e( 'Gruppe', 'enroute_offers' ); ?>
                        </h3>
                        <div style="margin-bottom:0.6rem;">
                            <label <?php echo $lbl; ?>><?php esc_html_e( 'Anzahl Personen unter 16 Jahren', 'enroute_offers' ); ?></label>
                            <input type="number" min="0" step="1" inputmode="numeric" x-model="form.persons_under16" <?php echo $inp; ?>>
                        </div>
                        <div style="margin-bottom:0.6rem;">
                            <label <?php echo $lbl; ?>><?php esc_html_e( 'Anzahl Personen über 16 Jahren', 'enroute_offers' ); ?></label>
                            <input type="number" min="0" step="1" inputmode="numeric" x-model="form.persons_over16" <?php echo $inp; ?>>
                        </div>
                        <div style="margin-bottom:0.6rem;">
                            <label <?php echo $lbl; ?>><?php esc_html_e( 'Anzahl Begleitpersonen', 'enroute_offers' ); ?></label>
                            <input type="number" min="0" step="1" inputmode="numeric" x-model="form.persons_companions" <?php echo $inp; ?>>
                        </div>
                        <div style="margin-bottom:1rem;">
                            <label <?php echo $lbl; ?>><?php esc_html_e( 'Weitere Angaben', 'enroute_offers' ); ?></label>
                            <textarea x-model="form.remarks" rows="4" <?php echo $inp; ?> style="width:100%; padding:0.5rem 0.6rem; border:1px solid rgba(0,0,0,0.3); background:#fff; font-size:0.9rem; box-sizing:border-box; resize:vertical;"></textarea>
                        </div>
