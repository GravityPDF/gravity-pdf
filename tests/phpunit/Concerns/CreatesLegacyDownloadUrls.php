<?php

declare(strict_types=1);

namespace GFPDF\Tests\Concerns;

/**
 * Creates forms that still hand out a v3 `?gf_pdf=1` PDF download URL, which Deprecation detects.
 */
trait CreatesLegacyDownloadUrls {

	use UsesFactory;

	/**
	 * Create a form carrying a legacy download URL in `$location`
	 *
	 * Gravity Forms stores the form, its confirmations and its notifications in three separate columns, each of
	 * which the detector has to search.
	 *
	 * @param string $location One of `form`, `confirmations` or `notifications`
	 * @param string $marker   The query argument the URL carries, so a near miss can be set up too
	 *
	 * @return int The new form's ID
	 */
	protected function create_form_with_legacy_url( string $location = 'confirmations', string $marker = 'gf_pdf=1' ): int {
		$form = \GFAPI::get_form( $this->gf_factory()->form->create() );
		$link = sprintf( '<a href="/?%s&fid=%d&lid=1&template=zadani.php">Download</a>', $marker, $form['id'] );

		switch ( $location ) {
			case 'confirmations':
				$form['confirmations'] = [
					[
						'id'      => 'abc123',
						'name'    => 'Default Confirmation',
						'type'    => 'message',
						'message' => $link,
					],
				];
				break;

			case 'notifications':
				$form['notifications'] = [
					[
						'id'      => 'def456',
						'name'    => 'Admin Notification',
						'to'      => 'admin@example.org',
						'subject' => 'New submission',
						'message' => $link,
					],
				];
				break;

			default:
				$form['description'] = $link;
		}

		\GFAPI::update_form( $form );

		/* Deprecation holds what its detectors found for the request, and this is one of the things they read */
		\GFPDF\Statics\Deprecation::flush_cache();

		return (int) $form['id'];
	}
}
