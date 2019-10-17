import { Role } from 'testcafe'
const path = require('path')

require('dotenv').config({ path: path.resolve(process.cwd(), 'wordpress/.env') })

export const baseURL = process.env.WP_BASE_URL.replace('${LOCAL_PORT}', process.env.LOCAL_PORT)

export const admin = Role(`${baseURL}/wp-login.php`, async t => {
  await t
    .wait(100)
    .typeText('#user_login', 'admin', { paste: true })
    .typeText('#user_pass', 'password', { paste: true })
    .click('#wp-submit')
})
