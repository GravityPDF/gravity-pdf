<?php

namespace GFPDF\Helper;

use GFForms;
use GFPDF\Statics\Acting_User;
use Gravity_Forms\Gravity_Forms\Async\GF_Background_Process_Service_Provider;
use Gravity_Forms\Gravity_Forms\Async\GF_Notifications_Processor;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gravity Forms background (async) notifications and resends
 *
 * @since 6.17.3
 */
class Helper_Async_Notifications implements Helper_Interface_Filters {

	/**
	 * Register the helper's hooks
	 *
	 * @since 6.17.3
	 */
	public function init(): void {
		$this->add_filters();
	}

	/**
	 * Tag notifications resent from the admin area with the user resending them
	 *
	 * @since 6.17.3
	 */
	public function add_filters(): void {
		add_filter( 'gform_before_resend_notifications', [ $this, 'record_resend' ] );
	}

	/**
	 * Sign the resending user onto each notification so a background render uses them
	 *
	 * @param array $form The form whose notifications are being resent
	 *
	 * @return array
	 *
	 * @since 6.17.3
	 */
	public function record_resend( $form ) {
		/* Signed, so a notification saved on a form can't pick who its PDFs render as */
		$resend = $this->sign( get_current_user_id(), time() + DAY_IN_SECONDS );

		foreach ( array_keys( $form['notifications'] ?? [] ) as $id ) {
			$form['notifications'][ $id ]['gfpdf_resend'] = $resend;
		}

		return $form;
	}

	/**
	 * Check if Gravity Forms will send the notifications in the background
	 *
	 * @param array $notifications An array containing the IDs of the notifications to be sent.
	 * @param array $form          The form being processed.
	 * @param array $entry         The entry being processed.
	 * @param array $data          An array of data which can be used in the notifications via the generic {object:property} merge tag. Defaults to empty array.
	 *
	 * @return bool
	 *
	 * @since 6.17.3
	 *
	 * @see   https://docs.gravityforms.com/gform_is_asynchronous_notifications_enabled/
	 */
	public function is_enabled( $notifications, $form, $entry, $data = [] ) {
		/* Gravity Forms 2.7.1+ */
		if ( method_exists( GF_Notifications_Processor::class, 'is_enabled' ) ) {
			return GFForms::get_service_container()
				->get( GF_Background_Process_Service_Provider::NOTIFICATIONS )
				->is_enabled( $notifications, $form, $entry, 'form_submission', $data );
		}

		return gf_apply_filters(
			[ 'gform_is_asynchronous_notifications_enabled', $form['id'] ],
			false,
			'form_submission',
			$notifications,
			$form,
			$entry,
			$data
		);
	}

	/**
	 * In a Gravity Forms background task, run a notification's PDF render as whoever resent it, or else the entry's author
	 *
	 * @param array    $notification The Gravity Forms notification
	 * @param array    $entry        The Gravity Forms entry
	 * @param callable $render       Generates the PDF
	 *
	 * @return mixed What `$render` returns
	 *
	 * @since 6.17.3
	 */
	public function render_as_sender( $notification, $entry, callable $render ) {
		/* Gravity Forms fires these around each background task; elsewhere the current user is kept */
		if ( did_action( 'gform_pre_process_async_notifications' ) <= did_action( 'gform_post_process_async_notifications' ) ) {
			return $render();
		}

		return Acting_User::run( $this->get_resent_by( $notification ) ?? $entry['created_by'] ?? 0, $render );
	}

	/**
	 * Get the user who resent a notification from its signed resend value
	 *
	 * @param array $notification The Gravity Forms notification
	 *
	 * @return int|null The user who resent the notification, or null without a valid, unexpired signature
	 *
	 * @since 6.17.3
	 */
	protected function get_resent_by( $notification ) {
		$resend = $notification['gfpdf_resend'] ?? '';
		if ( ! is_string( $resend ) ) {
			return null;
		}

		[ $user_id, $expires ] = explode( '|', $resend ) + [ '', '' ];

		return (int) $expires >= time() && hash_equals( $this->sign( (int) $user_id, (int) $expires ), $resend ) ? (int) $user_id : null;
	}

	/**
	 * Build the signed resend value, `user|expiry|signature`
	 *
	 * @param int $user_id The user resending the notification
	 * @param int $expires When the value stops being accepted, as a Unix timestamp
	 *
	 * @return string
	 *
	 * @since 6.17.3
	 */
	protected function sign( int $user_id, int $expires ): string {
		return "$user_id|$expires|" . wp_hash( "gfpdf_resend|$user_id|$expires" );
	}
}
