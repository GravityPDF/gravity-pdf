/**
 * Gravity PDF Settings JS Logic
 * Dependancies: backbone, underscore, jquery
 * @since 4.0
 */

(function($) {

  $(function() {

    /**
     * To prevent problems with PHP's ASP Short tags (removed in PHP 7) we'll change Underscore's delimiters to be Handlebars-ish:
     * {{= }}, {{- }} or {{ }}
     *
     * @see https://github.com/GravityPDF/gravity-pdf/issues/417
     * @type {{interpolate: RegExp, evaluate: RegExp, escape: RegExp}}
     * @since 4.0.1
     */
    var UnderscoreSettingsOverride = {
      evaluate: /\{\{(.+?)\}\}/gim,
      interpolate: /\{\{=(.+?)\}\}/gim,
      escape: /\{\{-(.+?)\}\}/gim
    };


    /**
     * Handles our Font CRUD Feature
     * Allows URLs to TTF font files to be passed
     * to a specific custom font 'group' defined by the user.
     *
     * Backbone model's save and destroy methods have been overridden
     * to work with WordPress' ajax-admin.php file endpoint.
     *
     * @since 4.0
     */
    var Fonts = {
      Model: {},
      Collection: {},
      View: {},
      Misc: {},
    };


    /**
     * Our Font Backbone Model which handles validation, saving and deleting
     * @since 4.0
     */
    Fonts.Model.Core = Backbone.Model.extend({
      /**
       * Set our default model parameters
       * Existing models pulled from the database also have an ID parameter
       * @type {Object}
       * @since 4.0
       */
      defaults: {
        font_name: 		'',
        regular: 		'',
        bold: 			'',
        italics: 		'',
        bolditalics: 	'',
        disabled: 		false,
      },

      /**
       * Set our custom endpoint to be used to sync the model
       * @type String
       * @since 4.0
       */
      url: GFPDF.ajaxUrl,

      /**
       * Route save through ajax-admin.php which doesn't support standard
       * REST API features. Emulate POST and JSON data structures.
       * @param  Object options
       * @param  Object additional configuration options
       * @return Object
       * @since 4.0
       */
      save: function(options, config) {

        var params = {
          emulateHTTP: true,
          emulateJSON: true,
          data: {
            action: 'gfpdf_font_save',
            nonce: options.nonce,
            payload : this.toJSON()
          }
        };

        /* Merge out two objects together */
        $.extend(params, config);

        return Backbone.sync( 'update', this, params );
      },

      /**
       * Route delete through ajax-admin.php which doesn't support standard
       * REST API features. Emulate POST and JSON data structures.
       * @param  Object options
       * @param  Object additional configuration options
       * @return Object
       * @since 4.0
       */
      destroy: function(options, config) {

        var params = {
          emulateHTTP: true,
          emulateJSON: true,
          data: {
            action: 'gfpdf_font_delete',
            nonce: options.nonce,
            id : this.get('id'),
          }
        };

        /* Merge out two objects together */
        $.extend(params, config);

        return Backbone.sync( 'update', this, params );
      },

      /**
       * Create custom validation method which will prevent a model being updated
       * when using .save() or .set() if the validation fails.
       *
       * Multiple custom events are also triggered to allow our view to update the DOM as needed
       * @param  Object attrs   The new model data
       * @param  Object options
       * @return String         On error, a string is returned
       * @since 4.0
       */
      validate: function(attrs, options) {

        /* Do name validation */
        var regex = new RegExp('^[A-Za-z0-9 ]+$');

        if( attrs.font_name.length > 0) {
          if(! regex.test(attrs.font_name) ) {

            /* If not successful trigger error */
            return 'invalid_characters';
          } else {

            /* trigger successful event to disable the view error */
            this.trigger('valid_name');
          }
        }

        /**
         * Validate the selected fonts
         */
        if(this.validateFonts(attrs) === false) {
          return 'invalid_font';
        }

        this.trigger('validation_passed', this);
      },

      /**
       * Check if font value (regular, bold, italics, bolditalics)
       * has a value at all, and if so whether it has a .ttf extension.
       *
       * Multiple custom events are also triggered to allow our view to update a particular
       * DOM element based on the validation success or failure.
       *
       * @param  Object attrs The new model data
       * @return Boolean Whether the group validation passed or failed
       * @since 4.0
       */
      validateFonts: function(attrs) {

        /* set validation to true and any that fail will mark as false */
        var validation = true;

        /* set up an object with our key-value pairs refering to the input data */
        var fonts = {
          regular: 		attrs.regular,
          bold: 			attrs.bold,
          italics: 		attrs.italics,
          bolditalics: 	attrs.bolditalics,
        };

        /* Loop through the object and use jQuery's proxy to correct the 'this' scope */
        $.each( fonts, $.proxy(function( index, font ) {

          /* if there is value for the font we'll validate it */
          if(font.length > 0 && this.isValidFile(font) === false) {

            /* mark our global validation as false */
            validation = false;
            this.trigger('invalid_font', this, true, index); /* tell our view the font is invalid */
          } else {
            this.trigger('valid_font', this, false, index); /* tell our view the font is valid */
          }

        }, this));

        return validation;
      },

      /**
       * Does our actual font validation
       * Checks if the string length is large enough to be considered valid,
       * and then checks if it has a file extension of .ttf
       * @param  String  font The font string to check
       * @return Boolean True on success, false on failure
       * @since 4.0
       */
      isValidFile: function(font) {

        /* Check if the value could contain enough characters to be valid */
        if(font.length < 5) {
          return false;
        }

        /* Get the last 4 characters and convert to lower case */
        var extension = font.substr(font.length - 4).toLowerCase();

        /* Check if they match a TTF font file */
        if(extension === '.ttf') {

          /* Check if we have a valid URL */
          var regex = new RegExp(/^[a-z](?:[-a-z0-9\+\.])*:(?:\/\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~!\$&'\(\)\*\+,;=:\xA0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[\uD800-\uD83E\uD840-\uD87E\uD880-\uD8BE\uD8C0-\uD8FE\uD900-\uD93E\uD940-\uD97E\uD980-\uD9BE\uD9C0-\uD9FE\uDA00-\uDA3E\uDA40-\uDA7E\uDA80-\uDABE\uDAC0-\uDAFE\uDB00-\uDB3E\uDB44-\uDB7E][\uDC00-\uDFFF]|[\uD83F\uD87F\uD8BF\uD8FF\uD93F\uD97F\uD9BF\uD9FF\uDA3F\uDA7F\uDABF\uDAFF\uDB3F\uDB7F][\uDC00-\uDFFD])*@)?(?:\[(?:(?:(?:[0-9a-f]{1,4}:){6}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|::(?:[0-9a-f]{1,4}:){5}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){4}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:[0-9a-f]{1,4}:[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){3}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,2}[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){2}(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,3}[0-9a-f]{1,4})?::[0-9a-f]{1,4}:(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,4}[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3})|(?:(?:[0-9a-f]{1,4}:){0,5}[0-9a-f]{1,4})?::[0-9a-f]{1,4}|(?:(?:[0-9a-f]{1,4}:){0,6}[0-9a-f]{1,4})?::)|v[0-9a-f]+[-a-z0-9\._~!\$&'\(\)\*\+,;=:]+)\]|(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(?:\.(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}|(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~!\$&'\(\)\*\+,;=@\xA0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[\uD800-\uD83E\uD840-\uD87E\uD880-\uD8BE\uD8C0-\uD8FE\uD900-\uD93E\uD940-\uD97E\uD980-\uD9BE\uD9C0-\uD9FE\uDA00-\uDA3E\uDA40-\uDA7E\uDA80-\uDABE\uDAC0-\uDAFE\uDB00-\uDB3E\uDB44-\uDB7E][\uDC00-\uDFFF]|[\uD83F\uD87F\uD8BF\uD8FF\uD93F\uD97F\uD9BF\uD9FF\uDA3F\uDA7F\uDABF\uDAFF\uDB3F\uDB7F][\uDC00-\uDFFD])*)(?::[0-9]*)?(?:\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~!\$&'\(\)\*\+,;=:@\xA0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[\uD800-\uD83E\uD840-\uD87E\uD880-\uD8BE\uD8C0-\uD8FE\uD900-\uD93E\uD940-\uD97E\uD980-\uD9BE\uD9C0-\uD9FE\uDA00-\uDA3E\uDA40-\uDA7E\uDA80-\uDABE\uDAC0-\uDAFE\uDB00-\uDB3E\uDB44-\uDB7E][\uDC00-\uDFFF]|[\uD83F\uD87F\uD8BF\uD8FF\uD93F\uD97F\uD9BF\uD9FF\uDA3F\uDA7F\uDABF\uDAFF\uDB3F\uDB7F][\uDC00-\uDFFD]))*)*|\/(?:(?:(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~!\$&'\(\)\*\+,;=:@\xA0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[\uD800-\uD83E\uD840-\uD87E\uD880-\uD8BE\uD8C0-\uD8FE\uD900-\uD93E\uD940-\uD97E\uD980-\uD9BE\uD9C0-\uD9FE\uDA00-\uDA3E\uDA40-\uDA7E\uDA80-\uDABE\uDAC0-\uDAFE\uDB00-\uDB3E\uDB44-\uDB7E][\uDC00-\uDFFF]|[\uD83F\uD87F\uD8BF\uD8FF\uD93F\uD97F\uD9BF\uD9FF\uDA3F\uDA7F\uDABF\uDAFF\uDB3F\uDB7F][\uDC00-\uDFFD]))+)(?:\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~!\$&'\(\)\*\+,;=:@\xA0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[\uD800-\uD83E\uD840-\uD87E\uD880-\uD8BE\uD8C0-\uD8FE\uD900-\uD93E\uD940-\uD97E\uD980-\uD9BE\uD9C0-\uD9FE\uDA00-\uDA3E\uDA40-\uDA7E\uDA80-\uDABE\uDAC0-\uDAFE\uDB00-\uDB3E\uDB44-\uDB7E][\uDC00-\uDFFF]|[\uD83F\uD87F\uD8BF\uD8FF\uD93F\uD97F\uD9BF\uD9FF\uDA3F\uDA7F\uDABF\uDAFF\uDB3F\uDB7F][\uDC00-\uDFFD]))*)*)?|(?:(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~!\$&'\(\)\*\+,;=:@\xA0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[\uD800-\uD83E\uD840-\uD87E\uD880-\uD8BE\uD8C0-\uD8FE\uD900-\uD93E\uD940-\uD97E\uD980-\uD9BE\uD9C0-\uD9FE\uDA00-\uDA3E\uDA40-\uDA7E\uDA80-\uDABE\uDAC0-\uDAFE\uDB00-\uDB3E\uDB44-\uDB7E][\uDC00-\uDFFF]|[\uD83F\uD87F\uD8BF\uD8FF\uD93F\uD97F\uD9BF\uD9FF\uDA3F\uDA7F\uDABF\uDAFF\uDB3F\uDB7F][\uDC00-\uDFFD]))+)(?:\/(?:(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~!\$&'\(\)\*\+,;=:@\xA0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[\uD800-\uD83E\uD840-\uD87E\uD880-\uD8BE\uD8C0-\uD8FE\uD900-\uD93E\uD940-\uD97E\uD980-\uD9BE\uD9C0-\uD9FE\uDA00-\uDA3E\uDA40-\uDA7E\uDA80-\uDABE\uDAC0-\uDAFE\uDB00-\uDB3E\uDB44-\uDB7E][\uDC00-\uDFFF]|[\uD83F\uD87F\uD8BF\uD8FF\uD93F\uD97F\uD9BF\uD9FF\uDA3F\uDA7F\uDABF\uDAFF\uDB3F\uDB7F][\uDC00-\uDFFD]))*)*|(?!(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~!\$&'\(\)\*\+,;=:@\xA0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[\uD800-\uD83E\uD840-\uD87E\uD880-\uD8BE\uD8C0-\uD8FE\uD900-\uD93E\uD940-\uD97E\uD980-\uD9BE\uD9C0-\uD9FE\uDA00-\uDA3E\uDA40-\uDA7E\uDA80-\uDABE\uDAC0-\uDAFE\uDB00-\uDB3E\uDB44-\uDB7E][\uDC00-\uDFFF]|[\uD83F\uD87F\uD8BF\uD8FF\uD93F\uD97F\uD9BF\uD9FF\uDA3F\uDA7F\uDABF\uDAFF\uDB3F\uDB7F][\uDC00-\uDFFD])))(?:\?(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~!\$&'\(\)\*\+,;=:@\/\?\xA0-\uD7FF\uE000-\uFDCF\uFDF0-\uFFEF]|[\uD800-\uD83E\uD840-\uD87E\uD880-\uD8BE\uD8C0-\uD8FE\uD900-\uD93E\uD940-\uD97E\uD980-\uD9BE\uD9C0-\uD9FE\uDA00-\uDA3E\uDA40-\uDA7E\uDA80-\uDABE\uDAC0-\uDAFE\uDB00-\uDB3E\uDB44-\uDB7E\uDB80-\uDBBE\uDBC0-\uDBFE][\uDC00-\uDFFF]|[\uD83F\uD87F\uD8BF\uD8FF\uD93F\uD97F\uD9BF\uD9FF\uDA3F\uDA7F\uDABF\uDAFF\uDB3F\uDB7F\uDBBF\uDBFF][\uDC00-\uDFFD])*)?(?:\#(?:%[0-9a-f][0-9a-f]|[-a-z0-9\._~!\$&'\(\)\*\+,;=:@\/\?\xA0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[\uD800-\uD83E\uD840-\uD87E\uD880-\uD8BE\uD8C0-\uD8FE\uD900-\uD93E\uD940-\uD97E\uD980-\uD9BE\uD9C0-\uD9FE\uDA00-\uDA3E\uDA40-\uDA7E\uDA80-\uDABE\uDAC0-\uDAFE\uDB00-\uDB3E\uDB44-\uDB7E][\uDC00-\uDFFF]|[\uD83F\uD87F\uD8BF\uD8FF\uD93F\uD97F\uD9BF\uD9FF\uDA3F\uDA7F\uDABF\uDAFF\uDB3F\uDB7F][\uDC00-\uDFFD])*)?$/igm);

          if(regex.test(font) ) {
            return true;
          }
        }

        return false;
      },

      /**
       * Our ModelBinder CSS Declaration Converter
       * @param  String direction Either ModelToView or ViewToModel
       * @param  String value     The value to convert
       * @param  String target    The DOM target name
       * @param  Object model     The Model
       * @return String
       * @since 4.0
       */
      cssDeclaration: function(direction, value, target, model) {
        var shortname = model.getShortname(value);
        return 'font-family: "' + value + '", ' + shortname + ', sans-serif;';
      },

      /**
       * Converts a standard name to the format used in mPDF
       * @param  String name The name to convert
       * @return String
       * @since 4.0
       */
      getShortname: function(name) {
        name = name.toLowerCase();
        name = name.replace(' ', '');

        return name;
      }
    });


    /**
     * Creates a Backbone collection with our model attached
     * @type Object
     * @since 4.0
     */
    Fonts.Collection.Core = Backbone.Collection.extend({
      model: Fonts.Model.Core
    });


    /**
     * Our top-level view for rendering our font list.
     * This view is responsible for looping through the Backbone collection,
     * creating a new Fonts.View.Item object and appending it to our container
     *
     * We also have a listenTo() event which triggers the rendering process when a new
     * model is added to our collection.
     *
     * @since 4.0
     */
    Fonts.View.Container = Backbone.View.extend({

      /**
       * Our View Target Element
       * @type String
       * @since 4.0
       */
      el: '#font-list',

      /**
       * Our View wrapper tag
       * @type String
       * @since 4.0
       */
      tagName: 'ul',

      /**
       * Automatically render our view upon initialization and add
       * an event listener so new Font.View.Item objects can be automatically
       * appended to our container (without re-rendering all the objects)
       *
       * @param  Object options Any passed in parameters when the object is created
       * @since 4.0
       */
      initialize: function(options) {

        this.listenTo(this.collection, 'add', this.addRender);
        this.listenTo(this.collection, 'remove', this.render);
        this.render();
      },

      /**
       * Handles the DOM display process
       * @return Object Returns reference to itself (for chaining purposes)
       * @since 4.0
       */
      render: function() {

        /* Check if our collection has any models */
        if(this.collection.length > 0) {

          /* Empty our element */
          this.$el.empty();

          /* Loop through our collection and render to our container */
          this.collection.each(function(font) {
            this.addRender(font);
          }, this);
        } else {
          /* Display getting started message to user */
          this.$el.html(_.template($( '#GravityPDFFontsEmpty' ).html(), null, UnderscoreSettingsOverride));
        }

        /* Return for chaining purposes */
        return this;
      },

      /**
       * Creates our individual Item View and append to the container
       * @param Object font The individual collection model
       * @since 4.0
       */
      addRender: function(font) {

        /* Empty our container if no models exist (as we display a welcome message if list is empty) */
        if(this.collection.length === 1) {
          this.$el.empty();
        }

        /* Create an individual font view */
        var item = new Fonts.View.Item({
          model: font,
          collection: this.collection
        });

        /* Append it to our container */
        this.$el.append(item.render().el);

        /* Return for chaining purposes */
        return this;
      }
    });



    /**
     * Handles the individual display of our font model
     *
     * Multiple DOM events are used to process form interactions, such as toggling the interface, deleting a model and savings.
     * Multiple custom event listeners are used to display validation errors in our form.
     *
     * The Backbone.ModelBinder() object is used to add two-way data binding between our model and view
     *
     * @since 4.0
     */
    Fonts.View.Item = Backbone.View.extend({

      /**
       * The ID of our Underscore HTML template
       * This can be found in /src/view/html/Settings/tools.php
       * @type String
       * @since 4.0
       */
      template: '#GravityPDFFonts',

      /**
       * Our View wrapper tag
       * @type String
       * @since 4.0
       */
      tagName: 'li',

      /**
       * Our DOM Events
       * @type Object
       * @since 4.0
       */
      events: {

        /* Click event for toggling our form interface for editing */
        'click .font-name' : 'toggleView',

        /* Click event to delete a model */
        'click .delete-font': 'deleteModel',

        /* Submit event for saving a model */
        'submit form': 'formSubmission',
      },

      /**
       * Initialize our model-view data binder object and set up
       * our event listeners for displaying visual validation queues to the user
       * @since 4.0
       */
      initialize: function() {

        /* Intialize our two-way data binder */
        this.modelBinder = new Backbone.ModelBinder();

        /* Show Name Validation Errors */
        this.listenTo(this.model, 'invalid valid_name', this.nameError);

        /* Show Font Group Validation Errors */
        this.listenTo(this.model, 'invalid_font valid_font', this.fontError);

        /* Enable / Disable the Submit button based on validation errors */
        this.listenTo(this.model, 'invalid', this.disableSubmitButton);
        this.listenTo(this.model, 'validation_passed', this.enableSubmitButton);
      },

      /**
       * Render the individual view based on an Underscore template
       * @return Object Return 'this' for chaining
       * @since 4.0
       */
      render: function() {

        /* Set up our Underscore template file */
        this.template = _.template($( this.template ).html(), null, UnderscoreSettingsOverride);

        /* Set View Element HTML to our Underscore template, passing in our model */
        this.$el.html(this.template({
          model: this.model,
        }));

        /**
         * Enable two-way data binding between our model and view
         *
         * By default updates are only triggered on change events but our name field
         * should also trigger on keyUp.
         *
         * We also want to run our validation routine on the this.model.set() command (by default this is disabled)
         * so the user isn't confused with the live-update display of the Font Name field.
         */
        this.modelBinder.bind(this.model, this.el, {
            font_name: 		[ { selector: '[name=font_name]' } , {selector: '[name=usage]', converter: this.model.cssDeclaration } ],
            regular: 		'[name=regular]',
            bold: 			'[name=bold]',
            italics: 		'[name=italics]',
            bolditalics: 	'[name=bolditalics]',
          },

          {

            changeTriggers: {
              '': 'change',
              '.font-name-field': 'keyup'
            },

            modelSetOptions: {
              validate: true
            }
          });

        /* Return for chaining purposes */
        return this;
      },

      /**
       * Show / Hide the Font Manager (Editor)
       * @param  Object ev The Backbone event object
       * @since 4.0
       */
      toggleView: function(ev) {

        /* Prevent default anchor action */
        ev.preventDefault();

        /* Toggle our Font Manager Container */
        $(ev.currentTarget).next().toggle();

      },

      /**
       * Highlight field with red border when error is detected
       * @param  Object model   The model currently being modified
       * @param  String error   The name of the error triggered
       * @since 4.0
       */
      nameError: function(model, error) {

        if(error && error == 'invalid_characters') {

          /* highlight errors */
          this.$el.find('input[name="font_name"]').css('border-color', 'red');
        } else {

          /* un-highlight errors */
          this.$el.find('input[name="font_name"]').removeAttr('style');
        }
      },

      /**
       * Highlight / Un-highlight Font Field that failed validation
       * @param  Object model   The model currently being modified
       * @param  String error   The name of the error triggered
       * @param  {[type]} name  The name of the field which failed validation
       * @since 4.0
       */
      fontError: function(model, error, name) {

        /* Get the input that failed validation */
        $input = this.$el.find('input[name="' + name + '"]');

        /* If error is triggered display message to user */
        if(error) {

          /* Set up an error message */
          $error = $('<span class="gf_settings_description"><label>Only TTF font files are supported.</label></span>');

          /* Tell user about error */
          if(! $input.hasClass('invalid')) {
            $input.addClass('invalid').next().after($error.clone());
          }
        } else {

          /* Remove validation error */
          if($input.hasClass('invalid')) {
            $input.removeClass('invalid').next().next().remove();
          }
        }
      },

      /**
       * Disable the form submit button due to validation failure and mark is
       * as currently disabled to prevent any funny business
       * @param  Object model An instance of Fonts.Model.Core()
       * @since 4.0
       */
      disableSubmitButton: function(model) {

        this.$el.find('.font-submit button').prop('disabled', true);
        model.set('disabled', true);
      },

      /**
       * Enable the form submit button due to validation passing and mark is
       * as currently enabled. Users can now save the model.
       * @param  Object model An instance of Fonts.Model.Core()
       * @since 4.0
       */
      enableSubmitButton: function(model) {

        this.$el.find('.font-submit button').prop('disabled', false);
        model.set('disabled', false);
      },

      /**
       * Save model after checking browser's native validation API passes and that model isn't enabled.
       * @param  Object ev The Backbone event object
       * @since 4.0
       */
      formSubmission: function(ev) {

        /* Allow native validation without submitting actual form to backend */
        ev.preventDefault();

        var $form = $(ev.currentTarget);

        /* Check if the native form validation is a success */
        if(ev.currentTarget.checkValidity() && this.model.get('disabled') === false) {

          /* Show saving spinner */
          this.addSpinner();

          /* Remove previous message */
          this.removeMessage();

          console.log(this.model);

          this.model.save({
            nonce: this.$el.find('input[name=wpnonce]').val()
          }, {
            success: $.proxy(function(model, response, options) {

              /* Remove saving spinner */
              this.removeSpinner();

              /* Display Message */
              this.displayMessage(GFPDF.updateSuccess);

              /* Keep our model in sync */
              this.model.set(model);

            }, this),

            error: $.proxy(function(response, type, errorName) {

              /* Remove saving spinner */
              this.removeSpinner();

              /* Display Error */
              if(response.responseJSON.error) {
                this.displayMessage(response.responseJSON.error, true);
              }
            }, this)
          });
        }
      },

      /**
       * Delete our model
       * @param  Object ev The Backbone event object
       * @since 4.0
       */
      deleteModel: function(ev) {

        /* Prevent default anchor action */
        ev.preventDefault();

        /* Get our dialog box */
        var $dialog = $( '#delete-confirm' );

        /* Set up our dialog box buttons */
        var deleteButtons = [{
          text: GFPDF.delete,
          click: $.proxy(function() {

            /* Hide the confirmation dialog */
            $dialog.wpdialog( 'destroy' );

            /* If an ID is set (pulled from DB) do our AJAX delete call */
            if(this.model.get('id')) {

              /* Show saving spinner */
              this.addSpinner();

              /* Remove previous message */
              this.removeMessage();

              /* Hide the container */
              this.$el.hide();

              this.model.destroy({
                nonce: this.$el.find('input[name=wpnonce]').val()
              }, {
                success: $.proxy(function(model, response, options) {

                  /* Remove saving spinner */
                  this.removeSpinner();

                  /* Display Message */
                  this.displayMessage(GFPDF.deleteSuccess);

                  /* Remove from collection */
                  this.collection.remove(this.model);

                }, this),

                error: $.proxy(function(response, type, errorName) {

                  /* Remove saving spinner */
                  this.removeSpinner();

                  /* Remove from collection */
                  this.collection.remove(this.model);

                  /* Display Error */
                  if(response.responseJSON.error) {
                    this.displayMessage(response.responseJSON.error, true);
                  }

                }, this)
              });

              /* TODO: if destroy() is successful remove the hidden item */
            } else {
              this.collection.remove(this.model);
            }

          }, this)
        },
          {
            text: GFPDF.cancel,
            click: function() {

              /* Cancel */
              $dialog.wpdialog( 'destroy' );
            }
          }];

        /* Set up our dialog box */
        Fonts.Misc.Dialog($dialog, deleteButtons, 300, 175);

        /* Open the dialog box */
        $dialog.wpdialog( 'open' );
      },

      /**
       * Adds an AJAX loader so the user knows a query is being made
       * @since 4.0
       */
      addSpinner: function() {
        var $spinner = $('<img alt="Loading" src="' + GFPDF.spinnerUrl + '" class="gfpdf-spinner" style="margin-top: 4px;" />');
        this.$el.find('.font-submit button').after($spinner);
      },

      /**
       * Remove AJAX loader so the user knows a query is finished
       * @since 4.0
       */
      removeSpinner: function() {
        this.$el.find('.gfpdf-spinner').remove();
      },

      /**
       * Show message to the user
       * @param  String  msg     The message to be displayed
       * @param  Boolean isError If set to true an error will be displayed
       * @since 4.0
       */
      displayMessage: function(msg, isError) {

        /* Generate our error template */
        var $message = $('<div class="updated notice">');

        /* Add our error class if requested */
        if(isError === true) {
          $message.addClass('error');
        }

        /* Add the message to be displayed */
        $message.html('<p>' + msg + '</p>');

        /* Add message to the DOM */
        this.$el.find('form').before($message);
      },

      /**
       * Remove any messages currently being shown to the user
       * @since 4.0
       */
      removeMessage: function() {
        this.$el.find('div.notice').slideUp(function() {
          $(this).remove();
        });
      }
    });

    /**
     * A simple view that creates an interface for adding new models to our collection
     * @since 4.0
     */
    Fonts.View.Add = Backbone.View.extend({

      /**
       * Our View Target Element
       * @type String
       * @since 4.0
       */
      el: '#font-add-list',

      /**
       * Our DOM Events
       * @type Object
       * @since 4.0
       */
      events: {
        'click': 'addFont',
      },

      /**
       * Store our Fonts.Container.Core() object so our view can easily add new models
       * and renders the view
       * @param  Object options User-passed parameters
       * @since 4.0
       */
      initialize: function(options) {

        this.container = options.container;
        this.render();
      },

      /**
       * Renders our View
       * @since 4.0
       */
      render: function() {
        this.$el.html('<i class="fa fa-plus fa-4x"></i><span>Add Font</span>');
      },

      /**
       * Add a new model to our collection
       * @param  Object ev The Backbone event object
       * @since 4.0
       */
      addFont: function(ev) {

        /* Create an empty model */
        var font = new Fonts.Model.Core();

        /* Add new model to our collection */
        this.collection.add(font);

        /* Toggle the new model's font manager and set focus to our name field */
        this.container.$el.find('li:last .font-settings').toggle()
          .find('input[type="text"]:first').focus();
      }
    });


    /**
     * Generate a WP Dialog box
     * @param  jQuery Object $elm The element we want to bind the dialog box to
     * @param  Object buttonsList Handles the button creation process. More information found in the jQuery UI Dialog Documentation
     * @param  Integer boxWidth    How wide should the dialog box be
     * @param  Integer boxHeight   How tall should the dialog box be
     * @since 4.0
     */
    Fonts.Misc.Dialog = function($elm, buttonsList, boxWidth, boxHeight) {
      $elm.wpdialog({
        autoOpen: 		false,
        resizable: 		false,
        draggable: 		false,
        width: 			boxWidth,
        height: 		boxHeight,
        modal: 			true,
        dialogClass: 	'wp-dialog',
        zIndex: 		300000,
        buttons: 		buttonsList,

        /**
         * Open our dialog box and set focus to the first button find
         * Also add an event listener to close the dialog when the background is clicked
         */
        open: function() {
          $(this).siblings('.ui-dialog-buttonpane').find('button:eq(1)').focus();

          $('.ui-widget-overlay').bind('click', function() {
            $elm.wpdialog('close');
          });
        }
      });
    };




    /**
     * Our Documentation Search API
     * We'll add a search bar and output the results of the search
     * from our API to assist users with our software.
     * @since 4.0
     */

    var help  = {}; // create namespace for our app

    help.SearchModel = Backbone.Model.extend({});

    help.SearchCollection = Backbone.Collection.extend({
      model: help.SearchModel,

      initialize: function(models, options) {
        this.url = options.url;
      },

    });

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
          .attr('placeholder', '  ' + GFPDF.searchPlaceholder)
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

        new help.DocsView({
          s: search,
        });
      }

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
          collection: this.collection.toJSON(),
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
        this.url = 'https://gravitypdf.com/wp-json/wp/v2/v4_docs/';
        this.s   = options.s;
        this.render();
      },

      render: function() {
        /* set up out template */
        this.template = _.template($(this.template).html(), null, UnderscoreSettingsOverride);

        /* show the loading spinner */
        this.showSpinner();

        /* set up view search params */
        var s   = encodeURIComponent(this.s);
        var url = this.url + '?search=' + s;

        /* initialise our collection */
        this.collection = new help.SearchCollection([], {
          url: url,
        });

        /* ping api for results */
        this.callAPI(url);

        return this;
      },
    });

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
      };

      this.is_settings = function() {
        return $('#tab_PDF').length;
      };

      this.processSettings = function() {
        var active = $('.nav-tab-wrapper a.nav-tab-active:first').data('id');

        switch (active) {
          case 'tools':
            this.tools_settings();
            break;

          case 'help':
            this.help_settings();
            break;
        }
      };

      /**
       * The help settings model method
       * This sets up and processes any of the JS that needs to be applied on the help settings tab
       * @since 4.0
       */
      this.help_settings = function() {
        /**
         * Load our settings dependancy
         */
        new help.ContainerView();
      };

      /**
       * The help tools model method
       * This sets up and processes any of the JS that needs to be applied on the tools settings tab
       * @since 4.0
       */
      this.tools_settings = function() {

        var json = JSON.parse(GFPDF.customFontData);

        /* Initialise our collection and load with our font JSON data */
        var fontCollection = new Fonts.Collection.Core(json);

        /* Create a container View and pass in our collection */
        var container = new Fonts.View.Container({
          collection: fontCollection
        });

        /* Create a Add View so user's can add new fonts and pass in our collection and container */
        new Fonts.View.Add({
          collection: fontCollection,
          container: container
        });
      };

    }

    var pdf = new GravityPDF();
    pdf.init();

  });
})(jQuery);