/* Dependencies */
import React from 'react'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display header of the font list UI
 *
 * @since 6.0
 */
const FontListHeader = () => (
  <div className='font-list-header'>
    <div />
    <div className='font-name'>{GFPDF.fontListInstalledFonts}</div>
    <div>{GFPDF.fontListRegular}</div>
    <div>{GFPDF.fontListItalics}</div>
    <div>{GFPDF.fontListBold}</div>
    <div>{GFPDF.fontListBoldItalics}</div>
  </div>
)

export default FontListHeader
