import React from 'react';
import { shallow } from 'enzyme';
import { findByTestAttr } from '../../testUtils';
import FontManagerHeader from '../../../../../src/assets/js/react/components/FontManager/FontManagerHeader';

describe('FontManager - FontManagerHeader.js', () => {
	const wrapper = shallow(<FontManagerHeader id="rubix" />);

	describe('RENDERS COMPONENT', () => {
		test('render <FontManagerHeader /> component', () => {
			const component = findByTestAttr(
				wrapper,
				'component-FontManagerHeader'
			);

			expect(component.length).toBe(1);
		});

		test('render <CloseDialog /> component', () => {
			expect(
				wrapper
					.find({
						id: 'rubix',
					})
					.exists()
			).toBe(true);
		});
	});
});
