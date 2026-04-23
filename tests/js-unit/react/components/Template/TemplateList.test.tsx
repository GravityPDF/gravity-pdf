import type { ReactNode } from 'react';
import { findByTestAttr, renderWithStore } from '../../testUtilsRTL';
import TemplateList from '../../../../../src/assets/js/react/components/Template/TemplateList';
import type { TemplateState } from '../../../../../src/assets/js/react/types';

jest.mock(
	'../../../../../src/assets/js/react/components/Template/TemplateContainer',
	() =>
		function TemplateContainer({ children }: { children: ReactNode }) {
			return <div data-test="component-templateList">{children}</div>;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/components/Template/TemplateSearch',
	() =>
		function TemplateSearch() {
			return <div data-test="component-templateSearch" />;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/components/Template/TemplateListItem',
	() =>
		function TemplateListItem() {
			return <div data-test="component-templateListItem" />;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/components/Template/TemplateUploader',
	() =>
		function TemplateUploader() {
			return <div data-test="component-templateUploader" />;
		}
);

describe('Template - TemplateList.js', () => {
	const defaultProps = {
		onSelectTemplate: jest.fn(),
		onClose: jest.fn(),
	};

	const sampleTemplate = {
		id: 'zadani',
		template: 'Zadani',
		description: '',
		author: '',
		group: '',
		path: '/templates/',
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

	test('renders <TemplateList /> component', () => {
		const { container } = renderWithStore(
			<TemplateList {...defaultProps} />,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateList')
		).toBeInTheDocument();
	});

	test('renders <TemplateSearch /> component', () => {
		const { container } = renderWithStore(
			<TemplateList {...defaultProps} />,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateSearch')
		).toBeInTheDocument();
	});

	test('renders <TemplateListItem /> for each template in store', () => {
		const { container } = renderWithStore(
			<TemplateList {...defaultProps} />,
			initialState
		);
		expect(
			container.querySelectorAll(
				'[data-test="component-templateListItem"]'
			)
		).toHaveLength(1);
	});

	test('renders <TemplateUploader /> when user has admin privileges', () => {
		const { container } = renderWithStore(
			<TemplateList {...defaultProps} />,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateUploader')
		).toBeInTheDocument();
	});

	test('reads template list from Redux store', () => {
		const multiState = {
			...initialState,
			template: {
				...initialState.template,
				list: [
					sampleTemplate,
					{ ...sampleTemplate, id: 'rubix', template: 'Rubix' },
				],
			} as unknown as TemplateState,
		};
		const { container } = renderWithStore(
			<TemplateList {...defaultProps} />,
			multiState
		);
		expect(
			container.querySelectorAll(
				'[data-test="component-templateListItem"]'
			)
		).toHaveLength(2);
	});
});
