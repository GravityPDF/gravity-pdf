<?php

namespace GFPDF\Helper;

use GFCommon;
use GFForms;

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
	}

	/**
	 * Override GF notices on Gravity PDF pages
	 *
	 * @since 6.5
	 * @deprecated 6.11 No longer required. Running all notices through standard WP hooks, but have included `gf-notice` class so GF does not remove it
	 */
	public function maybe_remove_non_pdf_messages(): void {
		_deprecated_function( __METHOD__, '6.11' );
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
	 * @param string $notice    The message to be queued
	 * @param string $css_class The class that should be included with the notice box. Pass a WordPress `notice-*`
	 *                          state class, like `notice-warning`, to override the default success styling.
	 *
	 * @since 4.0
	 */
	public function add_notice( $notice, $css_class = '' ): void {
		$this->notices[] = [
			'message' => $notice,
			'class'   => $css_class,
		];
	}

	/**
	 * Public endpoint for adding a new notice
	 *
	 * @param string $error     The error message that should be added
	 * @param string $css_class Any class names that should apply to the error, including a WordPress `notice-*`
	 *                          state class to override the default error styling
	 *
	 * @since    4.0
	 */
	public function add_error( $error, $css_class = '' ) {
		$this->errors[] = [
			'message' => $error,
			'class'   => $css_class,
		];
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
	 * @since 6.11 Restrict admin pages the notices can be displayed on
	 */
	public function process(): void {
		if ( ! $this->can_display_notice_on_this_page() ) {
			return;
		}

		foreach ( $this->notices as $notice ) {
			$this->html( $notice['message'], $notice['class'], 'updated' );
		}

		foreach ( $this->errors as $error ) {
			$this->html( $error['message'], $error['class'], 'error' );
		}
	}

	/**
	 * Generate the HTML used to display the notice / error
	 *
	 * @param string $text          The message to be displayed
	 * @param string $css_class     Any classes the caller asked for
	 * @param string $default_state The state class used when the caller hasn't chosen one (updated / error)
	 *
	 * @since 4.0
	 * @since 6.17.0 Added $default_state
	 */
	protected function html( string $text, string $css_class = '', string $default_state = 'updated' ): void {
		$allow_form_elements = static function ( $tags ) {
			$tags['input'] = [
				'type'  => true,
				'name'  => true,
				'value' => true,
				'class' => true,
			];

			return $tags;
		};

		add_filter( 'wp_kses_allowed_html', $allow_form_elements );

		/* `div.updated` out-specifies `.notice-warning`, so a caller-supplied state replaces ours instead of joining it */
		$state   = strpos( $css_class, 'notice-' ) === false ? $default_state : '';
		$classes = trim( $state . ' ' . $css_class );

		/* Add specific classes on Gravity Forms page so the notice displays correctly */
		if ( class_exists( 'GFForms' ) && \GFForms::is_gravity_page() ) {
			$classes  = 'notice gf-notice gform-settings__wrapper ' . $classes;
			$classes .= $state === 'updated' ? ' notice-success' : '';
		} else {
			$classes = 'notice ' . $classes;
		}

		?>
		<div class="<?php echo esc_attr( $classes ); ?>">
			<p><?php echo wp_kses_post( $text ); ?></p>
		</div>
		<?php

		remove_filter( 'wp_kses_allowed_html', $allow_form_elements );
	}

	/**
	 * Restrict notices to:
	 *
	 * 1. Any Gravity PDF admin pages
	 * 2. GF Forms, Entry, and Settings pages
	 * 3. WP Dashboard, Plugins List, and General Settings pages
	 *
	 * @return bool
	 *
	 * @since 6.11
	 * @since 6.17.0 Made public so callers can skip building a notice that would be discarded
	 */
	public function can_display_notice_on_this_page() {
		global $pagenow;

		$misc = \GPDFAPI::get_misc_class();

		$is_admin_area       = is_admin();
		$is_specific_wp_page = in_array( $pagenow, [ 'index.php', 'plugins.php', 'options-general.php' ], true );
		$is_specific_gf_page = $pagenow === 'admin.php' && in_array( rgget( 'page' ), [ 'gf_edit_forms', 'gf_entries', 'gf_settings' ], true );
		$is_gpdf_page        = $misc->is_gfpdf_page();

		if ( $is_gpdf_page ) {
			return true;
		}

		if ( $is_admin_area && $is_specific_gf_page ) {
			return true;
		}

		if ( $is_admin_area && $is_specific_wp_page ) {
			return true;
		}

		return false;
	}

	/**
	 * Reset Gravity Forms messages
	 *
	 * @param array $messages The registered Gravity Forms messages
	 *
	 * @return array $this->errors
	 *
	 * @since 6.5
	 * @deprecated 6.11 No longer required. Running all notices through standard WP hooks, but have included `gf-notice` class so GF does not remove it
	 */
	public function reset_gravityforms_messages( $messages ) {
		_deprecated_function( __METHOD__, '6.11' );

		return $messages;
	}

	/**
	 * Merge notices with the current Gravity Forms notice messages.
	 *
	 * @param array $messages The message to be displayed
	 *
	 * @return array
	 *
	 * @since 6.5
	 * @deprecated 6.11 No longer required. Running all notices through standard WP hooks, but have included `gf-notice` class so GF does not remove it
	 */
	public function set_gravitypdf_notices( $messages ) {
		_deprecated_function( __METHOD__, '6.11' );

		return $messages;
	}

	/**
	 * Merge error with the current Gravity Forms error messages.
	 *
	 * @param array $errors The message to be displayed
	 *
	 * @return array
	 *
	 * @since 6.5
	 * @deprecated 6.11 No longer required. Running all notices through standard WP hooks, but have included `gf-notice` class so GF does not remove it
	 */
	public function set_gravitypdf_errors( $errors ) {
		_deprecated_function( __METHOD__, '6.11' );

		return $errors;
	}
}
