/* Dependencies */
import { useEffect } from 'react'
import { useDispatch } from 'react-redux'
/* Redux Actions */
import { clearFinishedTemplateUploads } from '../../actions/templates'

/**
 * Rendered while the Template Manager is closed, so it reopens without the last batch's results
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.18.0
 */

/**
 * React Component
 *
 * @returns {null} Nothing to render
 *
 * @since 6.18.0
 */
const TemplateManagerClosed = () => {
  const dispatch = useDispatch()

  useEffect(() => {
    dispatch(clearFinishedTemplateUploads())
  }, [dispatch])

  return null
}

export default TemplateManagerClosed
