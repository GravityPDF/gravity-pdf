<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

/**
 * Moves the PDF tmp location into the system temp dir, where flock() works. Docker Desktop's bind mounts grant every
 * lock without enforcing it.
 */
trait UsesLockableTmpLocation {

	/** @var string|null */
	private $original_tmp_location;

	protected function use_lockable_tmp_location(): void {
		$data = \GPDFAPI::get_data_class();

		$this->original_tmp_location = $this->original_tmp_location ?? $data->template_tmp_location;
		$data->template_tmp_location = trailingslashit( get_temp_dir() ) . 'gfpdf-tmp-' . uniqid() . '/';
	}

	protected function restore_tmp_location(): void {
		if ( $this->original_tmp_location === null ) {
			return;
		}

		$data = \GPDFAPI::get_data_class();
		if ( is_dir( $data->template_tmp_location ) ) {
			\GPDFAPI::get_misc_class()->rmdir( $data->template_tmp_location );
		}

		$data->template_tmp_location = $this->original_tmp_location;
		$this->original_tmp_location = null;
	}
}
