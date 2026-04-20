import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import TemplateScreenshot from '../../../../../src/assets/js/react/components/Template/TemplateScreenshot';

describe('Template - TemplateScreenshot.js', () => {
	test('renders list variant with image', () => {
		const { container } = render(<TemplateScreenshot image="test.jpg" />);
		expect(
			findByTestAttr(container, 'component-templateScreenshot')
		).toBeInTheDocument();
		expect(container.querySelector('img')).toBeInTheDocument();
	});

	test('renders detail variant (wrapped) with theme-screenshots outer wrapper', () => {
		const { container } = render(
			<TemplateScreenshot image="test.jpg" wrapped />
		);
		expect(
			findByTestAttr(container, 'component-templateScreenshots')
		).toBeInTheDocument();
		expect(
			container.querySelector('.theme-screenshots .screenshot img')
		).toBeInTheDocument();
	});

	test('renders blank class when no image provided', () => {
		const { container } = render(<TemplateScreenshot />);
		expect(
			container.querySelector('.theme-screenshot.blank')
		).toBeInTheDocument();
	});
});
