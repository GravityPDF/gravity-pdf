import { __ } from '@wordpress/i18n';

/* eslint-disable no-var */
declare const gf_vars: Record<string, unknown> | undefined;
/* eslint-enable no-var */

/**
 * Replace some of Gravity Forms JS variables so it functions correctly with our PDF version
 *
 * @since 4.1
 */
export function setupGravityForms(): void {
	/**
	 * Check if the global gf_vars has been set and if so replace the .thisFormButton, .show, .hide objects with our
	 * customised options.
	 * @since 4.0
	 */
	if (typeof gf_vars !== 'undefined') {
		gf_vars.thisFormButton = __('this PDF if', 'gravity-pdf');
		gf_vars.show = __('Enable', 'gravity-pdf');
		gf_vars.hide = __('Disable', 'gravity-pdf');
	}
}
