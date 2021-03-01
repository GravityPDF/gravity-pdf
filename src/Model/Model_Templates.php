<?php

namespace GFPDF\Model;

use Exception;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Templates;
use GFPDF_Vendor\Upload\File;
use GFPDF_Vendor\Upload\Storage\FileSystem;
use GFPDF_Vendor\Upload\Validation\Extension;
use GFPDF_Vendor\Upload\Validation\Mimetype;
use GFPDF_Vendor\Upload\Validation\Size;
use GPDFAPI;
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
 * Model_Actions
 *
 * Handles the grunt work for our PDF template manager
 *
 * @since 4.1
 */
class Model_Templates extends Helper_Abstract_Model {

	/**
	 * Holds our Helper_Templates object
	 * used to ease access to our PDF templates
	 *
	 * @var Helper_Templates
	 *
	 * @since 4.1
	 */
	protected $templates;

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 4.1
	 */
	protected $log;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var Helper_Data
	 *
	 * @since 4.1
	 */
	protected $data;

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
	 * Model_Templates constructor.
	 *
	 * @param Helper_Templates $templates
	 * @param LoggerInterface  $log
	 * @param Helper_Data      $data
	 * @param Helper_Misc      $misc
	 *
	 * @since 4.1
	 */
	public function __construct( Helper_Templates $templates, LoggerInterface $log, Helper_Data $data, Helper_Misc $misc ) {
		/* Assign our internal variables */
		$this->templates = $templates;
		$this->data      = $data;
		$this->log       = $log;
		$this->misc      = $misc;
	}

	/**
	 * AJAX Endpoint to handle the uploading of PDF templates
	 *
	 * @global string $_POST ['nonce'] a valid nonce
	 *
	 * @since 4.1
	 */
	public function ajax_process_uploaded_template() {

		$this->misc->handle_ajax_authentication( 'Process Uploaded Template Zip Package' );

		/* Validate uploaded file */
		try {
			$storage  = new FileSystem( $this->data->template_tmp_location );
			$file     = new File( 'template', $storage );
			$zip_path = $this->move_template_to_tmp_dir( $file );
		} catch ( Exception $e ) {
			$this->log->warning(
				'File validation and move failed',
				[
					'file'  => $_FILES,
					'error' => $e->getMessage(),
				]
			);

			/* Bad Request */
			wp_die( '400', 400 );
		}

		/* Unzip and check the PDF templates look valid */
		try {
			$this->unzip_and_verify_templates( $zip_path );
		} catch ( Exception $e ) {
			$this->cleanup_template_files( $zip_path );

			$this->log->warning(
				'File validation and move failed',
				[
					'file'  => $_FILES,
					'error' => $e->getMessage(),
				]
			);

			header( 'Content-Type: application/json' );
			echo json_encode(
				[
					'error' => $e->getMessage(),
				]
			);

			/* Bad Response */
			wp_die( '', 400 );
		}

		/* Copy all the files to the active PDF working directory */
		$unzipped_dir_name = $this->get_unzipped_dir_name( $zip_path );
		$template_path     = $this->templates->get_template_path();

		$results = $this->misc->copyr( $unzipped_dir_name, $template_path );

		/* Get the template headers now all the files are in the right location */
		$this->templates->flush_template_transient_cache();
		$headers = $this->get_template_info( glob( $unzipped_dir_name . '*.php' ) );

		/* Fix template path */
		$headers = array_map(
			function( $header ) use ( $unzipped_dir_name, $template_path ) {
				$header['path'] = str_replace( $unzipped_dir_name, $template_path, $header['path'] );

				return $header;
			},
			$headers
		);

		/* Run PDF template SetUp method if required */
		$this->maybe_run_template_setup( $headers );

		/* Cleanup tmp uploaded files */
		$this->cleanup_template_files( $zip_path );

		if ( is_wp_error( $results ) ) {
			/* Internal Server Error */
			wp_die( '500', 500 );
		}

		/* Return newly-installed template headers */
		header( 'Content-Type: application/json' );
		echo json_encode(
			[
				'templates' => $headers,
			]
		);

		/* Okay Response */
		wp_die( '', 200 );
	}

