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
        Alpine.data( 'enrouteOffersListing', ( offers ) => ({

            all:        offers,
            filterOpen: false,

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

            get filtered() {
                return this.all.filter( offer => {
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

            toggleMultiFilter( key, value ) {
                const idx = this.pending[ key ].indexOf( value );
                if ( idx === -1 ) this.pending[ key ].push( value );
                else              this.pending[ key ].splice( idx, 1 );
            },

            applyFilters() {
                Object.keys( this.filters ).forEach( k => {
                    this.filters[ k ] = [ ...this.pending[ k ] ];
                });
            },

            resetFilters() {
                Object.keys( this.filters ).forEach( k => {
                    this.filters[ k ]  = [];
                    this.pending[ k ]  = [];
                });
            },
        }) );

        // ── RESOURCES LISTING ─────────────────────────────────────────────
        Alpine.data( 'enrouteResourcesListing', ( resources ) => ({

            all:        resources,
            filterOpen: false,

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

            get filtered() {
                return this.all.filter( res => {
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

            toggleMultiFilter( key, value ) {
                const idx = this.pending[ key ].indexOf( value );
                if ( idx === -1 ) this.pending[ key ].push( value );
                else              this.pending[ key ].splice( idx, 1 );
            },

            applyFilters() {
                Object.keys( this.filters ).forEach( k => {
                    this.filters[ k ] = [ ...this.pending[ k ] ];
                });
            },

            resetFilters() {
                Object.keys( this.filters ).forEach( k => {
                    this.filters[ k ]  = [];
                    this.pending[ k ]  = [];
                });
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

    if ( window.Alpine ) {
        registerComponents();
        registerGuidesListing();
        registerBookingForm();
        document.addEventListener( 'alpine:init', registerComponents );
        document.addEventListener( 'alpine:init', registerGuidesListing );
        document.addEventListener( 'alpine:init', registerBookingForm );
    } else {
        document.addEventListener( 'alpine:init', registerComponents );
        document.addEventListener( 'alpine:init', registerGuidesListing );
        document.addEventListener( 'alpine:init', registerBookingForm );
    }

})();
