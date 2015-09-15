<?php

namespace GFPDF\Helper;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;

use Psr\Log\LoggerInterface;

use Exception;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
	 * Holds abstracted functions related to the forms plugin
	 * @var Object
	 * @since 4.0
	 */
	protected $form;

	/**
	 * Holds our log class
	 * @var Object
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 * @var Object
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 * @var Object
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 * @var Object
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 * @var Object Helper_Notices
	 * @since 4.0
	 */
	protected $notices;

	/**
	 * Load our model and view and required actions
	 */
	public function __construct( Helper_Abstract_Form $form, LoggerInterface $log, Helper_Data $data, Helper_Options $options, Helper_Misc $misc, Helper_Notices $notices ) {

		/* Assign our internal variables */
		$this->form    = $form;
		$this->log     = $log;
		$this->data    = $data;
		$this->options = $options;
		$this->misc    = $misc;
		$this->notices = $notices;
	}

	/**
	 * Process our v3 to v4 migration
	 * @return Boolean
	 * @since 4.0
	 */
	public function begin_migration() {

		/* Load our configuration file */
		try {
			$raw_config = $this->load_old_configuration();
		} catch (Exception $e) {

			$this->log->addError( 'Migration Error', array(
				'exception' => $e->getMessage(),
			) );

			$this->notices->add_error( __( 'There was a problem processing the action. Please try again.', 'gravitypdf' ) );

            return false;
		}

		/* Convert our v3 config into our v4 format */
		$v4_config = $this->convert_v3_to_v4( $raw_config );

		/* Index configuration by form ID */
		$config = $this->process_v3_configuration( $v4_config );

		/* Import the configuration into the database */
		$this->import_v3_config( $config );

        return true;
	}

	/**
	 * Load our v3 configuration
	 * @return Array
	 * @since 4.0
	 */
	private function load_old_configuration() {

		/* Import our configuration files */
		if ( ! is_multisite() && is_file( $this->data->template_location . 'configuration.php' ) ) {
			require_once( $this->data->template_location . 'configuration.php' );

		} elseif ( is_multisite() && is_file( $this->data->multisite_template_location . 'configuration.php' ) ) {
			require_once( $this->data->multisite_template_location . 'configuration.php' );

		} else {
			throw new Exception( 'Could not locate v3 configuration file.' );
		}

		return array(
			'default' => ( is_array( $gf_pdf_default_configuration ) ) ? $gf_pdf_default_configuration : array(),
			'config'  => ( is_array( $gf_pdf_config ) ) ? $gf_pdf_config : array(),
		);
	}

	/**
	 * Process v3 config into our v4
	 * @param Array $config The config data loaded from our v3 configuration file
	 * @return Array
	 * @since 4.0
	 */
	private function convert_v3_to_v4( $raw_config ) {

		$migration_key = array(
			'notifications'				   => 'notification',
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
		);

		foreach ( $raw_config['config'] as &$node ) {
			$node = $this->process_individual_v3_nodes( $node, $migration_key );
		}

		$raw_config['default'] = $this->process_individual_v3_nodes( $raw_config['default'], $migration_key );

		return $raw_config;
	}

	/**
	 * Pass in an individual v3 configuration node and conver to our v4 format
	 * @param  Array $node          The configuration to be converted
	 * @param  Array $migration_key A migration mapping key to convert the previous config keys
	 * @return Array
	 * @since 4.0
	 */
	private function process_individual_v3_nodes( $node, $migration_key = array() ) {

		/* Handle PDFA1B and PDFX1A separately */
		if ( isset( $node['pdfa1b'] ) && $node['pdfa1b'] === true ) {
			unset( $node['pdfa1b']);
			$node['format'] = 'PDFA1B';
		}

		if ( isset( $node['pdfx1a'] ) && $node['pdfx1a'] === true ) {
			unset( $node['pdfx1a']);
			$node['format'] = 'PDFX1A';
		}

		if( ! isset( $node['format'] ) ) {
			$node['format'] = 'Standard';
		}

		/* Fix the public access key */
		if ( isset( $node['access'] ) ) {
			$node['access'] = ($node['access'] == 'all') ? 'Yes' : 'No';
		}

		/* Remove .php from the template file */
		if ( isset( $node['template'] ) && substr( $node['template'], -4 ) === '.php' ) {
			$node['template'] = substr( $node['template'], 0, -4 );
		}

		/* Remove .pdf from the filename */
		if ( isset( $node['filename'] ) && substr( $node['filename'], -4 ) === '.pdf' ) {
			$node['filename'] = substr( $node['filename'], 0, -4 );
		}

        /* Fix up our custom PDF size */
        if( isset( $node['pdf_size'] ) && is_array( $node['pdf_size'] ) ) {

            /* Ensure it's in the correct format */
            if( sizeof( $node['pdf_size'] ) == 2 ) {
                $node['pdf_size'][0]     = (int) $node['pdf_size'][0];
                $node['pdf_size'][1]     = (int) $node['pdf_size'][1];
                $node['pdf_size'][2]     = 'millimeters';
                
                $node['custom_pdf_size'] = $node['pdf_size'];
                $node['pdf_size']        = 'custom';
            } else {
                unset( $node['pdf_size'] );
            }
        }

		/* Loop through each array key */
		foreach ( $node as $id => &$val ) {

			/* Convert our boolean values into 'Yes' or 'No' responses, with the exception of notification */
			$skip_nodes = array( 'notifications', 'notification' );
			if ( is_bool( $val ) && ! in_array( $id, $skip_nodes ) ) {
				$val = ($val) ? 'Yes' : 'No';
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
	 * @param Array $config The config data loaded from our v3 configuration file
	 * @return Array
	 * @since 4.0
	 */
	private function process_v3_configuration( $raw_config ) {

		if ( ! is_array( $raw_config['config'] ) || sizeof( $raw_config['config'] ) == 0 ) {
			return $config;
		}

		/* Store configuration by form ID */
		$config_by_fid = array();

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
						unset($new_node['form_id']);
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
	 * Merge the configuration node with the default options, ensuring the config node takes precendent
	 * @param Array $defaults The default data loaded from our v3 configuration file
     * @param Array $node The individual PDF node
	 * @return Array
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
	 * @param Array $config The config data loaded from our v3 configuration file
	 * @return Array
	 * @since 4.0
	 */
	private function import_v3_config( $config ) {

		$errors = array();

		/* Loop through forms and attempt to get the form data */
		foreach ( $config as $form_id => $nodes ) {
			$form = $this->form->get_form( $form_id );

			if ( ! is_wp_error( $form ) ) {

				/* Get an array of all the form notification for later use */
				$notifications = array();
				foreach ( $form['notifications'] as $not ) {
					$notifications[ $not['id'] ] = $not['name'];
				}


                /* Hold name in array so we can prevent duplicates */
                $name = array();

				/* Loop through the nodes and add to our form array */
				foreach ( $nodes as $node ) {

                    /* Skip any nodes which don't have a template */
                    if( empty( $node['template'] ) ) {
                        continue;
                    }

                    /* Set our default fields */
					$node['id']               = uniqid();
					$node['active']           = true;
					$node['name']             = $this->misc->human_readable( $node['template'] );
					$node['conditionalLogic'] = '';


					/* Include a filename if none given */
					if( empty( $node['filename'] ) ) {
						$node['filename'] = 'form-{form_id}-entry-{entry_id}';
					}

                    /* Prevent duplicate names by adding a number to the end of the name */
                    if( isset( $name[ $node['name'] ] ) ) {
                        $original_name = $node['name'];
                        $node['name'] .= ' #' . $name[ $node['name'] ];
                        $name[ $original_name ]++;
                    } else {
                        $name[ $node['name'] ] = 1;
                    }

					/* Update all notification and pull correct IDs into new array */
					if( isset( $node['notification'] ) ) {

						/* If assigned to all we'll consume all notification IDs, otherwise we'll sniff out the correct IDs */
						if ( $node['notification'] === true ) {
							$node['notification'] = array_keys( $notifications );
						} else {

							/* Turn into array if not already */
							if( ! is_array( $node['notification'] ) ) {
								$node['notification'] = array( $node['notification'] );
							}

							$new_notification = array();
							foreach( $node['notification'] as $email ) {
								$match = array_search( $email, $notifications );

								if( $match !== false ) {
									$new_notification[] = $match;
								}
							}

							$node['notification'] = $new_notification;

							if( sizeof( $node['notification'] ) === 0 ) {
								unset( $node['notification'] );
							}
						}
					}

					/* Insert into database */
					$results = $this->options->update_pdf( $form_id, $node['id'], $node, true, false );

					if ( $results ) {
						/* return the ID if successful */
						$this->log->addNotice( 'Successfully Added.', array( 'pdf' => $node ) );
					} else {
						/* Log errors */
						$this->log->addError( 'Error Saving.', array(
							'error' => $results,
							'pdf' => $node,
						) );

						$node['form_id'] = $form_id;
						$errors[]        = $node;
					}
				}
			}
		}

		/* Check for any errors */
		if ( sizeof( $errors ) > 0 ) {

			$error_msg = __( 'There was a problem migrating the following configuration nodes. You will need to manually setup those PDFs.', 'gravitypdf' );
			$error_msg .= '<ul>';

			foreach ( $errors as $error ) {
				$error_msg .= "<li>Form #{$error['form_id']}: {$error['template']}</li>";
			}

			$error_msg .= '</ul>';
			$this->notices->add_error( $error_msg );
		} else {
            $this->notices->add_notice( __( 'Migration Successful.', 'gravitypdf' ) );
        }

		/* Attempt to rename the configuration file */
		$this->archive_v3_configuration();

        return true;

	}

	/**
	 * Archive our configuration file
	 * @return void
	 * @since 4.0
	 */
	private function archive_v3_configuration() {
		if ( ! is_multisite() && is_file( $this->data->template_location . 'configuration.php' ) ) {
			rename( $this->data->template_location . 'configuration.php', $this->data->template_location . 'configuration.archive.php' );
		}

		/* Check multisite installation */
		if ( is_multisite() && is_file( $this->data->multisite_template_location . 'configuration.php' ) ) {
			rename( $this->data->multisite_template_location . 'configuration.php', $this->data->multisite_template_location . 'configuration.archive.php' );
		}
	}
}
