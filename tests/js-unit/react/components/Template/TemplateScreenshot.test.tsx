import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import TemplateScreenshot from '../../../../../src/assets/js/react/components/Template/TemplateScreenshot';

describe('Template - TemplateScreenshot.js', () => {
	test('renders <TemplateScreenshot /> component and image', () => {
		const { container } = render(<TemplateScreenshot image="test.jpg" />);
		expect(
			findByTestAttr(container, 'component-templateScreenshot')
		).toBeInTheDocument();
		expect(container.querySelector('img')).toBeInTheDocument();
	});
});
