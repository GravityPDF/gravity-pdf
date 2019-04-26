import $ from 'jquery'

/**
 * Rich Media Uploader
 * JS Pulled straight from Easy Digital Download's admin-scripts.js
 * @return void
 * @since 4.0
 */
export function doUploadListener () {
  // WP 3.5+ uploader
  let fileFrame
  window.formfield = ''

  $('body').off('click', '.gfpdf_settings_upload_button').on('click', '.gfpdf_settings_upload_button', function (e) {
    e.preventDefault()

    let $button = $(this)
    window.formfield = $(this).parent().prev()

    /* If the media frame already exists, reopen it. */
    if (fileFrame) {
      fileFrame.open()
      return
    }

    /* Create the media frame. */
    fileFrame = wp.media.frames.file_frame = wp.media({
      title: $button.data('uploader-title'),
      button: {
        text: $button.data('uploader-button-text')
      },
      multiple: false
    })

    /* When a file is selected, run a callback. */
    fileFrame.on('select', function () {
      let selection = fileFrame.state().get('selection')
      selection.each(function (attachment, index) {
        attachment = attachment.toJSON()
        window.formfield.val(attachment.url).change()
      })
    })

    /* Finally, open the modal */
    fileFrame.open()
  })
}
