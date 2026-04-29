import { render, fireEvent } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import {
	CoreFontListResults,
	Retry,
} from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontListResults';
import type { ConsoleLine } from '../../../../../src/assets/js/react/types';

describe('CoreFonts - CoreFontListResults.js', () => {
	describe('CoreFontListResults Component', () => {
		const fontList = [
			'AboriginalSansREGULAR.ttf',
			'Abyssinica_SIL.ttf',
			'DejaVuSerifCondensed.ttf',
		];
		const dataPending: Record<string, ConsoleLine> = {
			'AboriginalSansREGULAR.ttf': {
				status: 'pending',
				message: 'Downloading AboriginalSansREGULAR.ttf...',
			},
			'Abyssinica_SIL.ttf': {
				status: 'pending',
				message: 'Downloading Abyssinica_SIL.ttf...',
			},
		};
		const dataSuccess: Record<string, ConsoleLine> = {
			'AboriginalSansREGULAR.ttf': {
				status: 'success',
				message: 'Completed installation of AboriginalSansREGULAR.ttf',
			},
			'Abyssinica_SIL.ttf': {
				status: 'success',
				message: 'Completed installation of Abyssinica_SIL.ttf',
			},
		};
		const dataCompleted: Record<string, ConsoleLine> = {
			'Abyssinica_SIL.ttf': {
				status: 'success',
				message: 'Completed installation of Abyssinica_SIL.ttf',
			},
			completed: {
				status: 'success',
				message: 'ALL CORE FONTS SUCCESSFULLY INSTALLED',
			},
		};

		test('renders <CoreFontListResults /> component container', () => {
			const { container } = render(
				<CoreFontListResults console={dataPending} retry={[]} />
			);

			expect(
				findByTestAttr(container, 'component-coreFont-container')
			).toBeInTheDocument();
		});

		test('renders console pending output for our core font downloader', () => {
			const { container } = render(
				<CoreFontListResults console={dataPending} retry={[]} />
			);
			const pending = container.querySelectorAll(
				'.gfpdf-core-font-status-pending'
			);

			expect(pending).toHaveLength(2);
			expect(pending[0].textContent).toBe(
				'Downloading Abyssinica_SIL.ttf... '
			);
			expect(pending[1].textContent).toBe(
				'Downloading AboriginalSansREGULAR.ttf... '
			);
		});

		test('renders console success output for our core font downloader', () => {
			const { container } = render(
				<CoreFontListResults console={dataSuccess} retry={[]} />
			);
			const success = container.querySelectorAll(
				'.gfpdf-core-font-status-success'
			);

			expect(success).toHaveLength(2);
			expect(success[0].textContent).toBe(
				'Completed installation of Abyssinica_SIL.ttf '
			);
			expect(success[1].textContent).toBe(
				'Completed installation of AboriginalSansREGULAR.ttf '
			);
		});

		test('renders list spacer container component <ListSpacer />', () => {
			const { container } = render(
				<CoreFontListResults console={dataCompleted} retry={[]} />
			);
			const success = container.querySelectorAll(
				'.gfpdf-core-font-status-success'
			);

			expect(success).toHaveLength(2);
			expect(success[0].textContent).toBe(
				'ALL CORE FONTS SUCCESSFULLY INSTALLED ---'
			);
			expect(success[1].textContent).toBe(
				'Completed installation of Abyssinica_SIL.ttf '
			);
		});

		test('renders retry component <Retry />', () => {
			const { container } = render(
				<CoreFontListResults console={dataCompleted} retry={fontList} />
			);

			expect(
				findByTestAttr(container, 'component-retry-link')
			).toBeInTheDocument();
		});
	});

	describe('Retry Component', () => {
		test('renders <Retry /> component container', () => {
			const { container } = render(<Retry />);

			expect(
				findByTestAttr(container, 'component-retry-link')
			).toBeInTheDocument();
		});

		test('renders link text', () => {
			const { container } = render(<Retry />);

			expect(container.querySelector('button')!.textContent).toBe(
				'Retry Failed Downloads?'
			);
		});

		test('check link click', () => {
			const mockOnRetry = jest.fn();
			const { container } = render(<Retry onRetry={mockOnRetry} />);
			fireEvent.click(findByTestAttr(container, 'component-retry-link')!);

			expect(mockOnRetry).toHaveBeenCalledTimes(1);
		});
	});
});
