/**
 * Gravity PDF Multisite v3 to v4 Migration Script
 * Dependancies: jQuery
 * @since 4.0
 */

(function($) {

	function ajax_migration( blog_id, nonce, $container ) {
		$container.append( '<p>Migrating site #' + blog_id + ' <img alt="' + GFPDF.spinnerAlt + '" src="' + GFPDF.spinnerUrl + '" class="gfpdf-spinner" style="width:20px;vertical-align: middle;padding-left:5px" /></p>' );

		$.ajax({
			type : "post",
			dataType : "json",
			url : GFPDF.ajaxurl,
			data : {
	  			'action': 'multisite_v3_migration',
	  			'nonce': nonce,
	  			'blog_id': blog_id,
			},
			success: function( json ) {
				$container.find( '.gfpdf-spinner' ).remove();

				if( json.results === "complete" ) {
					$container.append( '<p>Site #' + blog_id + ' migration complete.</p>' );
				} else if( json.results.error ) {
					$container.append( '<p><strong>Migration Error: ' + json.results.error + '</strong></p>' );
				} else {
					$container.append( '<p><strong>Site #' + blog_id + ' migration errors.</strong></p>' );
				}

				if( gfpdf_migration_multisite_ids.length > 0 ) {
					ajax_migration( gfpdf_migration_multisite_ids.shift(), nonce, $container );
				} else {
					$( '#gfpdf-multisite-migration-complete' ).show();
				}
			},
			error: function() {
				$container.find( '.gfpdf-spinner' ).remove();
				$container.append( '<p><strong>Site #' + blog_id + ' migration errors.</strong></p>' );

				if( gfpdf_migration_multisite_ids.length > 0 ) {
					ajax_migration( gfpdf_migration_multisite_ids.shift(), nonce, $container );
				}
			},
		});
	}

	/**
	 * Fires on the Document Ready Event (the same as $(document).ready(function() { ... });)
	 * @since 4.0
	 */
	$(function() {
		var $container = $( '#gfpdf-multisite-migration-copy' );
		var nonce = $container.data( 'nonce' );

		if( gfpdf_migration_multisite_ids.length > 0 ) {
			ajax_migration( gfpdf_migration_multisite_ids.shift(), nonce, $container );
		}
	});

})( jQuery );
