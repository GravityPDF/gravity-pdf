/**
 * Update the URL parameter
 * @param { string } url      - The URL to parse
 * @param { string } param    - The URL parameter to want to update
 * @param { string } paramVal - The replacement string for the URL parameter
 *
 * @return { string }   The processed URL
 *
 * @since 4.0
 *
 * {@link http://stackoverflow.com/a/10997390/11236 Link}
 */
export function updateURLParameter(url, param, paramVal) {
	let newAdditionalURL = '';
	let tempArray = url.split('?');
	const baseURL = tempArray[0];
	const additionalURL = tempArray[1];
	let temp = '';
	if (additionalURL) {
		tempArray = additionalURL.split('&');
		for (let i = 0; i < tempArray.length; i++) {
			if (tempArray[i].split('=')[0] !== param) {
				newAdditionalURL += temp + tempArray[i];
				temp = '&';
			}
		}
	}

	const rowsTxt = temp + '' + param + '=' + paramVal;
	return baseURL + '?' + newAdditionalURL + rowsTxt;
}
