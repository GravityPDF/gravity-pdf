/* Dependencies */
import React, { lazy, Suspense } from 'react'
import { createRoot } from 'react-dom/client'
import { Provider } from 'react-redux'
/* Redux store */
import { getStore } from '../store'
/* Routes */
const Routes = lazy(() => import('../router/coreFontRouter'))

/**
 * Core Font Downloader Bootstrap
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Mount our Core Font UI on the DOM
 *
 * @since 5.0
 */
export default function coreFontBootstrap () {
  const container = document.getElementById('gfpdf-button-wrapper-install_core_fonts')
  const button = container.getElementsByTagName('button')[0]
  const store = getStore()
  const root = createRoot(container)

  root.render(
    <Suspense fallback={<div>{GFPDF.spinnerAlt}</div>}>
      <Provider store={store}>
        <Routes button={button} />
      </Provider>
    </Suspense>
  )
}
