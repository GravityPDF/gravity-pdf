/* Dependencies */
import { createSlice } from '@reduxjs/toolkit';
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

/**
 * Our Redux Template Reducer that take the objects returned from our Redux Template Actions
 * and updates the template portion of our store
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * @typedef { Object } TemplateReducerState
 * @property { Array<Object> } list                            - list of GFPDF prebuilt templates
 * @property { Object }        activeTemplate                  - current template used
 * @property { string }        search                          - filter keyword value
 * @property { string }        updateSelectBoxText             - state of select box text
 * @property { string }        templateProcessing              - state of template processed
 * @property { Object }        templateUploadProcessingSuccess - state when upload is successful
 * @property { Object }        templateUploadProcessingError   - state when upload is not successful
 */

/**
 * Setup the initial state of the "template" portion of our Redux store
 *
 * @return { TemplateReducerState } initialState
 *
 * @since 4.1
 */
export const initialState = {
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
			.addCase(SEARCH_TEMPLATES, (state, action) => ({
				...state,
				search: action.text,
			}))
			.addCase(SELECT_TEMPLATE, (state, action) => ({
				...state,
				activeTemplate: action.id,
			}))
			.addCase(ADD_TEMPLATE, (state, action) => ({
				...state,
				list: [...state.list, action.template],
			}))
			.addCase(UPDATE_TEMPLATE_PARAM, (state, action) => {
				const updatedList = state.list.map((item) => {
					if (item.id === action.id) {
						return { ...item, [action.name]: action.value };
					}
					return item;
				});
				return {
					...state,
					list: updatedList,
				};
			})
			.addCase(DELETE_TEMPLATE, (state, action) => {
				const list = state.list.filter((item) => item.id !== action.id);
				return {
					...state,
					list: [...list],
				};
			})
			.addCase(UPDATE_SELECT_BOX_SUCCESS, (state, action) => ({
				...state,
				updateSelectBoxText: action.payload,
			}))
			.addCase(TEMPLATE_PROCESSING_SUCCESS, (state, action) => ({
				...state,
				templateProcessing: action.payload,
			}))
			.addCase(TEMPLATE_PROCESSING_FAILED, (state, action) => ({
				...state,
				templateProcessing: action.payload,
			}))
			.addCase(CLEAR_TEMPLATE_PROCESSING, (state) => ({
				...state,
				templateProcessing: '',
			}))
			.addCase(TEMPLATE_UPLOAD_PROCESSING_SUCCESS, (state, action) => ({
				...state,
				templateUploadProcessingSuccess: action.payload,
			}))
			.addCase(TEMPLATE_UPLOAD_PROCESSING_FAILED, (state, action) => ({
				...state,
				templateUploadProcessingError: action.payload,
			}))
			.addCase(CLEAR_TEMPLATE_UPLOAD_PROCESSING, (state) => ({
				...state,
				templateUploadProcessingSuccess: {},
				templateUploadProcessingError: {},
			}));
	},
});

export default templateSlice.reducer;
