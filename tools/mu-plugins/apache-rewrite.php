<?php

/*
 * Tell WordPress that mod_rewrite is available so `wp rewrite structure --hard`
 * writes .htaccess from the WP-CLI container (where $is_apache is false). Only
 * fires when a non-plain permalink structure is configured so plain-permalink
 * envs don't get an empty .htaccess. Replaces the wp-cli.yml `apache_modules`
 * mapping, which can't be bind-mounted under Docker Desktop VirtioFS.
 */

add_filter(
	'got_rewrite',
	static function ( $got_rewrite ) {
		return $got_rewrite || (bool) get_option( 'permalink_structure' );
	}
);
