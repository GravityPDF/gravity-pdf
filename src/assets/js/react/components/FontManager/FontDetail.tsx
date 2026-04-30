/* Dependencies */
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
/* Store */
import {
	FONT_MANAGER_STORE_NAME,
	fontManagerStore,
} from '../../store/fontManagerStore';
/* Hooks */
import { useEditingFont } from '../../utilities/FontManager/useEditingFont';
/* Components */
import EmptyDetail from './EmptyDetail';
import FontDetailHeader from './FontDetailHeader';
import FontNameField from './FontNameField';
import VariantsTable from './VariantsTable';
import FontPreview from './FontPreview';
import TemplateUsage from './TemplateUsage';
import FontDetailFooter from './FontDetailFooter';
/* Types */
import type { VariantKey } from './VariantRow';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

interface Props {
	onSave: () => void;
	onCancel: () => void;
	onRequestDelete: () => void;
	onSetActive: () => void;
	onMobileBack: () => void;
	onRejected: (message: string) => void;
	onAddFont: () => void;
}

const FontDetail = ({
	onSave,
	onCancel,
	onRequestDelete,
	onSetActive,
	onMobileBack,
	onRejected,
	onAddFont,
}: Props) => {
	const { setEditingState } = useDispatch(FONT_MANAGER_STORE_NAME);
	const { editingFont, savedFont, dirty, nameError, canSave } =
		useEditingFont();
	const selectedFont = useSelect(
		(select) => select(fontManagerStore).getSelectedFont(),
		[]
	);
	const saving = useSelect(
		(select) => select(fontManagerStore).getAddFontLoading(),
		[]
	);

	if (!editingFont) {
		return (
			<section
				data-test="component-FontDetail"
				className="gfpdf-fm-detail"
			>
				<EmptyDetail onAddFont={onAddFont} />
			</section>
		);
	}

	const handleNameChange = (value: string) => {
		setEditingState({ ...editingFont, label: value });
	};

	const handleUpload = (key: VariantKey, file: File) => {
		setEditingState({
			...editingFont,
			fontStyles: { ...editingFont.fontStyles, [key]: file },
		});
	};

	const handleDeleteVariant = (key: VariantKey) => {
		setEditingState({
			...editingFont,
			fontStyles: { ...editingFont.fontStyles, [key]: '' },
		});
	};

	const isDraft = editingFont.isDraft;
	const mode: 'add' | 'edit' = isDraft ? 'add' : 'edit';
	const isActive = !isDraft && selectedFont === editingFont.id;
	const hasSavedRegular = !!savedFont?.regular;
	const canSetActive = !isDraft && hasSavedRegular && !dirty;
	const canShowPreviewAndTemplate = !isDraft && hasSavedRegular;
	const canDelete = !isDraft;
	const canCancel = isDraft || dirty;

	return (
		<section data-test="component-FontDetail" className="gfpdf-fm-detail">
			<div className="gfpdf-fm-detail__inner">
				<FontDetailHeader
					mode={mode}
					isActive={isActive}
					canSetActive={canSetActive}
					dirty={dirty}
					onSetActive={onSetActive}
					onMobileBack={onMobileBack}
				/>

				<div className="gfpdf-fm-section">
					<FontNameField
						value={editingFont.label}
						error={nameError}
						onChange={handleNameChange}
					/>
				</div>

				<div className="gfpdf-fm-section">
					<h3 className="gfpdf-fm-section__title">
						{__('Font files', 'gravity-pdf')}
					</h3>
					<p className="gfpdf-fm-section__desc">
						{__(
							'Add a .ttf file for each style you want to use in your PDFs. Regular is required; Italic, Bold, and Bold Italic are optional and only used when your templates need them.',
							'gravity-pdf'
						)}
					</p>
					<VariantsTable
						fontStyles={editingFont.fontStyles}
						onUpload={handleUpload}
						onDelete={handleDeleteVariant}
						onRejected={onRejected}
					/>
				</div>

				{canShowPreviewAndTemplate && savedFont && (
					<div className="gfpdf-fm-section">
						<h3 className="gfpdf-fm-section__title">
							{__('Preview', 'gravity-pdf')}
						</h3>
						<FontPreview
							familyId={editingFont.id}
							fontStyles={editingFont.fontStyles}
						/>
					</div>
				)}

				{canShowPreviewAndTemplate && savedFont && (
					<div className="gfpdf-fm-section">
						<TemplateUsage
							id={savedFont.id}
							font_name={savedFont.font_name}
						/>
					</div>
				)}
			</div>

			<FontDetailFooter
				isDraft={isDraft}
				canDelete={canDelete}
				canSave={canSave}
				canCancel={canCancel}
				saving={saving}
				onSave={onSave}
				onCancel={onCancel}
				onRequestDelete={onRequestDelete}
			/>
		</section>
	);
};

export default FontDetail;
