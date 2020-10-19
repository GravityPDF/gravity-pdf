import { fieldLabel, fieldDescription, userRestrictionOption } from '../../utilities/page-model/helpers/field'
import General from '../../utilities/page-model/tabs/general-settings'

const run = new General()

fixture`General settings tab - User restriction field test`

test('should display \'User Restriction\' field', async t => {
  // Actions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')

  // Assertions
  await t
    .expect(fieldLabel('User Restriction').exists).ok()
    .expect(fieldDescription('Restrict PDF access to users with any of these capabilities. The Administrator Role always has full access.', 'span').exists).ok()
    .expect(userRestrictionOption('gravityforms_edit_forms').exists).ok()
    .expect(userRestrictionOption('gravityforms_delete_forms').exists).ok()
    .expect(userRestrictionOption('gravityforms_create_form').exists).ok()
    .expect(userRestrictionOption('gravityforms_view_entries').exists).ok()
    .expect(userRestrictionOption('gravityforms_edit_entries').exists).ok()
    .expect(userRestrictionOption('gravityforms_delete_entries').exists).ok()
    .expect(userRestrictionOption('gravityforms_view_settings').exists).ok()
    .expect(userRestrictionOption('gravityforms_edit_settings').exists).ok()
    .expect(userRestrictionOption('gravityforms_export_entries').exists).ok()
    .expect(userRestrictionOption('gravityforms_uninstall').exists).ok()
    .expect(userRestrictionOption('gravityforms_view_entry_notes').exists).ok()
    .expect(userRestrictionOption('gravityforms_edit_entry_notes').exists).ok()
    .expect(userRestrictionOption('gravityforms_view_updates').exists).ok()
    .expect(userRestrictionOption('gravityforms_view_addons').exists).ok()
    .expect(userRestrictionOption('gravityforms_preview_forms').exists).ok()
    .expect(userRestrictionOption('gravityforms_system_status').exists).ok()
    .expect(userRestrictionOption('gravityforms_logging').exists).ok()
    .expect(userRestrictionOption('gravityforms_api_settings').exists).ok()
    .expect(userRestrictionOption('switch_themes').exists).ok()
    .expect(userRestrictionOption('edit_themes').exists).ok()
    .expect(userRestrictionOption('activate_plugins').exists).ok()
    .expect(userRestrictionOption('edit_plugins').exists).ok()
    .expect(userRestrictionOption('edit_users').exists).ok()
    .expect(userRestrictionOption('edit_files').exists).ok()
    .expect(userRestrictionOption('manage_options').exists).ok()
    .expect(userRestrictionOption('moderate_comments').exists).ok()
    .expect(userRestrictionOption('manage_categories').exists).ok()
    .expect(userRestrictionOption('manage_links').exists).ok()
    .expect(userRestrictionOption('upload_files').exists).ok()
    .expect(userRestrictionOption('import').exists).ok()
    .expect(userRestrictionOption('unfiltered_html').exists).ok()
    .expect(userRestrictionOption('edit_posts').exists).ok()
    .expect(userRestrictionOption('edit_others_posts').exists).ok()
    .expect(userRestrictionOption('edit_published_posts').exists).ok()
    .expect(userRestrictionOption('publish_posts').exists).ok()
    .expect(userRestrictionOption('edit_pages').exists).ok()
    .expect(userRestrictionOption('read').exists).ok()
    .expect(userRestrictionOption('level_10').exists).ok()
    .expect(userRestrictionOption('level_9').exists).ok()
    .expect(userRestrictionOption('level_8').exists).ok()
    .expect(userRestrictionOption('level_7').exists).ok()
    .expect(userRestrictionOption('level_6').exists).ok()
    .expect(userRestrictionOption('level_5').exists).ok()
    .expect(userRestrictionOption('level_4').exists).ok()
    .expect(userRestrictionOption('level_3').exists).ok()
    .expect(userRestrictionOption('level_2').exists).ok()
    .expect(userRestrictionOption('level_1').exists).ok()
    .expect(userRestrictionOption('level_0').exists).ok()
    .expect(userRestrictionOption('edit_others_pages').exists).ok()
    .expect(userRestrictionOption('edit_published_pages').exists).ok()
    .expect(userRestrictionOption('publish_pages').exists).ok()
    .expect(userRestrictionOption('delete_pages').exists).ok()
    .expect(userRestrictionOption('delete_others_pages').exists).ok()
    .expect(userRestrictionOption('delete_published_pages').exists).ok()
    .expect(userRestrictionOption('delete_posts').exists).ok()
    .expect(userRestrictionOption('delete_others_posts').exists).ok()
    .expect(userRestrictionOption('delete_published_posts').exists).ok()
    .expect(userRestrictionOption('delete_private_posts').exists).ok()
    .expect(userRestrictionOption('edit_private_posts').exists).ok()
    .expect(userRestrictionOption('read_private_posts').exists).ok()
    .expect(userRestrictionOption('delete_private_pages').exists).ok()
    .expect(userRestrictionOption('edit_private_pages').exists).ok()
    .expect(userRestrictionOption('read_private_pages').exists).ok()
    .expect(userRestrictionOption('delete_users').exists).ok()
    .expect(userRestrictionOption('create_users').exists).ok()
    .expect(userRestrictionOption('unfiltered_upload').exists).ok()
    .expect(userRestrictionOption('edit_dashboard').exists).ok()
    .expect(userRestrictionOption('update_plugins').exists).ok()
    .expect(userRestrictionOption('delete_plugins').exists).ok()
    .expect(userRestrictionOption('install_plugins').exists).ok()
    .expect(userRestrictionOption('update_themes').exists).ok()
    .expect(userRestrictionOption('install_themes').exists).ok()
    .expect(userRestrictionOption('update_core').exists).ok()
    .expect(userRestrictionOption('list_users').exists).ok()
    .expect(userRestrictionOption('remove_users').exists).ok()
    .expect(userRestrictionOption('promote_users').exists).ok()
    .expect(userRestrictionOption('edit_theme_options').exists).ok()
    .expect(userRestrictionOption('delete_themes').exists).ok()
    .expect(userRestrictionOption('export').exists).ok()
})

