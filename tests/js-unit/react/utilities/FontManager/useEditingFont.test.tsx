import { renderHook } from '@testing-library/react';
import { RegistryProvider, createRegistry } from '@wordpress/data';
import {
	createFontManagerStore,
	FONT_MANAGER_STORE_NAME,
} from '../../../../../src/assets/js/react/store/fontManagerStore';
import { useEditingFont } from '../../../../../src/assets/js/react/utilities/FontManager/useEditingFont';
import type {
	EditingFontState,
	FontItem,
	FontManagerState,
} from '../../../../../src/assets/js/react/types';

const sample: FontItem = {
	id: 'roboto',
	font_name: 'Roboto',
	regular: 'paths/Roboto-Regular.ttf',
	italics: 'paths/Roboto-Italic.ttf',
	bold: '',
	bolditalics: '',
};

function wrap(initial: Partial<FontManagerState>) {
	const registry = createRegistry();
	registry.register(createFontManagerStore(initial));
	return ({ children }: { children: React.ReactNode }) => (
		<RegistryProvider value={registry}>{children}</RegistryProvider>
	);
}

describe('Utilities/FontManager - useEditingFont', () => {
	test('returns null editingFont when nothing is being edited', () => {
		const { result } = renderHook(() => useEditingFont(), {
			wrapper: wrap({ fontList: [sample] }),
		});
		expect(result.current.editingFont).toBeNull();
		expect(result.current.dirty).toBe(false);
	});

	test('a draft with empty label and no files is not dirty', () => {
		const editingFont: EditingFontState = {
			id: 'draft-1',
			isDraft: true,
			label: '',
			fontStyles: { regular: '', italics: '', bold: '', bolditalics: '' },
		};
		const { result } = renderHook(() => useEditingFont(), {
			wrapper: wrap({ fontList: [], editingFont }),
		});
		expect(result.current.dirty).toBe(false);
		expect(result.current.regularMissing).toBe(true);
		expect(result.current.canSave).toBe(false);
	});

	test('a draft with name + regular is dirty and canSave', () => {
		const editingFont: EditingFontState = {
			id: 'draft-1',
			isDraft: true,
			label: 'Roboto',
			fontStyles: {
				regular: new File([new Uint8Array(0)], 'Roboto-Regular.ttf'),
				italics: '',
				bold: '',
				bolditalics: '',
			},
		};
		const { result } = renderHook(() => useEditingFont(), {
			wrapper: wrap({ fontList: [], editingFont }),
		});
		expect(result.current.dirty).toBe(true);
		expect(result.current.regularMissing).toBe(false);
		expect(result.current.canSave).toBe(true);
	});

	test('editing-existing with no changes is not dirty and cannot save', () => {
		const editingFont: EditingFontState = {
			id: 'roboto',
			isDraft: false,
			label: 'Roboto',
			fontStyles: {
				regular: 'paths/Roboto-Regular.ttf',
				italics: 'paths/Roboto-Italic.ttf',
				bold: '',
				bolditalics: '',
			},
		};
		const { result } = renderHook(() => useEditingFont(), {
			wrapper: wrap({ fontList: [sample], editingFont }),
		});
		expect(result.current.dirty).toBe(false);
		expect(result.current.canSave).toBe(false);
	});

	test('renaming an existing font flips dirty', () => {
		const editingFont: EditingFontState = {
			id: 'roboto',
			isDraft: false,
			label: 'Roboto 2',
			fontStyles: {
				regular: 'paths/Roboto-Regular.ttf',
				italics: 'paths/Roboto-Italic.ttf',
				bold: '',
				bolditalics: '',
			},
		};
		const { result } = renderHook(() => useEditingFont(), {
			wrapper: wrap({ fontList: [sample], editingFont }),
		});
		expect(result.current.dirty).toBe(true);
		expect(result.current.canSave).toBe(true);
	});

	test('replacing an existing variant File raises hasDestructiveFileChange', () => {
		const editingFont: EditingFontState = {
			id: 'roboto',
			isDraft: false,
			label: 'Roboto',
			fontStyles: {
				regular: new File(
					[new Uint8Array(0)],
					'New-Roboto-Regular.ttf'
				),
				italics: 'paths/Roboto-Italic.ttf',
				bold: '',
				bolditalics: '',
			},
		};
		const { result } = renderHook(() => useEditingFont(), {
			wrapper: wrap({ fontList: [sample], editingFont }),
		});
		expect(result.current.hasDestructiveFileChange).toBe(true);
	});

	test('removing an existing variant raises hasDestructiveFileChange', () => {
		const editingFont: EditingFontState = {
			id: 'roboto',
			isDraft: false,
			label: 'Roboto',
			fontStyles: {
				regular: 'paths/Roboto-Regular.ttf',
				italics: '',
				bold: '',
				bolditalics: '',
			},
		};
		const { result } = renderHook(() => useEditingFont(), {
			wrapper: wrap({ fontList: [sample], editingFont }),
		});
		expect(result.current.hasDestructiveFileChange).toBe(true);
	});

	test('disallowed characters surface a name error', () => {
		const editingFont: EditingFontState = {
			id: 'draft-1',
			isDraft: true,
			label: 'Bad@Name',
			fontStyles: {
				regular: new File([new Uint8Array(0)], 'r.ttf'),
				italics: '',
				bold: '',
				bolditalics: '',
			},
		};
		const { result } = renderHook(() => useEditingFont(), {
			wrapper: wrap({ fontList: [], editingFont }),
		});
		expect(result.current.nameError).toMatch(
			/letters, numbers and spaces/i
		);
		expect(result.current.canSave).toBe(false);
	});
});
