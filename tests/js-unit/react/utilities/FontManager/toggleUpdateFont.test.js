import {
  toggleUpdateFont,
  removeClass,
  addClass
} from '../../../../../src/assets/js/react/utilities/FontManager/toggleUpdateFont'

describe('Utilities/FontManager - toggleUpdateFont.test.js', () => {

  const history = {
    push: jest.fn(),
    location: { pathname: '/fontmanager/roboto' }
  }
  const mockedElementDOM = {
    classList: {
      remove: jest.fn(),
      add: jest.fn()
    }
  }

  test('toggleUpdateFont() - If fontId exist then remove show class', () => {
    // Mock update font panel DOM
    document.body.innerHTML =
      '<div class="update-font show">' +
      '</div>'

    toggleUpdateFont(history, 'roboto')

    expect(document.querySelector('div.update-font.show')).toBe(null)
  })

  test('toggleUpdateFont() - If fontId exist then add show class', () => {
    // Mock update font panel DOM
    document.body.innerHTML =
      '<div class="update-font">' +
      '</div>'

    toggleUpdateFont(history, 'gotham')

    expect(document.querySelector('div.update-font.show')).toBeTruthy()
  })

  test('toggleUpdateFont() - If fontId doesn\'t exist then remove show class', () => {
    // Mock update font panel DOM
    document.body.innerHTML =
      '<div class="update-font">' +
      '</div>'

    toggleUpdateFont(history, '')

    expect(document.querySelector('div.update-font.show')).toBe(null)
  })

  test('removeClass() - Avoid Warning: Hash history cannot PUSH the same path', () => {
    const history = {
      push: jest.fn(),
      location: { pathname: '/fontmanager/' }
    }

    removeClass(mockedElementDOM, history)

    expect(mockedElementDOM.classList.remove).toHaveBeenCalledTimes(1)
    expect(history.push.mock.calls.length).toBe(0)
  })

  test('removeClass() - ', () => {
    const mockedElementDOM = { classList: { remove: jest.fn() } }

    removeClass(mockedElementDOM, history)

    expect(mockedElementDOM.classList.remove).toHaveBeenCalledTimes(1)
    expect(history.push.mock.calls.length).toBe(1)
  })

  test('addClass() - ', () => {
    const history = {
      push: jest.fn(),
      location: { pathname: '/fontmanager/' }
    }

    addClass(mockedElementDOM, history, 'roboto')

    expect(mockedElementDOM.classList.add).toHaveBeenCalledTimes(1)
    expect(history.push.mock.calls.length).toBe(1)
  })
})
