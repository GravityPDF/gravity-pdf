/* Dependencies */
import { useState, useEffect } from '@wordpress/element';
import { useNavigate, useLocation } from 'react-router';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
/* Components */
import { CoreFontListResults } from './CoreFontListResults';
import Counter from './CoreFontCounter';
import Spinner from '../Spinner';
/* Store */
import { useSelect, useDispatch } from '@wordpress/data';
import {
	CORE_FONTS_STORE_NAME,
	coreFontsStore,
} from '../../store/coreFontsStore';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

const CoreFontContainer = () => {
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
		(select) => select(coreFontsStore).getButtonClicked(),
		[]
	);
	const fontList = useSelect(
		(select) => select(coreFontsStore).getFontList(),
		[]
	);
	const getFilesFromGitHubFailed = useSelect(
		(select) => select(coreFontsStore).getFilesFromGitHubFailed(),
		[]
	);
	const consoleList = useSelect(
		(select) => select(coreFontsStore).getConsole(),
		[]
	);
	const retry = useSelect((select) => select(coreFontsStore).getRetry(), []);
	const requestDownload = useSelect(
		(select) => select(coreFontsStore).getRequestDownload(),
		[]
	);
	const queue = useSelect(
		(select) => select(coreFontsStore).getDownloadCounter(),
		[]
	);

	const [isLoading, setIsLoading] = useState(false);

	const startDownloadFonts = async (
		files: string[],
		error: string | null = null
	) => {
		if (files.length === 0) {
			clearButtonClickedAndRetryList();
			setIsLoading(false);
			addToConsole('completed', 'error', error ?? '');
			navigate('/');
			return;
		}

		clearConsole();
		clearButtonClickedAndRetryList();
		navigate('/');

		for (let i = 0; i < files.length; i += 5) {
			await Promise.all(
				files.slice(i, i + 5).map((file) => downloadFonts(file))
			);
		}
	};

	const handleTriggerFontDownload = () => {
		if (!isLoading) {
			setIsLoading(true);
			getFilesFromGitHub();
		}
	};

	const resetState = () => {
		setIsLoading(false);
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
			void startDownloadFonts(fontList);
		}
	}, [fontList, buttonClicked]); // eslint-disable-line react-hooks/exhaustive-deps

	/* Load current hash history location & retry font list */
	useEffect(() => {
		if (
			location.pathname === '/retryDownloadCoreFonts' &&
			retry.length > 0
		) {
			setIsLoading(true);
			void startDownloadFonts(retry);
		}
	}, [location.pathname, retry]); // eslint-disable-line react-hooks/exhaustive-deps

	/* Load error if something went wrong */
	useEffect(() => {
		if (getFilesFromGitHubFailed !== '' && buttonClicked) {
			void startDownloadFonts(fontList, getFilesFromGitHubFailed);
		}
	}, [getFilesFromGitHubFailed, buttonClicked]); // eslint-disable-line react-hooks/exhaustive-deps

	/* If request download is finished, call resetState */
	useEffect(() => {
		if (requestDownload === 'finished') {
			resetState();
		}
	}, [requestDownload]); // eslint-disable-line react-hooks/exhaustive-deps

	const disabled = (queue < fontList.length && queue !== 0) || isLoading;

	return (
		<div data-test="component-coreFont-downloader">
			<Button
				data-test="component-coreFont-button"
				variant="secondary"
				onClick={handleTriggerFontDownload}
				disabled={disabled}
			>
				{__('Download Core Fonts', 'gravity-pdf')}
			</Button>
			{isLoading && <Spinner />}
			{isLoading && queue !== 0 && <Counter queue={queue} />}
			<CoreFontListResults console={consoleList} retry={retry} />
		</div>
	);
};

export default CoreFontContainer;
