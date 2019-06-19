import { Role } from 'testcafe'

require('dotenv').config()

export const baseURL = process.env.E2E_TESTING_URL

export const admin = Role(`${baseURL}/wp-login.php`, async t => {
  await t
    .wait(100)
    .typeText('#user_login', 'admin', { paste: true })
    .typeText('#user_pass', 'password', { paste: true })
    .click('#wp-submit')
})
