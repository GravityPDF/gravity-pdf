import { fireEvent } from '@testing-library/react';
import {
	createTestStore,
	findByTestAttr,
	renderWithStore,
} from '../../testUtilsRTL';
import FontDetail from '../../../../../src/assets/js/react/components/FontManager/FontDetail';
import type { FontItem } from '../../../../../src/assets/js/react/types';

const sample: FontItem = {
	id: 'roboto',
	font_name: 'Roboto',
	regular: 'paths/Roboto-Regular.ttf',
	italics: '',
	bold: '',
	bolditalics: '',
};

const baseProps = {
	onSave: jest.fn(),
	onCancel: jest.fn(),
	onRequestDelete: jest.fn(),
	onSetActive: jest.fn(),
	onMobileBack: jest.fn(),
	onRejected: jest.fn(),
	onAddFont: jest.fn(),
};

describe('FontManager - FontDetail', () => {
	test('renders EmptyDetail when no font is being edited', () => {
		const store = createTestStore({
			fontManager: { fontList: [sample], editingFont: null },
		});
		const { container } = renderWithStore(
			<FontDetail {...baseProps} />,
			{},
			{},
			store
		);
		expect(
			findByTestAttr(container, 'component-EmptyDetail')
		).toBeInTheDocument();
	});

	test('renders the editing form when a draft is in progress', () => {
		const store = createTestStore({
			fontManager: {
				fontList: [],
				editingFont: {
					id: 'draft-1',
					isDraft: true,
					label: 'New Font',
					fontStyles: {
						regular: '',
						italics: '',
						bold: '',
						bolditalics: '',
					},
				},
			},
		});
		const { container, getByRole } = renderWithStore(
			<FontDetail {...baseProps} />,
			{},
			{},
			store
		);
		expect(
			findByTestAttr(container, 'component-FontDetail')
		).toBeInTheDocument();
		expect(
			getByRole('heading', { level: 2, name: 'Add font' })
		).toBeInTheDocument();
	});

	test('Preview and TemplateUsage hidden for unsaved drafts', () => {
		const store = createTestStore({
			fontManager: {
				fontList: [],
				editingFont: {
					id: 'draft-1',
					isDraft: true,
					label: 'New Font',
					fontStyles: {
						regular: new File(
							[new Uint8Array(0)],
							'NewFont-Regular.ttf'
						),
						italics: '',
						bold: '',
						bolditalics: '',
					},
				},
			},
		});
		const { container } = renderWithStore(
			<FontDetail {...baseProps} />,
			{},
			{},
			store
		);
		expect(findByTestAttr(container, 'component-FontPreview')).toBeNull();
		expect(findByTestAttr(container, 'component-TemplateUsage')).toBeNull();
	});

	test('Preview and TemplateUsage shown when editing a saved font with a saved Regular', () => {
		const store = createTestStore({
			fontManager: {
				fontList: [sample],
				editingFont: {
					id: 'roboto',
					isDraft: false,
					label: 'Roboto',
					fontStyles: {
						regular: 'paths/Roboto-Regular.ttf',
						italics: '',
						bold: '',
						bolditalics: '',
					},
				},
			},
		});
		const { container } = renderWithStore(
			<FontDetail {...baseProps} />,
			{},
			{},
			store
		);
		expect(
			findByTestAttr(container, 'component-FontPreview')
		).toBeInTheDocument();
		expect(
			findByTestAttr(container, 'component-TemplateUsage')
		).toBeInTheDocument();
	});

	test('clicking Save fires onSave', () => {
		const onSave = jest.fn();
		const store = createTestStore({
			fontManager: {
				fontList: [sample],
				editingFont: {
					id: 'roboto',
					isDraft: false,
					label: 'Roboto',
					fontStyles: {
						regular: new File(
							[new Uint8Array(0)],
							'Replacement-Regular.ttf'
						),
						italics: '',
						bold: '',
						bolditalics: '',
					},
				},
			},
		});
		const { getByRole } = renderWithStore(
			<FontDetail {...baseProps} onSave={onSave} />,
			{},
			{},
			store
		);
		fireEvent.click(getByRole('button', { name: 'Save changes' }));
		expect(onSave).toHaveBeenCalled();
	});
});
