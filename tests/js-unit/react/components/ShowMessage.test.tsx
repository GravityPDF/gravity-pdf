import React from 'react';
import { render, act } from '@testing-library/react';
import { findByTestAttr } from '../testUtilsRTL';
import ShowMessage from '../../../../src/assets/js/react/components/ShowMessage';

describe('Components - ShowMessage.js', () => {
	test('renders <ShowMessage /> component', () => {
		const { container } = render(<ShowMessage text="text" error />);
		const component = findByTestAttr(container, 'component-showMessage');

		expect(component).toBeInTheDocument();
		expect(component!.textContent).toBe('text');
		expect(component).toHaveClass('notice', 'inline', 'error');
	});

	test('renders without error class when error prop not passed', () => {
		const { container } = render(<ShowMessage text="text" />);
		const component = findByTestAttr(container, 'component-showMessage');

		expect(component).not.toHaveClass('error');
	});

	test('auto-dismisses after delay and renders empty div', () => {
		jest.useFakeTimers();
		const { container } = render(
			<ShowMessage text="text" dismissable delay={100} />
		);

		expect(
			findByTestAttr(container, 'component-showMessage')
		).toBeInTheDocument();

		act(() => {
			jest.runAllTimers();
		});

		expect(
			findByTestAttr(container, 'component-showMessage')
		).not.toBeInTheDocument();
		expect(container.innerHTML).toBe('<div></div>');

		jest.useRealTimers();
	});

	test('calls dismissableCallback after auto-dismiss', () => {
		jest.useFakeTimers();
		const callback = jest.fn();
		render(
			<ShowMessage
				text="text"
				dismissable
				delay={100}
				dismissableCallback={callback}
			/>
		);

		act(() => {
			jest.runAllTimers();
		});

		expect(callback).toHaveBeenCalledTimes(1);

		jest.useRealTimers();
	});

	test('clears timeout on unmount', () => {
		const clearTimeoutSpy = jest.spyOn(window, 'clearTimeout');
		const { unmount } = render(<ShowMessage text="text" dismissable />);
		unmount();

		expect(clearTimeoutSpy).toHaveBeenCalled();

		clearTimeoutSpy.mockRestore();
	});
});
