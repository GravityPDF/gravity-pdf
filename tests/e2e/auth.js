const { Role } = require('testcafe')
const path = require('path')

require('dotenv').config({ path: path.resolve(process.cwd(), '.env') })

const url = process.env.WP_BASE_URL || 'http://localhost'
const port = process.env.WP_ENV_TESTS_PORT || '8889'
const baseURL = url + ':' + port

const admin = Role(`${baseURL}/wp-login.php`, async t => {
  await t
    .typeText('#user_login', 'admin', { paste: true })
    .typeText('#user_pass', 'password', { paste: true })
    .click('#wp-submit')
})

const editor = Role(`${baseURL}/wp-login.php`, async t => {
  await t
    .typeText('#user_login', 'editor', { paste: true })
    .typeText('#user_pass', 'password', { paste: true })
    .click('#wp-submit')
})

module.exports = {
  baseURL: baseURL,
  admin: admin,
  editor: editor
}
