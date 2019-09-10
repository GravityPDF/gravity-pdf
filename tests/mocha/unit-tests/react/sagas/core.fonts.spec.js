import { channel } from 'redux-saga'
import { call, fork, put, take, takeLatest } from 'redux-saga/effects'
import {
  getDownloadFonts,
  getFilesFromGitHub,
  watchGetFilesFromGitHub,
  watchDownloadFonts
} from '../../../../../src/assets/js/react/sagas/coreFonts'
import {
  addToConsole,
  addToRetryList,
  currentDownload,
  downloadFontsApiCall,
  GET_FILES_FROM_GITHUB,
  DOWNLOAD_FONTS_API_CALL,
  GET_FILES_FROM_GITHUB_FAILED
} from '../../../../../src/assets/js/react/actions/coreFonts'
import * as api from '../../../../../src/assets/js/react/api/coreFonts'

describe('Sagas coreFonts -', () => {

  describe('watchGetFilesFromGitHub()', () => {
    const gen = watchGetFilesFromGitHub()

    it('should check the watcher to loads up the getFilesFromGitHub function and call GET_FILES_FROM_GITHUB action', () => {
      expect(gen.next().value).to.deep.eql(takeLatest(GET_FILES_FROM_GITHUB, getFilesFromGitHub))
    })
  })

  describe('getFilesFromGitHub()', () => {
    const gen = getFilesFromGitHub()

    it('should check that saga asks to call the API for getFilesFromGitHub', () => {
      expect(gen.next().value).to.deep.eql(call(api.apiGetFilesFromGitHub))
    })

    it('should check that saga handles correctly to the failure of getFilesFromGitHub API call', () => {
      expect(gen.throw('failed').value).to.deep.eql(put({
        type: GET_FILES_FROM_GITHUB_FAILED,
        payload: 'failed'
      }))
    })
  })

  describe('watchDownloadFonts()', () => {
    const gen = watchDownloadFonts()
    const chan = call(channel)

    it('should check the watcher loads up five workers and listens for the font api call', () => {
      expect(gen.next().value).to.deep.eql(chan)

      for (let i = 0; i < 5; i++) {
        expect(gen.next(chan).value).to.deep.eql(fork(getDownloadFonts, chan))
      }

      expect(gen.next().value).to.deep.eql(take(DOWNLOAD_FONTS_API_CALL))
    })
  })

  describe('getDownloadFonts()', () => {
    const gen = getDownloadFonts(channel)
    const payload = downloadFontsApiCall('test1.ttf')

    it('should display the pending download message', () => {
      expect(gen.next().value).to.deep.eql(take(channel))
      expect(gen.next(payload).value).to.deep.eql(put(addToConsole(payload, 'pending', '[object Object]')))
    })

    it('should display the success download message', () => {
      expect(gen.next().value).to.deep.eql(call(api.apiPostDownloadFonts, payload))
      expect(gen.next({ body: 'test' }).value).to.deep.eql(put(addToConsole(payload, 'success', '[object Object]')))
    })

    it('should display the error download message', () => {
      expect(gen.throw().value).to.deep.eql(put(addToConsole(payload, 'error', '[object Object]')))
      expect(gen.next().value).to.deep.eql(put(addToRetryList(payload)))
    })

    it('should pass to redux store', () => {
      expect(gen.next().value).to.deep.eql(put(currentDownload()))
    })
  })
})
