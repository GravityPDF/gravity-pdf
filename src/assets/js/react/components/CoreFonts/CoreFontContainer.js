/* Dependencies */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
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
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Handles the grunt work for our Core Font downloader (API calls, display, state ect)
 *
 * @since 5.0
 */
export class CoreFontContainer extends Component {
	/**
	 *
	 * @since 5.0
	 */
	static propTypes = {
		location: PropTypes.object,
		requestDownload: PropTypes.string,
		clearRequestRemainingData: PropTypes.func,
		getFilesFromGitHub: PropTypes.func,
		buttonClicked: PropTypes.bool,
		fontList: PropTypes.array,
		getFilesFromGitHubFailed: PropTypes.string,
		retry: PropTypes.array,
		clearConsole: PropTypes.func,
		navigate: PropTypes.func,
		clearButtonClickedAndRetryList: PropTypes.func,
		downloadFontsApiCall: PropTypes.func,
		addToConsole: PropTypes.func,
		console: PropTypes.object,
		buttonClassName: PropTypes.string,
		buttonText: PropTypes.string,
		counterText: PropTypes.string,
		retryText: PropTypes.string,
		queue: PropTypes.number,
	};

	/**
	 * Switches to show loaders
	 *
	 * @type {{ajax: boolean}}
	 *
	 * @since 5.0
	 */
	state = {
		ajax: false,
	};

	/**
	 * If component did update and new props are received we'll check if the font list should be loaded
	 *
	 * @param { Readonly<Object> } prevProps
	 *
	 * @since 5.0
	 */
	componentDidUpdate(prevProps) {
		const {
			fontList,
			buttonClicked,
			location,
			retry,
			getFilesFromGitHubFailed,
			requestDownload,
		} = this.props;

		/* Load current font list */
		if (fontList.length > 0 && buttonClicked) {
			this.startDownloadFonts(fontList);
		}

		/* Check for /downloadCoreFonts redirect URL and run the installer */
		if (
			location.pathname === '/downloadCoreFonts' &&
			prevProps?.location.pathname !== location.pathname
		) {
			this.handleTriggerFontDownload();
		}

		/* Load current hash history location & retry font list */
		if (location.pathname === '/retryDownloadCoreFonts') {
			this.maybeStartDownload(location.pathname, retry);
		}

		/* Load error if something went wrong */
		if (getFilesFromGitHubFailed !== '' && buttonClicked) {
			this.startDownloadFonts(fontList, getFilesFromGitHubFailed);
		}

		/* If request download is finished, call resetState function */
		if (requestDownload === 'finished') {
			this.resetState();
		}
	}

	/**
	 * Check for /downloadCoreFonts redirect URL and run the installer
	 *
	 * @since 5.0
	 */
	componentDidMount() {
		if (this.props.location.pathname === '/downloadCoreFonts') {
			this.handleTriggerFontDownload();
		}
	}

	/**
	 * If the Hash History matches our keys (and not already loading) start the download
	 *
	 * @param { string }        location
	 * @param { Array<Object> } fontList
	 * @param { Object= }       error
	 *
	 * @since 5.0
	 */
	maybeStartDownload = (location, fontList, error = null) => {
		if (location === '/downloadCoreFonts') {
			this.startDownloadFonts(fontList, error);
		}

		if (location === '/retryDownloadCoreFonts') {
			this.setState({ ajax: true });
			this.startDownloadFonts(fontList, error);
		}
	};

	/**
	 * Call our server to download the fonts in batches of 5
	 *
	 * @param { Array<Object> } files The font files to download (usually passed in from the 'retry' prop)
	 * @param { Object }        error
	 *
	 * @since 5.0
	 */
	startDownloadFonts = (files, error) => {
		if (files.length === 0) {
			this.props.clearButtonClickedAndRetryList();

			return this.handleGithubApiError(error);
		}

		this.props.clearConsole();
		this.props.clearButtonClickedAndRetryList();

		/* Clean Hash History */
		this.props.navigate('/');

		setTimeout(
			() => files.map((file) => this.props.downloadFontsApiCall(file)),
			300
		);
	};

	/**
	 * Add a GitHub API overall status to the console
	 *
	 * @param { Object } error
	 *
	 * @since 5.0
	 */
	handleGithubApiError = (error) => {
		this.setState({ ajax: false });
		this.props.addToConsole('completed', 'error', error);
		this.props.navigate('/');
	};

	/**
	 * Request GitHub for font names & trigger font download
	 *
	 * @since 5.0
	 */
	handleTriggerFontDownload = () => {
		if (this.state.ajax === false) {
			/* Get the font names from GitHub we need to download */
			this.setState({ ajax: true }, () => {
				this.props.getFilesFromGitHub();
			});
		}
	};

	/**
	 * Reset ajax/loading state to false
	 *
	 * @since 5.0
	 */
	resetState = () => {
		const { clearRequestRemainingData: clear, navigate } = this.props;

		this.setState({ ajax: false });
		clear();
		navigate('/');
	};

	/**
	 * Renders our Core Font downloader UI
	 *
	 * @return {JSX.Element} CoreFontContainer Component
	 *
	 * @since 5.0
	 */
	render() {
		const { ajax } = this.state;

		const {
			fontList,
			buttonClassName,
			buttonText,
			counterText,
			queue,
			navigate,
			console: consoleList,
			retry,
			retryText,
		} = this.props;

		const disabled = (queue < fontList.length && queue !== 0) || ajax;

		return (
			<div data-test="component-coreFont-downloader">
				<button
					data-test="component-coreFont-button"
					className={buttonClassName}
					type="button"
					onClick={this.handleTriggerFontDownload}
					disabled={disabled}
				>
					{buttonText}
				</button>
				{ajax && <Spinner />}
				{ajax && queue !== 0 && (
					<Counter text={counterText} queue={queue} />
				)}
				<CoreFontListResults
					navigate={navigate}
					console={consoleList}
					retry={retry}
					retryText={retryText}
				/>
			</div>
		);
	}
}

/**
 * Map Redux state to props
 *
 * @param { Object } state
 * @param { Object } state.coreFonts
 *
 * @return {{
 *  buttonClicked: boolean,
 *  fontList: Array<Object>,
 *  getFilesFromGitHubFailed: string,
 *  console: Object,
 *  retry: Array<*>,
 *  requestDownload: string,
 *  queue: boolean
 * }} mapped state
 *
 * @since 5.0
 */
const mapStateToProps = (state) => {
	return {
		buttonClicked: state.coreFonts.buttonClicked,
		fontList: state.coreFonts.fontList,
		getFilesFromGitHubFailed: state.coreFonts.getFilesFromGitHubFailed,
		console: state.coreFonts.console,
		retry: state.coreFonts.retry,
		requestDownload: state.coreFonts.requestDownload,
		queue: state.coreFonts.downloadCounter,
	};
};

/**
 * Map Redux actions to props
 *
 * @return {{
 *  addToConsole,
 *  clearButtonClickedAndRetryList,
 *  getFilesFromGitHub,
 *  downloadFontsApiCall,
 *  clearRequestRemainingData,
 *  clearConsole
 * }} mapped dispatch
 *
 * @since 5.0
 */

export default connect(mapStateToProps, {
	addToConsole,
	clearButtonClickedAndRetryList,
	getFilesFromGitHub,
	downloadFontsApiCall,
	clearRequestRemainingData,
	clearConsole,
})(CoreFontContainer);
