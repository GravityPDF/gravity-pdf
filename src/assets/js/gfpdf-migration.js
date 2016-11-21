/**
 * Gravity PDF Multisite v3 to v4 Migration Script
 * Dependancies: jQuery
 * @since 4.0
 */

(function($) {

	function ajax_migration( blog_id, nonce, $container ) {
		$container.append( '<p>' + GFPDF.migratingSite.replace( /%s/g, blog_id ) + ' <img alt="' + GFPDF.spinnerAlt + '" src="' + GFPDF.spinnerUrl + '" class="gfpdf-spinner" style="width:20px;vertical-align: middle;padding-left:5px" /></p>' );

		$.ajax({
			type : "post",
			dataType : "json",
			url : GFPDF.ajaxUrl,
			data : {
	  			'action': 'multisite_v3_migration',
	  			'nonce': nonce,
	  			'blog_id': blog_id,
			},
			success: function( json ) {
				/* Remove the spinner */
				$container.find( '.gfpdf-spinner' ).remove();

				/* Display appropriate response. Either complete, specific error or generic error */
				if( json.results === "complete" ) {
					$container.append( '<p>' + GFPDF.siteMigrationComplete.replace( /%s/g, blog_id ) + '</p>' );
				} else if( json.results.error ) {
					$container.append( '<p><strong>' + GFPDF.migrationError + ': ' + json.results.error + '</strong></p>' );
				} else {
					$container.append( '<p><strong>' + GFPDF.siteMigrationErrors.replace( /%s/g, blog_id ) + '</strong></p>' );
				}

				/* Run the next migration, if it exists, or show as complete */
				if( gfpdf_migration_multisite_ids.length > 0 ) {
					ajax_migration( gfpdf_migration_multisite_ids.shift(), nonce, $container );
				} else {
					$( '#gfpdf-multisite-migration-complete' ).show();
				}
			},
			error: function() {
				/* Remove the spinner */
				$container.find( '.gfpdf-spinner' ).remove();

				/* Add a generic error */
				$container.append( '<p><strong>' + GFPDF.siteMigrationErrors.replace( /%s/g, blog_id ) + '</strong></p>' );

				/* Run the next migration, if it exists, or show as complete */
				if( gfpdf_migration_multisite_ids.length > 0 ) {
					ajax_migration( gfpdf_migration_multisite_ids.shift(), nonce, $container );
				} else {
					$( '#gfpdf-multisite-migration-complete' ).show();
				}
			},
		});
	}

	/**
	 * Fires on the Document Ready Event (the same as $(document).ready(function() { ... });)
	 * @since 4.0
	 */
	$(function() {

		/* Grab the container and nonce */
		var $container = $( '#gfpdf-multisite-migration-copy' );
		var nonce = $container.data( 'nonce' );

		/* Begin the migration */
		if( gfpdf_migration_multisite_ids.length > 0 ) {
			ajax_migration( gfpdf_migration_multisite_ids.shift(), nonce, $container );
		}
	});

})( jQuery );
