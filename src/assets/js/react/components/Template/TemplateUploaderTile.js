/* Dependencies */
import React, { useContext } from 'react';
/* Components */
import { TemplateUploaderContext } from './TemplateUploaderContext';

/**
 * The "Add New Template" tile at the end of the template list. Opens the file picker owned by
 * <TemplateUploader />, which doubles as a drop target covering the whole Template Manager
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.17
 */

/**
 * React Component
 *
 * @since 6.17
 */
const TemplateUploaderTile = () => {
	const { open, ajax, addTemplateText, templateInstallInstructions } =
		useContext(TemplateUploaderContext);

	const handleClick = (e) => {
		e.preventDefault();
		open();
	};

	return (
		<div
			data-test="component-templateUploaderTile"
			className="theme add-new-theme gfpdf-dropzone"
		>
			<a
				href="#/template"
				className={ajax ? 'doing-ajax' : ''}
				onClick={handleClick}
				aria-labelledby="gfpdf-template-install-instructions"
			>
				<div className="theme-screenshot">
					<span />
				</div>

				<h2 className="theme-name">{addTemplateText}</h2>
			</a>

			<div
				className="gfpdf-template-install-instructions"
				id="gfpdf-template-install-instructions"
			>
				{templateInstallInstructions}
			</div>
		</div>
	);
};

export default TemplateUploaderTile;
