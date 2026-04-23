/* Dependencies */
import { useState, useEffect } from '@wordpress/element';
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
			return;
		}

		clearConsole();
		clearButtonClickedAndRetryList();

		for (let i = 0; i < files.length; i += 5) {
			await Promise.all(
				files.slice(i, i + 5).map((file) => downloadFonts(file))
			);
		}
	};

	const handleTriggerFontDownload = () => {
		if (isLoading) {
			return;
		}
		setIsLoading(true);
		getFilesFromGitHub();
	};

	const handleRetry = () => {
		if (retry.length === 0) {
			return;
		}
		setIsLoading(true);
		void startDownloadFonts(retry);
	};

	const resetState = () => {
		setIsLoading(false);
		clearRequestRemainingData();
	};

	/* Auto-start download when navigated here from WP backend via hash URL */
	useEffect(() => {
		if (window.location.hash !== '#/downloadCoreFonts') {
			return;
		}
		handleTriggerFontDownload();
	}, []); // eslint-disable-line react-hooks/exhaustive-deps

	/* Load current font list when fontList and buttonClicked are both available */
	useEffect(() => {
		if (fontList.length === 0 || !buttonClicked) {
			return;
		}
		void startDownloadFonts(fontList);
	}, [fontList, buttonClicked]); // eslint-disable-line react-hooks/exhaustive-deps

	/* Load error if something went wrong */
	useEffect(() => {
		if (getFilesFromGitHubFailed === '' || !buttonClicked) {
			return;
		}
		void startDownloadFonts(fontList, getFilesFromGitHubFailed);
	}, [getFilesFromGitHubFailed, buttonClicked]); // eslint-disable-line react-hooks/exhaustive-deps

	/* If request download is finished, call resetState */
	useEffect(() => {
		if (requestDownload !== 'finished') {
			return;
		}
		resetState();
	}, [requestDownload]); // eslint-disable-line react-hooks/exhaustive-deps

	const disabled = (queue < fontList.length && queue !== 0) || isLoading;

	return (
		<div data-test="component-coreFont-downloader">
			<Button
				data-test="component-coreFont-button"
				variant="secondary"
				onClick={handleTriggerFontDownload}
				disabled={disabled}
				accessibleWhenDisabled={true}
				__next40pxDefaultSize={true}
			>
				{__('Download Core Fonts', 'gravity-pdf')}
			</Button>
			{isLoading && <Spinner />}
			{isLoading && queue !== 0 && <Counter queue={queue} />}
			<CoreFontListResults
				console={consoleList}
				retry={retry}
				onRetry={handleRetry}
			/>
		</div>
	);
};

export default CoreFontContainer;
