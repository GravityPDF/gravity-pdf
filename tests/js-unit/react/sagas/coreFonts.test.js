import { channel } from 'redux-saga'
import { call, fork, put, take, takeLatest } from 'redux-saga/effects'
import {
  getDownloadFonts,
  getFilesFromGitHub,
  watchGetFilesFromGitHub,
  watchDownloadFonts
} from '../../../../src/assets/js/react/sagas/coreFonts'
import {
  addToConsole,
  addToRetryList,
  currentDownload,
  downloadFontsApiCall,
  GET_FILES_FROM_GITHUB,
  DOWNLOAD_FONTS_API_CALL,
  GET_FILES_FROM_GITHUB_FAILED
} from '../../../../src/assets/js/react/actions/coreFonts'
import * as api from '../../../../src/assets/js/react/api/coreFonts'

describe('Sagas - coreFonts', () => {

  describe('watchGetFilesFromGitHub()', () => {
    const gen = watchGetFilesFromGitHub()

    test('should check the watcher to loads up the getFilesFromGitHub function and call GET_FILES_FROM_GITHUB action', () => {
      expect(gen.next().value).toEqual(takeLatest(GET_FILES_FROM_GITHUB, getFilesFromGitHub))
    })
  })

  describe('getFilesFromGitHub()', () => {
    const gen = getFilesFromGitHub()

    test('should check that saga asks to call the API for getFilesFromGitHub', () => {
      expect(gen.next().value).toEqual(call(api.apiGetFilesFromGitHub))
    })

    test('should check that saga handles correctly to the failure of getFilesFromGitHub API call', () => {
      expect(gen.throw().value).toEqual(put({
        type: GET_FILES_FROM_GITHUB_FAILED,
        payload: GFPDF.coreFontGithubError
      }))
    })
  })

  describe('watchDownloadFonts()', () => {
    const gen = watchDownloadFonts()
    const chan = call(channel)

    test('should check the watcher loads up five workers and listens for the font api call', () => {
      expect(gen.next().value).toEqual(chan)

      for (let i = 0; i < 5; i++) {
        expect(gen.next(chan).value).toEqual(fork(getDownloadFonts, chan))
      }

      expect(gen.next().value).toEqual(take(DOWNLOAD_FONTS_API_CALL))
    })
  })

  describe('getDownloadFonts()', () => {
    const gen = getDownloadFonts(channel)
    const payload = downloadFontsApiCall('test1.ttf')

    test('should display the pending download message', () => {
      expect(gen.next().value).toEqual(take(channel))
      expect(gen.next(payload).value).toEqual(put(addToConsole(payload, 'pending', '[object Object]')))
    })

    test('should display the success download message', () => {
      expect(gen.next().value).toEqual(call(api.apiPostDownloadFonts, payload))
      expect(gen.next({ body: 'test' }).value).toEqual(put(addToConsole(payload, 'success', '[object Object]')))
    })

    test('should display the error download message', () => {
      expect(gen.throw().value).toEqual(put(addToConsole(payload, 'error', '[object Object]')))
      expect(gen.next().value).toEqual(put(addToRetryList(payload)))
    })

    test('should pass to redux store', () => {
      expect(gen.next().value).toEqual(put(currentDownload()))
    })
  })
})
