/* Dependencies */
import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import { useSelector, useDispatch } from 'react-redux';
import { useNavigate, useLocation } from 'react-router-dom';
/* Components */
import { CoreFontListResults } from './CoreFontListResults';
import Counter from './CoreFontCounter';
import Spinner from '../Spinner';
/* Redux actions */
import {
	clearButtonClickedAndRetryList,
	addToConsole,
	getFilesFromGitHub,
	downloadFontsApiCall,
	clearRequestRemainingData,
	clearConsole,
} from '../../actions/coreFonts';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Handles the grunt work for our Core Font downloader (API calls, display, state ect)
 *
 * @param {Object} root0
 * @param {*}      root0.buttonClassName
 * @param {*}      root0.buttonText
 * @param {*}      root0.counterText
 * @param {*}      root0.retryText
 * @since 5.0
 */
const CoreFontContainer = ({
	buttonClassName,
	buttonText,
	counterText,
	retryText,
}) => {
	const navigate = useNavigate();
	const location = useLocation();
	const dispatch = useDispatch();

	const buttonClicked = useSelector((state) => state.coreFonts.buttonClicked);
	const fontList = useSelector((state) => state.coreFonts.fontList);
	const getFilesFromGitHubFailed = useSelector(
		(state) => state.coreFonts.getFilesFromGitHubFailed
	);
	const consoleList = useSelector((state) => state.coreFonts.console);
	const retry = useSelector((state) => state.coreFonts.retry);
	const requestDownload = useSelector(
		(state) => state.coreFonts.requestDownload
	);
	const queue = useSelector((state) => state.coreFonts.downloadCounter);

	const [ajax, setAjax] = useState(false);

	const handleGithubApiError = (error) => {
		setAjax(false);
		dispatch(addToConsole('completed', 'error', error));
		navigate('/');
	};

	const startDownloadFonts = (files, error) => {
		if (files.length === 0) {
			dispatch(clearButtonClickedAndRetryList());
			return handleGithubApiError(error);
		}

		dispatch(clearConsole());
		dispatch(clearButtonClickedAndRetryList());
		navigate('/');

		setTimeout(
			() => files.forEach((file) => dispatch(downloadFontsApiCall(file))),
			300
		);
	};

	const maybeStartDownload = (loc, files, error = null) => {
		if (loc === '/downloadCoreFonts') {
			startDownloadFonts(files, error);
		}

		if (loc === '/retryDownloadCoreFonts') {
			setAjax(true);
			startDownloadFonts(files, error);
		}
	};

	const handleTriggerFontDownload = () => {
		if (!ajax) {
			setAjax(true);
			dispatch(getFilesFromGitHub());
		}
	};

	const resetState = () => {
		setAjax(false);
		dispatch(clearRequestRemainingData());
		navigate('/');
	};

	/* Check for /downloadCoreFonts redirect URL and run the installer */
	useEffect(() => {
		if (location.pathname === '/downloadCoreFonts') {
			handleTriggerFontDownload();
		}
	}, [location.pathname]); // eslint-disable-line react-hooks/exhaustive-deps

	/* Load current font list when fontList and buttonClicked are both available */
	useEffect(() => {
		if (fontList.length > 0 && buttonClicked) {
			startDownloadFonts(fontList);
		}
	}, [fontList, buttonClicked]); // eslint-disable-line react-hooks/exhaustive-deps

	/* Load current hash history location & retry font list */
	useEffect(() => {
		if (location.pathname === '/retryDownloadCoreFonts') {
			maybeStartDownload(location.pathname, retry);
		}
	}, [location.pathname, retry]); // eslint-disable-line react-hooks/exhaustive-deps

	/* Load error if something went wrong */
	useEffect(() => {
		if (getFilesFromGitHubFailed !== '' && buttonClicked) {
			startDownloadFonts(fontList, getFilesFromGitHubFailed);
		}
	}, [getFilesFromGitHubFailed, buttonClicked]); // eslint-disable-line react-hooks/exhaustive-deps

	/* If request download is finished, call resetState */
	useEffect(() => {
		if (requestDownload === 'finished') {
			resetState();
		}
	}, [requestDownload]); // eslint-disable-line react-hooks/exhaustive-deps

	const disabled = (queue < fontList.length && queue !== 0) || ajax;

	return (
		<div data-test="component-coreFont-downloader">
			<button
				data-test="component-coreFont-button"
				className={buttonClassName}
				type="button"
				onClick={handleTriggerFontDownload}
				disabled={disabled}
			>
				{buttonText}
			</button>
			{ajax && <Spinner />}
			{ajax && queue !== 0 && (
				<Counter text={counterText} queue={queue} />
			)}
			<CoreFontListResults
				console={consoleList}
				retry={retry}
				retryText={retryText}
			/>
		</div>
	);
};

CoreFontContainer.propTypes = {
	buttonClassName: PropTypes.string,
	buttonText: PropTypes.string,
	counterText: PropTypes.string,
	retryText: PropTypes.string,
};

export default CoreFontContainer;
