import React from 'react';
import PropTypes from 'prop-types';
import { Snippet } from 'react-instantsearch';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.12.4
 */

/**
 * Display individual search results
 *
 * @param { Object } props
 * @param { Object } props.hit
 *
 * @return {JSX.Element} DisplayResult Component
 *
 * @since 6.12.4
 */
export function DisplayResult({ hit }) {
	return (
		<a href={hit.url} target="_blank" rel="noopener noreferrer">
			<div className="hit-container">
				<div className="hit-icon">
					<svg width="20" height="20" viewBox="0 0 20 20">
						<path
							d="M17 6v12c0 .52-.2 1-1 1H4c-.7 0-1-.33-1-1V2c0-.55.42-1 1-1h8l5 5zM14 8h-3.13c-.51 0-.87-.34-.87-.87V4"
							stroke="currentColor"
							fill="none"
							fillRule="evenodd"
							strokeLinejoin="round"
						/>
					</svg>
				</div>
				<div className="hit-content-wrapper">
					<span className="hit-title">
						<Snippet
							hit={hit}
							attribute={
								hit.type === 'content'
									? hit.type
									: 'hierarchy.' + hit.type
							}
						/>
					</span>
					<span className="hit-path">
						<Snippet hit={hit} attribute="hierarchy.lvl1" />
					</span>
				</div>
				<div className="hit-action">
					<svg
						className="DocSearch-Hit-Select-Icon"
						width="20"
						height="20"
						viewBox="0 0 20 20"
					>
						<g
							stroke="currentColor"
							fill="none"
							fillRule="evenodd"
							strokeLinecap="round"
							strokeLinejoin="round"
						>
							<path d="M18 3v4c0 2-2 4-4 4H2" />
							<path d="M8 17l-6-6 6-6" />
						</g>
					</svg>
				</div>
			</div>
		</a>
	);
}

/**
 * PropTypes
 *
 * @since 6.12.4
 */
DisplayResult.propTypes = {
	hit: PropTypes.shape({
		url: PropTypes.string,
		hierarchy: PropTypes.object,
		type: PropTypes.string,
	}),
};

export default DisplayResult;
