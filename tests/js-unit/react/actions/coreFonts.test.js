import {
  addToConsole,
  ADD_TO_CONSOLE,
  clearConsole,
  CLEAR_CONSOLE,
  addToRetryList,
  ADD_TO_RETRY_LIST,
  clearButtonClickedAndRetryList,
  CLEAR_BUTTON_CLICKED_AND_RETRY_LIST,
  getFilesFromGitHub,
  GET_FILES_FROM_GITHUB,
  getFilesFromGitHubSuccess,
  GET_FILES_FROM_GITHUB_SUCCESS,
  getFilesFromGitHubFailed,
  GET_FILES_FROM_GITHUB_FAILED,
  downloadFontsApiCall,
  DOWNLOAD_FONTS_API_CALL,
  currentDownload,
  REQUEST_SENT_COUNTER,
  clearRequestRemainingData,
  CLEAR_REQUEST_REMAINING_DATA
} from '../../../../src/assets/js/react/actions/coreFonts'

describe('Actions - coreFonts', () => {

  let results
  let data

  test('addToConsole - check if it returns the correct action', () => {
    results = addToConsole('key', 'status', 'message')

    expect(results.type).toEqual(ADD_TO_CONSOLE)
    expect(results.key).toBe('key')
    expect(results.status).toBe('status')
    expect(results.message).toBe('message')
  })

  test('clearConsole - check if it returns the correct action', () => {
    results = clearConsole()

    expect(results.type).toEqual(CLEAR_CONSOLE)
  })

  test('addToRetryList - check if returns the correct action', () => {
    results = addToRetryList('name')

    expect(results.type).toEqual(ADD_TO_RETRY_LIST)
    expect(results.name).toBe('name')
  })

  test('clearButtonClickedAndRetryList - check if it returns the correct action', () => {
    results = clearButtonClickedAndRetryList()

    expect(results.type).toEqual(CLEAR_BUTTON_CLICKED_AND_RETRY_LIST)
  })

  test('getFilesFromGitHub - check if it returns the correct action', () => {
    results = getFilesFromGitHub()

    expect(results.type).toEqual(GET_FILES_FROM_GITHUB)
  })

  test('getFilesFromGitHubSuccess - check if it returns the correct action', () => {
    data = ['file1', 'file2']
    results = getFilesFromGitHubSuccess(data)

    expect(results.type).toEqual(GET_FILES_FROM_GITHUB_SUCCESS)
    expect(results.payload).toBeInstanceOf(Array)
    expect(results.payload).toEqual(data)
  })

  test('getFilesFromGitHubFailed - check if it returns the correct action', () => {
    data = { 'error': 'data1' }
    results = getFilesFromGitHubFailed(data)

    expect(results.type).toEqual(GET_FILES_FROM_GITHUB_FAILED)
    expect(results.payload).toBeInstanceOf(Object)
    expect(results.payload).toEqual(data)
  })

  test('downloadFontsApiCall - check if it returns the correct action', () => {
    results = downloadFontsApiCall('file')

    expect(results.type).toEqual(DOWNLOAD_FONTS_API_CALL)
    expect(results.payload).toBe('file')
  })

  test('currentDownload - check if it returns the correct action', () => {
    results = currentDownload()

    expect(results.type).toEqual(REQUEST_SENT_COUNTER)
  })

  test('clearRequestRemainingData - check if it returns the correct action', () => {
    results = clearRequestRemainingData()

    expect(results.type).toEqual(CLEAR_REQUEST_REMAINING_DATA)
  })
})
