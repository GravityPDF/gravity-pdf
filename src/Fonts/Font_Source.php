<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

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
 * One registry of installable font entries
 *
 * A plain record: the id every catalog row and queue item carries, the label and description the UI shows, the root
 * whose `index.json` lists this source, and the query args its root request carries. Two records sharing a root cost
 * one root request between them.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
final class Font_Source {

	/**
	 * Source ids are pipeline-controlled, so no underscore — unlike a font key, which an import may carry
	 *
	 * @since 7.0
	 */
	public const ID_PATTERN = '/^[a-z0-9\-]+$/';

	/**
	 * @var string
	 * @since 7.0
	 */
	private $id;

	/**
	 * @var string
	 * @since 7.0
	 */
	private $label;

	/**
	 * @var string
	 * @since 7.0
	 */
	private $root_url;

	/**
	 * @var array<string, scalar>
	 * @since 7.0
	 */
	private $request_args;

	/**
	 * @var string
	 * @since 7.0
	 */
	private $description;

	/**
	 * @param string                $id           Matches ID_PATTERN and is unique across registered sources
	 * @param string                $label        UI text: group heading, "Browse {label}" and the entry's source line
	 * @param string                $root_url     The root whose index.json lists this source
	 * @param array<string, scalar> $request_args Query args the root request carries; the built-ins send none
	 * @param string                $description  One or two sentences shown behind the source info icon
	 *
	 * @since 7.0
	 */
	public function __construct( string $id, string $label, string $root_url, array $request_args = [], string $description = '' ) {
		$this->id           = $id;
		$this->label        = $label;
		$this->root_url     = $root_url;
		$this->request_args = $request_args;
		$this->description  = $description;
	}

	/**
	 * @since 7.0
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * @since 7.0
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Trailing-slashed, so callers concatenate rather than repeat the join
	 *
	 * @since 7.0
	 */
	public function get_root_url(): string {
		return trailingslashit( $this->root_url );
	}

	/**
	 * @return array<string, scalar>
	 *
	 * @since 7.0
	 */
	public function get_request_args(): array {
		return $this->request_args;
	}

	/**
	 * @since 7.0
	 */
	public function get_description(): string {
		return $this->description;
	}
}
