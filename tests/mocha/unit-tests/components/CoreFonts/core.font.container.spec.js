import React from 'react'
import { mount } from 'enzyme'
import createHistory from 'history/createHashHistory'
import request from 'superagent'

let mock = require('superagent-mocker')(request)
mock.timeout = 1

import { CoreFontContainer } from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontContainer'

let consoleList, retry
let History = createHistory()

describe('<CoreFontContainer />', () => {

  beforeEach(function () {
    History.replace('/')

    consoleList = {}
    retry = []

    mock.post('/saveCoreFont', (req) => {
      return {
        body: true,
      }
    })
  })

  afterEach(function () {
    mock.clearRoutes()
  })

  it('Test Url Update', () => {
    const comp = mount(
      <CoreFontContainer
        history={History}
        location={History.location}
        listUrl="/githubList"

        console={consoleList}
        retry={retry}
      />
    )

    //console.error(comp.html())
    comp.find('button').simulate('click')
    expect(window.location.hash).to.equal('#/downloadCoreFonts')
  })

  it('Test GitHub File List Request', async () => {
    mock.get('/githubList', (req) => {
      return {
        body: [
          {name: 'file1'},
          {name: 'file2'},
        ],
      }
    })

    const comp = mount(
      <CoreFontContainer
        history={History}
        location={History.location}
        listUrl="/githubList"

        console={consoleList}
        retry={retry}
      />
    )

    const instance = comp.instance()
    const files = await instance.getFilesFromGitHub()

    expect(files.length).to.equal(2)
    expect(files[0]).to.equal('file1')
  })

  it('Test Font Download', async () => {
    GFPDF.ajaxUrl = '/font'
    GFPDF.ajaxNonce = ''
    mock.post('/font', (req) => {
      return {body: true}
    })

    const comp = mount(
      <CoreFontContainer
        history={History}
        location={History.location}
        listUrl="/githubList"

        console={consoleList}
        retry={retry}

        itemPending="Pending: %s"
        itemSuccess="Success: %s"
        itemError="Error: %s"

        addToConsole={(name, status, message) => {
          consoleList = {...consoleList, [name]: {status, message}}
        }}
        clearConsole={() => null}
        addToRetryList={() => null}
        clearRetryList={() => null}
      />
    )

    const instance = comp.instance()
    await instance.downloadFontsApiCall('Item 1')
    await instance.downloadFontsApiCall('Item 2')

    expect(Object.keys(consoleList).length).to.equal(2)
    expect(consoleList['Item 1'].status).to.equal('success')

    mock.post('/font', (req) => {
      return {body: false}
    })

    await instance.downloadFontsApiCall('Item 1')
    expect(consoleList['Item 1'].status).to.equal('error')

    mock.post('/font', (req) => {
      return {body: true}
    })

    consoleList = {}

    expect(instance.getQueueLength()).to.equal(0)
    await instance.startDownloadFonts(['Item1', 'Item2', 'Item3', 'Item4', 'Item5', 'Item6', 'Item7'])

    expect(Object.keys(consoleList).length).to.equal(5)
    expect(instance.getQueueLength()).to.equal(7)
  })

  it('Test Redux Props', async () => {
    const comp = mount(
      <CoreFontContainer
        history={History}
        location={History.location}
        listUrl="/githubList"

        console={consoleList}
        retry={retry}

        success="Complete"
        error="Complete Error: %s"
        itemPending="Pending: %s"
        itemSuccess="Success: %s"
        itemError="Error: %s"
        gitHubError="GitHub Error"

        addToConsole={(name, status, message) => {
          consoleList = {...consoleList, [name]: {status, message}}
        }}
        clearConsole={() => null}
        addToRetryList={() => null}
        clearRetryList={() => null}
      />
    )

    const instance = comp.instance()

    instance.addFontPendingMessage('Item 1')
    instance.addFontPendingMessage('Item 2')
    instance.addFontPendingMessage('Item 3')

    expect(Object.keys(consoleList).length).to.equal(3)
    expect(consoleList['Item 1'].status).to.equal('pending')
    expect(consoleList['Item 1'].message).to.equal('Pending: Item 1')

    consoleList = {}

    instance.addFontSuccessMessage('Item 1')
    expect(consoleList['Item 1'].status).to.equal('success')
    expect(consoleList['Item 1'].message).to.equal('Success: Item 1')

    consoleList = {}

    instance.addFontErrorMessage('Item 1')
    expect(consoleList['Item 1'].status).to.equal('error')
    expect(consoleList['Item 1'].message).to.equal('Error: Item 1')

    consoleList = {}

    instance.handleGithubApiError()
    expect(consoleList['completed'].status).to.equal('error')

    consoleList = {}

    instance.showDownloadCompletedStatus()
    expect(consoleList['completed'].status).to.equal('success')
    expect(consoleList['completed'].message).to.equal('Complete')

    consoleList = {}

    comp.setProps({...comp.props(), retry: ['Item 1', 'Item 2']})
    instance.showDownloadCompletedStatus()
    expect(consoleList['completed'].status).to.equal('error')
    expect(consoleList['completed'].message).to.equal('Complete Error: 2')
  })
})