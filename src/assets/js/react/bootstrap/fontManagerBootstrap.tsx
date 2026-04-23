/* Dependencies */
import {
	useState,
	useEffect,
	createRoot,
	createPortal,
} from '@wordpress/element';
/* Components */
import AdvancedButton from '../components/FontManager/AdvancedButton';
import FontManager from '../components/FontManager/FontManager';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface AppProps {
	buttonContainer: Element;
}

const FontManagerApp = ({ buttonContainer }: AppProps) => {
	const [isOpen, setIsOpen] = useState(false);
	const [activeFontId, setActiveFontId] = useState('');

	/* Auto-open when navigated here from WP backend via hash URL */
	useEffect(() => {
		if (window.location.hash !== '#/fontmanager') {
			return;
		}
		setIsOpen(true);
	}, []);

	const handleOpen = () => {
		setIsOpen(true);
	};

	const handleClose = () => {
		setIsOpen(false);
		setActiveFontId('');
	};

	return (
		<>
			{createPortal(
				<AdvancedButton onOpen={handleOpen} />,
				buttonContainer
			)}
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
 * Mount the font manager button and overlay as a single React root.
 *
 * @param defaultFontField
 * @param buttonStyle
 * @since 6.0
 */
export function fontManagerBootstrap(
	defaultFontField: Element,
	buttonStyle?: string
): void {
	/* Prevent button reset styling on tools tab */
	const preventButtonReset = !buttonStyle ? '' : buttonStyle;

	createAdvancedButtonWrapper(defaultFontField, preventButtonReset);

	const buttonContainer = document.querySelector(
		'#gpdf-advance-font-manager-selector' + preventButtonReset
	)!;
	const overlayContainer = document.querySelector('#font-manager-overlay')!;

	createRoot(overlayContainer).render(
		<FontManagerApp buttonContainer={buttonContainer} />
	);
}

/**
 * Create html element wrapper for our font manager advanced button
 *
 * @param defaultFontField
 * @param preventButtonReset
 * @since 6.0
 */
export function createAdvancedButtonWrapper(
	defaultFontField: Element,
	preventButtonReset: string
): void {
	const fontWrapper = document.createElement('span');
	fontWrapper.setAttribute(
		'id',
		'gpdf-advance-font-manager-selector' + preventButtonReset
	);

	const popupWrapper = document.createElement('div');
	popupWrapper.setAttribute('id', 'font-manager-overlay');
	popupWrapper.setAttribute('class', 'theme-overlay');

	if (defaultFontField.nodeName === 'SELECT') {
		const wrapper = document.createElement('div');
		wrapper.setAttribute(
			'id',
			'gfpdf-settings-field-wrapper-font-container'
		);
		wrapper.innerHTML = (defaultFontField as HTMLElement).outerHTML;
		wrapper.appendChild(fontWrapper);
		wrapper.appendChild(popupWrapper);
		(defaultFontField as HTMLElement).outerHTML = wrapper.outerHTML;
	} else {
		defaultFontField.appendChild(fontWrapper);
		defaultFontField.appendChild(popupWrapper);
	}
}
