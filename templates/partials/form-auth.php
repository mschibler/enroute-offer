<?php
/**
 * Login / register box (optional account).
 * Shared by the offer booking form and the event registration form.
 * Optional $auth_hint: text under the login box.
 * Expects $inp / $lbl (input + label style attributes) and an Alpine x-data with `form`.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!-- Login / Register -->
                        <div x-data="enrouteUserAuth()" style="margin-bottom:1rem; border-bottom:1px solid rgba(0,0,0,0.2); padding-bottom:1rem;">

                            <!-- Logged in -->
                            <div x-show="loggedIn" style="display:flex; align-items:center; justify-content:space-between; font-size:0.85rem;">
                                <span>
                                    ✓ <span x-text="(profile.enroute_first_name || '') + ' ' + (profile.enroute_last_name || profile.email || '')"></span>
                                    <a :href="enrouteUserVars.profileUrl" style="margin-left:0.5rem; font-size:0.8rem; color:#374151;"><?php esc_html_e( 'Profil', 'enroute_offers' ); ?></a>
                                </span>
                                <button @click="
                                    const d=new FormData(); d.append('action','enroute_logout'); d.append('nonce',enrouteUserVars.nonce);
                                    fetch(enrouteUserVars.ajaxUrl,{method:'POST',body:d}).then(()=>window.location.reload());
                                " style="background:none; border:none; cursor:pointer; font-size:0.8rem; text-decoration:underline; color:#374151;">
                                    <?php esc_html_e( 'Abmelden', 'enroute_offers' ); ?>
                                </button>
                            </div>

                            <!-- Not logged in -->
                            <div x-show="!loggedIn">
                                <!-- Tab switcher -->
                                <div style="display:flex; gap:0.5rem; margin-bottom:0.75rem;">
                                    <button @click="mode='login'; errorMsg=''" :style="mode==='login' ? { 'background': '#111', 'color': '#fff' } : { 'background': '#fff', 'color': '#111' }"
                                        style="flex:1; padding:0.4rem; border:1px solid #111; font-size:0.82rem; cursor:pointer;">
                                        <?php esc_html_e( 'Anmelden', 'enroute_offers' ); ?>
                                    </button>
                                    <button @click="mode='register'; errorMsg=''" :style="mode==='register' ? { 'background': '#111', 'color': '#fff' } : { 'background': '#fff', 'color': '#111' }"
                                        style="flex:1; padding:0.4rem; border:1px solid #111; font-size:0.82rem; cursor:pointer;">
                                        <?php esc_html_e( 'Registrieren', 'enroute_offers' ); ?>
                                    </button>
                                </div>

                                <div x-show="errorMsg" style="display:none; margin-bottom:0.5rem; padding:0.5rem; background:#fef2f2; color:#991b1b; font-size:0.8rem;" x-text="errorMsg"></div>

                                <!-- Login form -->
                                <div x-show="mode==='login'">
                                    <div style="margin-bottom:0.5rem;">
                                        <input type="email" x-model="form.email" placeholder="<?php esc_attr_e( 'E-Mail', 'enroute_offers' ); ?>"
                                               style="width:100%; padding:0.4rem 0.6rem; border:1px solid rgba(0,0,0,0.3); font-size:0.85rem; box-sizing:border-box;">
                                    </div>
                                    <div style="margin-bottom:0.5rem;">
                                        <input type="password" x-model="form.password" placeholder="<?php esc_attr_e( 'Passwort', 'enroute_offers' ); ?>"
                                               style="width:100%; padding:0.4rem 0.6rem; border:1px solid rgba(0,0,0,0.3); font-size:0.85rem; box-sizing:border-box;">
                                    </div>
                                    <button @click="submit()" :disabled="loading"
                                        style="width:100%; padding:0.5rem; background:#111; color:#fff; border:none; font-size:0.85rem; cursor:pointer;">
                                        <span x-show="!loading"><?php esc_html_e( 'Anmelden', 'enroute_offers' ); ?></span>
                                        <span x-show="loading">…</span>
                                    </button>
                                </div>

                                <!-- Register form -->
                                <div x-show="mode==='register'">
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.4rem; margin-bottom:0.4rem;">
                                        <input type="text" x-model="form.enroute_first_name" placeholder="<?php esc_attr_e( 'Vorname *', 'enroute_offers' ); ?>"
                                               style="padding:0.4rem 0.6rem; border:1px solid rgba(0,0,0,0.3); font-size:0.85rem;">
                                        <input type="text" x-model="form.enroute_last_name" placeholder="<?php esc_attr_e( 'Nachname *', 'enroute_offers' ); ?>"
                                               style="padding:0.4rem 0.6rem; border:1px solid rgba(0,0,0,0.3); font-size:0.85rem;">
                                    </div>
                                    <div style="margin-bottom:0.4rem;">
                                        <input type="email" x-model="form.email" placeholder="<?php esc_attr_e( 'E-Mail *', 'enroute_offers' ); ?>"
                                               style="width:100%; padding:0.4rem 0.6rem; border:1px solid rgba(0,0,0,0.3); font-size:0.85rem; box-sizing:border-box;">
                                    </div>
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.4rem; margin-bottom:0.5rem;">
                                        <input type="password" x-model="form.password" placeholder="<?php esc_attr_e( 'Passwort *', 'enroute_offers' ); ?>"
                                               style="padding:0.4rem 0.6rem; border:1px solid rgba(0,0,0,0.3); font-size:0.85rem;">
                                        <input type="password" x-model="form.password2" placeholder="<?php esc_attr_e( 'Wiederholen *', 'enroute_offers' ); ?>"
                                               style="padding:0.4rem 0.6rem; border:1px solid rgba(0,0,0,0.3); font-size:0.85rem;">
                                    </div>
                                    <button @click="submit()" :disabled="loading"
                                        style="width:100%; padding:0.5rem; background:#111; color:#fff; border:none; font-size:0.85rem; cursor:pointer;">
                                        <span x-show="!loading"><?php esc_html_e( 'Konto erstellen', 'enroute_offers' ); ?></span>
                                        <span x-show="loading">…</span>
                                    </button>
                                </div>

                                <p style="font-size:0.75rem; color:#6b7280; margin:0.5rem 0 0; text-align:center;">
                                    <?php echo esc_html( $auth_hint ?? __( 'Optional — Sie können auch ohne Konto buchen.', 'enroute_offers' ) ); ?>
                                </p>
                            </div>
                        </div>
