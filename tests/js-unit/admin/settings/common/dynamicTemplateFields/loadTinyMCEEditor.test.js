import { loadTinyMCEEditor } from '../../../../../../src/assets/js/admin/settings/common/dynamicTemplateFields/loadTinyMCEEditor'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.14.3
 */

function setupEditor ({ initialized = true } = {}) {
  const removeAllRanges = jest.fn()
  const selection = { removeAllRanges }
  const win = { getSelection: jest.fn(() => selection) }
  const editor = {
    initialized,
    on: jest.fn(),
    getWin: jest.fn(() => win)
  }
  return { editor, win, selection, removeAllRanges }
}

describe('loadTinyMCEEditor', () => {
  beforeEach(() => {
    window.tinyMCE = {
      init: jest.fn(),
      execCommand: jest.fn(),
      get: jest.fn()
    }
    window.switchEditors = { go: jest.fn() }
    window.getUserSetting = jest.fn().mockReturnValue('tmce')

    window.QTags = jest.fn()
    window.QTags._buttonsInit = jest.fn()
  })

  afterEach(() => {
    delete window.tinyMCE
    delete window.switchEditors
    delete window.getUserSetting
    delete window.QTags
  })

  it('initialises each editor with the supplied settings and registers it with TinyMCE/QTags', () => {
    const { editor } = setupEditor()
    window.tinyMCE.get.mockReturnValue(editor)

    /* settings is mutated in-place each iteration, so snapshot the selector at call time */
    const observedSelectors = []
    window.tinyMCE.init.mockImplementation((settings) => observedSelectors.push(settings.selector))

    loadTinyMCEEditor(['editor-one', 'editor-two'], {})

    expect(observedSelectors).toEqual(['#editor-one', '#editor-two'])
    expect(window.tinyMCE.execCommand).toHaveBeenCalledWith('mceAddEditor', false, 'editor-one')
    expect(window.tinyMCE.execCommand).toHaveBeenCalledWith('mceAddEditor', false, 'editor-two')
    expect(window.QTags).toHaveBeenCalledWith({ id: 'editor-one' })
    expect(window.QTags).toHaveBeenCalledWith({ id: 'editor-two' })
    expect(window.QTags._buttonsInit).toHaveBeenCalledTimes(2)
  })

  it("applies TinyMCE-style defaults (body_class, formats, content_style) when settings is provided", () => {
    const { editor } = setupEditor()
    window.tinyMCE.get.mockReturnValue(editor)

    const settings = {}
    loadTinyMCEEditor(['editor'], settings)

    expect(settings.body_class).toBe('id post-type-post post-status-publish post-format-standard')
    expect(settings.formats).toEqual(expect.objectContaining({
      alignleft: expect.any(Array),
      aligncenter: expect.any(Array),
      alignright: expect.any(Array),
      strikethrough: { inline: 'del' }
    }))
    expect(settings.content_style).toContain('body#tinymce')
  })

  it('does not touch settings when called with settings === null', () => {
    window.tinyMCE.get.mockReturnValue(setupEditor().editor)

    expect(() => loadTinyMCEEditor([], null)).not.toThrow()
    expect(window.tinyMCE.init).not.toHaveBeenCalled()
  })

  describe('restoring the user\'s last-selected editor tab', () => {
    it("switches to Code mode when getUserSetting('editor') is 'html'", () => {
      window.getUserSetting.mockReturnValue('html')
      const { editor } = setupEditor({ initialized: true })
      window.tinyMCE.get.mockReturnValue(editor)

      loadTinyMCEEditor(['editor-one'], {})

      expect(window.switchEditors.go).toHaveBeenCalledWith('editor-one', 'html')
    })

    it("switches to Visual mode when getUserSetting('editor') is anything other than 'html'", () => {
      window.getUserSetting.mockReturnValue('tinymce')
      const { editor } = setupEditor({ initialized: true })
      window.tinyMCE.get.mockReturnValue(editor)

      loadTinyMCEEditor(['editor-one'], {})

      expect(window.switchEditors.go).toHaveBeenCalledWith('editor-one', 'tmce')
    })

    it('applies the switch immediately when the editor is already initialised', () => {
      const { editor } = setupEditor({ initialized: true })
      window.tinyMCE.get.mockReturnValue(editor)

      loadTinyMCEEditor(['editor-one'], {})

      expect(window.switchEditors.go).toHaveBeenCalledTimes(1)
      expect(editor.on).not.toHaveBeenCalled()
    })

    it("defers the switch until TinyMCE fires 'init' when the editor isn't initialised yet", () => {
      const { editor } = setupEditor({ initialized: false })
      window.tinyMCE.get.mockReturnValue(editor)

      loadTinyMCEEditor(['editor-one'], {})

      expect(window.switchEditors.go).not.toHaveBeenCalled()
      expect(editor.on).toHaveBeenCalledWith('init', expect.any(Function))

      const [, deferredApply] = editor.on.mock.calls[0]
      deferredApply()

      expect(window.switchEditors.go).toHaveBeenCalledWith('editor-one', 'tmce')
    })

    it('clears the iframe window selection before calling switchEditors.go (prevents scroll jump)', () => {
      window.getUserSetting.mockReturnValue('html')
      const { editor, removeAllRanges } = setupEditor({ initialized: true })
      window.tinyMCE.get.mockReturnValue(editor)

      loadTinyMCEEditor(['editor-one'], {})

      expect(removeAllRanges).toHaveBeenCalledTimes(1)
      const removeOrder = removeAllRanges.mock.invocationCallOrder[0]
      const goOrder = window.switchEditors.go.mock.invocationCallOrder[0]
      expect(removeOrder).toBeLessThan(goOrder)
    })

    it('still attempts the switch when the editor exposes no getWin()', () => {
      window.getUserSetting.mockReturnValue('html')
      const { editor } = setupEditor({ initialized: true })
      delete editor.getWin
      window.tinyMCE.get.mockReturnValue(editor)

      loadTinyMCEEditor(['editor-one'], {})

      expect(window.switchEditors.go).toHaveBeenCalledWith('editor-one', 'html')
    })

    it('still attempts the switch when the iframe getSelection() returns null', () => {
      window.getUserSetting.mockReturnValue('html')
      const { editor, win } = setupEditor({ initialized: true })
      win.getSelection = jest.fn(() => null)
      window.tinyMCE.get.mockReturnValue(editor)

      loadTinyMCEEditor(['editor-one'], {})

      expect(window.switchEditors.go).toHaveBeenCalledWith('editor-one', 'html')
    })

    it('swallows exceptions from switchEditors.go so the surrounding template-swap callback keeps running', () => {
      const { editor } = setupEditor({ initialized: true })
      window.tinyMCE.get.mockReturnValue(editor)
      window.switchEditors.go.mockImplementation(() => {
        throw new TypeError('iframeElement is undefined')
      })

      expect(() => loadTinyMCEEditor(['editor-one'], {})).not.toThrow()
    })

    it('no-ops when window.switchEditors is unavailable', () => {
      delete window.switchEditors
      const { editor, removeAllRanges } = setupEditor({ initialized: true })
      window.tinyMCE.get.mockReturnValue(editor)

      expect(() => loadTinyMCEEditor(['editor-one'], {})).not.toThrow()
      expect(removeAllRanges).not.toHaveBeenCalled()
    })

    it('skips restoration entirely when QTags is unavailable', () => {
      delete window.QTags
      const { editor } = setupEditor({ initialized: true })
      window.tinyMCE.get.mockReturnValue(editor)

      loadTinyMCEEditor(['editor-one'], {})

      expect(window.switchEditors.go).not.toHaveBeenCalled()
    })
  })
})
