/* Dependencies */
import { useState, useEffect } from '@wordpress/element';
import { useNavigate, useLocation } from 'react-router-dom';
/* Components */
import { CoreFontListResults } from './CoreFontListResults';
import Counter from './CoreFontCounter';
import Spinner from '../Spinner';
/* Store */
import { useSelect, useDispatch } from '@wordpress/data';
import { CORE_FONTS_STORE_NAME } from '../../store/coreFontsStore';

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
	const {
		clearButtonClickedAndRetryList,
		addToConsole,
		getFilesFromGitHub,
		downloadFonts,
		clearRequestRemainingData,
		clearConsole,
	} = useDispatch(CORE_FONTS_STORE_NAME);

	const buttonClicked = useSelect(
		(select) => select(CORE_FONTS_STORE_NAME).getButtonClicked(),
		[]
	);
	const fontList = useSelect(
		(select) => select(CORE_FONTS_STORE_NAME).getFontList(),
		[]
	);
	const getFilesFromGitHubFailed = useSelect(
		(select) => select(CORE_FONTS_STORE_NAME).getFilesFromGitHubFailed(),
		[]
	);
	const consoleList = useSelect(
		(select) => select(CORE_FONTS_STORE_NAME).getConsole(),
		[]
	);
	const retry = useSelect(
		(select) => select(CORE_FONTS_STORE_NAME).getRetry(),
		[]
	);
	const requestDownload = useSelect(
		(select) => select(CORE_FONTS_STORE_NAME).getRequestDownload(),
		[]
	);
	const queue = useSelect(
		(select) => select(CORE_FONTS_STORE_NAME).getDownloadCounter(),
		[]
	);

	const [ajax, setAjax] = useState(false);

	const handleGithubApiError = (error: string) => {
		setAjax(false);
		addToConsole('completed', 'error', error);
		navigate('/');
	};

	const startDownloadFonts = (
		files: string[],
		error: string | null = null
	) => {
		if (files.length === 0) {
			clearButtonClickedAndRetryList();
			return handleGithubApiError(error ?? '');
		}

		clearConsole();
		clearButtonClickedAndRetryList();
		navigate('/');

		setTimeout(
			() => files.forEach((file) => downloadFonts(file)),
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
			getFilesFromGitHub();
		}
	};

	const resetState = () => {
		setAjax(false);
		clearRequestRemainingData();
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
