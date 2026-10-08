<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Interface_Url_Signer;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Options_Fields;
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
 *
 * Handles all the PDF Mergetag logic
 *
 * @since 4.1
 */
class Model_Mergetags extends Helper_Abstract_Model {

	/**
	 * Matches {NAME:pdf:ID:MODIFIERS}
	 *
	 * @since 6.17.3
	 */
	const PDF_MERGETAG_REGEX = '/{.*?:pdf:([0-9A-Za-z]*)?:?(.*?)?}/';

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
	 * Above zero while Gravity Forms saves an entry, so PDF merge tags in administrative field defaults are kept for later
	 *
	 * @var int
	 */
	private $resolving_defaults_depth = 0;

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
					'label' => esc_html__( 'PDF', 'gravity-pdf' ) .
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
	 * @param string      $text       The string to convert
	 * @param array|false $form       The Gravity Form array
	 * @param array|false $entry      The Gravity Forms entry array, or false before the entry exists
	 * @param bool        $url_encode Whether to encode the URL or not
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

		/* Leave the tags in an administrative field's default for when there's an entry */
		if ( ( $form === false || $entry === false ) && $this->resolving_defaults_depth > 0 ) {
			return $text;
		}

		/* Match our PDF merge tags */
		$results = preg_match_all( static::PDF_MERGETAG_REGEX, $text, $matches, PREG_SET_ORDER );

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

				/* Use up the tag's trust now, so an invalid tag can't leave it for a later one */
				$trusted = $this->get_trust()->consume( $this->get_trust_key( $entry, $tag[0] ) );

				/* Get the PDF configuration */
				$config = $this->options->get_pdf( $form['id'], $tag[1] );

