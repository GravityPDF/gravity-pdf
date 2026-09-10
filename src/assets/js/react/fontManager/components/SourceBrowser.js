/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __, _n, sprintf } from '@wordpress/i18n';
import {
	Button,
	Notice,
	SelectControl,
	Spinner,
	TabPanel,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { useDebounce } from '@wordpress/compose';
import { PER_PAGE, STORE_NAME } from '../constants';
import { filterLabel } from '../utils/format';
import { paths } from '../utils/paths';
import DetailLoading from './DetailLoading';
import EmptyState from './EmptyState';
import FamilyCard from './FamilyCard';
import SearchBox from './SearchBox';
import SourceInfoTip from './SourceInfoTip';

/**
 * The browser, over any source
 *
 * Nothing here names a source: the tabs come from `GET /fonts/sources`, the filters from each source's own
 * `filters`, and the list from one paginated search. Google's ~1,800 families are why the search is the
 * server's and why the Language filter exists at all.
 *
 * @param {Object}   props
 * @param {string}   props.source   The source being browsed
 * @param {Function} props.navigate
 *
 * @return {JSX.Element} The browser
 *
 * @since 7.0
 */
export default function SourceBrowser({ source, navigate }) {
	const [term, setTerm] = useState('');
	const [search, setSearch] = useState('');
	const [category, setCategory] = useState('');
	const [subset, setSubset] = useState('');
	const [page, setPage] = useState(1);

	/* The field answers every keystroke; the request waits for the typing to stop */
	const settle = useDebounce(setSearch, 300);
	const onSearch = (value) => {
		setTerm(value);
		settle(value);
	};

	const record = useSelect(
		(select) => select(STORE_NAME).getSource(source),
		[source]
	);
	const sources = useSelect(
		(select) => select(STORE_NAME).getSources() ?? [],
		[]
	);

	useEffect(() => {
		setPage(1);
	}, [search, category, subset, source]);

	useEffect(() => {
		setTerm('');
		setSearch('');
		setCategory('');
		setSubset('');
	}, [source]);

	/* `/fontmanager/browse` with no source is the sentinel option's destination: send it to the first one */
	useEffect(() => {
		if (!source && sources.length > 0) {
			navigate(paths.browse(sources[0].id));
		}
	}, [source, sources, navigate]);

	const query = {
		s: search,
		category,
		subset,
		coverage: record?.coverage ? '1' : '',
		page: String(page),
	};

	const results = useSelect(
		(select) =>
			record ? select(STORE_NAME).getSourceEntries(source, query) : null,
		/* `query` is rebuilt on every render; its fields are the real dependencies */
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[source, search, category, subset, page, record?.coverage]
	);

	const statuses = useSelect(
		(select) => select(STORE_NAME).getStatuses(),
		[]
	);

	const { installEntry, refreshSources } = useDispatch(STORE_NAME);

	if (!record) {
		return <DetailLoading />;
	}

	const tabs = sources.map((item) => ({
		name: item.id,
		title: item.label,
	}));

	return (
		<section className="fm-detail">
			<div className="fm-detail-inner">
				<div className="gfpdf-fm-source-switch">
					{tabs.length <= 3 ? (
						<TabPanel
							className="gfpdf-fm-source-tabs"
							tabs={tabs}
							initialTabName={source}
							onSelect={(name) =>
								name !== source && navigate(paths.browse(name))
							}
						>
							{() => null}
						</TabPanel>
					) : (
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={__('Source', 'gravity-pdf')}
							value={source}
							options={tabs.map((tab) => ({
								label: tab.title,
								value: tab.name,
							}))}
							onChange={(name) => navigate(paths.browse(name))}
						/>
					)}

					<SourceInfoTip source={record} />
				</div>

				{!record.synced ? (
					<NotDownloaded record={record} onRefresh={refreshSources} />
				) : (
					<Catalogue
						record={record}
						results={results}
						statuses={statuses}
						term={term}
						onSearch={onSearch}
						category={category}
						setCategory={setCategory}
						subset={subset}
						setSubset={setSubset}
						page={page}
						setPage={setPage}
						navigate={navigate}
						onInstall={installEntry}
					/>
				)}
			</div>
		</section>
	);
}

/**
 * Everything below the source switch, once the catalogue is downloaded
 *
 * Its own component because the alternative was four nested ternaries and a closing stack nobody could read.
 *
 * @param {Object}   props
 *
 * @param {Object}   props.record      The source record
 * @param {?Object}  props.results     The page the store holds, or null while it loads
 * @param {Object}   props.statuses    The status map
 * @param {string}   props.term        What is in the search field
 * @param {Function} props.onSearch
 * @param {string}   props.category
 * @param {Function} props.setCategory
 * @param {string}   props.subset
 * @param {Function} props.setSubset
 * @param {number}   props.page
 * @param {Function} props.setPage
 * @param {Function} props.navigate
 * @param {Function} props.onInstall
 * @return {JSX.Element} The toolbar, the pager and the cards
 *
 * @since 7.0
 */
function Catalogue({
	record,
	results,
	statuses,
	term,
	onSearch,
	category,
	setCategory,
	subset,
	setSubset,
	page,
	setPage,
	navigate,
	onInstall,
}) {
	const filtered = category || subset;

	return (
		<>
			{(record.stale || record.last_error) && (
				<Notice
					status={record.stale ? 'warning' : 'info'}
					isDismissible={false}
				>
					{record.last_error ||
						__(
							'This catalogue has not been refreshed in a while.',
							'gravity-pdf'
						)}
				</Notice>
			)}

			<div className="gfpdf-fm-browser-toolbar">
				<SearchBox
					value={term}
					onChange={onSearch}
					label={__('Search the catalogue', 'gravity-pdf')}
					placeholder={__('Search this catalogue…', 'gravity-pdf')}
				/>

				{record.filters.category.length > 0 && (
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={__('Category', 'gravity-pdf')}
						value={category}
						options={options(
							record.filters.category,
							__('All categories', 'gravity-pdf')
						)}
						onChange={setCategory}
					/>
				)}

				<SelectControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={__('Language', 'gravity-pdf')}
					value={subset}
					options={options(
						record.filters.subsets,
						__('All languages', 'gravity-pdf')
					)}
					onChange={setSubset}
				/>
			</div>

			{!results ? (
				<Spinner />
			) : (
				<>
					<div className="gfpdf-fm-browser-pager">
						<span>
							{pagerText(results, page, category, subset)}
						</span>

						{filtered && (
							<Button
								variant="link"
								onClick={() => {
									setCategory('');
									setSubset('');
								}}
							>
								{__('Clear filters', 'gravity-pdf')}
							</Button>
						)}
					</div>

					{results.entries.length === 0 ? (
						<p className="fm-empty-text">
							{__(
								'No families match. Try a different search, or clear the filters.',
								'gravity-pdf'
							)}
						</p>
					) : (
						<div className="gfpdf-fm-cards">
							{results.entries.map((entry) => (
								<FamilyCard
									key={entry.entry}
									entry={entry}
									status={
										statuses[
											`${entry.source}/${entry.entry}`
										]
									}
									onOpen={() =>
										navigate(
											paths.entry(
												entry.source,
												entry.entry
											)
										)
									}
									onInstall={() =>
										onInstall(entry.source, entry.entry)
									}
								/>
							))}
						</div>
					)}

					{results.pages > 1 && (
						<div className="gfpdf-fm-pagination">
							<Button
								variant="secondary"
								disabled={page === 1}
								onClick={() => setPage(page - 1)}
							>
								{__('Previous', 'gravity-pdf')}
							</Button>
							<Button
								variant="secondary"
								disabled={page >= results.pages}
								onClick={() => setPage(page + 1)}
							>
								{__('Next', 'gravity-pdf')}
							</Button>
						</div>
					)}
				</>
			)}
		</>
	);
}

function NotDownloaded({ record, onRefresh }) {
	return (
		<EmptyState
			plain
			text={sprintf(
				/* translators: %s: the name of a font source, e.g. "Google Fonts" */
				__(
					"The %s catalogue hasn't been downloaded yet. Refresh to get it from Gravity PDF.",
					'gravity-pdf'
				),
				record.label
			)}
		>
			{record.last_error && (
				<Notice status="warning" isDismissible={false}>
					{record.last_error}
				</Notice>
			)}

			<Button variant="primary" onClick={() => onRefresh()}>
				{__('Refresh', 'gravity-pdf')}
			</Button>
		</EmptyState>
	);
}

function options(filters, allLabel) {
	return [
		{ label: allLabel, value: '' },
		...filters.map((filter) => ({
			label: `${filterLabel(filter.id)} (${filter.count})`,
			value: filter.id,
		})),
	];
}

function pagerText(results, page, category, subset) {
	const first = results.total === 0 ? 0 : (page - 1) * PER_PAGE + 1;
	const last = Math.min(results.total, page * PER_PAGE);

	const showing = sprintf(
		/* translators: 1: first row shown, 2: last row shown, 3: how many match */
		_n(
			'Showing %1$d–%2$d of %3$d family',
			'Showing %1$d–%2$d of %3$d families',
			results.total,
			'gravity-pdf'
		),
		first,
		last,
		results.total
	);

	const active = [category, subset].filter(Boolean).map(filterLabel);

	return active.length ? `${showing} · ${active.join(' · ')}` : showing;
}