	/**
	 * Execute the setUp method on any templates that implement it
	 *
	 * @param array $headers Contains the array returned from $this->get_template_info()
	 *
	 * @since 4.1
	 */
	public function maybe_run_template_setup( $headers = [] ) {
		foreach ( $headers as $template ) {
			$config = $this->templates->get_config_class( $template['id'] );

			/* Check if the PDF config implements our Setup/TearDown interface and run the tear down */
			if ( in_array( 'GFPDF\Helper\Helper_Interface_Setup_TearDown', class_implements( $config ), true ) ) {
				$config->setUp();
			}
		}
	}

	/**
	 * AJAX Endpoint for deleting user-uploaded PDF templates
	 *
	 * @global string $_POST ['nonce'] a valid nonce
	 * @global string $_POST ['id'] a valid PDF template ID
	 *
	 * @since 4.1
	 */
	public function ajax_process_delete_template() {

		$this->misc->handle_ajax_authentication( 'Delete PDF Template' );

		$template_id = ( isset( $_POST['id'] ) ) ? $_POST['id'] : '';

		/* Get all the necessary PDF template files to delete */
		try {
			$this->delete_template( $template_id );
		} catch ( Exception $e ) {
			/* Bad Request */
			wp_die( '400', 400 );
		}

		$this->templates->flush_template_transient_cache();

		header( 'Content-Type: application/json' );
		echo json_encode( true );

		/* Okay Response */
		wp_die( '', 200 );
	}

	/**
	 * Deletes a PDF templates files
	 *
	 * @param string $template_id
	 *
	 * @throws Exception
	 *
	 * @since 4.1
	 */
	public function delete_template( $template_id ) {
		try {
			$files  = $this->templates->get_template_files_by_id( $template_id );
			$config = $this->templates->get_config_class( $template_id );

			/* Check if the PDF config implements our Setup/TearDown interface and run the tear down */
			if ( in_array( 'GFPDF\Helper\Helper_Interface_Setup_TearDown', class_implements( $config ), true ) ) {
				$config->tearDown();
			}

			/* Remove the PDF template files */
			foreach ( $files as $file ) {
				unlink( $file );
			}
		} catch ( Exception $e ) {
			throw $e; /* throw further down the chain */
		}
	}

	/**
	 * AJAX Endpoint for building the template select box options (so we don't have to recreate the logic in React)
	 *
	 * @global string $_POST ['nonce'] a valid nonce
	 *
	 * @since 4.1
	 */
	public function ajax_process_build_template_options_html() {
		$this->misc->handle_ajax_authentication( 'Build Template Options HTML' );

		$options_class = GPDFAPI::get_options_class();

		$registered_settings = $options_class->get_registered_fields();
		$template_settings   = $registered_settings['form_settings']['template'];

		$templates = $template_settings['options'];
		$value     = $options_class->get_form_value( $template_settings );

		header( 'Content-Type: application/text' );
		echo $options_class->build_options_for_select( $templates, $value );

		/* Okay Response */
		wp_die( '', 200 );
	}

	/**
	 * Validations, renames and moves the uploaded zip file to an appropriate location
	 *
	 * @param File $file
	 *
	 * @return string The full path of the final resting place of the uploaded zip file
	 *
	 * @since 4.1
	 */
	public function move_template_to_tmp_dir( File $file ) {
		/* Validate our uploaded file and move to the PDF tmp directory for further processing */
		$file->setName( uniqid() );

		$file->addValidations(
			[
				new Extension( 'zip' ),
				new Size( '10240K' ), /* allow 10MB upload – accounts for fonts, PDF and PHP files */
			]
		);

		/* Do a check to ensure fileinfo is loaded. It should be loaded by default but in some cases this isn't so */
		if ( extension_loaded( 'fileinfo' ) ) {
			$file->addValidations(
				[
					new Mimetype( [ 'application/zip', 'application/octet-stream' ] ),
				]
			);
		}

		$file->upload();

		return $this->data->template_tmp_location . $file->getNameWithExtension();
	}

