/* Dependencies */
import { createSlice, PayloadAction } from '@reduxjs/toolkit';
/* Redux action types */
import {
	SEARCH_TEMPLATES,
	SELECT_TEMPLATE,
	ADD_TEMPLATE,
	UPDATE_TEMPLATE_PARAM,
	DELETE_TEMPLATE,
	UPDATE_SELECT_BOX_SUCCESS,
	TEMPLATE_PROCESSING_SUCCESS,
	TEMPLATE_PROCESSING_FAILED,
	CLEAR_TEMPLATE_PROCESSING,
	TEMPLATE_UPLOAD_PROCESSING_SUCCESS,
	TEMPLATE_UPLOAD_PROCESSING_FAILED,
	CLEAR_TEMPLATE_UPLOAD_PROCESSING,
} from '../actions/templates';
/* Types */
import { TemplateItem, TemplateState } from '../types';

/**
 * Our Redux Template Reducer that take the objects returned from our Redux Template Actions
 * and updates the template portion of our store
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

export const initialState: TemplateState = {
	list: GFPDF.templateList,
	activeTemplate: GFPDF.activeTemplate || GFPDF.activeDefaultTemplate,
	search: '',
	updateSelectBoxText: '',
	templateProcessing: '',
	templateUploadProcessingSuccess: {},
	templateUploadProcessingError: {},
};

const templateSlice = createSlice({
	name: 'template',
	initialState,
	reducers: {},
	extraReducers: (builder) => {
		builder
			.addCase(SEARCH_TEMPLATES, (state: TemplateState, action) => {
				const a = action as unknown as {
					type: typeof SEARCH_TEMPLATES;
					text: string;
				};
				return {
					...state,
					search: a.text,
				};
			})
			.addCase(SELECT_TEMPLATE, (state: TemplateState, action) => {
				const a = action as unknown as {
					type: typeof SELECT_TEMPLATE;
					id: string;
				};
				return {
					...state,
					activeTemplate: a.id,
				};
			})
			.addCase(ADD_TEMPLATE, (state: TemplateState, action) => {
				const a = action as unknown as {
					type: typeof ADD_TEMPLATE;
					template: TemplateItem;
				};
				return {
					...state,
					list: [...state.list, a.template],
				};
			})
			.addCase(UPDATE_TEMPLATE_PARAM, (state: TemplateState, action) => {
				const a = action as unknown as {
					type: typeof UPDATE_TEMPLATE_PARAM;
					id: string;
					name: keyof TemplateItem;
					value: unknown;
				};
				const updatedList = state.list.map((item) => {
					if (item.id === a.id) {
						return { ...item, [a.name]: a.value };
					}
					return item;
				});
				return {
					...state,
					list: updatedList,
				};
			})
			.addCase(DELETE_TEMPLATE, (state: TemplateState, action) => {
				const a = action as unknown as {
					type: typeof DELETE_TEMPLATE;
					id: string;
				};
				const list = state.list.filter((item) => item.id !== a.id);
				return {
					...state,
					list: [...list],
				};
			})
			.addCase(
				UPDATE_SELECT_BOX_SUCCESS,
				(state: TemplateState, action) => {
					const { payload } =
						action as unknown as PayloadAction<string>;
					return {
						...state,
						updateSelectBoxText: payload,
					};
				}
			)
			.addCase(
				TEMPLATE_PROCESSING_SUCCESS,
				(state: TemplateState, action) => {
					const { payload } =
						action as unknown as PayloadAction<string>;
					return {
						...state,
						templateProcessing: payload,
					};
				}
			)
			.addCase(
				TEMPLATE_PROCESSING_FAILED,
				(state: TemplateState, action) => {
					const { payload } =
						action as unknown as PayloadAction<string>;
					return {
						...state,
						templateProcessing: payload,
					};
				}
			)
			.addCase(CLEAR_TEMPLATE_PROCESSING, (state: TemplateState) => ({
				...state,
				templateProcessing: '',
			}))
			.addCase(
				TEMPLATE_UPLOAD_PROCESSING_SUCCESS,
				(state: TemplateState, action) => {
					const { payload } = action as unknown as PayloadAction<
						Record<string, unknown>
					>;
					return {
						...state,
						templateUploadProcessingSuccess: payload,
					};
				}
			)
			.addCase(
				TEMPLATE_UPLOAD_PROCESSING_FAILED,
				(state: TemplateState, action) => {
					const { payload } = action as unknown as PayloadAction<
						Record<string, unknown>
					>;
					return {
						...state,
						templateUploadProcessingError: payload,
					};
				}
			)
			.addCase(
				CLEAR_TEMPLATE_UPLOAD_PROCESSING,
				(state: TemplateState) => ({
					...state,
					templateUploadProcessingSuccess: {},
					templateUploadProcessingError: {},
				})
			);
	},
});

export default templateSlice.reducer;
