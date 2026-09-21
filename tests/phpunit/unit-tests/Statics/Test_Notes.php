<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GF_UnitTest_Factory;
use GFAPI;
use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * @group statics
 */
class Test_Notes extends WP_UnitTestCase {

	public function test_add_entry_note() {
		$factory = new GF_UnitTest_Factory();
		$form    = $factory->form->create_and_get();
		$entry   = $factory->entry->create_and_get( [ 'form_id' => $form['id'] ] );

		Notes::add_entry_note( $entry['id'], 'A <a href="https://example.org">note</a>', 'error' );

		$notes = GFAPI::get_notes( [ 'entry_id' => $entry['id'] ] );

		$this->assertCount( 1, $notes );
		$this->assertSame( '<div>A <a href="https://example.org">note</a></div>', $notes[0]->value );
		$this->assertSame( Notes::NOTE_TYPE, $notes[0]->note_type );
		$this->assertSame( 'error', $notes[0]->sub_type );
		$this->assertSame( 'Gravity PDF', $notes[0]->user_name );
		$this->assertEmpty( $notes[0]->user_id );

		Notes::add_entry_note( $entry['id'], 'Another note', '', 'Admin Notification (ID: 1234)' );

		$notes = GFAPI::get_notes( [ 'entry_id' => $entry['id'] ] );

		$this->assertSame( 'Admin Notification (ID: 1234)', $notes[1]->user_name );
	}

	public function test_note_avatar() {
		$avatar = '<svg></svg>';

		$this->assertStringContainsString( 'src/assets/images/gravitypdf-logo.svg', Notes::note_avatar( $avatar, (object) [ 'note_type' => Notes::NOTE_TYPE, 'user_id' => 0 ] ) );
		$this->assertFileExists( PDF_PLUGIN_DIR . 'src/assets/images/gravitypdf-logo.svg' );

		$this->assertSame( $avatar, Notes::note_avatar( $avatar, (object) [ 'note_type' => 'notification', 'user_id' => 0 ] ) );
		$this->assertSame( $avatar, Notes::note_avatar( $avatar, (object) [ 'note_type' => Notes::NOTE_TYPE, 'user_id' => 1 ] ) );
	}
}
