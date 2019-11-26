import React from 'react'
import { shallow } from 'enzyme'
import { storeFactory, findByTestAttr } from '../../testUtils'
import ConnectedCoreFontContainer, { CoreFontContainer } from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontContainer'

describe('CoreFonts - CoreFontContainer.js', () => {

  const fontList = ['AboriginalSansREGULAR.ttf', 'Abyssinica_SIL.ttf', 'DejaVuSerifCondensed.ttf']
  // Mocked functions
  const clearConsoleMock = jest.fn()
  const clearButtonClickedAndRetryListMock = jest.fn()
  const historyMock = { replace: jest.fn() }
  const downloadFontsApiCallMock = jest.fn()
  const addToConsoleMock = jest.fn()
  const getFilesFromGitHubMock = jest.fn()
  const clearRequestRemainingDataMock = jest.fn()

  describe('Check for redux properties', () => {

    const setup = (state = {}) => {
      const store = storeFactory(state)
      const wrapper = shallow(<ConnectedCoreFontContainer store={store} />).dive().dive()

      return wrapper
    }

    test('has access to `buttonClicked` state', () => {
      const wrapper = setup()
      const buttonClickedProp = wrapper.instance().props.buttonClicked

      expect(buttonClickedProp).toBe(false)
    })

    test('has access to `fontList` state', () => {
      const wrapper = setup({ coreFonts: { fontList } })
      const fontListProp = wrapper.instance().props.fontList

      expect(fontListProp).toBe(fontList)
      expect(fontList).toHaveLength(3)
    })

    test('has access to `console` state', () => {
      const data = {
        'AboriginalSansREGULAR.ttf': {
          'status': 'pending',
          'message': 'Downloading AboriginalSansREGULAR.ttf...'
        },
        'Abyssinica_SIL.ttf': {
          'status': 'pending',
          'message': 'Downloading Abyssinica_SIL.ttf...'
        }
      }
      const wrapper = setup({ coreFonts: { console: data, fontList: [] } })
      const consoleProp = wrapper.instance().props.console

      expect(Object.keys(consoleProp).length).toBe(2)
      expect(consoleProp['AboriginalSansREGULAR.ttf']).toEqual({
        'status': 'pending',
        'message': 'Downloading AboriginalSansREGULAR.ttf...'
      })
      expect(consoleProp['Abyssinica_SIL.ttf']).toEqual({
        'status': 'pending',
        'message': 'Downloading Abyssinica_SIL.ttf...'
      })
    })

    test('has access to `retry` state', () => {
      const wrapper = setup({ coreFonts: { retry: fontList, fontList: [] } })
      const retryProp = wrapper.instance().props.retry

      expect(retryProp).toBe(fontList)
      expect(retryProp).toHaveLength(3)
    })

    test('has access to `getFilesFromGitHubFailed` state', () => {
      const error = 'Could not download Core Font list. Try again.'
      const wrapper = setup({ coreFonts: { getFilesFromGitHubFailed: error, fontList: [] } })
      const getFilesFromGitHubFailedProp = wrapper.instance().props.getFilesFromGitHubFailed

      expect(getFilesFromGitHubFailedProp).toBe(error)
    })

    test('has access to `requestDownload` state', () => {
      const wrapper = setup({ coreFonts: { requestDownload: 'finished', fontList: [] } })
      const requestDownloadProp = wrapper.instance().props.requestDownload

      expect(requestDownloadProp).toBe('finished')
    })

    test('has access to `downloadCounter` state', () => {
      const wrapper = setup({ coreFonts: { downloadCounter: 82, fontList: [] } })
      const downloadCounterProp = wrapper.instance().props.queue

      expect(downloadCounterProp).toBe(82)
    })
  })

  describe('Component functions', () => {

    let wrapper
    let instance

    beforeEach(() => {
      wrapper = shallow(
        <CoreFontContainer
          fontList={[]}
          clearConsole={clearConsoleMock}
          clearButtonClickedAndRetryList={clearButtonClickedAndRetryListMock}
          history={historyMock}
          downloadFontsApiCall={downloadFontsApiCallMock}
        />
      )
      instance = wrapper.instance()
    })

    test('maybeStartDownload() - start the download if location === `/downloadCoreFonts`', done => {
      instance.maybeStartDownload('/downloadCoreFonts', fontList, null)

      expect(wrapper.state('ajax')).toBe(false)
      expect(clearConsoleMock.mock.calls.length).toBe(1)
      expect(clearButtonClickedAndRetryListMock.mock.calls.length).toBe(1)
      expect(historyMock.replace.mock.calls.length).toBe(1)
      setTimeout(() => {
        expect(downloadFontsApiCallMock.mock.calls.length).toBe(3)
        done()
      }, 300)
    })

    test('maybeStartDownload() - start the download if location === `/retryDownloadCoreFonts`', done => {
      instance.maybeStartDownload('/retryDownloadCoreFonts', fontList, null)

      expect(wrapper.state('ajax')).toBe(true)
      expect(clearConsoleMock.mock.calls.length).toBe(1)
      expect(clearButtonClickedAndRetryListMock.mock.calls.length).toBe(1)
      expect(historyMock.replace.mock.calls.length).toBe(1)
      setTimeout(() => {
        expect(downloadFontsApiCallMock.mock.calls.length).toBe(3)
        done()
      }, 300)
    })

    test('startDownloadFonts() - call our server to download the fonts', done => {
      instance.startDownloadFonts(fontList, null)

      expect(clearConsoleMock.mock.calls.length).toBe(1)
      expect(clearButtonClickedAndRetryListMock.mock.calls.length).toBe(1)
      expect(historyMock.replace.mock.calls.length).toBe(1)
      setTimeout(() => {
        expect(downloadFontsApiCallMock.mock.calls.length).toBe(3)
        done()
      }, 300)
    })

    test('startDownloadFonts() - error handling', () => {
      const newWrapper = shallow(
        <CoreFontContainer
          fontList={[]}
          clearButtonClickedAndRetryList={clearButtonClickedAndRetryListMock}
          history={historyMock}
          addToConsole={addToConsoleMock}
        />
      )
      const inst = newWrapper.instance()
      inst.startDownloadFonts([], 'Could not download Core Font list. Try again.')

      expect(clearButtonClickedAndRetryListMock.mock.calls.length).toBe(1)
      expect(newWrapper.state('ajax')).toBe(false)
      expect(addToConsoleMock.mock.calls.length).toBe(1)
      expect(historyMock.replace.mock.calls.length).toBe(1)
    })

    test('handleGithubApiError() - Add an overall error status to the console', () => {
      const newWrapper = shallow(
        <CoreFontContainer
          fontList={[]}
          history={historyMock}
          addToConsole={addToConsoleMock}
        />
      )
      const inst = newWrapper.instance()
      inst.handleGithubApiError('Could not download Core Font list. Try again.')

      expect(newWrapper.state('ajax')).toBe(false)
      expect(addToConsoleMock.mock.calls.length).toBe(1)
      expect(historyMock.replace.mock.calls.length).toBe(1)
    })

    test('handleTriggerFontDownload() - request GitHub for font names & trigger font download', () => {
      const newWrapper = shallow(
        <CoreFontContainer
          fontList={[]}
          getFilesFromGitHub={getFilesFromGitHubMock}
        />
      )
      const inst = newWrapper.instance()
      inst.handleTriggerFontDownload()

      expect(newWrapper.state('ajax')).toBe(true)
      expect(getFilesFromGitHubMock.mock.calls.length).toBe(1)
    })
  })

  describe('Run Lifecycle methods', () => {

    test('componentDidMount() - Check for /downloadCoreFonts redirect URL and run the installer', () => {
      const props = {
        fontList: [],
        location: {
          pathname: '/downloadCoreFonts'
        },
        getFilesFromGitHub: getFilesFromGitHubMock
      }
      const wrapper = shallow(<CoreFontContainer {...props} />)
      const handleTriggerFontDownload = jest.spyOn(wrapper.instance(), 'handleTriggerFontDownload')
      wrapper.instance().componentDidMount()

      expect(handleTriggerFontDownload).toHaveBeenCalledTimes(1)
      expect(wrapper.state('ajax')).toBe(true)
      expect(getFilesFromGitHubMock.mock.calls.length).toBe(1)
    })

    test('componentDidUpdate() - Load current font list', done => {
      const props = {
        fontList: fontList,
        location: {
          pathname: ''
        },
        getFilesFromGitHubFailed: '',
        buttonClicked: true
      }
      const wrapper  = shallow(
        <CoreFontContainer
          fontList={[]}
          clearConsole={clearConsoleMock}
          clearButtonClickedAndRetryList={clearButtonClickedAndRetryListMock}
          history={historyMock}
          downloadFontsApiCall={downloadFontsApiCallMock}
          {...props}
        />
      )
      const startDownloadFonts = jest.spyOn(wrapper.instance(), 'startDownloadFonts')
      wrapper.instance().componentDidUpdate()

      expect(startDownloadFonts).toHaveBeenCalledTimes(1)
      expect(clearConsoleMock.mock.calls.length).toBe(1)
      expect(clearButtonClickedAndRetryListMock.mock.calls.length).toBe(1)
      expect(historyMock.replace.mock.calls.length).toBe(1)
      setTimeout(() => {
        expect(downloadFontsApiCallMock.mock.calls.length).toBe(3)
        done()
      }, 300)
    })

    test('componentDidUpdate() - Check for /downloadCoreFonts redirect URL and run the installer', () => {
      const props = {
        fontList: [],
        location: { pathname: '/downloadCoreFonts' }
      }
      const wrapper = shallow(
        <CoreFontContainer
          fontList={[]}
          getFilesFromGitHub={getFilesFromGitHubMock}
          {...props}
        />
      )
      const handleTriggerFontDownload = jest.spyOn(wrapper.instance(), 'handleTriggerFontDownload')
      wrapper.instance().componentDidUpdate()

      expect(handleTriggerFontDownload).toHaveBeenCalledTimes(1)
      expect(wrapper.state('ajax')).toBe(true)
      expect(getFilesFromGitHubMock.mock.calls.length).toBe(1)
    })

    test('componentDidUpdate() - Load current hash history location & retry font list', done => {
      const props = {
        fontList: [],
        retry: fontList,
        location: { pathname: '/retryDownloadCoreFonts' }
      }
      const wrapper = shallow(
        <CoreFontContainer
          fontList={[]}
          clearConsole={clearConsoleMock}
          clearButtonClickedAndRetryList={clearButtonClickedAndRetryListMock}
          history={historyMock}
          downloadFontsApiCall={downloadFontsApiCallMock}
          {...props}
        />
      )
      const maybeStartDownload = jest.spyOn(wrapper.instance(), 'maybeStartDownload')
      wrapper.instance().componentDidUpdate()

      expect(maybeStartDownload).toHaveBeenCalledTimes(1)
      expect(wrapper.state('ajax')).toBe(true)
      expect(clearConsoleMock.mock.calls.length).toBe(1)
      expect(clearButtonClickedAndRetryListMock.mock.calls.length).toBe(1)
      expect(historyMock.replace.mock.calls.length).toBe(1)
      setTimeout(() => {
        expect(downloadFontsApiCallMock.mock.calls.length).toBe(3)
        done()
      }, 300)
    })

    test('componentDidUpdate() - Load error if something went wrong', () => {
      const props = {
        fontList: [],
        location: { pathname: '' },
        getFilesFromGitHubFailed: 'Could not download Core Font list. Try again.',
        buttonClicked: true
      }
      const wrapper = shallow(
        <CoreFontContainer
          fontList={[]}
          clearButtonClickedAndRetryList={clearButtonClickedAndRetryListMock}
          addToConsole={addToConsoleMock}
          history={historyMock}
          {...props}
        />
      )
      const startDownloadFonts = jest.spyOn(wrapper.instance(), 'startDownloadFonts')
      wrapper.instance().componentDidUpdate()

      expect(startDownloadFonts).toHaveBeenCalledTimes(1)
      expect(clearButtonClickedAndRetryListMock.mock.calls.length).toBe(1)
      expect(wrapper.state('ajax')).toBe(false)
      expect(addToConsoleMock.mock.calls.length).toBe(1)
      expect(historyMock.replace.mock.calls.length).toBe(1)
    })

    test('componentDidUpdate() - Set ajax/loading false if request download is finished', () => {
      const props = {
        fontList: [],
        location: { pathname: '' },
        requestDownload: 'finished'
      }
      const wrapper = shallow(
        <CoreFontContainer
          fontList={[]}
          clearRequestRemainingData={clearRequestRemainingDataMock}
          history={historyMock}
          {...props}
        />
      )
      wrapper.instance().componentDidUpdate()

      expect(wrapper.state('ajax')).toBe(false)
      expect(clearRequestRemainingDataMock.mock.calls.length).toBe(1)
      expect(historyMock.replace.mock.calls.length).toBe(1)
    })
  })

  let wrapper
  wrapper = shallow(<CoreFontContainer fontList={[]} />)

  test('renders <CoreFontContainer /> component container', () => {
    const component = findByTestAttr(wrapper, 'component-coreFont-downloader')

    expect(component.length).toBe(1)
  })

  test('renders core font downloader button', () => {
    const component = findByTestAttr(wrapper, 'component-coreFont-button')

    expect(component.length).toBe(1)
  })

  test('renders button text', () => {
    const props = {
      fontList: [],
      buttonText: 'Download Core Fonts'
    }
    const newWrapper = shallow(<CoreFontContainer {...props} />)

    expect(newWrapper.find('button').text()).toBe('Download Core Fonts')
  })

  test('check button click', () => {
    const getFilesFromGitHubMock = jest.fn()
    const newWrapper = shallow(
      <CoreFontContainer
        fontList={[]}
        getFilesFromGitHub={getFilesFromGitHubMock}
      />
    )
    const button = findByTestAttr(newWrapper, 'component-coreFont-button')
    button.simulate('click')

    expect(newWrapper.state('ajax')).toBe(true)
    expect(getFilesFromGitHubMock.mock.calls.length).toBe(1)
  })

  test('renders <Spinner /> component', () => {
    wrapper.setState({ ajax: true })

    expect(wrapper.find('Spinner').length).toEqual(1)
  })

  test('renders <Counter /> component', () => {
    wrapper = shallow(<CoreFontContainer fontList={[]} queue={1} />)
    wrapper.setState({ ajax: true })

    expect(wrapper.find('CoreFontCounter').length).toEqual(1)
  })

  test('renders <CoreFontListResults /> component', () => {
    expect(wrapper.find('CoreFontListResults').length).toEqual(1)
  })
})