				/* Strip tag if config not valid, it isn't active or conditional logic is not met */
				if ( is_wp_error( $config )
					 || $config['active'] !== true
					 || ! $this->misc->conditional_logic_passes( $config, $entry )
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
				$url       = $this->pdf->get_pdf_url( $tag[1], $entry['id'], (bool) in_array( 'download', $modifiers, true ), (bool) in_array( 'print', $modifiers, true ) );

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
							/* Sign trusted tags, or when the user can view the PDF anyway */
							if ( $trusted || $this->pdf->can_user_view_entry( $entry, $config ) ) {
								$url = $this->url_signer->sign( $url, trim( $modifier[1] ?? '' ) );
							}
							break;

						default:
							$url = apply_filters( 'gfpdf_mergetag_modifiers_url', $url, $modifier, $tag, $form, $entry, $config );
					}
				}

				if ( $url_encode ) {
					$url = esc_url( $url );
				}

				/* replace the merge tag */
				$text = str_replace( $tag[0], $url, $text );
			}
		}

		return $text;
	}

	/**
	 * Keep PDF merge tags in administrative field defaults while Gravity Forms saves the entry
	 *
	 * @internal Hooked to gform_entry_id_pre_save_lead
	 *
	 * @param mixed $entry_id The filtered entry ID, returned unchanged
	 *
	 * @return mixed
	 *
	 * @since 6.17.3
	 */
	public function start_resolving_administrative_defaults( $entry_id ) {
		++$this->resolving_defaults_depth;

		return $entry_id;
	}

	/**
	 * @internal Hooked to gform_entry_created
	 *
	 * @return void
	 *
	 * @since 6.17.3
	 */
	public function stop_resolving_administrative_defaults() {
		$this->resolving_defaults_depth = max( 0, $this->resolving_defaults_depth - 1 );
	}

	/**
	 * Trust the PDF merge tags in text before Gravity Forms merges in any field values
	 *
	 * @internal Hooked to gform_pre_replace_merge_tags
	 *
	 * @param mixed $text  The filtered text, which another callback may have changed to a non-string
	 * @param array $form
	 * @param array $entry
	 *
	 * @return mixed
	 *
	 * @since 6.17.3
	 */
	public function mark_trusted_pdf_mergetags( $text, $form, $entry ) {
		if ( ! is_string( $text ) || empty( $entry['id'] ) || strpos( $text, ':pdf:' ) === false || $this->get_trust()->is_untrusted() ) {
			return $text;
		}

		preg_match_all( static::PDF_MERGETAG_REGEX, $text, $matches );
		foreach ( $matches[0] as $tag ) {
			$this->get_trust()->grant( $this->get_trust_key( $entry, $tag ) );
		}

		return $text;
	}

	/**
	 * @param array  $entry
	 * @param string $tag The full PDF merge tag
	 *
	 * @return string The key a trusted merge tag is granted and used up by
	 *
	 * @since 6.17.3
	 */
	private function get_trust_key( $entry, $tag ) {
		return 'tag:' . (int) ( $entry['id'] ?? 0 ) . '|' . $tag;
	}

	/**
	 * @return Model_Signed_Url_Trust
	 *
	 * @since 6.17.3
	 */
	private function get_trust() {
		return \GPDFAPI::get_mvc_class( 'Model_Signed_Url_Trust' );
	}

	/**
	 * Add PDFs to the field map list
	 *
	 * @param array $fields
	 * @param int   $form_id
	 *
	 * @return array
	 *
	 * @since 6.3
	 */
	public function add_field_map_choices( $fields, $form_id, $field_type, $exclude_field_types ) {

		/* Only add PDFs to choices if $fields contains the Entry Properties information */
		if ( ! in_array( esc_html__( 'Entry Properties', 'gravityforms' ), array_column( $fields, 'label' ), true ) ) {
			return $fields;
		}

		$combined_choices = [];
		$pdfs             = $this->options->get_form_pdfs( $form_id );

		if ( is_wp_error( $pdfs ) ) {
			return $fields;
		}

		foreach ( $pdfs as $pdf ) {
			/* Ignore inactive pdf template. */
			if ( $pdf['active'] === false ) {
				continue;
			}

			$combined_choices[] = [
				'label' => $pdf['name'],
				'value' => sprintf(
					'{%s:pdf:%s}',
					$pdf['name'],
					$pdf['id']
				),
			];

			$combined_choices[] = [
				'label' => $pdf['name'] . ' ' . __( 'Signed (+1 week)', 'gravity-pdf' ),
				'value' => sprintf(
					'{%s:pdf:%s:signed,1 week}',
					$pdf['name'],
					$pdf['id']
				),
			];

			$combined_choices[] = [
				'label' => $pdf['name'] . ' ' . __( 'Signed (+1 month)', 'gravity-pdf' ),
				'value' => sprintf(
					'{%s:pdf:%s:signed,1 month}',
					$pdf['name'],
					$pdf['id']
				),
			];

			$combined_choices[] = [
				'label' => $pdf['name'] . ' ' . __( 'Signed (+1 year)', 'gravity-pdf' ),
				'value' => sprintf(
					'{%s:pdf:%s:signed,12 months}',
					$pdf['name'],
					$pdf['id']
				),
			];
		}

		if ( count( $combined_choices ) > 0 ) {
			$fields[] = [
				'label'   => __( 'PDF URLs', 'gravity-pdf' ),
				'choices' => $combined_choices,
			];
		}

		return $fields;
	}

	/**
	 * @param string $field_value
	 * @param array  $form
	 * @param array  $entry
	 * @param int    $field_id
	 *
	 * @return string
	 *
	 * @since 6.3
	 */
	public function process_field_value( $field_value, $form, $entry, $field_id ) {

		if ( ! empty( $field_value ) || strpos( $field_id, ':pdf:' ) === false ) {
			return $field_value;
		}

		$gform = \GPDFAPI::get_form_class();
		return $gform->process_tags( $field_id, $form, $entry );
	}

	/**
	 * Convert gform_mailchimp_field_value parameter to be compatible with gform_addon_field_value.
	 *
	 * @param string $field_value
	 * @param int    $form_id
	 * @param int    $field_id
	 * @param array $entry
	 *
	 * @return string
	 *
	 * @since 6.3.2
	 */
	public function process_field_value_mailchimp( $field_value, $form_id, $field_id, $entry ) {
		$gform = \GPDFAPI::get_form_class();
		return $this->process_field_value( $field_value, $gform->get_form( $form_id ), $entry, $field_id );
	}

	/**
	 * Process Gravity Wiz Google Sheets data
	 *
	 * @param string $field_value
	 * @param int $form_id
	 * @param int $field_id
	 * @param array $entry
	 *
	 * @return mixed
	 *
	 * @since 6.8.1
	 */
	public function process_field_value_gp_google_sheets( $field_value, $form_id, $field_id, $entry ) {
		$gform = \GPDFAPI::get_form_class();
		return $this->process_field_value( $field_value, $gform->get_form( $form_id ), $entry, $field_id );
	}
}
