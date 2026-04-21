/* Dependencies */
import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
/* Components */
import { CoreFontListResults } from './CoreFontListResults';
import Counter from './CoreFontCounter';
import Spinner from '../Spinner';
/* Redux */
import { useAppSelector, useAppDispatch } from '../../store/hooks';
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

interface Props {
	buttonClassName?: string;
	buttonText?: string;
	counterText?: string;
	retryText?: string;
}

const CoreFontContainer = ({
	buttonClassName,
	buttonText,
	counterText,
	retryText,
}: Props) => {
	const navigate = useNavigate();
	const location = useLocation();
	const dispatch = useAppDispatch();

	const buttonClicked = useAppSelector(
		(state) => state.coreFonts.buttonClicked
	);
	const fontList = useAppSelector((state) => state.coreFonts.fontList);
	const getFilesFromGitHubFailed = useAppSelector(
		(state) => state.coreFonts.getFilesFromGitHubFailed
	);
	const consoleList = useAppSelector((state) => state.coreFonts.console);
	const retry = useAppSelector((state) => state.coreFonts.retry);
	const requestDownload = useAppSelector(
		(state) => state.coreFonts.requestDownload
	);
	const queue = useAppSelector((state) => state.coreFonts.downloadCounter);

	const [ajax, setAjax] = useState(false);

	const handleGithubApiError = (error: string) => {
		setAjax(false);
		dispatch(addToConsole('completed', 'error', error));
		navigate('/');
	};

	const startDownloadFonts = (
		files: string[],
		error: string | null = null
	) => {
		if (files.length === 0) {
			dispatch(clearButtonClickedAndRetryList());
			return handleGithubApiError(error ?? '');
		}

		dispatch(clearConsole());
		dispatch(clearButtonClickedAndRetryList());
		navigate('/');

		setTimeout(
			() => files.forEach((file) => dispatch(downloadFontsApiCall(file))),
			300
		);
	};

	const maybeStartDownload = (
		loc: string,
		files: string[],
		error: string | null = null
	) => {
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

export default CoreFontContainer;
