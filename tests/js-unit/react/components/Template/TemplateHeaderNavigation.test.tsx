import React from 'react';
import { render, fireEvent } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import TemplateHeaderNavigation from '../../../../../src/assets/js/react/components/Template/TemplateHeaderNavigation';
import type { TemplateItem } from '../../../../../src/assets/js/react/types';

describe('Template - TemplateHeaderNavigation.js', () => {
	const navigate = jest.fn();
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
				navigate={navigate}
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
				navigate={navigate}
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
				navigate={navigate}
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

	test('previous button click navigates to previous template', () => {
		const { container } = render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				navigate={navigate}
			/>
		);
		fireEvent.click(
			findByTestAttr(container, 'component-showPreviousTemplateButton')!
		);
		expect(navigate).toHaveBeenCalledWith('/template/blank-slate');
	});

	test('next button click navigates to next template', () => {
		const { container } = render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				navigate={navigate}
			/>
		);
		fireEvent.click(
			findByTestAttr(container, 'component-showNextTemplateButton')!
		);
		expect(navigate).toHaveBeenCalledWith('/template/rubix');
	});

	test('previous button is disabled when on first template', () => {
		const { container } = render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={0}
				template={templates[0]}
				navigate={navigate}
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
				navigate={navigate}
			/>
		);
		expect(
			findByTestAttr(container, 'component-showNextTemplateButton')
		).toBeDisabled();
	});

	test('left arrow keydown navigates to previous template', () => {
		render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				navigate={navigate}
			/>
		);
		fireEvent.keyDown(window, { keyCode: 37 });
		expect(navigate).toHaveBeenCalledWith('/template/blank-slate');
	});

	test('right arrow keydown navigates to next template', () => {
		render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				navigate={navigate}
			/>
		);
		fireEvent.keyDown(window, { keyCode: 39 });
		expect(navigate).toHaveBeenCalledWith('/template/rubix');
	});

	test('attaches keydown event listener to window on mount', () => {
		const addEventListenerSpy = jest.spyOn(window, 'addEventListener');
		render(
			<TemplateHeaderNavigation
				templates={templates}
				templateIndex={1}
				template={templates[1]}
				navigate={navigate}
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
				navigate={navigate}
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
