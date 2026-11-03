<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

/**
 * Runs a callback from a second network of a Multisite
 */
trait SwitchesNetwork {

	/**
	 * @param callable $callback Given the second network's ID
	 */
	protected function on_another_network( callable $callback ): void {
		global $current_site;

		$network  = self::factory()->network->create(
			[
				'domain' => 'other-network.test',
				'path'   => '/',
			]
		);
		$original = $current_site;

		$current_site = get_network( $network );

		try {
			$callback( $network );
		} finally {
			$current_site = $original;
		}
	}
}
