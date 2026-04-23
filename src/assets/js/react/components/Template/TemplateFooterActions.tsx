/* Dependencies */
/* Components */
import TemplateActivateButton from './TemplateActivateButton';
import TemplateDeleteButton from './TemplateDeleteButton';
/* Types */
import { TemplateItem } from '../../types';

/**
 * Renders the template footer actions that get displayed on the
 * template detail view.
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface Props {
	template: TemplateItem;
	onSelectTemplate: (id: string) => void;
	onClose: () => void;
	isActiveTemplate?: boolean;
	ajaxUrl?: string;
	ajaxNonce?: string;
	activateText?: string;
	pdfWorkingDirPath?: string;
	templateDeleteText?: string;
	templateConfirmDeleteText?: string;
	templateDeleteErrorText?: string;
}

const TemplateFooterActions = ({
	template,
	onSelectTemplate,
	onClose,
	isActiveTemplate,
	ajaxUrl,
	ajaxNonce,
	activateText,
	pdfWorkingDirPath,
	templateDeleteText,
	templateConfirmDeleteText,
	templateDeleteErrorText,
}: Props) => {
	const notCoreTemplate = (t: TemplateItem) =>
		String(t.path).indexOf(pdfWorkingDirPath ?? '') !== -1;

	const isCompatible = template.compatible;

	return (
		<div
			data-test="component-templateFooterActions"
			className="theme-actions"
		>
			{!isActiveTemplate && isCompatible ? (
				<TemplateActivateButton
					onClose={onClose}
					template={template}
					buttonText={activateText}
				/>
			) : null}

			{!isActiveTemplate && notCoreTemplate(template) ? (
				<TemplateDeleteButton
					onSelectTemplate={onSelectTemplate}
					template={template}
					ajaxUrl={ajaxUrl}
					ajaxNonce={ajaxNonce}
					buttonText={templateDeleteText}
					templateConfirmDeleteText={templateConfirmDeleteText}
					templateDeleteErrorText={templateDeleteErrorText}
				/>
			) : null}
		</div>
	);
};

export default TemplateFooterActions;
