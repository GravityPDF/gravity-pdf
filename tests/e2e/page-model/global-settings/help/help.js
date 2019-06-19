import { Selector, t } from 'testcafe'
import { admin, baseURL } from '../../../auth'

class Help {
  constructor () {
    this.searchBar = Selector('#search-help-input')
    this.noResult = Selector('.noResult')
    this.messageText = Selector('.noResult')
    this.resultExist = Selector('.resultExist')
  }

  async navigateSettingsTab (text) {
    await t
      .useRole(admin)
      .navigateTo(`${baseURL}/wp-admin/admin.php?page=${text}`)
  }
}

export default Help
