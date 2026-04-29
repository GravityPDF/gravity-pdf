import { render } from '@testing-library/react';
import Spinner from '../../../../src/assets/js/react/components/Spinner';

describe('Components - Spinner.js', () => {
	test('renders <Spinner /> component', () => {
		const { container } = render(<Spinner />);

		expect(container.querySelector('svg')).toBeInTheDocument();
		expect(container.querySelector('svg')).toHaveClass('gfpdf-spinner');
	});
});
