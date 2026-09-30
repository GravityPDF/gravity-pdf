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
class Test_Acting_User extends TestCase {

	private int $original_id;

	private int $acting_id;

	public function set_up(): void {
		parent::set_up();

		$this->original_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$this->acting_id   = self::factory()->user->create();

		wp_set_current_user( $this->original_id );
	}

	public function test_run_returns_the_callback_value_as_the_given_user(): void {
		$this->assertSame( $this->acting_id, Acting_User::run( $this->acting_id, 'get_current_user_id' ) );
		$this->assertSame( 0, Acting_User::run( 0, 'get_current_user_id' ) );
		$this->assertSame( $this->acting_id, Acting_User::run( (string) $this->acting_id, 'get_current_user_id' ) );

		$this->assertSame( $this->original_id, get_current_user_id() );
	}

	public function test_run_restores_the_previous_user_after_a_throw(): void {
		try {
			Acting_User::run(
				$this->acting_id,
				function () {
					throw new Exception( 'Callback failed' );
				}
			);
			$this->fail( 'The exception was swallowed' );
		} catch ( Exception $e ) {
			$this->assertSame( 'Callback failed', $e->getMessage() );
		}

		$this->assertSame( $this->original_id, get_current_user_id() );
	}

	public function test_nested_runs_unwind_to_each_previous_user(): void {
		$seen = Acting_User::run(
			$this->acting_id,
			function () {
				$inner = Acting_User::run( 0, 'get_current_user_id' );

				return [ $inner, get_current_user_id() ];
			}
		);

		$this->assertSame( [ 0, $this->acting_id ], $seen );
		$this->assertSame( $this->original_id, get_current_user_id() );
	}

	public function test_run_restores_a_logged_out_user(): void {
		wp_set_current_user( 0 );

		$this->assertSame( $this->acting_id, Acting_User::run( $this->acting_id, 'get_current_user_id' ) );
		$this->assertSame( 0, get_current_user_id() );
	}
}
