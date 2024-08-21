/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Track form changes and display an alert if the user has unsaved changes
 * when navigating away from that form
 *
 * @param {Element} form
 */
export default function (form) {
  const beforeUnloadHandler = e => e.preventDefault()

  /* Add alert if form is modified but not saved */
  form.addEventListener('change', () => {
    window.addEventListener('beforeunload', beforeUnloadHandler)
  })

  /* Remove alert when doing form submission with a valid submitter (and not a simulated submit) */
  form.addEventListener('submit', (e) => {
    if (e.submitter === null) {
      return
    }

    window.removeEventListener('beforeunload', beforeUnloadHandler)
  })
}
