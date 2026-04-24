import { findByTestAttr, renderWithStore } from '../../testUtilsRTL';
import TemplateSingle from '../../../../../src/assets/js/react/components/Template/TemplateSingle';
import type { TemplateState } from '../../../../../src/assets/js/react/types';

jest.mock(
	'../../../../../src/assets/js/react/components/Template/TemplateHeaderNavigation',
	() =>
		function TemplateHeaderNavigation() {
			return <div data-test="component-templateHeaderNavigation" />;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/components/Template/TemplateFooterActions',
	() =>
		function TemplateFooterActions() {
			return <div data-test="component-templateFooterActions" />;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/components/Template/TemplateScreenshot',
	() =>
		function TemplateScreenshot({ wrapped }: { wrapped?: boolean }) {
			return (
				<div
					data-test={
						wrapped
							? 'component-templateScreenshots'
							: 'component-templateScreenshot'
					}
				/>
			);
		}
);

describe('Template - TemplateSingle.js', () => {
	const defaultProps = {
		onSelectTemplate: jest.fn(),
		onClose: jest.fn(),
	};

	const sampleTemplate = {
		id: 'zadani',
		template: 'Zadani',
		description: 'A description',
		author: 'Gravity PDF',
		'author uri': 'https://example.com',
		group: 'Core',
		path: '/templates/',
		screenshot: '',
		version: '1.0',
	};

	const initialState = {
		template: {
			list: [sampleTemplate],
			activeTemplate: '',
			search: '',
			updateSelectBoxText: '',
			templateProcessing: '',
			templateUploadProcessingSuccess: {},
			templateUploadProcessingError: {},
		} as unknown as TemplateState,
	};

	test('renders <TemplateSingle /> when template is found by activeTemplateId', () => {
		const { container } = renderWithStore(
			<TemplateSingle {...defaultProps} activeTemplateId="zadani" />,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateHeaderNavigation')
		).toBeInTheDocument();
		expect(
			findByTestAttr(container, 'component-templateFooterActions')
		).toBeInTheDocument();
		expect(
			container.querySelector('#gfpdf-template-detail-view')
		).toBeInTheDocument();
	});

	test('renders nothing when template is not found', () => {
		const { container } = renderWithStore(
			<TemplateSingle
				{...defaultProps}
				activeTemplateId="non-existent"
			/>,
			initialState
		);
		expect(
			container.querySelector('#gfpdf-template-detail-view')
		).not.toBeInTheDocument();
	});

	test('renders <TemplateScreenshots /> component', () => {
		const { container } = renderWithStore(
			<TemplateSingle {...defaultProps} activeTemplateId="zadani" />,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateScreenshots')
		).toBeInTheDocument();
	});

	test('renders ShowMessage for long_message when present', () => {
		const stateWithMessage = {
			...initialState,
			template: {
				...initialState.template,
				list: [
					{
						...sampleTemplate,
						long_message: 'Important notice text',
					},
				],
			} as unknown as TemplateState,
		};
		const { getByText } = renderWithStore(
			<TemplateSingle {...defaultProps} activeTemplateId="zadani" />,
			stateWithMessage
		);
		expect(getByText('Important notice text')).toBeInTheDocument();
	});

	test('renders ShowMessage for long_error when present', () => {
		const stateWithError = {
			...initialState,
			template: {
				...initialState.template,
				list: [
					{ ...sampleTemplate, long_error: 'Template error text' },
				],
			} as unknown as TemplateState,
		};
		const { getByText } = renderWithStore(
			<TemplateSingle {...defaultProps} activeTemplateId="zadani" />,
			stateWithError
		);
		expect(getByText('Template error text')).toBeInTheDocument();
	});
});
