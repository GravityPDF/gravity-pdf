<?php

declare( strict_types=1 );

namespace GFPDF\Helper;

use Exception;
use GFPDF\Tests\Integration\TestCase;

/**
 * @group   helper
 */
class Test_Helper_Async_Notifications extends TestCase {

	private Helper_Async_Notifications $async_notifications;

	private int $admin_id;

	private int $author_id;

	public function set_up(): void {
		parent::set_up();

		$this->async_notifications = new Helper_Async_Notifications();
		$this->admin_id            = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$this->author_id           = self::factory()->user->create();

		wp_set_current_user( $this->admin_id );
	}

	public function test_init_registers_the_resend_filter(): void {
		$this->async_notifications->init();

		$this->assertSame( 10, has_filter( 'gform_before_resend_notifications', [ $this->async_notifications, 'record_resend' ] ) );
	}

	public function test_render_as_sender_uses_the_author_only_in_the_background_task(): void {
		$entry = [ 'created_by' => (string) $this->author_id ];

		$this->assertSame( $this->admin_id, $this->async_notifications->render_as_sender( [], $entry, 'get_current_user_id' ) );

		do_action( 'gform_pre_process_async_notifications' );
		$this->assertSame( $this->author_id, $this->async_notifications->render_as_sender( [], $entry, 'get_current_user_id' ) );
		$this->assertSame( $this->admin_id, get_current_user_id() );

		do_action( 'gform_post_process_async_notifications' );
		$this->assertSame( $this->admin_id, $this->async_notifications->render_as_sender( [], $entry, 'get_current_user_id' ) );
	}

	public function test_render_as_sender_uses_user_0_for_a_guest_or_deleted_author(): void {
		$deleted_id = self::factory()->user->create();
		self::delete_user( $deleted_id );

		do_action( 'gform_pre_process_async_notifications' );

		$this->assertSame( 0, $this->async_notifications->render_as_sender( [], [ 'created_by' => '' ], 'get_current_user_id' ) );
		$this->assertSame( 0, $this->async_notifications->render_as_sender( [], [ 'created_by' => (string) $deleted_id ], 'get_current_user_id' ) );
		$this->assertSame( $this->admin_id, get_current_user_id() );
	}

	public function test_a_resend_renders_as_the_user_who_resent_it(): void {
		$form         = $this->async_notifications->record_resend( [ 'notifications' => [ 'n1' => [ 'id' => 'n1' ] ] ] );
		$notification = $form['notifications']['n1'];
		$entry        = [ 'created_by' => (string) $this->author_id ];

		/* The background task may run as anyone, including user 0 from the cron healthcheck */
		wp_set_current_user( 0 );
		do_action( 'gform_pre_process_async_notifications' );

		$this->assertSame( $this->admin_id, $this->async_notifications->render_as_sender( $notification, $entry, 'get_current_user_id' ) );
		$this->assertSame( 0, get_current_user_id() );

		$sign = function ( $user_id, $expires ) {
			return "$user_id|$expires|" . wp_hash( "gfpdf_resend|$user_id|$expires" );
		};

		/* Built the same way it's accepted, so the rejections below are down to what was changed */
		$notification['gfpdf_resend'] = $sign( $this->admin_id, time() + 60 );
		$this->assertSame( $this->admin_id, $this->async_notifications->render_as_sender( $notification, $entry, 'get_current_user_id' ) );

		[ , $expires, $signature ] = explode( '|', $notification['gfpdf_resend'] );
		$other_admin_id            = self::factory()->user->create( [ 'role' => 'administrator' ] );

		foreach ( [ 'forged', "$other_admin_id|$expires|$signature", $sign( $this->admin_id, time() - 1 ) ] as $resend ) {
			$notification['gfpdf_resend'] = $resend;
			$this->assertSame( $this->author_id, $this->async_notifications->render_as_sender( $notification, $entry, 'get_current_user_id' ), $resend );
		}
	}

	public function test_render_as_sender_restores_the_user_when_the_render_throws(): void {
		$entry = [ 'created_by' => (string) $this->author_id ];

		do_action( 'gform_pre_process_async_notifications' );

		try {
			$this->async_notifications->render_as_sender(
				[],
				$entry,
				function () {
					throw new Exception( 'Render failed' );
				}
			);
			$this->fail( 'The exception was swallowed' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Render failed', $e->getMessage() );
		}

		$this->assertSame( $this->admin_id, get_current_user_id() );
	}
}
