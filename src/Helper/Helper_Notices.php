<?php

namespace GFPDF\Helper;

use GFCommon;
use GFForms;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
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
	public function maybe_remove_non_pdf_messages(): void {}

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
	 * @param string $css_class The class that should be included with the notice box
	 *
	 * @since 4.0
	 */
	public function add_notice( $notice, $css_class = '' ): void {
		if ( empty( $css_class ) ) {
			$this->notices[] = $notice;
		} else {
			$this->notices[ $css_class ] = $notice;
		}
	}

	/**
	 * Public endpoint for adding a new notice
	 *
	 * @param string $error     The error message that should be added
	 * @param string $css_class Any class names that should apply to the error
	 *
	 * @since    4.0
	 */
	public function add_error( $error, $css_class = '' ) {
		if ( empty( $css_class ) ) {
			$this->errors[] = $error;
		} else {
			$this->errors[ $css_class ] = $error;
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
	 * @since 6.11 Restrict admin pages the notices can be displayed on
	 */
	public function process(): void {
		if ( ! $this->can_display_notice_on_this_page() ) {
			return;
		}

		foreach ( $this->notices as $css_class => $notice ) {
			$include_class = ( ! is_int( $css_class ) ) ? $css_class : '';
			$this->html( $notice, 'updated ' . $include_class );
		}

		foreach ( $this->errors as $css_class => $error ) {
			$include_class = ( ! is_int( $css_class ) ) ? $css_class : '';
			$this->html( $error, 'error ' . $include_class );
		}
	}

	/**
	 * Generate the HTML used to display the notice / error
	 *
	 * @param string $text      The message to be displayed
	 * @param string $css_class The class name (updated / error)
	 *
	 * @since 4.0
	 */
	protected function html( string $text, string $css_class = 'updated' ): void {
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

		/* Add specific classes on Gravity Forms page so the notice displays correctly */
		if ( class_exists( 'GFForms' ) && \GFForms::is_gravity_page() ) {
			$classes  = 'notice gf-notice gform-settings__wrapper ' . $css_class;
			$classes .= strpos( $css_class, 'updated' ) !== false ? ' notice-success' : '';
		} else {
			$classes = 'notice ' . $css_class;
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
	 */
	protected function can_display_notice_on_this_page() {
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
		return $errors;
	}
}
