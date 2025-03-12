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
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
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

/**
 * The action template reducer which updates our state
 *
 * @param { TemplateReducerState } state  The current state of our template store
 * @param { Object }               action The Redux action details being triggered
 *
 * @return { TemplateReducerState } State (whether updated or not)
 *
 * @since 4.1
 */
export default function (state = initialState, action) {
	switch (action.type) {
		/**
		 * Update the search key
		 *
		 * @since 4.1
		 */
		case SEARCH_TEMPLATES:
			return {
				...state,
				search: action.text,
			};

		/**
		 * Update the activeTemplate key
		 *
		 * @since 4.1
		 */
		case SELECT_TEMPLATE:
			return {
				...state,
				activeTemplate: action.id,
			};

		/**
		 * Push a new template into List
		 *
		 * @since 4.1
		 */
		case ADD_TEMPLATE:
			return {
				...state,
				list: [...state.list, action.template],
			};

		/**
		 * Update single parameter in template new value
		 *
		 * @since 4.1
		 */
		case UPDATE_TEMPLATE_PARAM: {
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
		}

		/**
		 * Remove template from List
		 *
		 * @since 4.1
		 */
		case DELETE_TEMPLATE: {
			const list = state.list.filter((item) => item.id !== action.id);
			return {
				...state,
				list: [...list],
			};
		}

		/**
		 * Update the new Select Box DOM data
		 *
		 * @since 5.2
		 */
		case UPDATE_SELECT_BOX_SUCCESS:
			return {
				...state,
				updateSelectBoxText: action.payload,
			};

		/**
		 * Remove the PDF template automatically
		 *
		 * @since 5.2
		 */
		case TEMPLATE_PROCESSING_SUCCESS:
			return {
				...state,
				templateProcessing: action.payload,
			};

		/**
		 * Fires Re-add template to our list and display an appropriate inline error message
		 *
		 * @since 5.2
		 */
		case TEMPLATE_PROCESSING_FAILED:
			return {
				...state,
				templateProcessing: action.payload,
			};

		/**
		 * Clear/reset the templateProcessing state
		 *
		 * @since 5.2
		 */
		case CLEAR_TEMPLATE_PROCESSING:
			return {
				...state,
				templateProcessing: '',
			};

		/**
		 * Update with the new PDF template details
		 *
		 * @since 5.2
		 */
		case TEMPLATE_UPLOAD_PROCESSING_SUCCESS:
			return {
				...state,
				templateUploadProcessingSuccess: action.payload,
			};

		/**
		 * Update/Show error
		 *
		 * @since 5.2
		 */
		case TEMPLATE_UPLOAD_PROCESSING_FAILED:
			return {
				...state,
				templateUploadProcessingError: action.payload,
			};

		/**
		 * Clear/reset state of templateUploadProcessingSuccess & templateUploadProcessingError
		 *
		 * @since 5.2
		 */
		case CLEAR_TEMPLATE_UPLOAD_PROCESSING:
			return {
				...state,
				templateUploadProcessingSuccess: {},
				templateUploadProcessingError: {},
			};
	}

	/* None of these actions fired so return state */
	return state;
}
