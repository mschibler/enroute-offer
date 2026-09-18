/**
 * Enroute Offers – Alpine JS component definitions
 *
 * Strategy: queue component factories before Alpine boots.
 * Alpine's CDN build checks window.Alpine before starting; we pre-populate
 * Alpine.data via a deferred-registration queue that works regardless of
 * whether this script runs before or after Alpine initialises.
 */

(function () {

    function registerComponents() {

        // ── OFFERS LISTING ────────────────────────────────────────────────
        Alpine.data( 'enrouteOffersListing', ( offers, currentLang ) => ({

            all:         offers,
            currentLang: currentLang || '',
            filterOpen:  false,
            searchQuery: '',

            pending: {
                weekday:      [],
                subject:      [],
                target_group: [],
                offer_type:   [],
                language:     [],
            },
            filters: {
                weekday:      [],
                subject:      [],
                target_group: [],
                offer_type:   [],
                language:     [],
            },

            perPage:       15,
            visibleCount:  15,

            get filtered() {
                const q = this.searchQuery.toLowerCase().trim();
                return this.all.filter( offer => {
                    if ( q && ! ( offer.title + ' ' + offer.subtitle + ' ' + offer.description ).toLowerCase().includes(q) ) return false;
                    if ( this.filters.weekday.length      && ! offer.weekdays.some( d => this.filters.weekday.includes(d) ) )                  return false;
                    if ( this.filters.language.length     && ! this.filters.language.includes( offer.language ) )                            return false;
                    if ( this.filters.subject.length      && ! offer.subject_ids.some( id => this.filters.subject.includes(id) ) )            return false;
                    if ( this.filters.target_group.length && ! offer.target_group_ids.some( id => this.filters.target_group.includes(id) ) )  return false;
                    if ( this.filters.offer_type.length   && ! offer.offer_type_ids.some( id => this.filters.offer_type.includes(id) ) )      return false;
                    return true;
                });
            },

            get activeFilterCount() {
                return Object.values( this.filters ).reduce( (n, arr) => n + arr.length, 0 );
            },

            get sorted() {
                if ( ! this.currentLang ) return this.filtered;
                const lang = this.currentLang;
                return [
                    ...this.filtered.filter( o => o.language === lang ),
                    ...this.filtered.filter( o => o.language !== lang ),
                ];
            },

            get visible() {
                return this.sorted.slice( 0, this.visibleCount );
            },

            get hasMore() {
                return this.visibleCount < this.filtered.length;
            },

            loadMore() {
                this.visibleCount += this.perPage;
            },

            toggleMultiFilter( key, value ) {
                const idx = this.pending[ key ].indexOf( value );
                if ( idx === -1 ) this.pending[ key ].push( value );
                else              this.pending[ key ].splice( idx, 1 );
            },

            applyFilters() {
                Object.keys( this.filters ).forEach( k => {
                    this.filters[ k ] = [ ...this.pending[ k ] ];
                });
                this.visibleCount = this.perPage;
            },

            resetFilters() {
                Object.keys( this.filters ).forEach( k => {
                    this.filters[ k ]  = [];
                    this.pending[ k ]  = [];
                });
                this.visibleCount = this.perPage;
                this.searchQuery  = '';
            },
        }) );

        // ── RESOURCES LISTING ─────────────────────────────────────────────
        Alpine.data( 'enrouteResourcesListing', ( resources, currentLang ) => ({

            all:          resources,
            currentLang:  currentLang || '',
            filterOpen:   false,
            searchQuery:  '',
            perPage:      15,
            visibleCount: 15,

            pending: {
                language:      [],
                subject:       [],
                target_group:  [],
                resource_type: [],
            },
            filters: {
                language:      [],
                subject:       [],
                target_group:  [],
                resource_type: [],
            },

            perPage:       15,
            visibleCount:  15,

            get filtered() {
                const q = this.searchQuery.toLowerCase().trim();
                return this.all.filter( res => {
                    if ( q && ! res.title.toLowerCase().includes(q) ) return false;
                    if ( this.filters.language.length      && ! this.filters.language.includes( res.language ) )                              return false;
                    if ( this.filters.subject.length       && ! res.subject_ids.some( id => this.filters.subject.includes(id) ) )              return false;
                    if ( this.filters.target_group.length  && ! res.target_group_ids.some( id => this.filters.target_group.includes(id) ) )    return false;
                    if ( this.filters.resource_type.length && ! res.resource_type_ids.some( id => this.filters.resource_type.includes(id) ) )  return false;
                    return true;
                });
            },

            get activeFilterCount() {
                return Object.values( this.filters ).reduce( (n, arr) => n + arr.length, 0 );
            },

            get sorted() {
                if ( ! this.currentLang ) return this.filtered;
                const lang = this.currentLang;
                return [
                    ...this.filtered.filter( o => o.language === lang ),
                    ...this.filtered.filter( o => o.language !== lang ),
                ];
            },

            get visible() {
                return this.sorted.slice( 0, this.visibleCount );
            },

            get hasMore() {
                return this.visibleCount < this.filtered.length;
            },

            loadMore() {
                this.visibleCount += this.perPage;
            },

            toggleMultiFilter( key, value ) {
                const idx = this.pending[ key ].indexOf( value );
                if ( idx === -1 ) this.pending[ key ].push( value );
                else              this.pending[ key ].splice( idx, 1 );
            },

            applyFilters() {
                Object.keys( this.filters ).forEach( k => {
                    this.filters[ k ] = [ ...this.pending[ k ] ];
                });
                this.visibleCount = this.perPage;
            },

            resetFilters() {
                Object.keys( this.filters ).forEach( k => {
                    this.filters[ k ]  = [];
                    this.pending[ k ]  = [];
                });
                this.visibleCount = this.perPage;
                this.searchQuery  = '';
            },
        }) );
    }

    /*
     * Alpine's CDN build exposes Alpine globally and fires 'alpine:init'
     * before it processes the DOM. We cover all timing scenarios:
     *
     * 1. This script runs BEFORE Alpine loads → listener catches alpine:init
     * 2. This script runs AFTER Alpine has already initialised → call directly
     * 3. Alpine is loaded but init hasn't fired yet → listener still works
     */
    // ── USER PROFILE ──────────────────────────────────────────────────────────
    function registerUserProfile() {
        Alpine.data( 'enrouteUserProfile', () => ({
            editing:  false,
            saving:   false,
            message:  '',
            errorMsg: '',
            form:     window.enrouteInitialProfile || {},

            saveProfile() {
                this.saving  = true;
                this.message = '';
                this.errorMsg = '';
                const data = new FormData();
                data.append( 'action', 'enroute_save_profile' );
                data.append( 'nonce',  enrouteUserVars.nonce );
                Object.entries( this.form ).forEach( ([k,v]) => { if (k !== 'email') data.append(k, v); } );
                fetch( enrouteUserVars.ajaxUrl, { method:'POST', body:data } )
                    .then(r => r.json())
                    .then(res => {
                        this.saving = false;
                        if (res.success) {
                            this.message = res.data.message;
                            this.editing = false;
                            setTimeout(() => this.message = '', 4000);
                        } else {
                            this.errorMsg = res.data.message || 'Fehler.';
                        }
                    })
                    .catch(() => { this.saving = false; this.errorMsg = 'Verbindungsfehler.'; });
            },

            logout() {
                const data = new FormData();
                data.append('action', 'enroute_logout');
                data.append('nonce', enrouteUserVars.nonce);
                fetch(enrouteUserVars.ajaxUrl, { method:'POST', body:data })
                    .then(() => { window.location.reload(); });
            },
        }) );
    }

    function registerGuidesListing() {

        // ── GUIDES LISTING ────────────────────────────────────────────────────
        Alpine.data( 'enrouteGuidesListing', ( guides ) => ({

            guides:    guides,
            modalOpen: false,
            active:    null,

            openModal( guide ) {
                this.active    = guide;
                this.modalOpen = true;
                document.body.style.overflow = 'hidden';
            },

            closeModal() {
                this.modalOpen = false;
                this.active    = null;
                document.body.style.overflow = '';
            },
        }) );
    }

    // ── USER AUTH (login/register drawer) ────────────────────────────────────
    function registerUserAuth() {
        Alpine.data( 'enrouteUserAuth', () => ({
            mode:       'login',   // 'login' | 'register'
            loading:    false,
            errorMsg:   '',
            loggedIn:   (typeof enrouteUserVars !== 'undefined') && enrouteUserVars.loggedIn,
            profile:    (typeof enrouteUserVars !== 'undefined') ? enrouteUserVars.profile : {},
            form: {
                email: '', password: '', password2: '',
                enroute_first_name: '', enroute_last_name: '',
                enroute_salutation: '', enroute_institution: '',
                enroute_street: '', enroute_zip: '', enroute_place: '', enroute_phone: '',
            },

            submit() {
                this.errorMsg = '';
                this.loading  = true;
                const action  = this.mode === 'login' ? 'enroute_login' : 'enroute_register';
                const data    = new FormData();
                data.append('action', action);
                data.append('nonce', enrouteUserVars.nonce);
                Object.entries(this.form).forEach(([k,v]) => data.append(k,v));
                fetch(enrouteUserVars.ajaxUrl, { method:'POST', body:data })
                    .then(r => r.json())
                    .then(res => {
                        this.loading = false;
                        if (res.success) {
                            this.loggedIn = true;
                            this.profile  = res.data.profile;
                            // Dispatch event so booking form can prefill
                            window.dispatchEvent(new CustomEvent('enroute:loggedin', { detail: res.data.profile }));
                        } else {
                            this.errorMsg = res.data.message || 'Fehler.';
                        }
                    })
                    .catch(() => { this.loading = false; this.errorMsg = 'Verbindungsfehler.'; });
            },
        }) );
    }

    function registerBookingForm() {

        // ── BOOKING FORM ──────────────────────────────────────────────────────
        Alpine.data( 'enrouteBookingForm', () => ({
            form: {
                salutation:  '',
                institution: '',
                first_name:  '',
                last_name:   '',
                street:      '',
                zip:         '',
                place:       '',
                email:       '',
                phone:       '',
                date_1:      '',
                time_1:      '',
                date_2:      '',
                time_2:      '',
                persons:     '',
                remarks:     '',
            },
            loading:    false,
            submitted:  false,
            errorMsg:   '',
            successMsg: '',

            init() {
                // Prefill from profile if already logged in
                if ( typeof enrouteUserVars !== 'undefined' && enrouteUserVars.loggedIn && enrouteUserVars.profile ) {
                    this.prefillFromProfile( enrouteUserVars.profile );
                }
            },

            prefillFromProfile( profile ) {
                if ( ! profile ) return;
                this.form.salutation  = profile.enroute_salutation  || '';
                this.form.institution = profile.enroute_institution || '';
                this.form.first_name  = profile.enroute_first_name  || '';
                this.form.last_name   = profile.enroute_last_name   || '';
                this.form.street      = profile.enroute_street      || '';
                this.form.zip         = profile.enroute_zip         || '';
                this.form.place       = profile.enroute_place       || '';
                this.form.email       = profile.email               || '';
                this.form.phone       = profile.enroute_phone       || '';
            },

            submitBooking( offerId ) {
                this.errorMsg = '';

                // Client-side validation
                if ( ! this.form.first_name || ! this.form.last_name ) {
                    this.errorMsg = 'Bitte geben Sie Vor- und Nachnamen ein.';
                    return;
                }
                if ( ! this.form.email ) {
                    this.errorMsg = 'Bitte geben Sie eine E-Mail-Adresse ein.';
                    return;
                }
                if ( ! this.form.date_1 ) {
                    this.errorMsg = 'Bitte wählen Sie ein Wunschdatum.';
                    return;
                }

                this.loading = true;

                const data = new FormData();
                data.append( 'action', 'enroute_submit_booking' );
                data.append( 'nonce',  enrouteBookingVars.nonce );
                data.append( 'offer_id', offerId );
                Object.entries( this.form ).forEach( ([ k, v ]) => data.append( k, v ) );

                fetch( enrouteBookingVars.ajaxUrl, { method: 'POST', body: data } )
                    .then( r => r.json() )
                    .then( res => {
                        this.loading = false;
                        if ( res.success ) {
                            this.submitted  = true;
                            this.successMsg = res.data.message || '';
                        } else {
                            this.errorMsg = res.data.message || 'Ein Fehler ist aufgetreten.';
                        }
                    } )
                    .catch( () => {
                        this.loading  = false;
                        this.errorMsg = 'Verbindungsfehler. Bitte versuchen Sie es erneut.';
                    } );
            }
        }) );
    }

    const allComponents = [registerComponents, registerGuidesListing, registerUserProfile, registerUserAuth, registerBookingForm];
    if ( window.Alpine ) {
        allComponents.forEach(fn => fn());
        allComponents.forEach(fn => document.addEventListener('alpine:init', fn));
    } else {
        allComponents.forEach(fn => document.addEventListener('alpine:init', fn));
    }

})();
