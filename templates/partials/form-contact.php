<?php
/**
 * Contact person fields.
 * Shared by the offer booking form and the event registration form.
 * Expects $inp / $lbl (input + label style attributes) and an Alpine x-data with `form`.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!-- Kontaktperson -->
                        <h3 style="font-size:0.95rem; font-weight:700; margin:1rem 0 0.75rem; padding-bottom:0.4rem; border-bottom:1px solid rgba(0,0,0,0.2);">
                            <?php esc_html_e( 'Kontaktperson', 'enroute_offers' ); ?>
                        </h3>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.6rem; margin-bottom:0.6rem;">
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Anrede', 'enroute_offers' ); ?></label>
                                <select x-model="form.salutation" <?php echo $inp; ?>>
                                    <option value=""><?php esc_html_e( 'Bitte wählen', 'enroute_offers' ); ?></option>
                                    <option value="Herr"><?php esc_html_e( 'Herr', 'enroute_offers' ); ?></option>
                                    <option value="Frau"><?php esc_html_e( 'Frau', 'enroute_offers' ); ?></option>
                                    <option value="Divers"><?php esc_html_e( 'Divers', 'enroute_offers' ); ?></option>
                                </select>
                            </div>
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Institution', 'enroute_offers' ); ?></label>
                                <input type="text" x-model="form.institution" <?php echo $inp; ?>>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.6rem; margin-bottom:0.6rem;">
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Vorname', 'enroute_offers' ); ?> *</label>
                                <input type="text" x-model="form.first_name" <?php echo $inp; ?>>
                            </div>
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Nachname', 'enroute_offers' ); ?> *</label>
                                <input type="text" x-model="form.last_name" <?php echo $inp; ?>>
                            </div>
                        </div>

                        <div style="margin-bottom:0.6rem;">
                            <label <?php echo $lbl; ?>><?php esc_html_e( 'Strasse', 'enroute_offers' ); ?></label>
                            <input type="text" x-model="form.street" <?php echo $inp; ?>>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 2fr; gap:0.6rem; margin-bottom:0.6rem;">
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'PLZ', 'enroute_offers' ); ?></label>
                                <input type="text" x-model="form.zip" <?php echo $inp; ?>>
                            </div>
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Ort', 'enroute_offers' ); ?></label>
                                <input type="text" x-model="form.place" <?php echo $inp; ?>>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.6rem; margin-bottom:0.6rem;">
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'E-Mail', 'enroute_offers' ); ?> *</label>
                                <input type="email" x-model="form.email" <?php echo $inp; ?>>
                            </div>
                            <div>
                                <label <?php echo $lbl; ?>><?php esc_html_e( 'Telefon', 'enroute_offers' ); ?></label>
                                <input type="tel" x-model="form.phone" <?php echo $inp; ?>>
                            </div>
                        </div>
