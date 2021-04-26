export default function checkFormUnsavedChanges (form) {
  form.addEventListener('change', () => {
    window.onbeforeunload = () => ''

    return window
  })
}
