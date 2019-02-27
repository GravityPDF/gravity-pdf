import React from 'react'
import { mount } from 'enzyme'
import Immutable from 'immutable'
import createHistory from 'history/createHashHistory'
import { TemplateActivateButton } from '../../../../../src/assets/js/react/components/Template/TemplateActivateButton'

describe(
	'<TemplateActivateButton />',
	() => {
		let History = createHistory()

		beforeEach(
			function () {
				History.replace( '/template' )
			}
		)

	it(
		'a button should be displayed',
		() => {
			const comp = mount( < TemplateActivateButton history = {History} buttonText = "Activate" / > )
			expect( comp.find( 'a' ).text() ).to.equal( 'Activate' )
		}
	)

	it(
		'simulate click on a button and check our functions fire',
		() => {
			const onButtonClick                             = sinon.spy()
			const comp                                  = mount(
				< TemplateActivateButton history        = {History} onTemplateSelect = {onButtonClick}
											   template = {Immutable.fromJS( [] )} activateText = "Activate" / >
			)

		comp.find( 'a' ).simulate( 'click' )
		expect( window.location.hash ).to.equal( '#/' )
		expect( onButtonClick.calledOnce ).to.equal( true )
		}
	)
	}
)
