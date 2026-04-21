import React from 'react';
import { findByTestAttr, renderWithStore } from '../../testUtilsRTL';
import TemplateSingle from '../../../../../src/assets/js/react/components/Template/TemplateSingle';
import type { TemplateState } from '../../../../../src/assets/js/react/types';

jest.mock(
	'../../../../../src/assets/js/react/components/Template/TemplateContainer',
	() =>
		function TemplateContainer({
			children,
		}: {
			children: React.ReactNode;
		}) {
			return (
				<div data-test="component-templateContainer">{children}</div>
			);
		}
);

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
	'../../../../../src/assets/js/react/components/Template/TemplateScreenshots',
	() =>
		function TemplateScreenshots() {
			return <div data-test="component-templateScreenshots" />;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/utilities/withRouterHooks',
	() => (Component: React.ComponentType) => Component
);

describe('Template - TemplateSingle.js', () => {
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

	test('renders <TemplateSingle /> when template is found by params id', () => {
		const { container } = renderWithStore(
			<TemplateSingle params={{ id: 'zadani' }} />,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateContainer')
		).toBeInTheDocument();
	});

	test('renders nothing when template is not found', () => {
		const { container } = renderWithStore(
			<TemplateSingle params={{ id: 'non-existent' }} />,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateContainer')
		).not.toBeInTheDocument();
	});

	test('renders <TemplateScreenshots /> component', () => {
		const { container } = renderWithStore(
			<TemplateSingle params={{ id: 'zadani' }} />,
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
			<TemplateSingle params={{ id: 'zadani' }} />,
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
			<TemplateSingle params={{ id: 'zadani' }} />,
			stateWithError
		);
		expect(getByText('Template error text')).toBeInTheDocument();
	});
});
