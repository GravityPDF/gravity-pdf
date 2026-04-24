/* Dependencies */
import { useState, createRoot } from '@wordpress/element';
/* Components */
import AdvancedButton from '../components/FontManager/AdvancedButton';
import FontManager from '../components/FontManager/FontManager';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

const FontManagerApp = () => {
	/* Auto-open when navigated here from WP backend via hash URL */
	const [isOpen, setIsOpen] = useState(() =>
		window.location.hash.startsWith('#/fontmanager')
	);
	const [activeFontId, setActiveFontId] = useState('');

	const handleClose = () => {
		setIsOpen(false);
		setActiveFontId('');
	};

	return (
		<>
			<AdvancedButton onOpen={() => setIsOpen(true)} />
			{isOpen && (
				<FontManager
					activeFontId={activeFontId}
					onSelectFont={setActiveFontId}
					onClose={handleClose}
				/>
			)}
		</>
	);
};

/**
 * Mount the font manager on a single React root adjacent to the field.
 *
 * @param defaultFontField
 * @since 6.0
 */
export function fontManagerBootstrap(defaultFontField: Element): void {
	const mountPoint = createAdvancedButtonWrapper(defaultFontField);
	createRoot(mountPoint).render(<FontManagerApp />);
}

/**
 * Wrap a <select> field in a flex container (for inline select + button
 * layout) and append a <span> mount point for the React root. For non-select
 * anchors (e.g. the Tools tab wrapper), append the mount point directly.
 *
 * @param defaultFontField
 * @since 6.0
 */
export function createAdvancedButtonWrapper(
	defaultFontField: Element
): HTMLSpanElement {
	const mountPoint = document.createElement('span');
	mountPoint.id = 'gpdf-advance-font-manager-selector';

	if (defaultFontField.nodeName === 'SELECT') {
		const wrapper = document.createElement('div');
		wrapper.id = 'gfpdf-settings-field-wrapper-font-container';
		defaultFontField.parentNode!.insertBefore(wrapper, defaultFontField);
		wrapper.appendChild(defaultFontField);
		wrapper.appendChild(mountPoint);
	} else {
		defaultFontField.appendChild(mountPoint);
	}

	return mountPoint;
}
