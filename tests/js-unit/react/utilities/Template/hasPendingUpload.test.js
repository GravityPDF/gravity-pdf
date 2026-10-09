import { hasPendingUpload } from '../../../../../src/assets/js/react/utilities/Template/hasPendingUpload'

describe('Utilities - hasPendingUpload.js', () => {
  test('is true while any upload is pending', () => {
    expect(hasPendingUpload([{ status: 'success' }, { status: 'pending' }])).toBe(true)
  })

  test('is false once every upload has reported back', () => {
    expect(hasPendingUpload([{ status: 'success' }, { status: 'failed' }])).toBe(false)
    expect(hasPendingUpload([])).toBe(false)
  })
})
