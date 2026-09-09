<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF_Vendor\Psr\Log\LoggerInterface;

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
 * Install what a PDF is about to draw, while it is being drawn
 *
 * The trigger that makes the other three optional: a form can be submitted in a language nobody configured, and
 * the notification PDF is generated in that same request. So this one is allowed to fetch inside the render — and
 * bounded three ways for it. The request must not already have been running long (a submission slowed by a payment
 * gateway does not also pay for fonts), only one PHP worker on the site may be in a batch at a time, and no file
 * over the inline cap is ever fetched here.
 *
 * A render that gets none of that still produces a correct PDF: the bundled faces cover Latin, Greek and Cyrillic,
 * everything else falls to the background queue, and the miss is recorded for the health check.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Render_Font_Trigger {

	/**
	 * How long this request may already have been running before the inline fetch is given up on
	 *
	 * `REQUEST_TIME_FLOAT`, not `max_execution_time`: on Linux that limit counts CPU time and a request blocked in
	 * curl spends none, so it is the submitter's patience being protected here and not PHP's.
	 *
	 * @since 7.0
	 */
	public const REQUEST_BUDGET = 15;

	/**
	 * The one inline batch a site may be running, whatever its traffic
	 *
	 * @since 7.0
	 */
	public const SLOT = 'font_inline_fetch';

	/**
	 * @var Script_Detector
	 * @since 7.0
	 */
	protected $detector;

	/**
	 * @var Coverage_Resolver
	 * @since 7.0
	 */
	protected $resolver;

	/**
	 * @var Install_Queue
	 * @since 7.0
	 */
	protected $queue;

	/**
	 * @var Font_Installer
	 * @since 7.0
	 */
	protected $installer;

	/**
	 * @var Font_Lock
	 * @since 7.0
	 */
	protected $lock;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	public function __construct(
		Script_Detector $detector,
		Coverage_Resolver $resolver,
		Install_Queue $queue,
		Font_Installer $installer,
		Font_Lock $lock,
		LoggerInterface $log
	) {
		$this->detector  = $detector;
		$this->resolver  = $resolver;
		$this->queue     = $queue;
		$this->installer = $installer;
		$this->lock      = $lock;
		$this->log       = $log;
	}

	/**
	 * Before mPDF is built: fetch what this document needs, if it can be afforded
	 *
	 * @param array $form     The form, for its labels and choices
	 * @param array $entry    The submission
	 * @param array $settings The PDF's own settings, for its headers and footers
	 *
	 * @since 7.0
	 */
	public function before_render( array $form, array $entry, array $settings ): void {
		$this->install( $this->detector->detect( ...$this->strings( $form, $entry, $settings ) ), true );
	}

	/**
	 * After the HTML is written: queue what only the template knew about
	 *
	 * Nothing is fetched and nothing is re-rendered — this PDF is already drawn. It exists so the *next* one is
	 * right when the text came from a template rather than from the submission.
	 *
	 * @since 7.0
	 */
	public function after_render( string $html ): void {
		$this->install( $this->detector->detect( $html ), false );
	}

	/**
	 * @param string[] $scripts
	 *
	 * @since 7.0
	 */
	protected function install( array $scripts, bool $inline ): void {
		if ( $scripts === [] ) {
			return;
		}

		$requests = $this->resolver->for_scripts( $scripts );

		if ( ! $inline || ! $this->within_budget() ) {
			$this->queue_all( $requests );

			return;
		}

		/*
		 * The loser writes nothing at all — no claim, no failure, no backoff. A render that could not get the slot
		 * has learned nothing about the entry, and recording anything would let ordinary traffic poison it for the
		 * request that did get it.
		 */
		if ( ! $this->lock->acquire( static::SLOT, Font_Downloader::INLINE_TIMEOUT ) ) {
			return;
		}

		try {
			$files = $this->queue->enqueue_for_render( $requests );

			if ( $files !== [] ) {
				$this->installer->install_inline( $files );
			}
		} finally {
			$this->lock->release( static::SLOT );
		}
	}

	/**
	 * Ask for the whole of every entry, in the background
	 *
	 * What a request means once its reason cannot be acted on: dropping `scripts` is what turns trigger 3's
	 * selector back into the plain "all of it, later" the other triggers use.
	 *
	 * @param array[] $requests
	 *
	 * @since 7.0
	 */
	protected function queue_all( array $requests ): void {
		$this->queue->enqueue_all(
			array_map(
				static function ( array $request ): array {
					unset( $request['scripts'] );

					return $request;
				},
				$requests
			)
		);
	}

	/**
	 * @since 7.0
	 */
	protected function within_budget(): bool {
		$started = (float) ( $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime( true ) );

		return ( microtime( true ) - $started ) <= static::REQUEST_BUDGET;
	}

	/**
	 * Everything this PDF might draw, as strings
	 *
	 * Deliberately narrow rather than a walk of the whole form: a 200-field form carries far more configuration
	 * than text, and the gate is only affordable because it sees the text.
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	protected function strings( array $form, array $entry, array $settings ): array {
		$strings = array_merge( $this->scalars( $entry ), $this->scalars( $settings ) );

		foreach ( (array) ( $form['fields'] ?? [] ) as $field ) {
			$strings[] = (string) ( $field->label ?? '' );
			$strings[] = (string) ( $field->description ?? '' );

			foreach ( (array) ( $field->choices ?? [] ) as $choice ) {
				$strings[] = (string) ( $choice['text'] ?? '' );
			}

			foreach ( (array) ( $field->inputs ?? [] ) as $input ) {
				$strings[] = (string) ( $input['label'] ?? '' );
			}
		}

		return $strings;
	}

	/**
	 * @return string[]
	 *
	 * @since 7.0
	 */
	protected function scalars( array $values ): array {
		$strings = [];

		foreach ( $values as $value ) {
			if ( is_string( $value ) ) {
				$strings[] = $value;

				continue;
			}

			if ( is_array( $value ) ) {
				$strings = array_merge( $strings, $this->scalars( $value ) );
			}
		}

		return $strings;
	}
}