test('should save selected user restriction capabilities', async t => {
  // Actions && Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.securityCollapsiblePanel)
    .click(run.userRestrictionOption.nth(0))
    .click(run.userRestrictionOption.nth(1))
    .click(run.userRestrictionOption.nth(2))
    .click(run.userRestrictionOption.nth(4))
    .click(run.userRestrictionOption.nth(5))
    .click(run.userRestrictionOption.nth(6))
    .click(run.userRestrictionOption.nth(7))
    .click(run.userRestrictionOption.nth(8))
    .click(run.userRestrictionOption.nth(9))
    .click(run.userRestrictionOption.nth(10))
    .click(run.userRestrictionOption.nth(11))
    .click(run.userRestrictionOption.nth(12))
    .click(run.userRestrictionOption.nth(13))
    .click(run.userRestrictionOption.nth(14))
    .click(run.userRestrictionOption.nth(15))
    .click(run.userRestrictionOption.nth(16))
    .click(run.userRestrictionOption.nth(17))
    .click(run.userRestrictionOption.nth(18))
    .click(run.userRestrictionOption.nth(19))
    .click(run.userRestrictionOption.nth(20))
    .click(run.userRestrictionOption.nth(21))
    .click(run.userRestrictionOption.nth(22))
    .click(run.userRestrictionOption.nth(23))
    .click(run.userRestrictionOption.nth(24))
    .click(run.userRestrictionOption.nth(25))
    .click(run.userRestrictionOption.nth(26))
    .click(run.userRestrictionOption.nth(27))
    .click(run.userRestrictionOption.nth(28))
    .click(run.userRestrictionOption.nth(29))
    .click(run.userRestrictionOption.nth(30))
    .click(run.userRestrictionOption.nth(31))
    .click(run.userRestrictionOption.nth(32))
    .click(run.userRestrictionOption.nth(33))
    .click(run.userRestrictionOption.nth(34))
    .click(run.userRestrictionOption.nth(35))
    .click(run.userRestrictionOption.nth(36))
    .click(run.userRestrictionOption.nth(37))
    .click(run.userRestrictionOption.nth(38))
    .click(run.userRestrictionOption.nth(39))
    .click(run.userRestrictionOption.nth(40))
    .click(run.userRestrictionOption.nth(41))
    .click(run.userRestrictionOption.nth(42))
    .click(run.userRestrictionOption.nth(43))
    .click(run.userRestrictionOption.nth(44))
    .click(run.userRestrictionOption.nth(45))
    .click(run.userRestrictionOption.nth(46))
    .click(run.userRestrictionOption.nth(47))
    .click(run.userRestrictionOption.nth(48))
    .click(run.userRestrictionOption.nth(49))
    .click(run.userRestrictionOption.nth(50))
    .click(run.userRestrictionOption.nth(51))
    .click(run.userRestrictionOption.nth(52))
    .click(run.userRestrictionOption.nth(53))
    .click(run.userRestrictionOption.nth(54))
    .click(run.userRestrictionOption.nth(55))
    .click(run.userRestrictionOption.nth(56))
    .click(run.userRestrictionOption.nth(57))
    .click(run.userRestrictionOption.nth(58))
    .click(run.userRestrictionOption.nth(59))
    .click(run.userRestrictionOption.nth(60))
    .click(run.userRestrictionOption.nth(61))
    .click(run.userRestrictionOption.nth(62))
    .click(run.userRestrictionOption.nth(63))
    .click(run.userRestrictionOption.nth(64))
    .click(run.userRestrictionOption.nth(65))
    .click(run.userRestrictionOption.nth(66))
    .click(run.userRestrictionOption.nth(67))
    .click(run.userRestrictionOption.nth(68))
    .click(run.userRestrictionOption.nth(69))
    .click(run.userRestrictionOption.nth(70))
    .click(run.userRestrictionOption.nth(71))
    .click(run.userRestrictionOption.nth(72))
    .click(run.userRestrictionOption.nth(73))
    .click(run.userRestrictionOption.nth(74))
    .click(run.userRestrictionOption.nth(75))
    .click(run.userRestrictionOption.nth(76))
    .click(run.userRestrictionOption.nth(77))
    .click(run.userRestrictionOption.nth(78))
    .click(run.saveSettings)
    .expect(run.userRestrictionOption.nth(0).checked).ok()
    .expect(run.userRestrictionOption.nth(1).checked).ok()
    .expect(run.userRestrictionOption.nth(2).checked).ok()
    .expect(run.userRestrictionOption.nth(3).checked).ok()
    .expect(run.userRestrictionOption.nth(4).checked).ok()
    .expect(run.userRestrictionOption.nth(5).checked).ok()
    .expect(run.userRestrictionOption.nth(6).checked).ok()
    .expect(run.userRestrictionOption.nth(7).checked).ok()
    .expect(run.userRestrictionOption.nth(8).checked).ok()
    .expect(run.userRestrictionOption.nth(9).checked).ok()
    .expect(run.userRestrictionOption.nth(10).checked).ok()
    .expect(run.userRestrictionOption.nth(11).checked).ok()
    .expect(run.userRestrictionOption.nth(12).checked).ok()
    .expect(run.userRestrictionOption.nth(13).checked).ok()
    .expect(run.userRestrictionOption.nth(14).checked).ok()
    .expect(run.userRestrictionOption.nth(15).checked).ok()
    .expect(run.userRestrictionOption.nth(16).checked).ok()
    .expect(run.userRestrictionOption.nth(17).checked).ok()
    .expect(run.userRestrictionOption.nth(18).checked).ok()
    .expect(run.userRestrictionOption.nth(19).checked).ok()
    .expect(run.userRestrictionOption.nth(20).checked).ok()
    .expect(run.userRestrictionOption.nth(21).checked).ok()
    .expect(run.userRestrictionOption.nth(22).checked).ok()
    .expect(run.userRestrictionOption.nth(23).checked).ok()
    .expect(run.userRestrictionOption.nth(24).checked).ok()
    .expect(run.userRestrictionOption.nth(25).checked).ok()
    .expect(run.userRestrictionOption.nth(26).checked).ok()
    .expect(run.userRestrictionOption.nth(27).checked).ok()
    .expect(run.userRestrictionOption.nth(28).checked).ok()
    .expect(run.userRestrictionOption.nth(29).checked).ok()
    .expect(run.userRestrictionOption.nth(30).checked).ok()
    .expect(run.userRestrictionOption.nth(31).checked).ok()
    .expect(run.userRestrictionOption.nth(32).checked).ok()
    .expect(run.userRestrictionOption.nth(33).checked).ok()
    .expect(run.userRestrictionOption.nth(34).checked).ok()
    .expect(run.userRestrictionOption.nth(35).checked).ok()
    .expect(run.userRestrictionOption.nth(36).checked).ok()
    .expect(run.userRestrictionOption.nth(37).checked).ok()
    .expect(run.userRestrictionOption.nth(38).checked).ok()
    .expect(run.userRestrictionOption.nth(39).checked).ok()
    .expect(run.userRestrictionOption.nth(40).checked).ok()
    .expect(run.userRestrictionOption.nth(41).checked).ok()
    .expect(run.userRestrictionOption.nth(42).checked).ok()
    .expect(run.userRestrictionOption.nth(43).checked).ok()
    .expect(run.userRestrictionOption.nth(44).checked).ok()
    .expect(run.userRestrictionOption.nth(45).checked).ok()
    .expect(run.userRestrictionOption.nth(46).checked).ok()
    .expect(run.userRestrictionOption.nth(47).checked).ok()
    .expect(run.userRestrictionOption.nth(48).checked).ok()
    .expect(run.userRestrictionOption.nth(49).checked).ok()
    .expect(run.userRestrictionOption.nth(50).checked).ok()
    .expect(run.userRestrictionOption.nth(51).checked).ok()
    .expect(run.userRestrictionOption.nth(52).checked).ok()
    .expect(run.userRestrictionOption.nth(53).checked).ok()
    .expect(run.userRestrictionOption.nth(54).checked).ok()
    .expect(run.userRestrictionOption.nth(55).checked).ok()
    .expect(run.userRestrictionOption.nth(56).checked).ok()
    .expect(run.userRestrictionOption.nth(57).checked).ok()
    .expect(run.userRestrictionOption.nth(58).checked).ok()
    .expect(run.userRestrictionOption.nth(59).checked).ok()
    .expect(run.userRestrictionOption.nth(60).checked).ok()
    .expect(run.userRestrictionOption.nth(61).checked).ok()
    .expect(run.userRestrictionOption.nth(62).checked).ok()
    .expect(run.userRestrictionOption.nth(63).checked).ok()
    .expect(run.userRestrictionOption.nth(64).checked).ok()
    .expect(run.userRestrictionOption.nth(65).checked).ok()
    .expect(run.userRestrictionOption.nth(66).checked).ok()
    .expect(run.userRestrictionOption.nth(67).checked).ok()
    .expect(run.userRestrictionOption.nth(68).checked).ok()
    .expect(run.userRestrictionOption.nth(69).checked).ok()
    .expect(run.userRestrictionOption.nth(70).checked).ok()
    .expect(run.userRestrictionOption.nth(71).checked).ok()
    .expect(run.userRestrictionOption.nth(72).checked).ok()
    .expect(run.userRestrictionOption.nth(73).checked).ok()
    .expect(run.userRestrictionOption.nth(74).checked).ok()
    .expect(run.userRestrictionOption.nth(75).checked).ok()
    .expect(run.userRestrictionOption.nth(76).checked).ok()
    .expect(run.userRestrictionOption.nth(77).checked).ok()
    .expect(run.userRestrictionOption.nth(78).checked).ok()
})

