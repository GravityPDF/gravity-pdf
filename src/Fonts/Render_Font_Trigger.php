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
	 * @var Registry
	 * @since 7.0
	 */
	protected $registry;

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
		Registry $registry,
		Font_Lock $lock,
		LoggerInterface $log
	) {
		$this->detector  = $detector;
		$this->resolver  = $resolver;
		$this->queue     = $queue;
		$this->installer = $installer;
		$this->registry  = $registry;
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
	 * @return string[] The tags this render still has no font for, whatever it managed to install
	 *
	 * @since 7.0
	 */
	public function before_render( array $form, array $entry, array $settings ): array {
		$scripts = $this->detect(
			function () use ( $form, $entry, $settings ): array {
				return $this->strings( $form, $entry, $settings );
			}
		);

		if ( $scripts === [] ) {
			return [];
		}

		$this->install( $scripts, true );

		return $this->unresolved( $scripts );
	}

	/**
	 * The tags the language map cannot answer, asked of the map itself
	 *
	 * Not "which entries are incomplete": a pack whose Regular face landed and whose bold has not draws this
	 * document correctly, and asking the map is what tells the two apart. It is asked after the install, so a face
	 * fetched a moment ago counts.
	 *
	 * @param string[] $scripts
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	protected function unresolved( array $scripts ): array {
		$map = $this->registry->language_to_font();

		return array_values(
			array_filter(
				$scripts,
				static function ( string $tag ) use ( $map ): bool {
					return $map->getLanguageOptions( $tag, false ) === '';
				}
			)
		);
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
		$this->install(
			$this->detect(
				static function () use ( $html ): array {
					return [ $html ];
				}
			),
			false
		);
	}

	/**
	 * The scripts this document uses that the site has no font for
	 *
	 * The catalogue is asked first and the document is only read if it answers with something — on a provisioned
	 * site that is the whole cost, and it is two cached reads. Collecting the strings is deferred behind a closure
	 * for the same reason: a 200-field entry is a few hundred of them, and the usual answer is that nobody needs
	 * to look.
	 *
	 * @param callable(): string[] $text
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	protected function detect( callable $text ): array {
		$wanted = $this->resolver->uninstalled_scripts();

		if ( $wanted === [] ) {
			return [];
		}

		return $this->detector->detect( $wanted, $text() );
	}

	/**
	 * @param string[] $scripts
	 *
	 * @since 7.0
	 */
	protected function install( array $scripts, bool $inline ): void {
		/* Asked here as well as in the queue: with it off there is no point resolving the entries either */
		if ( $scripts === [] || ! $this->registry->auto_install_enabled() ) {
			return;
		}

		$requests = $this->resolver->for_scripts( $scripts );

		if ( ! $inline || ! $this->within_budget() ) {
			$this->queue->enqueue_all( $requests );

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
