import {
	ADD_TO_CONSOLE,
	ADD_TO_RETRY_LIST,
	CLEAR_RETRY_LIST,
	CLEAR_CONSOLE
} from '../actionTypes/coreFonts'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (c) 2019, Blue Liquid Designs

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Found
 */

/**
 * Setup the initial state of the "coreFont" portion of our Redux store
 *
 * @type {{console: {}, retry: Array}}
 *
 * @since 5.0
 */
export const initialState = {
	console: {},
	retry: [],
}

/**
 * The action coreFont reducer which updates our state
 *
 * @param state The current state of our template store
 * @param action The Redux action details being triggered
 *
 * @returns {*} State (whether updated or note)
 *
 * @since 5.0
 */
export default function (state = initialState, action) {
	switch (action.type) {

		/**
		 * @since 5.0
		 */
		case ADD_TO_CONSOLE:
		return {
			...state,
			console: {
				...state.console,
				[action.key]: {
					status: action.status,
					message: action.message,
				}
			}
		}

		/**
		 * @since 5.0
		 */
		case CLEAR_CONSOLE:
		return {
			...state,
			console: {}
		}

		/**
		 * @since 5.0
		 */
		case ADD_TO_RETRY_LIST:
			/* Do not allow the same item in the retry list */
			if (state.retry.includes( action.name )) {
				break
			}

		return {
			...state,
			retry: [
			...state.retry,
			action.name
			]
		}

		/**
		 * @since 5.0
		 */
		case CLEAR_RETRY_LIST:
		return {
			...state,
			retry: [],
		}
	}

	/* None of these actions fired so return state */
	return state
}
