/* Dependencies */
import { createReduxStore } from '@wordpress/data';
import { speak } from '@wordpress/a11y';
import { __ } from '@wordpress/i18n';
/* Actions */
import {
	SEARCH_TEMPLATES,
	SELECT_TEMPLATE,
	ADD_TEMPLATE,
	UPDATE_TEMPLATE_PARAM,
	DELETE_TEMPLATE,
	UPDATE_SELECT_BOX,
	UPDATE_SELECT_BOX_SUCCESS,
	UPDATE_SELECT_BOX_FAILED,
	TEMPLATE_PROCESSING,
	TEMPLATE_PROCESSING_SUCCESS,
	TEMPLATE_PROCESSING_FAILED,
	CLEAR_TEMPLATE_PROCESSING,
	POST_TEMPLATE_UPLOAD_PROCESSING,
	TEMPLATE_UPLOAD_PROCESSING_SUCCESS,
	TEMPLATE_UPLOAD_PROCESSING_FAILED,
	CLEAR_TEMPLATE_UPLOAD_PROCESSING,
	searchTemplates as searchTemplatesAction,
	selectTemplate,
	addTemplate,
	updateTemplateParam,
	deleteTemplate,
	updateSelectBoxSuccess,
	updateSelectBoxFailed,
	templateProcessingSuccess,
	templateProcessingFailed,
	clearTemplateProcessing,
	templateUploadProcessingSuccess,
	templateUploadProcessingFailed,
	clearTemplateUploadProcessing,
} from '../actions/templates';
/* APIs */
import {
	apiPostUpdateSelectBox,
	apiPostTemplateProcessing,
	apiPostTemplateUploadProcessing,
} from '../api/templates';
/* Selectors */
import getFilteredTemplates from '../selectors/getTemplates';
/* Types */
import { TemplateItem, TemplateState } from '../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

export const TEMPLATE_STORE_NAME = 'gravity-pdf/template' as const;

type ThunkArgs = { dispatch: (action: unknown) => unknown };

/* Wraps a thunk factory so the returned thunk carries a .type tag.
   This allows jest.spyOn(store, 'dispatch') assertions to match { type }. */
function taggedThunk<TArgs extends unknown[]>(
	type: string,
	factory: (...args: TArgs) => (args: ThunkArgs) => Promise<void>
): (...args: TArgs) => ((args: ThunkArgs) => Promise<void>) & { type: string } {
	return (...args: TArgs) => {
		const thunk = factory(...args) as ((
			args: ThunkArgs
		) => Promise<void>) & { type: string };
		thunk.type = type;
		return thunk;
	};
}

