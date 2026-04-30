import { fireEvent } from '@testing-library/react';
import { renderWithStore } from '../../testUtilsRTL';
import VariantRow from '../../../../../src/assets/js/react/components/FontManager/VariantRow';

const ttfFile = (name = 'Roboto-Regular.ttf') =>
	new File([new Uint8Array([0])], name, { type: 'font/ttf' });
const otfFile = (name = 'Roboto-Regular.otf') =>
	new File([new Uint8Array([0])], name, { type: 'font/otf' });

const baseProps = {
	variantDef: {
		key: 'regular' as const,
		label: 'Regular',
		required: true,
	},
	value: '' as string | File,
	onUpload: jest.fn(),
	onDelete: jest.fn(),
	onRejected: jest.fn(),
};

describe('FontManager - VariantRow', () => {
	test('renders empty state with the required marker', () => {
		const { getByText } = renderWithStore(<VariantRow {...baseProps} />);
		expect(getByText(/no .ttf file added/i)).toBeInTheDocument();
		expect(getByText(/required/i)).toBeInTheDocument();
	});

	test('hides the trash button on the required Regular variant when filled', () => {
		const { queryByRole } = renderWithStore(
			<VariantRow {...baseProps} value="paths/Roboto-Regular.ttf" />
		);
		expect(
			queryByRole('button', { name: /delete regular font file/i })
		).not.toBeInTheDocument();
	});

	test('shows the trash button on optional variants when filled', () => {
		const { getByRole } = renderWithStore(
			<VariantRow
				{...baseProps}
				variantDef={{
					key: 'italics',
					label: 'Italic',
					required: false,
				}}
				value="paths/Roboto-Italic.ttf"
			/>
		);
		expect(
			getByRole('button', { name: /delete italic font file/i })
		).toBeInTheDocument();
	});

	test('clicking delete dispatches onDelete', () => {
		const onDelete = jest.fn();
		const { getByRole } = renderWithStore(
			<VariantRow
				{...baseProps}
				variantDef={{
					key: 'italics',
					label: 'Italic',
					required: false,
				}}
				value="paths/Roboto-Italic.ttf"
				onDelete={onDelete}
			/>
		);
		fireEvent.click(
			getByRole('button', { name: /delete italic font file/i })
		);
		expect(onDelete).toHaveBeenCalled();
	});

	test('uploading a .ttf via the file input dispatches onUpload', () => {
		const onUpload = jest.fn();
		const { container } = renderWithStore(
			<VariantRow {...baseProps} onUpload={onUpload} />
		);
		const fileInput = container.querySelector(
			'input[type="file"]'
		) as HTMLInputElement;
		const file = ttfFile();
		fireEvent.change(fileInput, { target: { files: [file] } });
		expect(onUpload).toHaveBeenCalledWith(file);
	});

	test('uploading a .otf via the file input dispatches onRejected', () => {
		const onUpload = jest.fn();
		const onRejected = jest.fn();
		const { container } = renderWithStore(
			<VariantRow
				{...baseProps}
				onUpload={onUpload}
				onRejected={onRejected}
			/>
		);
		const fileInput = container.querySelector(
			'input[type="file"]'
		) as HTMLInputElement;
		fireEvent.change(fileInput, { target: { files: [otfFile()] } });
		expect(onRejected).toHaveBeenCalledWith(
			expect.stringMatching(/only .ttf/i)
		);
		expect(onUpload).not.toHaveBeenCalled();
	});
});
