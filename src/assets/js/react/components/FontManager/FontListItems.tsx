/* Dependencies */
import { __ } from '@wordpress/i18n';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components';
/* Components */
import Spinner from '../Spinner';
/* Hooks */
import { useFontListItems } from '../../utilities/FontManager/useFontListItems';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	activeFontId: string;
	onSelectFont: (id: string) => void;
	hasDetailOpen: boolean;
}

const Icon = ({ font }: { font: string }) => (
	<div>
		<span className={'dashicons dashicons-' + (font ? 'yes' : 'no-alt')} />
	</div>
);

const FontListItems = ({
	activeFontId,
	onSelectFont,
	hasDetailOpen,
}: Props) => {
	const {
		loading,
		fontList,
		searchResult,
		selectedFont,
		disableSelectFontName,
		deleteId,
		pendingDeleteId,
		handleFontClick,
		handleFontClickKeypress,
		requestDeleteFont,
		requestDeleteFontKeypress,
		confirmDeleteFont,
		cancelDeleteFont,
		handleSelectFont,
		handleSelectFontKeypress,
	} = useFontListItems({ activeFontId, onSelectFont, hasDetailOpen });

	const list = !searchResult ? fontList : searchResult;
	const tabIndex = !hasDetailOpen ? 0 : -1;

	return (
		<div
			data-test="component-FontListItems"
			className="font-list-items"
			role="listbox"
			aria-label={__('Installed Fonts', 'gravity-pdf')}
			aria-live="polite"
		>
			{list &&
				list.map((font) => (
					<div
						key={font.id}
						className={
							'font-list-item' +
							(font.id === activeFontId ? ' active' : '')
						}
						onClick={() => handleFontClick(font.id)}
						onKeyDown={(e) => handleFontClickKeypress(e, font.id)}
						tabIndex={tabIndex}
						role="option"
						aria-selected={font.id === activeFontId}
					>
						<span className="font-name">
							{!disableSelectFontName && (
								<input
									type="radio"
									className="select-font-name"
									name={'select-font-name-' + font.id}
									value={font.id}
									onChange={(e) => handleSelectFont(e)}
									onClick={(e) => e.stopPropagation()}
									onKeyDown={(e) =>
										handleSelectFontKeypress(e)
									}
									checked={font.id === selectedFont}
									aria-label={
										__('Select font', 'gravity-pdf') +
										': ' +
										font.font_name
									}
									tabIndex={tabIndex}
								/>
							)}
							{font.font_name}
						</span>

						<Icon font={font.regular} />
						<Icon font={font.italics} />
						<Icon font={font.bold} />
						<Icon font={font.bolditalics} />

						{loading && deleteId === font.id ? (
							<Spinner style="delete-font" />
						) : (
							<span
								role="button"
								className="dashicons dashicons-trash"
								onClick={(e) => requestDeleteFont(e, font.id)}
								onKeyDown={(e) =>
									requestDeleteFontKeypress(e, font.id)
								}
								tabIndex={tabIndex}
							/>
						)}
					</div>
				))}

			<ConfirmDialog
				isOpen={pendingDeleteId !== null}
				onConfirm={confirmDeleteFont}
				onCancel={cancelDeleteFont}
			>
				{__(
					'Are you sure you want to delete this font?',
					'gravity-pdf'
				)}
			</ConfirmDialog>
		</div>
	);
};

export default FontListItems;
