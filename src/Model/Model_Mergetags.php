<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Interface_Url_Signer;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Options_Fields;
use Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 * Handles all the PDF Mergetag logic
 *
 * @since 4.1
 */
class Model_Mergetags extends Helper_Abstract_Model {

	/**
	 * @var Model_PDF
	 *
	 * @since 4.1
	 */
	protected $pdf;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var Helper_Options_Fields
	 *
	 * @since 4.1
	 */
	protected $options;

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 4.1
	 */
	protected $log;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var Helper_Misc
	 *
	 * @since 4.1
	 */
	protected $misc;

	/**
	 * @var Helper_Interface_Url_Signer
	 * @since 6.0
	 */
	protected $url_signer;

	/**
	 * Model_Mergetags constructor.
	 *
	 * @param Helper_Abstract_Options $options
	 * @param Model_PDF               $pdf
	 * @param LoggerInterface         $log
	 *
	 * @since    4.1
	 */
	public function __construct( Helper_Abstract_Options $options, Model_PDF $pdf, LoggerInterface $log, Helper_Misc $misc, Helper_Interface_Url_Signer $url_signer ) {

		/* Assign our internal variables */
		$this->pdf        = $pdf;
		$this->log        = $log;
		$this->options    = $options;
		$this->misc       = $misc;
		$this->url_signer = $url_signer;
	}

	/**
	 * Add our PDF Merge tags to the merge tag selector
	 * The PDF Merge tag format is {NAME:pdf:ID}
	 *
	 * The NAME is purely to help users identify what PDF the merge tag relates to
	 * The ID is the PDF PID parameter
	 *
	 * @param array $tags    The current list of custom tags
	 * @param int   $form_id The Gravity FOrm ID
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function add_pdf_mergetags( $tags, $form_id ) {

		/* Exit early if the Gravity Form could not be identified */
		if ( $form_id === 0 ) {
			return $tags;
		}

		$pdfs = $this->options->get_form_pdfs( $form_id );

		if ( is_wp_error( $pdfs ) ) {
			return $tags;
		}

		/* Loop through the results and add all PDF URLs to the merge tags */
		foreach ( $pdfs as $id => $pdf ) {
			if ( $pdf['active'] === true ) {
				$tags[] = [
					'tag'   => sprintf( '{%s:pdf:%s}', $pdf['name'], $id ),
					/* Format "PDF: %s" - we split it up like this so we didn't have to add another translation */
					'label' => esc_html__( 'PDF', 'gravity-forms-pdf-extended' ) .
							   ': ' .
							   esc_html( $pdf['name'] ),
				];
			}
		}

		return $tags;
	}

	/**
	 * Replace the Gravity PDF merge tag ({NAME:pdf:ID}) with the associated PDF URL
	 *
	 * @param string $text       The string to convert
	 * @param array  $form       The Gravity Form array
	 * @param array  $entry      The Gravity Forms entry array
	 * @param bool   $url_encode Whether to encode the URL or not
	 *
	 * @return string
	 *
	 * @since 4.1
	 */
	public function process_pdf_mergetags( $text, $form, $entry, $url_encode ) {

		/* Check if there are any PDF merge tags to process, otherwise exit early */
		if ( strpos( $text, ':pdf:' ) === false ) {
			return $text;
		}

		/* Match our PDF merge tags */
		$results = preg_match_all( '/{.*?:pdf:([0-9A-Za-z]*)?:?(.*?)?}/', $text, $matches, PREG_SET_ORDER );

		/* Verify we have a match */
		if ( $results ) {

			$this->log->notice(
				'Begin Converting PDF Mergetags',
				[
					'form_id'  => $form['id'] ?? 0,
					'entry_id' => $entry['id'] ?? 0,

					'tags'     => $matches,
					'text'     => $text,
				]
			);

			foreach ( $matches as $tag ) {

				/* If no valid form or entry, convert tag to empty string */
				if ( $form === false || $entry === false ) {
					$text = str_replace( $tag[0], '', $text );
					continue;
				}

				/* Get the PDF configuration */
				$config = $this->options->get_pdf( $form['id'], $tag[1] );

				/* Strip tag if config not valid, it isn't active or conditional logic is not met */
				if ( is_wp_error( $config )
					 || $config['active'] !== true
					 || ( isset( $config['conditionalLogic'] ) && ! $this->misc->evaluate_conditional_logic( $config['conditionalLogic'], $entry ) )
				) {
					$error = 'Conditional logic did not pass';
					if ( is_wp_error( $config ) ) {
						$error  = $config->get_error_message();
						$config = [];
					} elseif ( $config['active'] !== true ) {
						$error = 'PDF is not currently active';
					}

					$this->log->error(
						'PDF Mergetag is not valid',
						[
							'error'    => $error,
							'tag'      => $tag,
							'form_id'  => $form['id'],
							'entry_id' => $entry['id'],
							'config'   => $config,
						]
					);

					/* Remove the tag and the new line if present (prevents any odd spacing issues) */
					$text = str_replace( [ $tag[0] . '<br>', $tag[0] . '<br />', $tag[0] . "\n", $tag[0] ], '', $text );
					continue;
				}

				/* Everything is valid so get the URL and display */
				$modifiers = explode( ':', $tag[2] ?? '' );
				$url       = $this->pdf->get_pdf_url( $tag[1], $entry['id'], (bool) in_array( 'download', $modifiers, true ), (bool) in_array( 'print', $modifiers, true ), $url_encode );

				/*
				 * A URL cannot be modified after signing (becomes invalid), so move the signing option to the bottom
				 */
				foreach ( $modifiers as $key => $modifier ) {
					if ( strpos( $modifier, 'signed' ) === 0 ) {
						unset( $modifiers[ $key ] );
						$modifiers[] = $modifier;
						break;
					}
				}

				foreach ( $modifiers as $modifier ) {
					$modifier = explode( ',', $modifier );

					switch ( $modifier[0] ?? '' ) {
						case 'signed':
							$expires = trim( $modifier[1] ?? '' );
							$url     = $this->url_signer->sign( $url, $expires );
							break;

						default:
							$url = apply_filters( 'gfpdf_mergetag_modifiers_url', $url, $modifier, $tag, $form, $entry, $config );
					}
				}

				/* replace the merge tag */
				$text = str_replace( $tag[0], $url, $text );
			}
		}

		return $text;
	}
}
