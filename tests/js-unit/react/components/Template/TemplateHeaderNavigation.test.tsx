import { render, fireEvent } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import TemplateHeaderNavigation from '../../../../../src/assets/js/react/components/Template/TemplateHeaderNavigation';
import type { TemplateItem } from '../../../../../src/assets/js/react/types';

describe('Template - TemplateHeaderNavigation.js', () => {
	const onSelectTemplate = jest.fn();
	const templates = [
		{ id: 'blank-slate', template: 'Blank Slate' },
		{ id: 'focus-gravity', template: 'Focus Gravity' },
		{ id: 'rubix', template: 'Rubix' },
		{ id: 'zadani', template: 'Zadani' },
	] as TemplateItem[];

	beforeEach(() => jest.clearAllMocks());
	afterEach(() => jest.restoreAllMocks());

	test('renders <TemplateHeaderNavigation /> component', () => {
		const { container } = render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				onSelectTemplate={onSelectTemplate}
			/>
		);
		expect(
			findByTestAttr(container, 'component-templateHeaderNavigation')
		).toBeInTheDocument();
	});

	test('renders previous and next buttons', () => {
		const { container } = render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				onSelectTemplate={onSelectTemplate}
			/>
		);
		expect(
			findByTestAttr(container, 'component-showPreviousTemplateButton')
		).toBeInTheDocument();
		expect(
			findByTestAttr(container, 'component-showNextTemplateButton')
		).toBeInTheDocument();
	});

	test('renders screen reader text for previous and next buttons', () => {
		const { container } = render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				onSelectTemplate={onSelectTemplate}
				showPreviousTemplateText="Show previous"
				showNextTemplateText="Show next template"
			/>
		);
		expect(
			findByTestAttr(container, 'component-showPreviousTemplateButton')!
				.textContent
		).toBe('Show previous');
		expect(
			findByTestAttr(container, 'component-showNextTemplateButton')!
				.textContent
		).toBe('Show next template');
	});

	test('previous button click calls onSelectTemplate with previous id', () => {
		const { container } = render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				onSelectTemplate={onSelectTemplate}
			/>
		);
		fireEvent.click(
			findByTestAttr(container, 'component-showPreviousTemplateButton')!
		);
		expect(onSelectTemplate).toHaveBeenCalledWith('blank-slate');
	});

	test('next button click calls onSelectTemplate with next id', () => {
		const { container } = render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				onSelectTemplate={onSelectTemplate}
			/>
		);
		fireEvent.click(
			findByTestAttr(container, 'component-showNextTemplateButton')!
		);
		expect(onSelectTemplate).toHaveBeenCalledWith('rubix');
	});

	test('previous button is disabled when on first template', () => {
		const { container } = render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={0}
				template={templates[0]}
				onSelectTemplate={onSelectTemplate}
			/>
		);
		expect(
			findByTestAttr(container, 'component-showPreviousTemplateButton')
		).toBeDisabled();
	});

	test('next button is disabled when on last template', () => {
		const { container } = render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={3}
				template={templates[3]}
				onSelectTemplate={onSelectTemplate}
			/>
		);
		expect(
			findByTestAttr(container, 'component-showNextTemplateButton')
		).toBeDisabled();
	});

	test('left arrow keydown calls onSelectTemplate with previous id', () => {
		render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				onSelectTemplate={onSelectTemplate}
			/>
		);
		fireEvent.keyDown(window, { keyCode: 37 });
		expect(onSelectTemplate).toHaveBeenCalledWith('blank-slate');
	});

	test('right arrow keydown calls onSelectTemplate with next id', () => {
		render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				onSelectTemplate={onSelectTemplate}
			/>
		);
		fireEvent.keyDown(window, { keyCode: 39 });
		expect(onSelectTemplate).toHaveBeenCalledWith('rubix');
	});

	test('attaches keydown event listener to window on mount', () => {
		const addEventListenerSpy = jest.spyOn(window, 'addEventListener');
		render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				onSelectTemplate={onSelectTemplate}
			/>
		);
		expect(addEventListenerSpy).toHaveBeenCalledWith(
			'keydown',
			expect.any(Function),
			false
		);
	});

	test('removes keydown event listener from window on unmount', () => {
		const removeEventListenerSpy = jest.spyOn(
			window,
			'removeEventListener'
		);
		const { unmount } = render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				onSelectTemplate={onSelectTemplate}
			/>
		);
		unmount();
		expect(removeEventListenerSpy).toHaveBeenCalledWith(
			'keydown',
			expect.any(Function),
			false
		);
	});
});
