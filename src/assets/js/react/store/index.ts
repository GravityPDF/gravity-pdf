/* Dependencies */
import { register } from '@wordpress/data';
/* Stores */
import { templateStore } from './templateStore';
import { coreFontsStore } from './coreFontsStore';
import { fontManagerStore } from './fontManagerStore';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

register(templateStore);
register(coreFontsStore);
register(fontManagerStore);