	/**
	 * Gets the full path to a new directory which is based on the zip file's unique name
	 *
	 * @param string $zip_path The full path to the zip file
	 *
	 * @return string
	 *
	 * @since 4.1
	 */
	public function get_unzipped_dir_name( $zip_path ) {
		return dirname( $zip_path ) . '/' . basename( $zip_path, '.zip' ) . '/';
	}

	/**
	 * Extracts the zip file, checks there are valid PDF template files found and retrieves information about them
	 *
	 * @param string $zip_path The full path to the zip file
	 *
	 * @throws Exception Thrown if a PDF template file isn't valid
	 *
	 * @since 4.1
	 */
	public function unzip_and_verify_templates( $zip_path ) {
		$this->enable_wp_filesystem();

		$dir     = $this->get_unzipped_dir_name( $zip_path );
		$results = unzip_file( $zip_path, $dir );

		/* If the unzip failed we'll throw an error */
		if ( is_wp_error( $results ) ) {
			throw new Exception( $results->get_error_message() );
		}

		/* Check unzipped templates for a valid v4 header, or v3 string pattern */
		$files = glob( $dir . '*.php' );

		if ( ! is_array( $files ) || count( $files ) === 0 ) {
			throw new Exception( esc_html__( 'No valid PDF template found in Zip archive.', 'gravity-forms-pdf-extended' ) );
		}

		$this->check_for_valid_pdf_templates( $files );
	}

	/**
	 * Sniffs the PHP file for signs that it's a valid Gravity PDF template file
	 *
	 * @param array $files The full paths to the PDF templates
	 *
	 * @throws Exception Thrown if file found not to be valid
	 *
	 * @since 4.1
	 */
	public function check_for_valid_pdf_templates( $files = [] ) {
		foreach ( $files as $file ) {

			/* Check if we have a valid v4 template header in the file */
			$info = $this->templates->get_template_info_by_path( $file );

			if ( ! isset( $info['template'] ) || strlen( $info['template'] ) === 0 ) {
				/* Check if it's a v3 template */
				$fp        = fopen( $file, 'r' );
				$file_data = fread( $fp, 8192 );
				fclose( $fp );

				/* Check the first 8kiB contains the string RGForms or GFForms, which signifies our v3 templates */
				if ( strpos( $file_data, 'RGForms' ) === false && strpos( $file_data, 'GFForms' ) === false ) {
					throw new Exception( sprintf( esc_html__( 'The PHP file %s is not a valid PDF Template.', 'gravity-forms-pdf-extended' ), basename( $file ) ) );
				}
			}
		}
	}

	/**
	 * Get the PDF template info to pass to our application
	 *
	 * @param array $files
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function get_template_info( $files = [] ) {
		return array_map(
			function( $file ) {
				return $this->templates->get_template_info_by_path( $file );
			},
			$files
		);
	}

	/**
	 * Remove the zip file and the unzipped directory
	 *
	 * @param string $zip_path The full path to the zip file
	 *
	 * @since 4.1
	 */
	public function cleanup_template_files( $zip_path ) {
		$dir = $this->get_unzipped_dir_name( $zip_path );

		$this->misc->rmdir( $dir );
		unlink( $zip_path );
	}

	/**
	 * A hack to ensure we can use unzip_file() without worrying about
	 * credentials being prompted.
	 *
	 * @since 4.1
	 */
	private function enable_wp_filesystem() {

		/* This occurs on an AJAX call so don't need to worry about removing the filter afterwards */
		add_filter(
			'filesystem_method',
			function() {
				return 'direct';
			}
		);

		WP_Filesystem();
	}
}
