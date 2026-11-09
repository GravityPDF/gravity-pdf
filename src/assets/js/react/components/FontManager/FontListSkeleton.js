/* Dependencies */
import React from 'react';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display font list loading skeleton UI
 *
 * @since 6.0
 */
const FontListSkeleton = () => {
	const fontList = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];

	return (
		<div
			data-test="component-FontListSkeleton"
			className="font-list-items-skeleton"
			aria-hidden="true"
		>
			{fontList.map((font) => (
				<div key={font} className="font-list-item">
					<span className="placeholder font-swatch" />
					<span className="font-meta">
						<span className="placeholder font-name" />
						<span className="placeholder font-sub" />
					</span>
				</div>
			))}
		</div>
	);
};

export default FontListSkeleton;
