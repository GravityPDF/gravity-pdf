<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Options_Fields;
use GFPDF\Statics\Deprecation;
use GPDFAPI;

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
 * Model_Actions
 *
 * Handles the grunt work of our one-time actions
 *
 * @since 4.0
 */
class Model_Actions extends Helper_Abstract_Model {

	/**
	 * The prefix each deprecated feature's dismissal is recorded under in `action_dismissal`
	 *
	 * @since 6.17.0
	 */
	const DEPRECATED_FEATURE_DISMISSAL = 'deprecated_feature_';

	/**
	 * The prefix each unwritable folder's dismissal is recorded under in `action_dismissal`
	 *
	 * @since 6.17.3
	 */
	const UNWRITABLE_FOLDER_DISMISSAL = 'unwritable_folder_';

	/**
	 * How long dismissing the unwritable folders notice hides a folder that keeps failing
	 *
	 * @since 6.17.3
	 */
	const UNWRITABLE_FOLDER_SNOOZE = WEEK_IN_SECONDS;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var Helper_Notices
	 *
	 * @since 4.0
	 */
	protected $notices;

	/**
	 * Setup our class by injecting all our dependencies
	 *
	 * @param Helper_Data             $data    Our plugin data store
	 * @param Helper_Abstract_Options $options Our options class which allows us to access any settings
	 * @param Helper_Notices          $notices Our notice class used to queue admin messages and errors
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Data $data, Helper_Abstract_Options $options, Helper_Notices $notices ) {

		/* Assign our internal variables */
		$this->data    = $data;
		$this->options = $options;
		$this->notices = $notices;
	}

	/**
	 * Check if the current notice has already been dismissed
	 *
	 * @param string $type  The current notice ID
	 * @param int    $since Only count a dismissal made at or after this time
	 * @param int    $ttl   Only count a dismissal made in the last $ttl seconds
	 *
	 * @return boolean       True if dismissed, false otherwise
	 *
	 * @since 4.0
	 * @since 6.17.3 Added $since and $ttl
	 */
	public function is_notice_already_dismissed( $type, int $since = 0, int $ttl = 0 ) {

		$dismissed_notices = $this->options->get_option( 'action_dismissal', [] );

		if ( ! isset( $dismissed_notices[ $type ] ) ) {
			return false;
		}

		/* Dismissals before 6.17.3 recorded the notice ID rather than when */
		$dismissed_at = is_int( $dismissed_notices[ $type ] ) ? $dismissed_notices[ $type ] : 0;

		return $dismissed_at >= $since && ( $ttl === 0 || $dismissed_at >= time() - $ttl );
	}

	/**
	 * Mark the current notice as being dismissed
	 *
	 * @param string $type The current notice ID
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function dismiss_notice( $type ) {
		$this->dismiss_notices( [ $type ] );
	}

	/**
	 * Mark several notices as dismissed at once
	 *
	 * Written in one go, since each update_option() rewrites the whole autoloaded settings blob.
	 *
	 * @param string[] $types The notice IDs
	 *
	 * @since 6.17.0
	 */
	public function dismiss_notices( array $types ): void {
		if ( $types === [] ) {
			return;
		}

		$dismissed_notices = $this->options->get_option( 'action_dismissal', [] );

		foreach ( $types as $type ) {
			$dismissed_notices[ $type ] = time();
		}

		$this->options->update_option( 'action_dismissal', $dismissed_notices );
	}

	/**
	 * Check if one of the core fonts exists in the fonts directory
	 *
	 * @return bool
	 *
	 * @since 5.0
	 */
	public function core_font_condition() {

		$misc = GPDFAPI::get_misc_class();

		/* Check if one of the core fonts already exists */
		if ( ! is_file( $this->data->template_font_location . 'DejaVuSansCondensed.ttf' ) && ! $misc->is_gfpdf_settings_tab( 'tools' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Redirect user to our font installer tool
	 *
	 * @since 5.0
	 */
	public function core_font_redirect() {
		wp_safe_redirect( admin_url( 'admin.php?page=gf_settings&subview=PDF&tab=tools#/downloadCoreFonts' ) );
		exit;
	}

	/**
	 * The deprecated features in use that the user hasn't dismissed a notice for
	 *
	 * The recorded detections are read rather than detected here, because this runs on every admin page a notice
	 * can appear on. Deprecation records a fresh set on every version change — which is when a new round of
	 * removals arrives — and again whenever the system report or Site Health screens detect for themselves.
	 *
	 * @return string[] The feature IDs the notice should list
	 * @since 6.17.0
	 */
	public function get_undismissed_deprecated_features(): array {
		$features = Deprecation::get_features();

		return array_values(
			array_filter(
				Deprecation::get_detected_features(),
				function ( $key ) use ( $features ) {
					/* A record taken before a provider was removed can name a feature nothing declares any more */
					return isset( $features[ $key ] ) && ! $this->is_notice_already_dismissed( static::DEPRECATED_FEATURE_DISMISSAL . $key );
				}
			)
		);
	}

	/**
	 * Whether anything in use is worth raising the deprecation notice for
	 *
	 * @since 6.17.0
	 */
	public function has_deprecated_features(): bool {
		return $this->get_undismissed_deprecated_features() !== [];
	}

	/**
	 * Whether anything the notice lists has already been removed, which reads as an error rather than a warning
	 *
	 * @since 6.17.0
	 */
	public function has_unsupported_deprecated_feature(): bool {
		foreach ( $this->get_undismissed_deprecated_features() as $key ) {
			if ( ( Deprecation::get_feature( $key )['group'] ?? '' ) === Deprecation::GROUP_UNSUPPORTED ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Dismiss every feature the notice was listing when the user dismissed it
	 *
	 * Recorded per feature rather than against the notice itself, so a round of removals arriving later raises the
	 * notice again instead of being silenced by a dismissal that predates it.
	 *
	 * @since 6.17.0
	 */
	public function dismiss_deprecated_features(): void {
		$this->dismiss_notices(
			array_map(
				function ( $key ) {
					return static::DEPRECATED_FEATURE_DISMISSAL . $key;
				},
				$this->get_undismissed_deprecated_features()
			)
		);
	}

	/**
	 * The folders the last Model_Install::create_folder_structures() couldn't create or write to, less the ones dismissed
	 *
	 * @return string[]
	 * @since 6.17.3
	 */
	public function get_undismissed_unwritable_folders(): array {
		$folders = [];

		/* A dismissal only covers the failure it was made during, and only for a week */
		foreach ( (array) get_option( Model_Install::UNWRITABLE_FOLDERS, [] ) as $dir => $failing_since ) {
			if ( ! $this->is_notice_already_dismissed( $this->get_unwritable_folder_dismissal( $dir ), (int) $failing_since, static::UNWRITABLE_FOLDER_SNOOZE ) ) {
				$folders[] = $dir;
			}
		}

		return $folders;
	}

	/**
	 * @since 6.17.3
	 */
	public function has_unwritable_folders(): bool {
		return $this->get_undismissed_unwritable_folders() !== [];
	}

	/**
	 * Dismiss each folder the notice was listing, until a week passes or the folder recovers and fails again
	 *
	 * @since 6.17.3
	 */
	public function dismiss_unwritable_folders(): void {
		$this->dismiss_notices( array_map( [ $this, 'get_unwritable_folder_dismissal' ], $this->get_undismissed_unwritable_folders() ) );
	}

	/**
	 * The ID a folder's dismissal is recorded under in `action_dismissal`
	 *
	 * @since 6.17.3
	 */
	protected function get_unwritable_folder_dismissal( string $dir ): string {
		return static::UNWRITABLE_FOLDER_DISMISSAL . md5( $dir );
	}

	/**
	 * Send the user to the system report, which lists every detection behind the notice
	 *
	 * @since 6.17.0
	 */
	public function system_report_redirect() {
		wp_safe_redirect( Model_System_Report::get_report_url() );
		exit;
	}
}
