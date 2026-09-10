/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __, sprintf } from '@wordpress/i18n';
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { plus } from '@wordpress/icons';

/**
 * "+ Add new font"
 *
 * The browse items are built from `GET /fonts/sources` alone, so a source registered through
 * `gfpdf_font_sources` appears here with nothing in this file changing — which is the whole reason the menu
 * carries no fixed "Browse language packs" item.
 *
 * The offline zip import belongs here too and is not built: §4.7 registers no route for it, so there is
 * nothing for the item to do that would not be a promise.
 *
 * @param {Object}        props
 * @param {Array<Object>} props.sources  The registered source records
 * @param {Function}      props.onUpload
 * @param {Function}      props.onBrowse Called with a source id
 *
 * @return {JSX.Element} The menu
 *
 * @since 7.0
 */
export default function AddFontMenu({ sources, onUpload, onBrowse }) {
	return (
		<DropdownMenu
			icon={plus}
			label={__('Add new font', 'gravity-pdf')}
			text={__('Add new font', 'gravity-pdf')}
			className="gfpdf-fm-add-menu"
			toggleProps={{
				variant: 'secondary',
				__next40pxDefaultSize: true,
			}}
		>
			{({ onClose }) => (
				<>
					<MenuGroup>
						<MenuItem
							onClick={() => {
								onClose();
								onUpload();
							}}
						>
							{__('Upload font files', 'gravity-pdf')}
						</MenuItem>
					</MenuGroup>
					<MenuGroup>
						{sources.map((source) => (
							<MenuItem
								key={source.id}
								onClick={() => {
									onClose();
									onBrowse(source.id);
								}}
							>
								{sprintf(
									/* translators: %s: the name of a font source, e.g. "Google Fonts" */
									__('Browse %s', 'gravity-pdf'),
									source.label
								)}
							</MenuItem>
						))}
					</MenuGroup>
				</>
			)}
		</DropdownMenu>
	);
}