export function createTemplateStore(overrideInitial?: Partial<TemplateState>) {
	const defaultInitial: TemplateState = {
		list: GFPDF.templateList ?? [],
		activeTemplate:
			GFPDF.activeTemplate || GFPDF.activeDefaultTemplate || '',
		search: '',
		updateSelectBoxText: '',
		templateProcessing: '',
		templateUploadProcessingSuccess: {},
		templateUploadProcessingError: {},
	};

	const initial: TemplateState = { ...defaultInitial, ...overrideInitial };

	function reducer(
		state: TemplateState = initial,
		action: { type: string; [key: string]: unknown }
	): TemplateState {
		switch (action.type) {
			case SEARCH_TEMPLATES:
				return { ...state, search: action.text as string };
			case SELECT_TEMPLATE:
				return { ...state, activeTemplate: action.id as string };
			case ADD_TEMPLATE:
				return {
					...state,
					list: [...state.list, action.template as TemplateItem],
				};
			case UPDATE_TEMPLATE_PARAM:
				return {
					...state,
					list: state.list.map((item) =>
						item.id === action.id
							? {
									...item,
									[action.name as string]: action.value,
								}
							: item
					),
				};
			case DELETE_TEMPLATE:
				return {
					...state,
					list: state.list.filter((item) => item.id !== action.id),
				};
			case UPDATE_SELECT_BOX_SUCCESS:
				return {
					...state,
					updateSelectBoxText: action.payload as string,
				};
			case TEMPLATE_PROCESSING_SUCCESS:
			case TEMPLATE_PROCESSING_FAILED:
				return {
					...state,
					templateProcessing: action.payload as string,
				};
			case CLEAR_TEMPLATE_PROCESSING:
				return { ...state, templateProcessing: '' };
			case TEMPLATE_UPLOAD_PROCESSING_SUCCESS:
				return {
					...state,
					templateUploadProcessingSuccess: action.payload as Record<
						string,
						unknown
					>,
				};
			case TEMPLATE_UPLOAD_PROCESSING_FAILED:
				return {
					...state,
					templateUploadProcessingError: action.payload as Record<
						string,
						unknown
					>,
				};
			case CLEAR_TEMPLATE_UPLOAD_PROCESSING:
				return {
					...state,
					templateUploadProcessingSuccess: {},
					templateUploadProcessingError: {},
				};
			default:
				return state;
		}
	}

	return createReduxStore(TEMPLATE_STORE_NAME, {
		reducer,
		actions: {
			/* Sync action creators */
			searchTemplates: searchTemplatesAction,
			selectTemplate,
			addTemplate,
			updateTemplateParam,
			deleteTemplate,
			updateSelectBoxSuccess,
			updateSelectBoxFailed,
			templateProcessingSuccess,
			templateProcessingFailed,
			clearTemplateProcessing,
			templateUploadProcessingSuccess,
			templateUploadProcessingFailed,
			clearTemplateUploadProcessing,

			/* Thunk action creators (tagged with action type for spy compatibility) */
			updateSelectBox: taggedThunk(
				UPDATE_SELECT_BOX,
				() =>
					async ({ dispatch }: ThunkArgs) => {
						try {
							const html = await apiPostUpdateSelectBox();
							dispatch(updateSelectBoxSuccess(html));
						} catch {
							dispatch(updateSelectBoxFailed());
						}
					}
			),

			templateProcessing: taggedThunk(
				TEMPLATE_PROCESSING,
				(templateId: string) =>
					async ({ dispatch }: ThunkArgs) => {
						try {
							await apiPostTemplateProcessing(templateId);
							dispatch(templateProcessingSuccess('success'));
							speak(__('Template activated.', 'gravity-pdf'));
						} catch {
							dispatch(templateProcessingFailed('failed'));
							speak(
								__('Error activating template.', 'gravity-pdf'),
								'assertive'
							);
						}
					}
			),

			postTemplateUploadProcessing: taggedThunk(
				POST_TEMPLATE_UPLOAD_PROCESSING,
				(file: File, filename: string) =>
					async ({ dispatch }: ThunkArgs) => {
						try {
							const body = await apiPostTemplateUploadProcessing(
								file,
								filename
							);
							dispatch(
								templateUploadProcessingSuccess(
									body as Record<string, unknown>
								)
							);
							speak(__('Template installed.', 'gravity-pdf'));
						} catch (error) {
							dispatch(
								templateUploadProcessingFailed({
									message: (error as Error).message,
								})
							);
							speak(
								__('Error installing template.', 'gravity-pdf'),
								'assertive'
							);
						}
					}
			),
		},
		selectors: {
			getSearch: (state: TemplateState) => state.search,
			getActiveTemplate: (state: TemplateState) => state.activeTemplate,
			getList: (state: TemplateState) => state.list,
			getUpdateSelectBoxText: (state: TemplateState) =>
				state.updateSelectBoxText,
			getTemplateProcessing: (state: TemplateState) =>
				state.templateProcessing,
			getTemplateUploadProcessingSuccess: (state: TemplateState) =>
				state.templateUploadProcessingSuccess,
			getTemplateUploadProcessingError: (state: TemplateState) =>
				state.templateUploadProcessingError,
			getFilteredTemplates,
		},
	});
}

export const templateStore = createTemplateStore();
