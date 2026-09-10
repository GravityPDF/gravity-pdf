<?php

declare( strict_types=1 );

namespace GFPDF\Rest;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Data;
use WP_Error;
use WP_REST_Controller;

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
 * What every font route agrees on: the namespace, and who may call it
 *
 * The capability lives here rather than in each class because a font route answering a different one would be a hole
 * rather than a feature.
 *
 * @since 7.0
 */
abstract class Rest_Font_Base extends WP_REST_Controller {

	/**
	 * @since 7.0
	 */
	public const NAMESPACE = Helper_Data::REST_API_BASENAME . 'v1';

	/**
	 * @var Helper_Abstract_Form
	 * @since 7.0
	 */
	protected $gform;

	/**
	 * @since 7.0
	 */
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * @return true|WP_Error
	 *
	 * @since 7.0
	 */
	public function get_items_permissions_check( $request ) {
		if ( $this->gform->has_capability( 'gravityforms_edit_forms' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_cannot_view',
			__( 'Sorry, you do not have access to this endpoint.', 'gravity-pdf' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * @return true|WP_Error
	 *
	 * @since 7.0
	 */
	public function create_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * @return true|WP_Error
	 *
	 * @since 7.0
	 */
	public function update_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Named apart from the update check because a DELETE route reads wrong pointing at the update one
	 *
	 * Not where the multisite file-delete capability goes: that question is per row — one display entry can hold
	 * ten installs with different owners — and a permission callback sees only `{source, entry}`. It belongs on
	 * `Font_Repository::delete_file()`, which every unlink in the plugin already passes through.
	 *
	 * @return true|WP_Error
	 *
	 * @since 7.0
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Why this delete cannot go ahead, or null
	 *
	 * The multisite file-delete capability, asked here rather than in the permission callback because it is a
	 * question about the row: a callback sees `{source, entry}` and one display entry can hold ten installs with
	 * different owners. And asked here rather than in the repository because a capability is a property of a
	 * request — the same deletes run from uninstall, from the 7.0 migration and from an install replacing a
	 * variant, none of which has a user to ask about.
	 *
	 * @param array $font The row being removed, where the route has one; a coverage entry has none and needs none
	 *
	 * @since 7.0
	 */
	protected function refuse_file_delete( array $font = [] ): ?WP_Error {
		if ( $this->may_delete_files( \GPDFAPI::get_font_repository()->file_delete_capability( $font ) ) ) {
			return null;
		}

		return new WP_Error(
			'font_delete_forbidden',
			esc_html__( 'Font files are shared by every site on this network. Only a network administrator can remove one.', 'gravity-pdf' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Ask the right authority for the capability in hand
	 *
	 * Gravity Forms grants a form administrator blanket access to its own capabilities (`gform_full_access`), which
	 * is what makes `gravityforms_edit_forms` work at all — and exactly what must not apply to a network
	 * capability, which is not Gravity Forms' to grant.
	 *
	 * @since 7.0
	 */
	protected function may_delete_files( string $capability ): bool {
		return strpos( $capability, 'gravityforms_' ) === 0
			? $this->gform->has_capability( $capability )
			: current_user_can( $capability );
	}
}
