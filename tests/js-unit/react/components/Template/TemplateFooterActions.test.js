import React from 'react';
import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import TemplateFooterActions from '../../../../../src/assets/js/react/components/Template/TemplateFooterActions';

jest.mock(
	'../../../../../src/assets/js/react/components/Template/TemplateActivateButton',
	() =>
		function TemplateActivateButton() {
			return <div data-test="component-templateActivateButton" />;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/components/Template/TemplateDeleteButton',
	() =>
		function TemplateDeleteButton() {
			return <div data-test="component-templateDeleteButton" />;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/utilities/withRouterHooks',
	() => (Component) => Component
);

describe('Template - TemplateFooterActions.js', () => {
	test('renders <TemplateFooterActions /> component', () => {
		const template = { compatible: false, path: '' };
		const { container } = render(
			<TemplateFooterActions template={template} />
		);
		expect(
			findByTestAttr(container, 'component-templateFooterActions')
		).toBeInTheDocument();
	});

	test('renders <TemplateActivateButton /> when isActiveTemplate is false and template is compatible', () => {
		const template = { compatible: true, path: '' };
		const { container } = render(
			<TemplateFooterActions
				template={template}
				isActiveTemplate={false}
			/>
		);
		expect(
			findByTestAttr(container, 'component-templateActivateButton')
		).toBeInTheDocument();
	});

	test('does not render <TemplateActivateButton /> when isActiveTemplate is true', () => {
		const template = { compatible: true, path: '' };
		const { container } = render(
			<TemplateFooterActions
				template={template}
				isActiveTemplate={true}
			/>
		);
		expect(
			findByTestAttr(container, 'component-templateActivateButton')
		).not.toBeInTheDocument();
	});

	test('renders <TemplateDeleteButton /> when isActiveTemplate is false and path is not core', () => {
		const template = { compatible: true, path: '/uploads/pdf-extended/' };
		const { container } = render(
			<TemplateFooterActions
				template={template}
				isActiveTemplate={false}
				pdfWorkingDirPath="/uploads/"
			/>
		);
		expect(
			findByTestAttr(container, 'component-templateDeleteButton')
		).toBeInTheDocument();
	});

	test('does not render <TemplateDeleteButton /> when path is core template', () => {
		const template = { compatible: true, path: '/core/templates/' };
		const { container } = render(
			<TemplateFooterActions
				template={template}
				isActiveTemplate={false}
				pdfWorkingDirPath="/uploads/"
			/>
		);
		expect(
			findByTestAttr(container, 'component-templateDeleteButton')
		).not.toBeInTheDocument();
	});
});
