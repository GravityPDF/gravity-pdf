import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import CoreFontListResults, { Retry } from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontListResults'

describe('CoreFonts - CoreFontListResults.js', () => {

  describe('CoreFontListResults Component', () => {

    const fontList = ['AboriginalSansREGULAR.ttf', 'Abyssinica_SIL.ttf', 'DejaVuSerifCondensed.ttf']
    const dataPending = {
      'AboriginalSansREGULAR.ttf': {
        'status': 'pending',
        'message': 'Downloading AboriginalSansREGULAR.ttf...'
      },
      'Abyssinica_SIL.ttf': {
        'status': 'pending',
        'message': 'Downloading Abyssinica_SIL.ttf...'
      }
    }
    const dataSuccess = {
      'AboriginalSansREGULAR.ttf': {
        'status': 'success',
        'message': 'Completed installation of AboriginalSansREGULAR.ttf'
      },
      'Abyssinica_SIL.ttf': {
        'status': 'success',
        'message': 'Completed installation of Abyssinica_SIL.ttf'
      }
    }
    const dataCompleted = {
      'Abyssinica_SIL.ttf': {
        'status': 'success',
        'message': 'Completed installation of Abyssinica_SIL.ttf'
      },
      'completed': {
        'status': 'success',
        'message': 'ALL CORE FONTS SUCCESSFULLY INSTALLED'
      }
    }

    test('renders <CoreFontListResults /> component container', () => {
      const wrapper = shallow(<CoreFontListResults console={dataPending} retry={[]} />)
      const component = findByTestAttr(wrapper, 'component-coreFont-container')

      expect(component.length).toBe(1)
    })

    test('renders console pending output for our core font downloader', () => {
      const wrapper = shallow(<CoreFontListResults console={dataPending} retry={[]} />)

      expect(wrapper.find('div.gfpdf-core-font-status-pending').length).toEqual(2)
      expect(wrapper.find('div.gfpdf-core-font-status-pending').at(0).text()).toBe('Downloading Abyssinica_SIL.ttf... ')
      expect(wrapper.find('div.gfpdf-core-font-status-pending').at(1).text()).toBe('Downloading AboriginalSansREGULAR.ttf... ')
    })

    test('renders console success output for our core font downloader', () => {
      const wrapper = shallow(<CoreFontListResults console={dataSuccess} retry={[]} />)

      expect(wrapper.find('div.gfpdf-core-font-status-success').length).toEqual(2)
      expect(wrapper.find('div.gfpdf-core-font-status-success').at(0).text()).toBe('Completed installation of Abyssinica_SIL.ttf ')
      expect(wrapper.find('div.gfpdf-core-font-status-success').at(1).text()).toBe('Completed installation of AboriginalSansREGULAR.ttf ')
    })

    test('renders list spacer container component <ListSpacer />', () => {
      const wrapper = shallow(<CoreFontListResults console={dataCompleted} retry={[]} />)

      expect(wrapper.find('div.gfpdf-core-font-status-success').length).toEqual(2)
      expect(wrapper.find('div.gfpdf-core-font-status-success').at(0).text()).toBe('ALL CORE FONTS SUCCESSFULLY INSTALLED <CoreFontListSpacer />')
      expect(wrapper.find('div.gfpdf-core-font-status-success').at(1).text()).toBe('Completed installation of Abyssinica_SIL.ttf ')
    })

    test('renders retry component <Retry />', () => {
      const wrapper = shallow(<CoreFontListResults console={dataCompleted} retry={fontList} />)

      expect(wrapper.find('Retry').length).toEqual(1)
    })
  })

  describe('Retry Component', () => {

    test('renders <Retry /> component container', () => {
      const wrapper = shallow(<Retry />)
      const component = findByTestAttr(wrapper, 'component-retry-link')

      expect(component.length).toBe(1)
    })

    test('renders link text', () => {
      const wrapper = shallow(<Retry retryText={'Retry Failed Downloads?'} />)

      expect(wrapper.find('a').text()).toBe('Retry Failed Downloads?')
    })

    test('check link click', () => {
      const historyMock = {
        replace: jest.fn()
      }
      const wrapper = shallow(<Retry history={historyMock} />)
      const retryLink = findByTestAttr(wrapper, 'component-retry-link')
      retryLink.simulate('click', { preventDefault() {} })

      expect(historyMock.replace.mock.calls.length).toBe(1)
    })
  })
})