test('should save selected user restriction capabilities', async t => {
  // Actions && Assertions
  await run.navigateSettingsTab('gf_settings&subview=PDF&tab=general#')
  await t
    .click(run.securityCollapsiblePanel)
    .click(run.userRestrictionOption.nth(0))
    .click(run.userRestrictionOption.nth(1))
    .click(run.userRestrictionOption.nth(2))
    .click(run.userRestrictionOption.nth(4))
    .click(run.userRestrictionOption.nth(5))
    .click(run.userRestrictionOption.nth(6))
    .click(run.userRestrictionOption.nth(7))
    .click(run.userRestrictionOption.nth(8))
    .click(run.userRestrictionOption.nth(9))
    .click(run.userRestrictionOption.nth(10))
    .click(run.userRestrictionOption.nth(11))
    .click(run.userRestrictionOption.nth(12))
    .click(run.userRestrictionOption.nth(13))
    .click(run.userRestrictionOption.nth(14))
    .click(run.userRestrictionOption.nth(15))
    .click(run.userRestrictionOption.nth(16))
    .click(run.userRestrictionOption.nth(17))
    .click(run.userRestrictionOption.nth(18))
    .click(run.userRestrictionOption.nth(19))
    .click(run.userRestrictionOption.nth(20))
    .click(run.userRestrictionOption.nth(21))
    .click(run.userRestrictionOption.nth(22))
    .click(run.userRestrictionOption.nth(23))
    .click(run.userRestrictionOption.nth(24))
    .click(run.userRestrictionOption.nth(25))
    .click(run.userRestrictionOption.nth(26))
    .click(run.userRestrictionOption.nth(27))
    .click(run.userRestrictionOption.nth(28))
    .click(run.userRestrictionOption.nth(29))
    .click(run.userRestrictionOption.nth(30))
    .click(run.userRestrictionOption.nth(31))
    .click(run.userRestrictionOption.nth(32))
    .click(run.userRestrictionOption.nth(33))
    .click(run.userRestrictionOption.nth(34))
    .click(run.userRestrictionOption.nth(35))
    .click(run.userRestrictionOption.nth(36))
    .click(run.userRestrictionOption.nth(37))
    .click(run.userRestrictionOption.nth(38))
    .click(run.userRestrictionOption.nth(39))
    .click(run.userRestrictionOption.nth(40))
    .click(run.userRestrictionOption.nth(41))
    .click(run.userRestrictionOption.nth(42))
    .click(run.userRestrictionOption.nth(43))
    .click(run.userRestrictionOption.nth(44))
    .click(run.userRestrictionOption.nth(45))
    .click(run.userRestrictionOption.nth(46))
    .click(run.userRestrictionOption.nth(47))
    .click(run.userRestrictionOption.nth(48))
    .click(run.userRestrictionOption.nth(49))
    .click(run.userRestrictionOption.nth(50))
    .click(run.userRestrictionOption.nth(51))
    .click(run.userRestrictionOption.nth(52))
    .click(run.userRestrictionOption.nth(53))
    .click(run.userRestrictionOption.nth(54))
    .click(run.userRestrictionOption.nth(55))
    .click(run.userRestrictionOption.nth(56))
    .click(run.userRestrictionOption.nth(57))
    .click(run.userRestrictionOption.nth(58))
    .click(run.userRestrictionOption.nth(59))
    .click(run.userRestrictionOption.nth(60))
    .click(run.userRestrictionOption.nth(61))
    .click(run.userRestrictionOption.nth(62))
    .click(run.userRestrictionOption.nth(63))
    .click(run.userRestrictionOption.nth(64))
    .click(run.userRestrictionOption.nth(65))
    .click(run.userRestrictionOption.nth(66))
    .click(run.userRestrictionOption.nth(67))
    .click(run.userRestrictionOption.nth(68))
    .click(run.userRestrictionOption.nth(69))
    .click(run.userRestrictionOption.nth(70))
    .click(run.userRestrictionOption.nth(71))
    .click(run.userRestrictionOption.nth(72))
    .click(run.userRestrictionOption.nth(73))
    .click(run.userRestrictionOption.nth(74))
    .click(run.userRestrictionOption.nth(75))
    .click(run.userRestrictionOption.nth(76))
    .click(run.userRestrictionOption.nth(77))
    .click(run.userRestrictionOption.nth(78))
    .click(run.saveSettings)
    .expect(run.userRestrictionOption.nth(0).checked).notOk()
    .expect(run.userRestrictionOption.nth(1).checked).notOk()
    .expect(run.userRestrictionOption.nth(2).checked).notOk()
    .expect(run.userRestrictionOption.nth(3).checked).ok()
    .expect(run.userRestrictionOption.nth(4).checked).notOk()
    .expect(run.userRestrictionOption.nth(5).checked).notOk()
    .expect(run.userRestrictionOption.nth(6).checked).notOk()
    .expect(run.userRestrictionOption.nth(7).checked).notOk()
    .expect(run.userRestrictionOption.nth(8).checked).notOk()
    .expect(run.userRestrictionOption.nth(9).checked).notOk()
    .expect(run.userRestrictionOption.nth(10).checked).notOk()
    .expect(run.userRestrictionOption.nth(11).checked).notOk()
    .expect(run.userRestrictionOption.nth(12).checked).notOk()
    .expect(run.userRestrictionOption.nth(13).checked).notOk()
    .expect(run.userRestrictionOption.nth(14).checked).notOk()
    .expect(run.userRestrictionOption.nth(15).checked).notOk()
    .expect(run.userRestrictionOption.nth(16).checked).notOk()
    .expect(run.userRestrictionOption.nth(17).checked).notOk()
    .expect(run.userRestrictionOption.nth(18).checked).notOk()
    .expect(run.userRestrictionOption.nth(19).checked).notOk()
    .expect(run.userRestrictionOption.nth(20).checked).notOk()
    .expect(run.userRestrictionOption.nth(21).checked).notOk()
    .expect(run.userRestrictionOption.nth(22).checked).notOk()
    .expect(run.userRestrictionOption.nth(23).checked).notOk()
    .expect(run.userRestrictionOption.nth(24).checked).notOk()
    .expect(run.userRestrictionOption.nth(25).checked).notOk()
    .expect(run.userRestrictionOption.nth(26).checked).notOk()
    .expect(run.userRestrictionOption.nth(27).checked).notOk()
    .expect(run.userRestrictionOption.nth(28).checked).notOk()
    .expect(run.userRestrictionOption.nth(29).checked).notOk()
    .expect(run.userRestrictionOption.nth(30).checked).notOk()
    .expect(run.userRestrictionOption.nth(31).checked).notOk()
    .expect(run.userRestrictionOption.nth(32).checked).notOk()
    .expect(run.userRestrictionOption.nth(33).checked).notOk()
    .expect(run.userRestrictionOption.nth(34).checked).notOk()
    .expect(run.userRestrictionOption.nth(35).checked).notOk()
    .expect(run.userRestrictionOption.nth(36).checked).notOk()
    .expect(run.userRestrictionOption.nth(37).checked).notOk()
    .expect(run.userRestrictionOption.nth(38).checked).notOk()
    .expect(run.userRestrictionOption.nth(39).checked).notOk()
    .expect(run.userRestrictionOption.nth(40).checked).notOk()
    .expect(run.userRestrictionOption.nth(41).checked).notOk()
    .expect(run.userRestrictionOption.nth(42).checked).notOk()
    .expect(run.userRestrictionOption.nth(43).checked).notOk()
    .expect(run.userRestrictionOption.nth(44).checked).notOk()
    .expect(run.userRestrictionOption.nth(45).checked).notOk()
    .expect(run.userRestrictionOption.nth(46).checked).notOk()
    .expect(run.userRestrictionOption.nth(47).checked).notOk()
    .expect(run.userRestrictionOption.nth(48).checked).notOk()
    .expect(run.userRestrictionOption.nth(49).checked).notOk()
    .expect(run.userRestrictionOption.nth(50).checked).notOk()
    .expect(run.userRestrictionOption.nth(51).checked).notOk()
    .expect(run.userRestrictionOption.nth(52).checked).notOk()
    .expect(run.userRestrictionOption.nth(53).checked).notOk()
    .expect(run.userRestrictionOption.nth(54).checked).notOk()
    .expect(run.userRestrictionOption.nth(55).checked).notOk()
    .expect(run.userRestrictionOption.nth(56).checked).notOk()
    .expect(run.userRestrictionOption.nth(57).checked).notOk()
    .expect(run.userRestrictionOption.nth(58).checked).notOk()
    .expect(run.userRestrictionOption.nth(59).checked).notOk()
    .expect(run.userRestrictionOption.nth(60).checked).notOk()
    .expect(run.userRestrictionOption.nth(61).checked).notOk()
    .expect(run.userRestrictionOption.nth(62).checked).notOk()
    .expect(run.userRestrictionOption.nth(63).checked).notOk()
    .expect(run.userRestrictionOption.nth(64).checked).notOk()
    .expect(run.userRestrictionOption.nth(65).checked).notOk()
    .expect(run.userRestrictionOption.nth(66).checked).notOk()
    .expect(run.userRestrictionOption.nth(67).checked).notOk()
    .expect(run.userRestrictionOption.nth(68).checked).notOk()
    .expect(run.userRestrictionOption.nth(69).checked).notOk()
    .expect(run.userRestrictionOption.nth(70).checked).notOk()
    .expect(run.userRestrictionOption.nth(71).checked).notOk()
    .expect(run.userRestrictionOption.nth(72).checked).notOk()
    .expect(run.userRestrictionOption.nth(73).checked).notOk()
    .expect(run.userRestrictionOption.nth(74).checked).notOk()
    .expect(run.userRestrictionOption.nth(75).checked).notOk()
    .expect(run.userRestrictionOption.nth(76).checked).notOk()
    .expect(run.userRestrictionOption.nth(77).checked).notOk()
    .expect(run.userRestrictionOption.nth(78).checked).notOk()
})
