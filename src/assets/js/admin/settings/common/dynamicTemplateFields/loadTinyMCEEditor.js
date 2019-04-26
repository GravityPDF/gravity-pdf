import $ from 'jquery'

/**
 * Initialises AJAX-loaded wp_editor TinyMCE containers for use
 * @param  Array editors  The DOM element IDs to parse
 * @param  Object settings The TinyMCE settings to use
 * @return void
 * @since  4.0
 */
export function loadTinyMCEEditor (editors, settings) {
  if (settings != null) {
    /* Ensure appropriate settings defaults */
    settings.body_class = 'id post-type-post post-status-publish post-format-standard'
    settings.formats = {
      alignleft: [
        { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: { textAlign: 'left' } },
        { selector: 'img,table,dl.wp-caption', classes: 'alignleft' }
      ],
      aligncenter: [
        { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: { textAlign: 'center' } },
        { selector: 'img,table,dl.wp-caption', classes: 'aligncenter' }
      ],
      alignright: [
        { selector: 'p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li', styles: { textAlign: 'right' } },
        { selector: 'img,table,dl.wp-caption', classes: 'alignright' }
      ],
      strikethrough: { inline: 'del' }
    }
    settings.content_style = 'body#tinymce { max-width: 100%; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;}'
  }

  /* Load our new editors */
  $.each(editors, function (index, fullId) {
    /* Setup out selector */
    settings.selector = '#' + fullId

    /* Initialise our editor */
    tinyMCE.init(settings)

    /* Add our editor to the DOM */
    tinyMCE.execCommand('mceAddEditor', false, fullId)

    /* Enable WP quick tags */
    if (typeof (QTags) == 'function') {
      QTags({ 'id': fullId })
      QTags._buttonsInit()

      /* remember last tab selected */
      if (typeof switchEditors.switchto === 'function') {
        switchEditors.switchto(jQuery('#wp-' + fullId + '-wrap').find('.wp-switch-editor.switch-' + (getUserSetting('editor') == 'html' ? 'html' : 'tmce'))[0])
      }
    }
  })
}
