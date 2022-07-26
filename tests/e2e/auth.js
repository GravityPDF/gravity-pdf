import { Role } from 'testcafe'
const path = require('path')

require('dotenv').config({ path: path.resolve(process.cwd(), 'wordpress/.env') })

const url = process.env.WP_BASE_URL || 'http://localhost'
const port = process.env.WP_ENV_TESTS_PORT || '8889'
export const baseURL = url + ':' + port

export const admin = Role(`${baseURL}/wp-login.php`, async t => {
  await t
    .wait(100)
    .typeText('#user_login', 'admin', { paste: true })
    .typeText('#user_pass', 'password', { paste: true })
    .click('#wp-submit')
})
