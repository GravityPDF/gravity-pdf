/**
 * Open the file in a new window
 * @param {File|Blob|MediaSource} file
 */
export function viewFile (file) {
  // Create a link and set the URL using `createObjectURL`
  const link = document.createElement('a')
  link.style.display = 'none'
  link.href = URL.createObjectURL(file)
  link.target = 'gravity-pdf-preview'

  // It needs to be added to the DOM so it can be clicked
  document.body.appendChild(link)
  link.click()

  // To make this work on Firefox we need to wait
  // a little while before removing it.
  setTimeout(() => {
    URL.revokeObjectURL(link.href)
    link.parentNode.removeChild(link)
  }, 0)
}
