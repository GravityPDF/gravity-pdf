/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import {
	Button,
	DropZone,
	ExternalLink,
	FormFileUpload,
	Modal,
	Notice,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { DOCS_OFFLINE_INSTALL, STORE_NAME } from '../constants';
import { fileSize } from '../utils/format';

/**
 * "Install from file": one package, uploaded, and what to do when it cannot be
 *
 * Reached from all three surfaces §4.6 names — the Add menu, the packs view and an entry's page — and the same
 * dialog from each, because the archive names the font it installs. There is nothing to pick here and no way to
 * aim it: opening it from the Japanese pack and choosing the Korean archive installs Korean, and saying so is
 * cheaper than a per-entry variant that would have to lie about what the route accepts.
 *
 * @param {Object}   props
 * @param {Function} props.onClose
 * @param {Function} props.onInstalling Called with the `{source}/{entry}` now installing
 *
 * @return {JSX.Element} The dialog
 *
 * @since 7.0
 */
export default function ImportDialog({ onClose, onInstalling }) {
	const [file, setFile] = useState(null);

	const { busy, error, code } = useSelect((select) => {
		const store = select(STORE_NAME);

		return {
			busy: store.isBusy('import'),
			error: store.getError('import'),
			code: store.getErrorCode('import'),
		};
	}, []);

	const { importPackage, setError } = useDispatch(STORE_NAME);

	/* The error map outlives the dialog, and a refusal belongs to the archive it was about — so it is cleared
	   when this one opens, and again the moment another file is picked */
	useEffect(() => {
		setError('import', '');
	}, [setError]);

	const choose = (chosen) => {
		if (!chosen) {
			return;
		}

		setError('import', '');
		setFile(chosen);
	};

	const install = async () => {
		const id = await importPackage(file);

		if (id) {
			onInstalling(id);
		}
	};

	return (
		<Modal
			title={__('Install from file', 'gravity-pdf')}
			onRequestClose={onClose}
			className="gfpdf-fm-import"
			size="medium"
		>
			<p>
				{__(
					'Install a language pack from a package downloaded on another machine — for a site that cannot reach the font server itself. The package names the font it installs, and every file in it is checked against the catalogue before anything is written.',
					'gravity-pdf'
				)}
			</p>

			{error && (
				<Notice status="error" isDismissible={false}>
					<p>{error}</p>

					{UNUPLOADABLE.includes(code) && (
						<p>
							{__(
								'No package will upload on this site until that is fixed. The guide below covers the offline routes that need no upload at all.',
								'gravity-pdf'
							)}
						</p>
					)}
				</Notice>
			)}

			<div className="gfpdf-fm-import-drop">
				<DropZone onFilesDrop={(files) => choose(files[0])} />

				<p className="gfpdf-fm-import-file">
					{file
						? `${file.name} · ${fileSize(file.size)}`
						: __(
								'Drop a font package here, or choose one.',
								'gravity-pdf'
							)}
				</p>

				{/* Cleared after every pick, so choosing the same archive again still fires a change */}
				<FormFileUpload
					__next40pxDefaultSize
					variant="secondary"
					accept=".zip,application/zip"
					onChange={(event) => {
						choose(event.target.files?.[0]);

						event.target.value = '';
					}}
				>
					{file
						? __('Choose a different file', 'gravity-pdf')
						: __('Choose a file', 'gravity-pdf')}
				</FormFileUpload>
			</div>

			<div className="gfpdf-fm-import-actions">
				<ExternalLink href={DOCS_OFFLINE_INSTALL}>
					{__('Ways to install fonts offline', 'gravity-pdf')}
				</ExternalLink>

				<Button variant="tertiary" onClick={onClose}>
					{__('Cancel', 'gravity-pdf')}
				</Button>

				<Button
					variant="primary"
					isBusy={busy}
					disabled={busy || !file}
					onClick={install}
				>
					{__('Install', 'gravity-pdf')}
				</Button>
			</div>
		</Modal>
	);
}

/**
 * The two failures a different file will not fix
 *
 * One is the host's upload cap and the other is a missing `ZipArchive`, and they share the only advice worth
 * giving: stop uploading. Every other code is about this archive, and re-picking is the answer.
 *
 * @since 7.0
 */
const UNUPLOADABLE = ['font_package_too_large', 'font_package_unsupported'];
