import $ from 'jquery';
import { setupRequiredFields } from '../pdf/setupRequiredFields';
import { setupClearPdfCache } from './setupClearPdfCache';

/**
 * The general settings model method
 * This sets up and processes any of the JS that needs to be applied on the general settings tab
 *
 * @since 4.0
 */
export function generalSettings() {
	setupRequiredFields($('#pdfextended-settings > form'));
	setupClearPdfCache();
}
