const { admin } = require('./tests/e2e/auth')

module.exports = {
  hooks: {
    test: {
      before: async t => {
        await t.useRole(admin)
      }
    },
  },
}