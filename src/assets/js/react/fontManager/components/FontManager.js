/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import { Button, Modal, SnackbarList } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { STORE_NAME } from '../constants';
import { useHashRoute } from '../hooks/useHashRoute';
import { useInstallPoller } from '../hooks/useInstallPoller';
import { paths } from '../utils/paths';
import BundledDetail from './BundledDetail';
import CustomFontDetail from './CustomFontDetail';
import EmptyState from './EmptyState';
import EntryDetail from './EntryDetail';
import FontManagerHeader from './FontManagerHeader';
import FontSidebar from './FontSidebar';
import ImportDialog from './ImportDialog';
import LanguageSettings from './LanguageSettings';
import SourceBrowser from './SourceBrowser';
import UpdatesPanel from './UpdatesPanel';

/**
 * The modal, and the one place the route decides what the right-hand pane is
 *
 * The shell owns three things nothing else does: the poller that keeps every progress reading in the modal in
 * agreement, the resolution of `GET /fonts/` and `GET /fonts/sources` (so no pane races a resolver of its
 * own), and the association with the font select the modal was opened from.
 *
 * @param {Object}    props
 * @param {?Function} props.onActive Sets the associated select's value, when there is one
 *
 * @return {?JSX.Element} The modal, or null while it is closed
 *
 * @since 7.0
 */
export default function FontManager({ onActive = null }) {
	const { route, navigate, close } = useHashRoute();

	/*
	 * The shell is mounted on every screen carrying a font field, so nothing that costs a request may live
	 * above this line: reading `getFonts()` or arming the poller here had the closed modal fetching the font
	 * list and the status map on every PDF settings page load.
	 */
	if (!route) {
		return null;
	}

	return (
		<FontManagerShell
			route={route}
			navigate={navigate}
			close={close}
			onActive={onActive}
		/>
	);
}

function FontManagerShell({ route, navigate, close, onActive }) {
	const [adding, setAdding] = useState(false);
	const [importing, setImporting] = useState(false);

	/* Selected here, not primed here: the registry selectors resolve their own reads on first use */
	const row = useSelect(
		(select) =>
			route?.name === 'font'
				? select(STORE_NAME).getRow(route.params.id)
				: null,
		[route?.name, route?.params?.id]
	);

	const loaded = useSelect((select) => !!select(STORE_NAME).getFonts(), []);

	const notices = useSelect(
		(select) => select(noticesStore).getNotices(),
		[]
	);
	const { removeNotice } = useDispatch(noticesStore);
	const { setActiveFont } = useDispatch(STORE_NAME);

	useInstallPoller();

	const setActive = (id) => {
		setActiveFont(id);

		if (onActive) {
			onActive(id);
		}
	};

	const back = () => {
		setAdding(false);
		navigate(paths.home());
	};

	const openImport = () => setImporting(true);

	return (
		<Modal
			/* `contentLabel` rather than `title`: with the header hidden, a `title` leaves the dialog
			   pointing `aria-labelledby` at an element that was never rendered */
			contentLabel={__('Font manager', 'gravity-pdf')}
			onRequestClose={close}
			className="gfpdf-fm"
			overlayClassName="gfpdf-fm-overlay"
			__experimentalHideHeader
			isFullScreen={false}
		>
			<div className="gfpdf-fm-shell">
				<FontManagerHeader navigate={navigate} onClose={close} />

				<div
					className="fm-body"
					data-mobile-view={
						route.name === 'home' && !adding ? 'list' : 'detail'
					}
				>
					<FontSidebar
						route={route}
						navigate={navigate}
						adding={adding}
						onUpload={() => {
							setAdding(true);
							navigate(paths.home());
						}}
						onImport={openImport}
					/>

					{loaded &&
						detail({
							route,
							row,
							adding,
							navigate,
							back,
							setAdding,
							setActive,
							onImport: openImport,
							hasSelect: !!onActive,
						})}
				</div>
			</div>

			{importing && (
				<ImportDialog
					onClose={() => setImporting(false)}
					onInstalling={(id) => {
						const [source, entry] = id.split('/');

						setImporting(false);
						navigate(paths.entry(source, entry));
					}}
				/>
			)}

			<SnackbarList
				className="gfpdf-fm-snackbars"
				notices={notices}
				onRemove={removeNotice}
			/>
		</Modal>
	);
}

function detail({
	route,
	row,
	adding,
	navigate,
	back,
	setAdding,
	setActive,
	onImport,
	hasSelect,
}) {
	const shared = {
		onBack: back,
		navigate,
		hasSelect,
		onSetActive: setActive,
		onImport,
	};

	if (route.name === 'settings') {
		return <LanguageSettings onBack={back} />;
	}

	if (route.name === 'updates') {
		return <UpdatesPanel onBack={back} />;
	}

	if (route.name === 'browse') {
		return (
			<SourceBrowser
				source={route.params.source}
				navigate={navigate}
				onImport={onImport}
			/>
		);
	}

	if (route.name === 'entry') {
		return (
			<EntryDetail
				source={route.params.source}
				entry={route.params.entry}
				row={null}
				{...shared}
			/>
		);
	}

	/* A row of a display entry is one install of it, so both modes are the same panel */
	if (route.name === 'font') {
		if (!row) {
			return (
				<EmptyState
					text={__('This font is no longer available', 'gravity-pdf')}
				>
					<Button variant="secondary" onClick={back}>
						{__('Back to the font list', 'gravity-pdf')}
					</Button>
				</EmptyState>
			);
		}

		if (row.kind === 'bundled') {
			return <BundledDetail row={row} {...shared} />;
		}

		if (row.kind === 'entry') {
			return (
				<EntryDetail
					source={row.source}
					entry={row.entry}
					row={row}
					{...shared}
				/>
			);
		}
	}

	if (route.name !== 'font' && !adding) {
		return (
			<EmptyState
				glyph="Aa"
				text={__(
					'Select a font from the list to edit it',
					'gravity-pdf'
				)}
			>
				<Button variant="secondary" onClick={() => setAdding(true)}>
					{__('Add new font', 'gravity-pdf')}
				</Button>
			</EmptyState>
		);
	}

	return (
		<CustomFontDetail
			row={route.name === 'font' ? row : null}
			onBack={back}
			onDone={(id) => {
				setAdding(false);
				navigate(id ? paths.font(id) : paths.home());
			}}
			hasSelect={hasSelect}
			onSetActive={setActive}
		/>
	);
}
