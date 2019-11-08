import React from 'react'
import { mount } from 'enzyme'
import { createHashHistory } from 'history'
import { CoreFontContainer } from '../../../../../../src/assets/js/react/components/CoreFonts/CoreFontContainer'

const consoleList = {}
const data = ['file1', 'file2', 'file3', 'file4']
const retry = []
const spy = sinon.spy()
const History = createHashHistory()
History.replace('/')

describe('<CoreFontContainer />', () => {

  it('Test URL Update for downloadCoreFonts', () => {
    const comp = mount(
      <CoreFontContainer
        history={History}
        location={History.location}
        fontList={data}
        conSole={consoleList}
        retry={retry}
        getFilesFromGitHub={spy}
      />)
    comp.find('button').simulate('click')

    expect(window.location.hash).to.equal('#/downloadCoreFonts')
  })

  it('Test Font List Request Downloads', () => {
    const comp = mount(
      <CoreFontContainer
        history={History}
        location={History.location}
        fontList={data}
        conSole={consoleList}
        retry={retry}
        getFilesFromGitHub={spy}
        clearConsole={spy}
        clearRetryList={spy}
        downloadFontsApiCall={spy}
      />)
    const instance = comp.instance()
    const files = instance.startDownloadFonts(data)

    expect(files.length).to.equal(4)
    expect(files[0]).to.equal('file1')
  })

  it('Test Font List Request Download Failed/Error', () => {
    const comp = mount(
      <CoreFontContainer
        history={History}
        location={History.location}
        fontList={data}
        conSole={consoleList}
        retry={retry}
        getFilesFromGitHub={spy}
        clearConsole={spy}
        clearRetryList={spy}
        downloadFontsApiCall={spy}
        addToConsole={spy}
        githubError={'Could not download Core Font list. Try again.'}
      />)
    const instance = comp.instance()
    const files = instance.handleGithubApiError()

    expect(files).to.equal('Could not download Core Font list. Try again.')
  })
})
