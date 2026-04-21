/* Redux Action Type Constants */
export const SEARCH_TEMPLATES = 'SEARCH_TEMPLATES' as const;
export const SELECT_TEMPLATE = 'SELECT_TEMPLATE' as const;
export const ADD_TEMPLATE = 'ADD_TEMPLATE' as const;
export const UPDATE_TEMPLATE_PARAM = 'UPDATE_TEMPLATE_PARAM' as const;
export const DELETE_TEMPLATE = 'DELETE_TEMPLATE' as const;
export const UPDATE_SELECT_BOX = 'UPDATE_SELECT_BOX' as const;
export const UPDATE_SELECT_BOX_SUCCESS = 'UPDATE_SELECT_BOX_SUCCESS' as const;
export const UPDATE_SELECT_BOX_FAILED = 'UPDATE_SELECT_BOX_FAILED' as const;
export const TEMPLATE_PROCESSING = 'TEMPLATE_PROCESSING' as const;
export const TEMPLATE_PROCESSING_SUCCESS =
	'TEMPLATE_PROCESSING_SUCCESS' as const;
export const TEMPLATE_PROCESSING_FAILED = 'TEMPLATE_PROCESSING_FAILED' as const;
export const CLEAR_TEMPLATE_PROCESSING = 'CLEAR_TEMPLATE_PROCESSING' as const;
export const POST_TEMPLATE_UPLOAD_PROCESSING =
	'POST_TEMPLATE_UPLOAD_PROCESSING' as const;
export const TEMPLATE_UPLOAD_PROCESSING_SUCCESS =
	'TEMPLATE_UPLOAD_PROCESSING_SUCCESS' as const;
export const TEMPLATE_UPLOAD_PROCESSING_FAILED =
	'TEMPLATE_UPLOAD_PROCESSING_FAILED' as const;
export const CLEAR_TEMPLATE_UPLOAD_PROCESSING =
	'CLEAR_TEMPLATE_UPLOAD_PROCESSING' as const;

/**
 * Redux Actions - payloads of information that send data from your application to your store
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

import { TemplateItem } from '../types';

export const searchTemplates = (text: string) => {
	return {
		type: SEARCH_TEMPLATES,
		text,
	};
};

export const selectTemplate = (id: string) => {
	return {
		type: SELECT_TEMPLATE,
		id,
	};
};

export const addTemplate = (template: TemplateItem) => {
	return {
		type: ADD_TEMPLATE,
		template,
	};
};

export const updateTemplateParam = (
	id: string,
	name: string,
	value: string | null
) => {
	return {
		type: UPDATE_TEMPLATE_PARAM,
		id,
		name,
		value,
	};
};

export const deleteTemplate = (id: string) => {
	return {
		type: DELETE_TEMPLATE,
		id,
	};
};

export const updateSelectBox = () => {
	return {
		type: UPDATE_SELECT_BOX,
	};
};

export const updateSelectBoxSuccess = (text: string) => {
	return {
		type: UPDATE_SELECT_BOX_SUCCESS,
		payload: text,
	};
};

export const updateSelectBoxFailed = () => {
	return {
		type: UPDATE_SELECT_BOX_FAILED,
	};
};

export const templateProcessing = (templateId: string) => {
	return {
		type: TEMPLATE_PROCESSING,
		payload: templateId,
	};
};

export const templateProcessingSuccess = (data: string) => {
	return {
		type: TEMPLATE_PROCESSING_SUCCESS,
		payload: data,
	};
};

export const templateProcessingFailed = (data: string) => {
	return {
		type: TEMPLATE_PROCESSING_FAILED,
		payload: data,
	};
};

export const clearTemplateProcessing = () => {
	return {
		type: CLEAR_TEMPLATE_PROCESSING,
	};
};

export const postTemplateUploadProcessing = (file: File, filename: string) => {
	return {
		type: POST_TEMPLATE_UPLOAD_PROCESSING,
		payload: {
			file,
			filename,
		},
	};
};

export const templateUploadProcessingSuccess = (
	response: Record<string, unknown>
) => {
	return {
		type: TEMPLATE_UPLOAD_PROCESSING_SUCCESS,
		payload: response,
	};
};

export const templateUploadProcessingFailed = (
	error: Record<string, unknown>
) => {
	return {
		type: TEMPLATE_UPLOAD_PROCESSING_FAILED,
		payload: error,
	};
};

export const clearTemplateUploadProcessing = () => {
	return {
		type: CLEAR_TEMPLATE_UPLOAD_PROCESSING,
	};
};
