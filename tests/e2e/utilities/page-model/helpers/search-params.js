import { ClientFunction } from 'testcafe';

/**
 * Provides the ability to use Client APIs specifically url search params
 *
 * @param { string } param
 *
 * @return { string } param value
 */
export const getQueryParam = ClientFunction( ( param ) => {
	const urlParams = new URLSearchParams( window.location.search );
	return urlParams.get( param );
} );
