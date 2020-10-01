import React from 'react'

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
