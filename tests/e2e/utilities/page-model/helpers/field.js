import { Selector } from 'testcafe'

// Field header title
export function fieldHeaderTitle (text) {
  return Selector('legend').withText(text)
}

// Field label
export function fieldLabel (text, style = 'div') {
  return Selector(style).withText(text)
}

// Select box
export function selectBox (style, id) {
  return Selector('div').find(`[class^="${style}"][id="${id}"]`)
}

// Button
export function button (text) {
  return Selector('button').withText(text)
}

// Dropdown options
export function dropdownOptionGroup (text) {
  return Selector('optgroup').withAttribute('label', text)
}

export function dropdownOption (text) {
  return Selector('option').withText(text)
}

// Item in the list
export function listItem (text) {
  return Selector('li.gform-dropdown__item').find('span').withText(text)
}

// Info text
export function infoText (text, style = 'td') {
  return Selector(style).withText(text)
}

// Template details
export function templateDetails (style, text) {
  return Selector('div').find(`[class^="${style}"]`).withText(`${text}`)
}

// Link
export function link (style, text) {
  return Selector(`${style}`).find('a').withText(text)
}

// User restriction option
export function userRestrictionOption (value) {
  return Selector('.gfpdf-settings-multicheck-wrapper').find(`[value="${value}"]`)
}

export function mediaManagerTitle (text) {
  return Selector('.media-frame-title').withText(`${text}`)
}

// Add media button
export function addMediaButton (id, text) {
  return Selector(`#${id}`).find('button').withText(`${text}`)
}

// Merge tags
export function mergeTagsWrapper (id) {
  return Selector(`div#${id}`).find('ul.gform-dropdown__list')
}

// Merge tags for password
export function passwordGroupOption (text) {
  return mergeTagsWrapper('gfpdf-settings-field-wrapper-password').find('li.gform-dropdown__group').withText(`${text}`)
}

export function passwordOptionItem (text) {
  return mergeTagsWrapper('gfpdf-settings-field-wrapper-password').find('li').find('button').withText(`${text}`)
}

// Merge tags for filename
export function filenameGroupOption (text) {
  return mergeTagsWrapper('gfpdf-settings-field-wrapper-filename').find('li.gform-dropdown__group').withText(`${text}`)
}

export function filenameOptionItem (text) {
  return mergeTagsWrapper('gfpdf-settings-field-wrapper-filename').find('li').find('button').withText(`${text}`)
}
