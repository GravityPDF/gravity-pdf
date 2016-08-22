import React from 'react'
import { match } from 'react-router'

import { Routes } from '../../../../src/assets/js/react/router/templateRouter'

describe('<Routes />', () => {
  it('check the correct component renders in our route', () => {

    const routes = Routes()

    match({ routes, location: '/template' }, (error, redirectLocation, renderProps) => {
      const route = renderProps.routes[ renderProps.routes.length - 1 ]
        expect(route.component.displayName).to.equal('Connect(TemplateList)')
    })

    match({ routes, location: '/template/my-id' }, (error, redirectLocation, renderProps) => {
      const route = renderProps.routes[ renderProps.routes.length - 1 ]
      expect(route.component.displayName).to.equal('Connect(TemplateSingle)')
    })

    match({ routes, location: '/' }, (error, redirectLocation, renderProps) => {
      const route = renderProps.routes[ renderProps.routes.length - 1 ]
      expect(route.path).to.equal('*')
      expect(route.component.displayName).to.be.undefined
    })
  })
})