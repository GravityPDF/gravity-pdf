import React from 'react';
import { render } from '@testing-library/react';
import Empty from '../../../../src/assets/js/react/components/Empty';

describe('Components - Empty.js', () => {
	test('renders <Empty /> component', () => {
		const { container } = render(<Empty />);

		expect(container.firstChild).toBeNull();
	});
});
