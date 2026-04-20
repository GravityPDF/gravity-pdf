/* Dependencies */
/* Components */
import TemplateListItem from './TemplateListItem';
import TemplateSearch from './TemplateSearch';
import TemplateUploader from './TemplateUploader';
/* Store */
import { useSelect } from '@wordpress/data';
import { templateStore } from '../../store/templateStore';

/**
 * The master component for rendering the all PDF templates as a list.
 * Rendered as the modal body when no template is selected.
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	onSelectTemplate: (id: string) => void;
	onClose: () => void;
}

const TemplateList = ({ onSelectTemplate, onClose }: Props) => {
	const templates = useSelect(
		(select) => select(templateStore).getFilteredTemplates(),
		[]
	);

	const hasUserPrivs =
		GFPDF.userCapabilities.administrator ||
		GFPDF.userCapabilities.gravityforms_edit_settings ||
		false;

	return (
		<>
			<TemplateSearch />
			<div
				id="gfpdf-template-container"
				className="theme-about wp-clearfix theme-browser rendered"
			>
				<div role="listbox">
					{templates?.map((value, index) => (
						<TemplateListItem
							key={index}
							onSelectTemplate={onSelectTemplate}
							onClose={onClose}
							template={value}
						/>
					))}

					{hasUserPrivs && <TemplateUploader />}
				</div>
			</div>
		</>
	);
};

export default TemplateList;
