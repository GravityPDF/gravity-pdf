/**
 * Gravity PDF Settings JS Logic 
 * Dependancies: backbone, underscore, jquery
 * @since 4.0
 */

(function($) {	

	$(function() {
		/**
		 * Write our backbone model/view/controller for the help API
		 */	
		var help = {}; // create namespace for our app
		var app  = {};

		help.SearchModel = Backbone.Model.extend({});

		help.SearchCollection = Backbone.Collection.extend({
			model: help.SearchModel,

			initialize: function(models, options) {
				this.url = options.url;
			},

		});

		help.SearchCollectionForum = help.SearchCollection.extend({
			parse: function(response) {
				return response.topic_list.topics;
			}
		}),

		help.ContainerView = Backbone.View.extend({
			el: '#search-knowledgebase',

			events: {
				'keyup #search-help-input' : 'doSearch', 
				'change #search-help-input' : 'doSearch', 
			},

			initialize: function() {
				/* initialise our timer */
				this.timer = true;

				/* render the container view */
				this.render();
			},

			render: function() {
				this.addSearchBar();
				 		
				return this;
			},

			addSearchBar: function() {
				/* create our search element */
				var $input = $('<input>').attr('type', 'text')
						  			     .attr('placeholder', '  ' + GFPDF.help_search_placeholder)
						  			     .attr('id', 'search-help-input');					  			     						  			     

				/* add out search box and give it focus */
				this.$el.prepend($input);	  

				$input.tooltip({
					items: 'input',
					content: 'The search must be more than 3 characters.',
					tooltipClass: 'ui-state-error',
				}).tooltip( 'disable' );	

				/* give our search box focus */
				$input.focus();	    		
			},

			doSearch: function(ev) {
				var $search = $(ev.currentTarget);

				/* clear any previous events */
				window.clearTimeout(this.timer);				

				/* only trigger our search if user has entered more than 3 characters */
				var value         = $.trim($search.val());
				var previousValue = $search.data('currentValue');

				if(value.length > 3 && $search.data('previousValue') !== value) {
					$search.tooltip( 'disable' );

					/* track the data value */
					$search.data('currentValue', value);

					this.timer = window.setTimeout(_.bind(function() {
						this.processSearch(value);
					}, this), 500);
				} else if(value.length <= 3 && ev.keyCode == 13) {
					$search.tooltip( 'enable' ).tooltip( 'open' );
				}
			},

			processSearch: function(search) {
				/* Initialise our Collection and pull the data from our source */
				console.log('Searching our collection...');

				/* start our forum search */
				new help.ForumView({
					s: search,
				});

				new help.DocsView({
					s: search,
				})
			},



		});

		help.MainView = Backbone.View.extend({

			callAPI: function(url) {
				/* do our search */
				this.collection.fetch({
					success: _.bind(this.renderSearch, this),
					error: _.bind(this.renderSearchError),
				});							
			},

			renderSearch: function(collection, response) {
				console.log('Rendering Search Results');

				this.hideSpinner();

				var $container = this.$el.find('.inside ul');

				$container.html(this.template({
					collection: this.collection.first(6),
					url: this.url,
				}));

				var $wrapper = $container.parent();
				if(!$wrapper.is(':visible')) {
					$wrapper.slideDown(500);
				}				

			},

			renderSearchError: function(collection, response) {
				console.log('Search Failed');
				console.log(collection);
				console.log(response);
			},


			showSpinner: function() {
				this.$el.find('.spinner').addClass('is-active');										  

				if(!this.$el.is(':visible')) {
					this.$el.slideDown(500);
				}				
			},

			hideSpinner: function() {
				this.$el.find('.spinner').removeClass('is-active');
			},	    	
		});

		help.DocsView = help.MainView.extend({
			el: '#documentation-api',					

			template: '#GravityPDFSearchResultsDocumentation',

			initialize: function(options) {
				this.url = 'https://developer.gravitypdf.com/wp-json/posts/?type=docs';
				this.s   = options.s;
				this.render();
			},

			render: function() {	    
				/* set up out template */
				this.template = _.template($(this.template).html());

				/* show the loading spinner */			
				this.showSpinner();				

				/* set up view search params */
				var s   = encodeURIComponent(this.s);
				var url = this.url + '&s=' + s;

				/* initialise our collection */
				this.collection = new help.SearchCollection([], {
					url: url,	    			
				});

				/* ping api for results */
				this.callAPI(url);

				return this;
			},
		});

		help.ForumView = help.MainView.extend({
			el: '#forum-api',	

			template: '#GravityPDFSearchResultsForum',		

			initialize: function(options) {
				this.url = 'https://support.gravitypdf.com/';
				this.s   = options.s;
				this.render();
			},

			render: function() {	    
				/* set up out template */
				this.template = _.template($(this.template).html());

				/* show the loading spinner */			
				this.showSpinner();				

				/* set up view search params */
				var s   = encodeURIComponent(this.s);
				var url = this.url + 'search.json?search=' + s + '&q=' + s;
				
				/* initialise our collection */
				this.collection = new help.SearchCollectionForum([], {
					url: url,	    			
				});				

				/* ping api for results */
				this.callAPI(url);

				return this;
			},
		});

		/**
		 * Create a new underscore function to process iso date 
		 */
		_.template.formatdate = function (date) {
		    var d = new Date(date); // You could just pass in a regular timestamp here too
		    return d.toLocaleDateString();
		};

		/**
		 * Our Admin controller 
		 * Applies correct JS to settings pages 
		 */
		function GravityPDF () {
			var self = this;

			this.init = function() {
				if(this.is_settings()) {
					this.processSettings();
				}
			}

			this.is_settings = function() {
				return $('#tab_PDF').length;
			}

			this.processSettings = function() {
				var active = $('.nav-tab-wrapper a.nav-tab-active:first').text();

				switch (active) {
					case 'Help':
						this.help_settings();
					break;
				}
			}	

			/**
			 * The help settings model method 
			 * This sets up and processes any of the JS that needs to be applied on the help settings tab 
			 * @since 3.8
			 */
			this.help_settings = function() {
				/**
				 * Load our settings dependancy 
				 */
				new help.ContainerView();
			}					

		}	

		var pdf = new GravityPDF();
		pdf.init();				

	});		
})(jQuery);		