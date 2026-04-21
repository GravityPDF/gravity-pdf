import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import TemplateContainer from '../../../../../src/assets/js/react/components/Template/TemplateContainer';

jest.mock(
	'../../../../../src/assets/js/react/components/Modal/CloseDialog',
	() =>
		function CloseDialog() {
			return <div data-test="component-CloseDialog" />;
		}
);

describe('Template - TemplateContainer.js', () => {
	afterEach(() => jest.restoreAllMocks());

	test('renders <TemplateContainer /> component', () => {
		const { container } = render(
			<TemplateContainer>
				<div>content</div>
			</TemplateContainer>
		);
		expect(
			findByTestAttr(container, 'component-templateContainer')
		).toBeInTheDocument();
	});

	test('renders children', () => {
		const { getByText } = render(
			<TemplateContainer>
				<div>test-child</div>
			</TemplateContainer>
		);
		expect(getByText('test-child')).toBeInTheDocument();
	});

	test('renders <CloseDialog /> component', () => {
		const { container } = render(
			<TemplateContainer>
				<div>content</div>
			</TemplateContainer>
		);
		expect(
			findByTestAttr(container, 'component-CloseDialog')
		).toBeInTheDocument();
	});

	test('attaches focus event listener to document on mount', () => {
		const addEventListenerSpy = jest.spyOn(document, 'addEventListener');
		render(
			<TemplateContainer>
				<div>content</div>
			</TemplateContainer>
		);
		expect(addEventListenerSpy).toHaveBeenCalledWith(
			'focus',
			expect.any(Function),
			true
		);
	});

	test('removes focus event listener from document on unmount', () => {
		const removeEventListenerSpy = jest.spyOn(
			document,
			'removeEventListener'
		);
		const { unmount } = render(
			<TemplateContainer>
				<div>content</div>
			</TemplateContainer>
		);
		unmount();
		expect(removeEventListenerSpy).toHaveBeenCalledWith(
			'focus',
			expect.any(Function),
			true
		);
	});
});
