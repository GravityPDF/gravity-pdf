<?php

namespace GFPDF\Helper;

use GFCommon;
use GFForms;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 4.0
 */
class Helper_Notices implements Helper_Interface_Actions {

	/**
	 * Holds any notices that we've triggered
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $notices = [];

	/**
	 * Holds any errors that we've triggered
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	protected $errors = [];

	/**
	 * @since 4.0
	 */
	public function init(): void {
		$this->add_actions();
	}

	/**
	 * Apply any actions needed to implement notices
	 *
	 * @since 4.0
	 */
	public function add_actions(): void {
		add_action( $this->get_notice_type(), [ $this, 'process' ] );
		add_action( 'init', [ $this, 'maybe_remove_non_pdf_messages' ] );
	}

	/**
	 * Override GF notices on Gravity PDF pages
	 *
	 * @since 6.5
	 */
	public function maybe_remove_non_pdf_messages(): void {
		if ( ! \GPDFAPI::get_misc_class()->is_gfpdf_page() ) {
			return;
		}

		/* Remove existing notice */
		remove_action( $this->get_notice_type(), [ $this, 'process' ] );

		/* Delete Gravity Forms notices */
		add_action( 'gform_admin_messages', [ $this, 'reset_gravityforms_messages' ], 999 );
		add_action( 'gform_admin_error_messages', [ $this, 'reset_gravityforms_messages' ], 999 );

		/* Show Gravity PDF Notices */
		add_action( 'gform_admin_messages', [ $this, 'set_gravitypdf_notices' ], 1000 );
		add_action( 'gform_admin_error_messages', [ $this, 'set_gravitypdf_errors' ], 1000 );
	}

	/**
	 * Determine which notice should be triggered
	 *
	 * @since 4.0
	 */
	protected function get_notice_type(): string {
		if ( is_multisite() && is_network_admin() ) {
			return 'network_admin_notices';
		}

		return 'admin_notices';
	}

	/**
	 * Public endpoint for adding a new notice
	 *
	 * @param string $notice The message to be queued
	 * @param string $class  The class that should be included with the notice box
	 *
	 * @since 4.0
	 */
	public function add_notice( string $notice, string $class = '' ): void {
		if ( empty( $class ) ) {
			$this->notices[] = $notice;
		} else {
			$this->notices[ $class ] = $notice;
		}
	}

	/**
	 * Public endpoint for adding a new notice
	 *
	 * @param string $error The error message that should be added
	 * @param string $class Any class names that should apply to the error
	 *
	 * @since    4.0
	 */
	public function add_error( string $error, string $class = '' ) {
		if ( empty( $class ) ) {
			$this->errors[] = $error;
		} else {
			$this->errors[ $class ] = $error;
		}
	}

	/**
	 * Check if we currently have a notice
	 *
	 * @since 4.0
	 */
	public function has_notice(): bool {
		return count( $this->notices ) > 0;
	}

	/**
	 * Check if we currently have an error
	 *
	 * @since 4.0
	 */
	public function has_error(): bool {
		return count( $this->errors ) > 0;
	}

	/**
	 * Remove all notices / errors
	 *
	 * @param string $type Switch to remove all messages, errors or just notices. Valid arguments are 'all', 'notices', 'errors'
	 *
	 * @since 4.0
	 */
	public function clear( string $type = 'all' ): void {
		if ( in_array( $type, [ 'all', 'errors' ], true ) ) {
			$this->errors = [];
		}

		if ( in_array( $type, [ 'all', 'notices' ], true ) ) {
			$this->notices = [];
		}
	}

	/**
	 * Process our admin notice and error messages
	 *
	 * @since 4.0
	 */
	public function process(): void {
		foreach ( $this->notices as $class => $notice ) {
			$include_class = ( ! is_int( $class ) ) ? $class : '';
			$this->html( $notice, 'updated ' . $include_class );
		}

		foreach ( $this->errors as $class => $error ) {
			$include_class = ( ! is_int( $class ) ) ? $class : '';
			$this->html( $error, 'error ' . $include_class );
		}
	}

	/**
	 * Generate the HTML used to display the notice / error
	 *
	 * @param string $text  The message to be displayed
	 * @param string $class The class name (updated / error)
	 *
	 * @since 4.0
	 */
	protected function html( string $text, string $class = 'updated' ): void {
		$allow_form_elements = static function( $tags ) {
			$tags['input'] = [
				'type'  => true,
				'name'  => true,
				'value' => true,
				'class' => true,
			];

			return $tags;
		};

		add_filter( 'wp_kses_allowed_html', $allow_form_elements );

		?>
		<div class="<?php echo esc_attr( $class ); ?> notice">
			<p><?php echo wp_kses_post( $text ); ?></p>
		</div>
		<?php

		remove_filter( 'wp_kses_allowed_html', $allow_form_elements );
	}

	/**
	 * Reset Gravity Forms messages
	 *
	 * @param array $messages The registered Gravity Forms messages
	 *
	 * @return array $this->errors
	 *
	 * @since 6.5
	 */
	public function reset_gravityforms_messages( $messages ): array {
		return [];
	}

	/**
	 * Merge notices with the current Gravity Forms notice messages.
	 *
	 * @param array $messages The message to be displayed
	 *
	 * @return array
	 *
	 * @since 6.5
	 */
	public function set_gravitypdf_notices( $messages ): array {
		/* Error handling if we don't get the correct input type */
		if ( ! is_array( $messages ) ) {
			return $messages;
		}

		return array_merge( $messages, $this->notices );
	}

	/**
	 * Merge error with the current Gravity Forms error messages.
	 *
	 * @param array $errors The message to be displayed
	 *
	 * @return array
	 *
	 * @since 6.5
	 */
	public function set_gravitypdf_errors( $errors ): array {
		/* Error handling if we don't get the correct input type */
		if ( ! is_array( $errors ) ) {
			return $errors;
		}

		return array_merge( $errors, $this->errors );
	}
}
