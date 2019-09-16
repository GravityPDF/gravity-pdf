import {
  addToConsole,
  clearConsole,
  addToRetryList,
  clearRetryList,
  getFilesFromGitHub,
  getFilesFromGitHubSuccess,
  getFilesFromGitHubFailed,
  downloadFontsApiCall,
  currentDownload,
  clearRequestRemainingData,
  ADD_TO_CONSOLE,
  CLEAR_CONSOLE,
  ADD_TO_RETRY_LIST,
  CLEAR_RETRY_LIST,
  GET_FILES_FROM_GITHUB,
  GET_FILES_FROM_GITHUB_SUCCESS,
  GET_FILES_FROM_GITHUB_FAILED,
  DOWNLOAD_FONTS_API_CALL,
  REQUEST_SENT_COUNTER,
  CLEAR_REQUEST_REMAINING_DATA
} from '../../../../../src/assets/js/react/actions/coreFonts'

describe('Actions coreFonts -', () => {

  describe('addToConsole', () => {
    it('check if it returns the correct action', () => {
      let results = addToConsole('key', 'status', 'message')

      expect(results.key).is.equal('key')
      expect(results.status).is.equal('status')
      expect(results.message).is.equal('message')
      expect(results.type).is.equal(ADD_TO_CONSOLE)
    })
  })

  describe('clearConsole', () => {
    it('check if it returns the correct action', () => {
      let results = clearConsole()

      expect(results.type).is.equal(CLEAR_CONSOLE)
    })
  })

  describe('addToConsole', () => {
    it('check if it returns the correct action', () => {
      let results = addToRetryList('name')

      expect(results.name).is.equal('name')
      expect(results.type).is.equal(ADD_TO_RETRY_LIST)
    })
  })

  describe('clearRetryList', () => {
    it('check if it returns the correct action', () => {
      let results = clearRetryList()
      expect(results.type).is.equal(CLEAR_RETRY_LIST)
    })
  })

  describe('getFilesFromGitHub', () => {
    it('check if it returns the correct action', () => {
      let results = getFilesFromGitHub()

      expect(results.type).is.equal(GET_FILES_FROM_GITHUB)
    })
  })

  describe('getFilesFromGitHubSuccess', () => {
    it('check if it returns the correct action', () => {
      let data = ['file1', 'file2']
      let results = getFilesFromGitHubSuccess(data)

      expect(results.payload).to.be.a('array')
      expect(results.payload).is.equal(data)
      expect(results.type).is.equal(GET_FILES_FROM_GITHUB_SUCCESS)
    })
  })

  describe('getFilesFromGitHubFailed', () => {
    it('check if it returns the correct action', () => {
      let data = { 'info': 'data1' }
      let results = getFilesFromGitHubFailed(data)

      expect(results.payload).to.be.a('object')
      expect(results.payload).is.equal(data)
      expect(results.type).is.equal(GET_FILES_FROM_GITHUB_FAILED)
    })
  })

  describe('downloadFontsApiCall', () => {
    it('check if it returns the correct action', () => {
      let results = downloadFontsApiCall('file')

      expect(results.payload).is.equal('file')
      expect(results.type).is.equal(DOWNLOAD_FONTS_API_CALL)
    })
  })

  describe('currentDownload', () => {
    it('check if it returns the correct action', () => {
      let results = currentDownload()

      expect(results.type).is.equal(REQUEST_SENT_COUNTER)
    })
  })

  describe('clearRequestRemainingData', () => {
    it('check if it returns the correct action', () => {
      let results = clearRequestRemainingData()

      expect(results.type).is.equal(CLEAR_REQUEST_REMAINING_DATA)
    })
  })
})
