<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use Exception;
use GFPDF\Tests\Integration\TestCase;

/**
 * @package GFPDF\Statics
 *
 * @group   statics
 */
class Test_Queue_Callbacks extends TestCase {

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
	}

	public function test_create_pdf_throws_when_generation_returns_wp_error() {
		$this->expectException( Exception::class );
		Queue_Callbacks::create_pdf( 0, '' );
	}

	public function test_create_pdf_restores_previous_user_after_run() {
		$original    = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$masquerade  = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $original );

		try {
			Queue_Callbacks::create_pdf( 0, '', $masquerade );
		} catch ( Exception $e ) {
			// Expected — invalid IDs throw, but the user-restore must still happen.
		}

		$this->assertSame( $original, get_current_user_id(), 'previous user must be restored even on failure' );
	}

	public function test_send_notification_throws_when_form_missing() {
		$this->expectException( Exception::class );
		Queue_Callbacks::send_notification( 99999, 0, [] );
	}

	public function test_send_notification_throws_when_entry_invalid() {
		$form_id = $this->form( 'all-form-fields' )['id'];

		$this->expectException( Exception::class );
		Queue_Callbacks::send_notification( $form_id, 0, [] );
	}

	public function test_send_notification_restores_previous_user_after_successful_send() {
		$original   = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$masquerade = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $original );

		$form_id  = $this->form( 'all-form-fields' )['id'];
		$entry_id = $this->entry( 'all-form-fields' )['id'];

		$notification = [
			'id'      => 'test-notification-restore',
			'name'    => 'Test Notification',
			'event'   => 'form_submission',
			'to'      => 'noreply@example.test',
			'subject' => 'Test',
			'message' => 'Body',
			'from'    => 'sender@example.test',
		];

		/* Short-circuit the actual email send; we only care about the user-restore path. */
		add_filter( 'gform_pre_send_email', static function ( $email ) {
			$email['abort_email'] = true;
			return $email;
		} );

		Queue_Callbacks::send_notification( $form_id, $entry_id, $notification, $masquerade );

		$this->assertSame( $original, get_current_user_id(), 'previous user must be restored after a successful send' );
	}
}
