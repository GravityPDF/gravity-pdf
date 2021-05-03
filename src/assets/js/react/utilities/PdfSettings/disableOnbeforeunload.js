export default function disableOnbeforeunload (form) {
  /* Disable onbeforeunload event during form submission */
  form.onsubmit = () => {
    window.onbeforeunload = null

    return window
  }
}
