<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Health;

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
 * One thing wrong with a site, in the shape every consumer of the health report reads
 *
 * `id` is the unit of change: it is stable for as long as the same thing is wrong, so a notice the admin dismissed
 * stays dismissed and a new problem re-shows it. Everything else is display.
 *
 * @package GFPDF\Helper\Health
 *
 * @since 7.0
 */
class Health_Issue {

	/**
	 * @var string
	 * @since 7.0
	 */
	protected $id;

	/**
	 * @var string
	 * @since 7.0
	 */
	protected $summary;

	/**
	 * @var string[]
	 * @since 7.0
	 */
	protected $details;

	/**
	 * @var string
	 * @since 7.0
	 */
	protected $consequence;

	/**
	 * @var string
	 * @since 7.0
	 */
	protected $action_label;

	/**
	 * @var string
	 * @since 7.0
	 */
	protected $action_url;

	/**
	 * @param string   $id           Stable within the check for as long as the same thing is wrong
	 * @param string   $summary      One sentence, already translated
	 * @param string[] $details      One line each
	 * @param string   $consequence  What the admin gets if they do nothing
	 * @param string   $action_label The remedy, not a diagnostic
	 */
	public function __construct(
		string $id,
		string $summary,
		array $details = [],
		string $consequence = '',
		string $action_label = '',
		string $action_url = ''
	) {
		$this->id           = $id;
		$this->summary      = $summary;
		$this->details      = array_values( array_map( 'strval', $details ) );
		$this->consequence  = $consequence;
		$this->action_label = $action_label;
		$this->action_url   = $action_url;
	}

	public function get_id(): string {
		return $this->id;
	}

	public function get_summary(): string {
		return $this->summary;
	}

	/**
	 * @return string[]
	 */
	public function get_details(): array {
		return $this->details;
	}

	public function get_consequence(): string {
		return $this->consequence;
	}

	public function get_action_label(): string {
		return $this->action_label;
	}

	public function get_action_url(): string {
		return $this->action_url;
	}

	/**
	 * @since 7.0
	 */
	public function to_array(): array {
		return [
			'id'           => $this->id,
			'summary'      => $this->summary,
			'details'      => $this->details,
			'consequence'  => $this->consequence,
			'action_label' => $this->action_label,
			'action_url'   => $this->action_url,
		];
	}

	/**
	 * @since 7.0
	 */
	public static function from_array( array $issue ): self {
		return new self(
			(string) ( $issue['id'] ?? '' ),
			(string) ( $issue['summary'] ?? '' ),
			(array) ( $issue['details'] ?? [] ),
			(string) ( $issue['consequence'] ?? '' ),
			(string) ( $issue['action_label'] ?? '' ),
			(string) ( $issue['action_url'] ?? '' )
		);
	}
}
