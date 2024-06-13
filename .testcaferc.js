const { admin } = require('./tests/e2e/auth')

module.exports = {
  src: 'tests/e2e',
  disableNativeAutomation: true,
  disableMultipleWindows: true,
  skipJsErrors: true,
  screenshots: {
    takeOnFails: true,
    fullPage: true,
  },
  hooks: {
    test: {
      before: async t => {
        await t.useRole(admin)
      }
    },
  },
}