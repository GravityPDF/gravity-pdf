<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use Exception;
use GFPDF\Helper\Helper_PDF;
use GFPDF\Model\Model_PDF;
use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package GFPDF\Statics
 *
 * @group   statics
 */
class Test_Queue_Callbacks extends TestCase {

	const PDF_ID = '556690c67856b';

	const NESTED_PDF_ID = 'fawf90c678523b';

	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		static::load_fixtures( [ 'all-form-fields' ], [ 'all-form-fields' ] );
		static::copy_test_fonts();
	}

	public static function tear_down_after_class(): void {
		static::remove_test_fonts();
		parent::tear_down_after_class();
	}

	public function set_up(): void {
		parent::set_up();

		/* A cache key of this test's own, so PDFs cached by earlier tests aren't reused */
		$run = uniqid( '', true );
		add_filter(
			'gfpdf_cache_hash_extra',
			static function ( $extra ) use ( $run ) {
				$extra['queue_callbacks_test'] = $run;

				return $extra;
			}
		);

		add_filter(
			'gfpdf_pdf_config',
			static function ( $settings ) {
				$settings['template'] = 'zadani';

				return $settings;
			}
		);
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

	public function test_create_pdf_restores_the_user_when_generation_throws() {
		$original   = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$masquerade = self::factory()->user->create();
		wp_set_current_user( $original );

		$seen = [];
		add_filter(
			'gfpdf_pdf_generator_pre_processing',
			static function ( Helper_PDF $pdf_generator ) use ( &$seen ) {
				$seen = [ get_current_user_id(), $pdf_generator->get_cache_bypass() ];

				throw new Exception( 'Generation failed' );
			}
		);

		try {
			Queue_Callbacks::create_pdf( $this->entry( 'all-form-fields' )['id'], '555ad84787d7e', $masquerade );
			$this->fail( 'The exception was swallowed' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Generation failed', $e->getMessage() );
		}

		$this->assertSame( [ $masquerade, false ], $seen );
		$this->assertSame( $original, get_current_user_id() );
	}

	public function test_create_pdf_keeps_a_callers_bypass_override() {
		add_filter( 'gfpdf_override_pdf_bypass', '__return_false', 20 );
		$entry_id = $this->entry( 'all-form-fields' )['id'];

		Queue_Callbacks::create_pdf( $entry_id, self::PDF_ID );
		$this->assertSame( 20, has_filter( 'gfpdf_override_pdf_bypass', '__return_false' ) );

		add_action(
			'gfpdf_pre_generate_and_save_pdf',
			static function () {
				throw new Exception( 'Generation failed' );
			}
		);

		try {
			Queue_Callbacks::create_pdf( $entry_id, self::PDF_ID );
			$this->fail( 'The exception was swallowed' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Generation failed', $e->getMessage() );
		}

		$this->assertSame( 20, has_filter( 'gfpdf_override_pdf_bypass', '__return_false' ) );
	}

	public function test_a_bypass_listener_applies_to_every_render() {
		$entry    = $this->entry( 'all-form-fields' );
		$settings = GPDFAPI::get_pdf( $entry['form_id'], self::PDF_ID );

		add_filter( 'gfpdf_override_pdf_bypass', '__return_true' );

		$path = $this->model()->generate_and_save_pdf( $entry, $settings, 'view' );
		$this->assertSame( 'bypass', $this->model()->get_cache_status( $path ), 'View' );

		Queue_Callbacks::create_pdf( $entry['id'], self::PDF_ID );
		$this->assertSame( 'bypass', $this->model()->get_cache_status( $path ), 'Background render' );

		$this->assertSame( $path, GPDFAPI::create_pdf( $entry['id'], self::PDF_ID ) );
		$this->assertSame( 'bypass', $this->model()->get_cache_status( $path ), 'GPDFAPI::create_pdf()' );
	}

	public function test_a_template_can_bypass_the_cache_for_a_pdf_it_generates_in_a_background_render() {
		$entry_id = $this->entry( 'all-form-fields' )['id'];

		add_filter(
			'gfpdf_override_pdf_bypass',
			static function ( $bypass, Helper_PDF $pdf_generator ) {
				return $bypass || $pdf_generator->get_settings()['id'] === self::NESTED_PDF_ID;
			},
			10,
			2
		);

		$outer  = '';
		$nested = '';
		$nest   = static function ( $form, $entry, $settings, $pdf_generator ) use ( &$nest, &$outer, &$nested, $entry_id ) {
			remove_action( 'gfpdf_pre_pdf_generation', $nest );

			$outer  = $pdf_generator->get_full_pdf_path();
			$nested = GPDFAPI::create_pdf( $entry_id, self::NESTED_PDF_ID );
		};
		add_action( 'gfpdf_pre_pdf_generation', $nest, 10, 4 );

		Queue_Callbacks::create_pdf( $entry_id, self::PDF_ID );

		$this->assertSame( 'miss', $this->model()->get_cache_status( $outer ) );
		$this->assertSame( 'bypass', $this->model()->get_cache_status( $nested ) );
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

	public function test_send_notification_restores_the_user_when_a_notification_listener_throws() {
		$original   = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$masquerade = self::factory()->user->create();
		wp_set_current_user( $original );

		$seen = null;
		add_filter(
			'gform_notification',
			static function () use ( &$seen ) {
				$seen = get_current_user_id();

				throw new Exception( 'Listener failed' );
			}
		);

		$notification = [
			'id'    => 'test-notification-throws',
			'event' => 'form_submission',
		];

		try {
			Queue_Callbacks::send_notification( $this->form( 'all-form-fields' )['id'], $this->entry( 'all-form-fields' )['id'], $notification, $masquerade );
			$this->fail( 'The exception was swallowed' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Listener failed', $e->getMessage() );
		}

		$this->assertSame( $masquerade, $seen );
		$this->assertSame( $original, get_current_user_id() );
	}

	private function model(): Model_PDF {
		return GPDFAPI::get_mvc_class( 'Model_PDF' );
	}
}
