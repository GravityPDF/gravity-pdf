/* Dependencies */
import { createContext } from 'react';

/**
 * Shares the file picker and upload status from <TemplateUploader />, which wraps the entire Template
 * Manager, with the "Add New Template" tile rendered deep inside the template list
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.17
 */
export const TemplateUploaderContext = createContext({});
