import $ from 'jquery'
import {
  snapshotFormValues,
  restoreFormValues
} from '../../../../../../src/assets/js/admin/settings/common/dynamicTemplateFields/formValuesSnapshot'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.14.3
 */

/* Build a jQuery-wrapped container from raw HTML for use as $container */
function mountContainer (html) {
  const $container = $('<div id="test-template-section"></div>').html(html)
  $('body').append($container)
  return $container
}

describe('formValuesSnapshot helpers', () => {
  afterEach(() => {
    $('body').empty()
    delete window.tinyMCE
  })

  describe('snapshotFormValues', () => {
    it('captures values from text, textarea, and select inputs', () => {
      const $container = mountContainer(`
        <input type="text"     name="gfpdf_settings[background_color]" value="#ff0000" />
        <input type="text"     name="gfpdf_settings[background_image]" value="/path/to/image.png" />
        <textarea              name="gfpdf_settings[notes]">Hello world</textarea>
        <select                name="gfpdf_settings[font_size]">
          <option value="10">10</option>
          <option value="12" selected>12</option>
        </select>
      `)

      expect(snapshotFormValues($container)).toEqual([
        { kind: 'input', name: 'gfpdf_settings[background_color]', value: '#ff0000' },
        { kind: 'input', name: 'gfpdf_settings[background_image]', value: '/path/to/image.png' },
        { kind: 'input', name: 'gfpdf_settings[notes]', value: 'Hello world' },
        { kind: 'input', name: 'gfpdf_settings[font_size]', value: '12' }
      ])
    })

    it('captures checked + value for checkboxes and radios', () => {
      const $container = mountContainer(`
        <input type="checkbox" name="gfpdf_settings[show_form_title]"  value="Yes" checked />
        <input type="checkbox" name="gfpdf_settings[show_page_names]"  value="Yes" />
        <input type="radio"    name="gfpdf_settings[orientation]"      value="portrait"  checked />
        <input type="radio"    name="gfpdf_settings[orientation]"      value="landscape" />
      `)

      expect(snapshotFormValues($container)).toEqual([
        { kind: 'input', name: 'gfpdf_settings[show_form_title]', value: 'Yes', checked: true },
        { kind: 'input', name: 'gfpdf_settings[show_page_names]', value: 'Yes', checked: false },
        { kind: 'input', name: 'gfpdf_settings[orientation]', value: 'portrait', checked: true },
        { kind: 'input', name: 'gfpdf_settings[orientation]', value: 'landscape', checked: false }
      ])
    })

    it('prefers live TinyMCE content when the editor is in Visual mode', () => {
      const $container = mountContainer(`
        <textarea id="gfpdf_settings_header" name="gfpdf_settings[header]">stale textarea body</textarea>
      `)

      window.tinyMCE = {
        get: jest.fn((id) => {
          if (id !== 'gfpdf_settings_header') return null
          return { hidden: false, getContent: () => '<p>live editor body</p>' }
        })
      }

      expect(snapshotFormValues($container)).toEqual([
        { kind: 'input', name: 'gfpdf_settings[header]', value: '<p>live editor body</p>' }
      ])
    })

    it('reads textarea value when the TinyMCE editor is in Code mode (hidden=true)', () => {
      const $container = mountContainer(`
        <textarea id="gfpdf_settings_footer" name="gfpdf_settings[footer]">live code-mode body</textarea>
      `)

      window.tinyMCE = {
        get: jest.fn(() => ({ hidden: true, getContent: () => 'stale editor html' }))
      }

      expect(snapshotFormValues($container)).toEqual([
        { kind: 'input', name: 'gfpdf_settings[footer]', value: 'live code-mode body' }
      ])
    })

    it('falls back to textarea value when TinyMCE has no editor for that id', () => {
      const $container = mountContainer(`
        <textarea id="gfpdf_settings_footer" name="gfpdf_settings[footer]">raw textarea body</textarea>
      `)

      window.tinyMCE = { get: jest.fn(() => null) }

      expect(snapshotFormValues($container)).toEqual([
        { kind: 'input', name: 'gfpdf_settings[footer]', value: 'raw textarea body' }
      ])
    })

    it('falls back to textarea value when TinyMCE is undefined', () => {
      const $container = mountContainer(`
        <textarea id="gfpdf_settings_header" name="gfpdf_settings[header]">no tinyMCE</textarea>
      `)

      expect(snapshotFormValues($container)).toEqual([
        { kind: 'input', name: 'gfpdf_settings[header]', value: 'no tinyMCE' }
      ])
    })

    it('captures .gfpdf-input-toggle state keyed by the textarea it controls', () => {
      const $container = mountContainer(`
        <label><input type="checkbox" class="gfpdf-input-toggle" checked /> First Page Header</label>
        <div class="gfpdf-toggle-wrapper">
          <textarea id="gfpdf_settings_first_header" name="gfpdf_settings[first_header]">first header body</textarea>
        </div>
        <label><input type="checkbox" class="gfpdf-input-toggle" /> First Page Footer</label>
        <div class="gfpdf-toggle-wrapper" style="display:none">
          <textarea id="gfpdf_settings_first_footer" name="gfpdf_settings[first_footer]">first footer body</textarea>
        </div>
      `)

      const snapshot = snapshotFormValues($container)
      const toggleEntries = snapshot.filter(e => e.kind === 'toggle')

      expect(toggleEntries).toEqual([
        { kind: 'toggle', controls: 'gfpdf_settings[first_header]', checked: true },
        { kind: 'toggle', controls: 'gfpdf_settings[first_footer]', checked: false }
      ])
    })

    it('skips the template dropdown so the new (post-change) value is not captured', () => {
      const $container = mountContainer(`
        <select id="gfpdf_settings[template]" name="gfpdf_settings[template]">
          <option value="zadani" selected>zadani</option>
          <option value="rubix">rubix</option>
        </select>
        <input type="text" name="gfpdf_settings[background_color]" value="#000" />
      `)

      expect(snapshotFormValues($container)).toEqual([
        { kind: 'input', name: 'gfpdf_settings[background_color]', value: '#000' }
      ])
    })

    it('skips buttons, submits, and file inputs', () => {
      const $container = mountContainer(`
        <input type="text"   name="gfpdf_settings[real_field]" value="kept" />
        <input type="submit" name="gfpdf_settings[submit]"     value="Save" />
        <input type="button" name="gfpdf_settings[btn]"        value="Click" />
        <input type="reset"  name="gfpdf_settings[clear]"      value="Reset" />
        <input type="file"   name="gfpdf_settings[upload]" />
        <button              name="gfpdf_settings[plain]">Click</button>
      `)

      expect(snapshotFormValues($container)).toEqual([
        { kind: 'input', name: 'gfpdf_settings[real_field]', value: 'kept' }
      ])
    })

    it('skips inputs without a name attribute', () => {
      const $container = mountContainer(`
        <input type="text" value="anonymous" />
        <input type="text" name="gfpdf_settings[named]" value="kept" />
      `)

      expect(snapshotFormValues($container)).toEqual([
        { kind: 'input', name: 'gfpdf_settings[named]', value: 'kept' }
      ])
    })
  })

  describe('restoreFormValues', () => {
    it('restores text, textarea, and select values when names match', () => {
      const $container = mountContainer(`
        <input type="text" name="gfpdf_settings[background_color]" value="#000" />
        <textarea          name="gfpdf_settings[notes]">post-swap default</textarea>
        <select            name="gfpdf_settings[font_size]">
          <option value="10" selected>10</option>
          <option value="12">12</option>
        </select>
      `)

      restoreFormValues($container, [
        { kind: 'input', name: 'gfpdf_settings[background_color]', value: '#ff0000' },
        { kind: 'input', name: 'gfpdf_settings[notes]', value: 'restored body' },
        { kind: 'input', name: 'gfpdf_settings[font_size]', value: '12' }
      ])

      expect($container.find('[name="gfpdf_settings[background_color]"]').val()).toBe('#ff0000')
      expect($container.find('[name="gfpdf_settings[notes]"]').val()).toBe('restored body')
      expect($container.find('[name="gfpdf_settings[font_size]"]').val()).toBe('12')
    })

    it('drops snapshot entries whose names no longer exist in the new HTML', () => {
      const $container = mountContainer(`
        <input type="text" name="gfpdf_settings[background_color]" value="#000" />
      `)

      restoreFormValues($container, [
        { kind: 'input', name: 'gfpdf_settings[background_color]', value: '#ff0000' },
        { kind: 'input', name: 'gfpdf_settings[zadani_border_colour]', value: '#0000ff' }
      ])

      expect($container.find('[name="gfpdf_settings[background_color]"]').val()).toBe('#ff0000')
      expect($container.find('[name="gfpdf_settings[zadani_border_colour]"]').length).toBe(0)
    })

    it('toggles a checkbox from unchecked to checked and fires change', () => {
      const $container = mountContainer(`
        <input type="checkbox" name="gfpdf_settings[show_form_title]" value="Yes" />
      `)

      const onChange = jest.fn()
      $container.find('[name="gfpdf_settings[show_form_title]"]').on('change', onChange)

      restoreFormValues($container, [
        { kind: 'input', name: 'gfpdf_settings[show_form_title]', value: 'Yes', checked: true }
      ])

      expect($container.find('[name="gfpdf_settings[show_form_title]"]').prop('checked')).toBe(true)
      expect(onChange).toHaveBeenCalledTimes(1)
    })

    it('toggles a checkbox from checked to unchecked and fires change', () => {
      const $container = mountContainer(`
        <input type="checkbox" name="gfpdf_settings[show_form_title]" value="Yes" checked />
      `)

      const onChange = jest.fn()
      $container.find('[name="gfpdf_settings[show_form_title]"]').on('change', onChange)

      restoreFormValues($container, [
        { kind: 'input', name: 'gfpdf_settings[show_form_title]', value: 'Yes', checked: false }
      ])

      expect($container.find('[name="gfpdf_settings[show_form_title]"]').prop('checked')).toBe(false)
      expect(onChange).toHaveBeenCalledTimes(1)
    })

    it('skips checkbox change when the post-swap state already matches the snapshot', () => {
      const $container = mountContainer(`
        <input type="checkbox" name="gfpdf_settings[show_form_title]" value="Yes" checked />
      `)

      const onChange = jest.fn()
      $container.find('[name="gfpdf_settings[show_form_title]"]').on('change', onChange)

      restoreFormValues($container, [
        { kind: 'input', name: 'gfpdf_settings[show_form_title]', value: 'Yes', checked: true }
      ])

      expect(onChange).not.toHaveBeenCalled()
    })

    it('switches a radio group selection', () => {
      const $container = mountContainer(`
        <input type="radio" name="gfpdf_settings[orientation]" value="portrait"  checked />
        <input type="radio" name="gfpdf_settings[orientation]" value="landscape" />
      `)

      restoreFormValues($container, [
        { kind: 'input', name: 'gfpdf_settings[orientation]', value: 'portrait', checked: false },
        { kind: 'input', name: 'gfpdf_settings[orientation]', value: 'landscape', checked: true }
      ])

      expect($container.find('[name="gfpdf_settings[orientation]"][value="portrait"]').prop('checked')).toBe(false)
      expect($container.find('[name="gfpdf_settings[orientation]"][value="landscape"]').prop('checked')).toBe(true)
    })

    it('restores a .gfpdf-input-toggle from unchecked to checked via its controlled textarea name', () => {
      const $container = mountContainer(`
        <label><input type="checkbox" class="gfpdf-input-toggle" /> First Page Header</label>
        <div class="gfpdf-toggle-wrapper" style="display:none">
          <textarea id="gfpdf_settings_first_header" name="gfpdf_settings[first_header]"></textarea>
        </div>
      `)

      const onChange = jest.fn()
      $container.find('.gfpdf-input-toggle').on('change', onChange)

      restoreFormValues($container, [
        { kind: 'toggle', controls: 'gfpdf_settings[first_header]', checked: true }
      ])

      expect($container.find('.gfpdf-input-toggle').prop('checked')).toBe(true)
      expect(onChange).toHaveBeenCalledTimes(1)
    })

    it('skips a toggle entry whose controlled textarea no longer exists in the new HTML', () => {
      const $container = mountContainer(`
        <input type="text" name="gfpdf_settings[background_color]" value="#000" />
      `)

      expect(() => restoreFormValues($container, [
        { kind: 'toggle', controls: 'gfpdf_settings[first_header]', checked: true }
      ])).not.toThrow()
    })

    it('handles an empty snapshot without throwing', () => {
      const $container = mountContainer(`
        <input type="text" name="gfpdf_settings[background_color]" value="#000" />
      `)

      expect(() => restoreFormValues($container, [])).not.toThrow()
      expect($container.find('[name="gfpdf_settings[background_color]"]').val()).toBe('#000')
    })
  })

  describe('snapshot → swap → restore round-trip', () => {
    it('preserves user edits across an HTML swap where field names overlap', () => {
      const $container = mountContainer(`
        <input type="text"     name="gfpdf_settings[background_color]" value="#ffffff" />
        <input type="checkbox" name="gfpdf_settings[show_form_title]"  value="Yes" />
        <textarea              name="gfpdf_settings[header]">old header body</textarea>
        <input type="text"     name="gfpdf_settings[zadani_border_colour]" value="#000000" />
        <label><input type="checkbox" class="gfpdf-input-toggle" /> First Page Header</label>
        <div class="gfpdf-toggle-wrapper" style="display:none">
          <textarea id="gfpdf_settings_first_header" name="gfpdf_settings[first_header]"></textarea>
        </div>
      `)

      /* User edits */
      $container.find('[name="gfpdf_settings[background_color]"]').val('#ff0000')
      $container.find('[name="gfpdf_settings[show_form_title]"]').prop('checked', true)
      $container.find('[name="gfpdf_settings[header]"]').val('NEW HEADER')
      $container.find('[name="gfpdf_settings[zadani_border_colour]"]').val('#abcdef')
      $container.find('.gfpdf-input-toggle').prop('checked', true)
      $container.find('[name="gfpdf_settings[first_header]"]').val('FIRST HEADER BODY')

      const snapshot = snapshotFormValues($container)

      /* Swap HTML for a different template — same toggle pattern, no zadani-specific field, new rubix-specific field */
      $container.html(`
        <input type="text"     name="gfpdf_settings[background_color]" value="#ffffff" />
        <input type="checkbox" name="gfpdf_settings[show_form_title]"  value="Yes" />
        <textarea              name="gfpdf_settings[header]">default rubix header</textarea>
        <input type="text"     name="gfpdf_settings[rubix_container_background_colour]" value="#cccccc" />
        <label><input type="checkbox" class="gfpdf-input-toggle" /> First Page Header</label>
        <div class="gfpdf-toggle-wrapper" style="display:none">
          <textarea id="gfpdf_settings_first_header" name="gfpdf_settings[first_header]">rubix default first header</textarea>
        </div>
      `)

      restoreFormValues($container, snapshot)

      expect($container.find('[name="gfpdf_settings[background_color]"]').val()).toBe('#ff0000')
      expect($container.find('[name="gfpdf_settings[show_form_title]"]').prop('checked')).toBe(true)
      expect($container.find('[name="gfpdf_settings[header]"]').val()).toBe('NEW HEADER')
      expect($container.find('.gfpdf-input-toggle').prop('checked')).toBe(true)
      expect($container.find('[name="gfpdf_settings[first_header]"]').val()).toBe('FIRST HEADER BODY')
      /* Rubix-specific field keeps its server-rendered default — zadani snapshot doesn't apply to it */
      expect($container.find('[name="gfpdf_settings[rubix_container_background_colour]"]').val()).toBe('#cccccc')
    })
  })
})
