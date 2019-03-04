import selector, { searchTemplates, sortTemplates } from '../../../../src/assets/js/react/selectors/getTemplates'

const templateObject = [
  {id: 'zadani', group: 'Core', template: 'Zadani', description: '', author: 'Gravity PDF'},
  {id: 'rubix', group: 'Core', template: 'Rubix', description: '', author: ''},
  {id: 'focus-gravity', group: 'Core', template: 'Focus Gravity', description: '', author: 'William'},
  {id: 'adelade', group: 'Core', template: 'Adelade', description: '', author: 'William'},
  {id: 'default', group: 'Legacy', template: 'Default', description: 'Old Template', author: ''},
  {id: 'default-template', group: 'Legacy', template: 'Default Template', description: '', author: ''},
  {id: 'default-template', group: 'Core', template: 'Default Template', description: '', author: ''},
  {id: 'new-template', group: 'Custom', template: 'New Template', description: '', author: '', new: true},
]

const templates = templateObject

describe('sortTemplates()', () => {
  it('check the function sorts the results correctly', () => {
    let list = sortTemplates(templates, '')

    expect(list[0].id).is.equal('adelade')

    /* Check our new template is pushed to the end of the queue */
    const checkLast = list.length - 1
    expect(list[checkLast].id).is.equal('new-template')

    /* check the active template is hoisted above the rest */
    list = sortTemplates(templates, 'zadani')
    expect(list[0].id).is.equal('zadani')
  })
})

describe('searchTemplates()', () => {
  it('check we get the expected results', () => {
    expect(searchTemplates('default', templates).length).is.equal(3)
    expect(searchTemplates('Gravity PDF', templates).length).is.equal(1)
    expect(searchTemplates('William', templates).length).is.equal(2)
    expect(searchTemplates('Core', templates).length).is.equal(5)
    expect(searchTemplates('Zadani', templates).length).is.equal(1)
    expect(searchTemplates('Old', templates).length).is.equal(1)
  })
})

describe('selector', () => {
  it('check we get the correct results back from the actual reselect function', () => {

    let state = {
      template: {
        list: templates,
        search: '',
        activeTemplate: '',
      }
    }

    //check the sort works
    let list = selector(state, state, state)
    expect(list[0].id).is.equal('adelade')

    //check the search works
    let state2 = {
      template: {
        list: templates,
        search: 'default',
        activeTemplate: '',
      }
    }

    list = selector(state2, state2, state2)
    expect(list.length).is.equal(3)

    //check the sort and search works
    let state3 = {
      template: {
        list: templates,
        search: 'core',
        activeTemplate: 'zadani',
      }
    }

    list = selector(state3, state3, state3)
    const checkforLast = list.length - 1

    expect(list.length).is.equal(5)
    expect(list[0].id).is.equal('zadani')
    expect(list[checkforLast].id).is.equal('rubix')
  })
})
