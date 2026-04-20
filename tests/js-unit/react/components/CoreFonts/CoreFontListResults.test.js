import React from 'react';
import { render, fireEvent } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { findByTestAttr } from '../../testUtilsRTL';
import {
	CoreFontListResults,
	Retry,
} from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontListResults';

const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
	...jest.requireActual('react-router-dom'),
	useNavigate: () => mockNavigate,
}));

describe('CoreFonts - CoreFontListResults.js', () => {
	describe('CoreFontListResults Component', () => {
		const fontList = [
			'AboriginalSansREGULAR.ttf',
			'Abyssinica_SIL.ttf',
			'DejaVuSerifCondensed.ttf',
		];
		const dataPending = {
			'AboriginalSansREGULAR.ttf': {
				status: 'pending',
				message: 'Downloading AboriginalSansREGULAR.ttf...',
			},
			'Abyssinica_SIL.ttf': {
				status: 'pending',
				message: 'Downloading Abyssinica_SIL.ttf...',
			},
		};
		const dataSuccess = {
			'AboriginalSansREGULAR.ttf': {
				status: 'success',
				message: 'Completed installation of AboriginalSansREGULAR.ttf',
			},
			'Abyssinica_SIL.ttf': {
				status: 'success',
				message: 'Completed installation of Abyssinica_SIL.ttf',
			},
		};
		const dataCompleted = {
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
				<MemoryRouter>
					<CoreFontListResults console={dataPending} retry={[]} />
				</MemoryRouter>
			);

			expect(
				findByTestAttr(container, 'component-coreFont-container')
			).toBeInTheDocument();
		});

		test('renders console pending output for our core font downloader', () => {
			const { container } = render(
				<MemoryRouter>
					<CoreFontListResults console={dataPending} retry={[]} />
				</MemoryRouter>
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
				<MemoryRouter>
					<CoreFontListResults console={dataSuccess} retry={[]} />
				</MemoryRouter>
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
				<MemoryRouter>
					<CoreFontListResults console={dataCompleted} retry={[]} />
				</MemoryRouter>
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
				<MemoryRouter>
					<CoreFontListResults
						console={dataCompleted}
						retry={fontList}
					/>
				</MemoryRouter>
			);

			expect(
				findByTestAttr(container, 'component-retry-link')
			).toBeInTheDocument();
		});
	});

	describe('Retry Component', () => {
		beforeEach(() => {
			mockNavigate.mockClear();
		});

		test('renders <Retry /> component container', () => {
			const { container } = render(<Retry />);

			expect(
				findByTestAttr(container, 'component-retry-link')
			).toBeInTheDocument();
		});

		test('renders link text', () => {
			const { container } = render(
				<Retry retryText="Retry Failed Downloads?" />
			);

			expect(container.querySelector('button').textContent).toBe(
				'Retry Failed Downloads?'
			);
		});

		test('check link click', () => {
			const { container } = render(<Retry />);
			fireEvent.click(findByTestAttr(container, 'component-retry-link'));

			expect(mockNavigate).toHaveBeenCalledTimes(1);
			expect(mockNavigate).toHaveBeenCalledWith('retryDownloadCoreFonts');
		});
	});
});
