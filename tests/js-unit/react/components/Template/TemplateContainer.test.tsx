import { render } from '@testing-library/react';
import TemplateContainer from '../../../../../src/assets/js/react/components/Template/TemplateContainer';

/* Modal portals into document.body, so queries must run against `document`, not the render container */
const findInDocument = (val: string) =>
	document.querySelector(`[data-test="${val}"]`);

describe('Template - TemplateContainer.js', () => {
	afterEach(() => jest.restoreAllMocks());

	test('renders <TemplateContainer /> component', () => {
		render(
			<TemplateContainer onClose={jest.fn()}>
				<div>content</div>
			</TemplateContainer>
		);
		expect(
			findInDocument('component-templateContainer')
		).toBeInTheDocument();
	});

	test('renders children', () => {
		render(
			<TemplateContainer onClose={jest.fn()}>
				<div>test-child</div>
			</TemplateContainer>
		);
		expect(document.body.textContent).toContain('test-child');
	});

	test('forwards title prop to Modal', () => {
		render(
			<TemplateContainer title="Installed PDFs" onClose={jest.fn()}>
				<div>content</div>
			</TemplateContainer>
		);
		expect(document.body.textContent).toContain('Installed PDFs');
	});

	test('renders header above children when provided', () => {
		render(
			<TemplateContainer
				header={<span>header-slot</span>}
				onClose={jest.fn()}
			>
				<div>content</div>
			</TemplateContainer>
		);
		expect(document.body.textContent).toContain('header-slot');
	});
});
