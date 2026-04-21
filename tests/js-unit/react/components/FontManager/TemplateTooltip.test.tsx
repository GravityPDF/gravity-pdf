import { render, fireEvent } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import TemplateTooltip from '../../../../../src/assets/js/react/components/FontManager/TemplateTooltip';

describe('FontManager - TemplateTooltip.js', () => {
	describe('RENDERS COMPONENT', () => {
		test('render <TemplateTooltip /> component', () => {
			const { container } = render(<TemplateTooltip id="gotham" />);
			expect(
				findByTestAttr(container, 'component-TemplateTooltip')
			).toBeInTheDocument();
		});

		test('renders arrow-right icon when tooltip is closed, arrow-down and textarea when open', () => {
			const { container } = render(<TemplateTooltip />);

			expect(
				container.querySelector('.dashicons-arrow-right-alt2')
			).toBeInTheDocument();
			expect(
				container.querySelector('.dashicons-arrow-down-alt2')
			).not.toBeInTheDocument();
			expect(container.querySelector('textarea')).not.toBeInTheDocument();

			fireEvent.click(container.querySelector('button')!);

			expect(
				container.querySelector('.dashicons-arrow-right-alt2')
			).not.toBeInTheDocument();
			expect(
				container.querySelector('.dashicons-arrow-down-alt2')
			).toBeInTheDocument();
			expect(container.querySelector('button')!.textContent).toBe(
				'View template usage'
			);
			expect(container.querySelector('textarea')).toBeInTheDocument();
		});
	});

	describe('RUN COMPONENT METHODS', () => {
		test('handleContentHighlight() - focuses, selects, and copies textarea content', () => {
			const { container } = render(<TemplateTooltip id="gotham" />);

			fireEvent.click(container.querySelector('button')!);

			const textarea = container.querySelector('textarea')!;
			const focusMock = jest.spyOn(textarea, 'focus');
			const selectMock = jest.spyOn(textarea, 'select');
			document.execCommand = jest.fn();

			fireEvent.click(textarea);

			expect(focusMock).toHaveBeenCalledTimes(1);
			expect(selectMock).toHaveBeenCalledTimes(1);
			expect(document.execCommand).toHaveBeenCalledWith('copy');
		});
	});
});
