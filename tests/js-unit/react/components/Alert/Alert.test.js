import React from 'react';
import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import Alert from '../../../../../src/assets/js/react/components/Alert/Alert';

describe('Alert - Alert.js', () => {
	test('render <Alert /> component', () => {
		const { container } = render(<Alert msg="text" />);
		const component = findByTestAttr(container, 'component-Alert');

		expect(component).toBeInTheDocument();
	});
});
