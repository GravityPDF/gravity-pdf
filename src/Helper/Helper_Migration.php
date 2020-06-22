<?php

namespace GFPDF\Helper;

use Exception;
use Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class to assist with migrations
 *
 * @since 4.0
 */
class Helper_Migration {

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var \GFPDF\Helper\Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var \GFPDF\Helper\Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var \GFPDF\Helper\Helper_Notices
	 *
	 * @since 4.0
	 */
	protected $notices;

	/**
	 * Holds our Helper_Templates object
	 * used to ease access to our PDF templates
	 *
	 * @var \GFPDF\Helper\Helper_Templates
	 *
	 * @since 4.0
	 */
	protected $templates;

	/**
	 * Load our model and view and required actions
	 *
	 * @param \GFPDF\Helper\Helper_Abstract_Form    $form
	 * @param LoggerInterface                       $log
	 * @param \GFPDF\Helper\Helper_Data             $data
	 * @param \GFPDF\Helper\Helper_Abstract_Options $options
	 * @param \GFPDF\Helper\Helper_Misc             $misc
	 * @param \GFPDF\Helper\Helper_Notices          $notices
	 * @param \GFPDF\Helper\Helper_Templates        $templates
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Form $gform, LoggerInterface $log, Helper_Data $data, Helper_Abstract_Options $options, Helper_Misc $misc, Helper_Notices $notices, Helper_Templates $templates ) {

		/* Assign our internal variables */
		$this->gform     = $gform;
		$this->log       = $log;
		$this->data      = $data;
		$this->options   = $options;
		$this->misc      = $misc;
		$this->notices   = $notices;
		$this->templates = $templates;
	}

	/**
	 * Process our v3 to v4 migration
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function begin_migration() {

		/* Load our configuration file */
		try {
			$raw_config = $this->load_old_configuration();
		} catch ( Exception $e ) {

			$this->log->error(
				'Migration Error',
				[
					'exception' => $e->getMessage(),
				]
			);

			$this->notices->add_error( esc_html__( 'There was a problem processing the action. Please try again.', 'gravity-forms-pdf-extended' ) );

			return false;
		}

		/* Convert our v3 config into our v4 format and merge in the defaults */
		$v4_config = $this->convert_v3_to_v4( $raw_config );
		$v4_config = $this->process_default_configuration( $v4_config );

		/* Index configuration by form ID */
		$config = $this->process_v3_configuration( $v4_config );

		/* Import the configuration into the database */
		$this->import_v3_config( $config );

		/* Migrate fonts for multisite */
		$this->migrate_multisite_fonts();

		/* Clean-up the old 'output' directory as we use 'tmp' now */
		$this->cleanup_output_directory();

		/* Remove the old font config.php file */
		$this->cleanup_font_config();

		return true;
	}

	/**
	 * Load our v3 configuration
	 *
	 * @return array
	 *
	 * @throws Exception
	 *
	 * @since 4.0
	 */
	private function load_old_configuration() {

		$path = $this->templates->get_template_path();

		/* Import our configuration files */
		if ( is_file( $path . 'configuration.php' ) ) {
			require_once( $path . 'configuration.php' );
		} else {
			throw new Exception( 'Could not locate v3 configuration file.' );
		}

		return [
			'default' => ( isset( $gf_pdf_default_configuration ) && is_array( $gf_pdf_default_configuration ) ) ? $gf_pdf_default_configuration : [],
			'config'  => ( isset( $gf_pdf_config ) && is_array( $gf_pdf_config ) ) ? $gf_pdf_config : [],
		];
	}

	/**
	 * Process v3 config into our v4
	 *
	 * @param array $raw_config The config data loaded from our v3 configuration file
	 *
	 * @return array
	 *
	 * @since    4.0
	 */
	private function convert_v3_to_v4( $raw_config ) {

		$migration_key = [
			'notifications'                => 'notification',
			'premium'                      => 'advanced_template',
			'access'                       => 'public_access',
			'dpi'                          => 'image_dpi',
			'pdf_password'                 => 'password',
			'pdf_privileges'               => 'privileges',
			'pdf_master_password'          => 'master_password',
			'default-show-html'            => 'show_html',
			'default-show-empty'           => 'show_empty',
			'default-show-page-names'      => 'show_page_names',
			'default-show-section-content' => 'show_section_content',
		];

		foreach ( $raw_config['config'] as &$node ) {
			$node = $this->process_individual_v3_nodes( $node, $migration_key );
		}

		$raw_config['default'] = $this->process_individual_v3_nodes( $raw_config['default'], $migration_key );

		return $raw_config;
	}

	/**
	 * Pass in an individual v3 configuration node and conver to our v4 format
	 *
	 * @param array $node          The configuration to be converted
	 * @param array $migration_key A migration mapping key to convert the previous config keys
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	private function process_individual_v3_nodes( $node, $migration_key = [] ) {

		/* Handle PDFA1B and PDFX1A separately */
		if ( isset( $node['pdfa1b'] ) && $node['pdfa1b'] === true ) {
			unset( $node['pdfa1b'] );
			$node['format'] = 'PDFA1B';
		}

		if ( isset( $node['pdfx1a'] ) && $node['pdfx1a'] === true ) {
			unset( $node['pdfx1a'] );
			$node['format'] = 'PDFX1A';
		}

		if ( ! isset( $node['format'] ) ) {
			$node['format'] = 'Standard';
		}

		/* Fix the public access key */
		if ( isset( $node['access'] ) ) {
			$node['access'] = ( $node['access'] === 'all' ) ? 'Yes' : 'No';
		}

		/* Remove .php from the template file */
		if ( isset( $node['template'] ) ) {
			$node['template'] = $this->misc->remove_extension_from_string( $node['template'], '.php' );
		}

		/* Remove .pdf from the filename */
		if ( isset( $node['filename'] ) ) {
			$node['filename'] = $this->misc->remove_extension_from_string( $node['filename'] );
		}

		/* Fix up our custom PDF size */
		if ( isset( $node['pdf_size'] ) && is_array( $node['pdf_size'] ) ) {

			/* Ensure it's in the correct format */
			if ( sizeof( $node['pdf_size'] ) === 2 ) {
				$node['pdf_size'][0] = (int) $node['pdf_size'][0];
				$node['pdf_size'][1] = (int) $node['pdf_size'][1];
				$node['pdf_size'][2] = 'millimeters';

				$node['custom_pdf_size'] = $node['pdf_size'];
				$node['pdf_size']        = 'CUSTOM';
			} else {
				unset( $node['pdf_size'] );
			}
		} elseif ( isset( $node['pdf_size'] ) && ! is_array( $node['pdf_size'] ) ) {
			$node['pdf_size'] = mb_strtoupper( $node['pdf_size'] );
		}

		/* Loop through each array key */
		foreach ( $node as $id => &$val ) {

			/* Convert our boolean values into 'Yes' or 'No' responses, with the exception of notification */
			$skip_nodes = [ 'notifications', 'notification' ];
			if ( ! in_array( $id, $skip_nodes, true ) ) {
				$val = $this->misc->update_deprecated_config( $val );
			}

			/* Convert to our v4 configuration names */
			if ( isset( $migration_key[ $id ] ) ) {
				unset( $node[ $id ] );
				$node[ $migration_key[ $id ] ] = $val;
			}
		}

		return $node;
	}

	/**
	 * Process v3 config into an acceptable format
	 *
	 * @param array $raw_config The config data loaded from our v3 configuration file
	 *
	 * @return array
	 *
	 * @since    4.0
	 */
	private function process_v3_configuration( $raw_config ) {

		if ( ! is_array( $raw_config['config'] ) || sizeof( $raw_config['config'] ) === 0 ) {
			return [];
		}

		/* Store configuration by form ID */
		$config_by_fid = [];

		foreach ( $raw_config['config'] as $node ) {

			/* If set, merge in our defaults first */
			if ( ! defined( 'GFPDF_SET_DEFAULT_TEMPLATE' ) || GFPDF_SET_DEFAULT_TEMPLATE === true ) {
				$node = $this->merge_defaults( $raw_config['default'], $node );
			}

			if ( is_array( $node['form_id'] ) ) {
				foreach ( $node['form_id'] as $id ) {
					$id = (int) $id;

					if ( $id ) {
						$new_node = $node;
						unset( $new_node['form_id'] );
						$config_by_fid[ $id ][] = $new_node;
					}
				}
			} else {
				$id = (int) $node['form_id'];

				if ( $id ) {
					unset( $node['form_id'] );
					$config_by_fid[ $id ][] = $node;
				}
			}
		}

		return $config_by_fid;
	}

	/**
	 * Add the default configuration to any missing forms
	 *
	 * @param  array $raw_config The semi-processed configuration
	 *
	 * @return array
	 *
	 * @since  4.0
	 */
	private function process_default_configuration( $raw_config ) {

		/* Only handle when enabled */
		if ( ( ! defined( 'GFPDF_SET_DEFAULT_TEMPLATE' ) || GFPDF_SET_DEFAULT_TEMPLATE === true ) && sizeof( $raw_config['default'] ) > 0 ) {

			/* Get all forms */
			$forms = $this->gform->get_forms();

			/* Create an index of current form IDs */
			$form_ids = [];
			foreach ( $raw_config['config'] as $config ) {

				if ( is_array( $config['form_id'] ) ) {
					foreach ( $config['form_id'] as $fid ) {
						$form_ids[ $fid ] = 1;
					}
				} else {
					$form_ids[ $config['form_id'] ] = 1;
				}
			}

			/* Loop through all forms and merge in defaults */
			foreach ( $forms as $form ) {

				/* If nothing exists we'll merge in our default parameters */
				if ( ! isset( $form_ids[ $form['id'] ] ) ) {

					$new_config             = array_merge( $raw_config['default'], [ 'form_id' => $form['id'] ] );
					$raw_config['config'][] = $new_config;
				}
			}
		}

		return $raw_config;
	}

	/**
	 * Merge the configuration node with the default options, ensuring the config node takes precendent
	 *
	 * @param array $defaults The default data loaded from our v3 configuration file
	 * @param array $node     The individual PDF node
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	private function merge_defaults( $defaults, $node ) {

		/* If the default settings are set we'll merge them into the configuration index */
		if ( is_array( $defaults ) && is_array( $node ) ) {
			$node = array_replace_recursive( $defaults, $node );
		}

		return $node;
	}

	/**
	 * Import the v3 configuration into the database
	 *
	 * @param array $config The config data loaded from our v3 configuration file
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	private function import_v3_config( $config ) {

		$errors = [];

		/* Loop through forms and attempt to get the form data */
		foreach ( $config as $form_id => $nodes ) {
			$form = $this->gform->get_form( $form_id );

			if ( ! is_wp_error( $form ) ) {

				/* Get an array of all the form notification for later use */
				$notifications = [];

				/* Filter out the save and continue notifications */
				$omit = [ 'form_saved', 'form_save_email_requested' ];

				foreach ( $form['notifications'] as $notification ) {
					$event = ( isset( $notification['event'] ) ) ? $notification['event'] : '';

					if ( ! in_array( $event, $omit, true ) ) {
						$notifications[ $notification['id'] ] = $notification['name'];
					}
				}

				/* Hold name in array so we can prevent duplicates */
				$name = [];

				/* Loop through the nodes and add to our form array */
				foreach ( $nodes as $node ) {

					/* Skip any nodes which don't have a template */
					if ( empty( $node['template'] ) ) {
						continue;
					}

					/* Set our default fields */
					$node['id']               = uniqid();
					$node['active']           = true;
					$node['name']             = $this->templates->human_readable_template_name( $node['template'] );
					$node['conditionalLogic'] = '';

					/* Include a filename if none given */
					if ( empty( $node['filename'] ) ) {
						$node['filename'] = 'form-{form_id}-entry-{entry_id}';
					}

					/* Prevent duplicate names by adding a number to the end of the name */
					if ( isset( $name[ $node['name'] ] ) ) {
						$original_name = $node['name'];
						$node['name'] .= ' #' . $name[ $node['name'] ];
						$name[ $original_name ]++;
					} else {
						$name[ $node['name'] ] = 1;
					}

					/* Update all notification and pull correct IDs into new array */
					if ( isset( $node['notification'] ) ) {

						/* If assigned to all we'll consume all notification IDs, otherwise we'll sniff out the correct IDs */
						if ( $node['notification'] === true ) {
							$node['notification'] = array_keys( $notifications );
						} else {

							/* Turn into array if not already */
							if ( ! is_array( $node['notification'] ) ) {
								$node['notification'] = [ $node['notification'] ];
							}

							$new_notification = [];
							foreach ( $node['notification'] as $email ) {
								$match = array_search( $email, $notifications, true );

								if ( $match !== false ) {
									$new_notification[] = $match;
								}
							}

							$node['notification'] = $new_notification;

							if ( sizeof( $node['notification'] ) === 0 ) {
								unset( $node['notification'] );
							}
						}
					}

					/* Insert into database */
					$results = $this->options->update_pdf( $form_id, $node['id'], $node, true, false );

					if ( $results ) {
						/* return the ID if successful */
						$this->log->notice(
							'Successfully Imported v3 Node',
							[
								'pdf' => $node,
							]
						);
					} else {
						/* Log errors */
						$this->log->error(
							'Error Importing v3 Node',
							[
								'error' => $results,
								'pdf'   => $node,
							]
						);

						$node['form_id'] = $form_id;
						$errors[]        = $node;
					}
				}
			}
		}

		/* Check for any errors */
		if ( sizeof( $errors ) > 0 ) {

			$error_msg  = esc_html__( 'There was a problem migrating the following configuration nodes. You will need to manually setup those PDFs.', 'gravity-forms-pdf-extended' );
			$error_msg .= '<ul>';

			foreach ( $errors as $error ) {
				$error_msg .= "<li>Form #{$error['form_id']}: {$error['template']}</li>";
			}

			$error_msg .= '</ul>';
			$this->notices->add_error( $error_msg );
		} else {
			$this->notices->add_notice( esc_html__( 'Migration Successful.', 'gravity-forms-pdf-extended' ) );
		}

		/* Attempt to rename the configuration file */
		$this->archive_v3_configuration();

		return true;

	}

	/**
	 * Archive our configuration file
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	private function archive_v3_configuration() {
		$path = $this->templates->get_template_path();

		if ( is_file( $path . 'configuration.php' ) ) {
			@rename( $path . 'configuration.php', $path . 'configuration.archive.php' );
		}
	}

	/**
	 * Search through all multisite font directories and move them to our top level font folder before cleaning up individual font directories
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	private function migrate_multisite_fonts() {
		if ( is_multisite() ) {
			$path = $this->templates->get_template_path();

			/* Check if there is a fonts directory to migrate from and to */
			if ( is_dir( $path . 'fonts' ) && is_dir( $this->data->template_font_location ) ) {
				$fonts = glob( $path . 'fonts/' . '*.[tT][tT][fF]' );
				$fonts = ( is_array( $fonts ) ) ? $fonts : [];

				foreach ( $fonts as $font ) {
					$font_name = basename( $font );
					copy( $font, $this->data->template_font_location . $font_name );
				}

				/* Delete the existing font directory */
				$this->misc->rmdir( $path . 'fonts' );
			}
		}
	}

	/**
	 * Try and clean-up the old output directory during the migration
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	private function cleanup_output_directory() {
		$output_dir = $this->templates->get_template_path() . 'output';

		if ( is_dir( $output_dir ) ) {
			return $this->misc->rmdir( $output_dir );
		}

		return false;
	}

	/**
	 * Try remove the font/config.php file during the migration
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	private function cleanup_font_config() {
		$config = $this->data->template_font_location . 'config.php';

		if ( is_file( $config ) && unlink( $config ) ) {
			return true;
		}

		return false;
	}
}
