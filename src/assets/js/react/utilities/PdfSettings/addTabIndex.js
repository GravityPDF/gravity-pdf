export default function addTabIndex (elemMergeTags) {
  Array.from(elemMergeTags).map(elem => {
    const tooltip = elem.querySelector('a.tooltip-merge-tag')

    setTabIndex(tooltip)
    addKeydownListener(elem, tooltip)
  })
}

export function setTabIndex (tooltip) {
  tooltip.tabIndex = 0
}

export function addKeydownListener (elem, tooltip) {
  tooltip.addEventListener('keydown', e => {
    const enter = 13
    const space = 32
    const escape = 27

    /* Display available merge tags option */
    if (e.keyCode === enter || e.keyCode === space) {
      e.preventDefault()

      tooltip.click()
    }

    /* Hide merge tags option */
    if (e.keyCode === escape) {
      elem.querySelector('ul#gf_merge_tag_list').style.display = 'none'
    }
  })
}
