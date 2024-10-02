<?php

namespace GFPDF\Model;

use Exception;
use GF_Field;
use GFCommon;
use GFFormsModel;
use GFPDF\Controller\Controller_PDF;
use GFPDF\Helper\Fields\Field_Default;
use GFPDF\Helper\Fields\Field_Products;
use GFPDF\Helper\Helper_Abstract_Field_Products;
use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Interface_Field_Pdf_Config;
use GFPDF\Helper\Helper_Interface_Url_Signer;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Options_Fields;
use GFPDF\Helper\Helper_PDF;
use GFPDF\Helper\Helper_Templates;
use GFPDF_Vendor\Mpdf\Mpdf;
use GFPDF_Vendor\Spatie\UrlSigner\Exceptions\InvalidSignatureKey;
use GFQuiz;
use GFResults;
use GP_Populate_Anything_Live_Merge_Tags;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use WP_Error;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Model_PDF
 *
 * Handles all the PDF display logic
 *
 * @since 4.0
 *
 * @method Controller_PDF getController
 */
class Model_PDF extends Helper_Abstract_Model {

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var Helper_Form
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
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var Helper_Notices
	 *
	 * @since 4.0
	 */
	protected $notices;

	/**
	 * Holds our Helper_Templates object
	 * used to ease access to our PDF templates
	 *
	 * @var Helper_Templates
	 *
	 * @since 4.0
	 */
	protected $templates;

	/**
	 * @var Helper_Interface_Url_Signer
	 *
	 * @since 5.2
	 */
	protected $url_signer;

	/**
	 * Setup our view with the needed data and classes
	 *
	 * @param Helper_Abstract_Form        $gform   Our abstracted Gravity Forms helper functions
	 * @param LoggerInterface             $log     Our logger class
	 * @param Helper_Abstract_Options     $options Our options class which allows us to access any settings
	 * @param Helper_Data                 $data    Our plugin data store
	 * @param Helper_Misc                 $misc    Our miscellaneous class
	 * @param Helper_Notices              $notices Our notice class used to queue admin messages and errors
	 * @param Helper_Templates            $templates
	 * @param Helper_Interface_Url_Signer $url_signer
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Form $gform, LoggerInterface $log, Helper_Abstract_Options $options, Helper_Data $data, Helper_Misc $misc, Helper_Notices $notices, Helper_Templates $templates, Helper_Interface_Url_Signer $url_signer ) {

		/* Assign our internal variables */
		$this->gform      = $gform;
		$this->log        = $log;
		$this->options    = $options;
		$this->data       = $data;
		$this->misc       = $misc;
		$this->notices    = $notices;
		$this->templates  = $templates;
		$this->url_signer = $url_signer;
	}

	/**
	 * Authentication request then generate and display PDF
	 *
	 * @param string  $pid    The Gravity Form PDF Settings ID
	 * @param integer $lid    The Gravity Form Entry ID
	 * @param string  $action Whether the PDF should be viewed or downloaded
	 *
	 * @return WP_Error
	 * @since 4.0
	 * @since 6.12 View/Download PDF creation workflow standardized with Save PDF workflow
	 */
	public function process_pdf( $pid, $lid, $action = 'view' ) {

		/* Get entry */
		$entry = $this->gform->get_entry( $lid );
		if ( is_wp_error( $entry ) ) {
			$this->log->error(
				'Invalid Entry',
				[
					'entry_id'         => $lid,
					'WP_Error_Message' => $entry->get_error_message(),
					'WP_Error_Code'    => $entry->get_error_code(),
				]
			);

			return $entry; /* return error */
		}

		/* Get PDF setting */
		$settings = $this->options->get_pdf( $entry['form_id'], $pid );
		if ( is_wp_error( $settings ) ) {
			$this->log->error(
				'Invalid PDF Settings',
				[
					'entry'            => $entry,
					'WP_Error_Message' => $settings->get_error_message(),
					'WP_Error_Code'    => $settings->get_error_code(),
				]
			);

			return $settings; /* return error */
		}

		/*
		 * Prior to 6.12 this action was saved to the PDF settings, passed to Helper_PDF, and used to
		 * stream the document to the client correctly. Since 6.12, we no longer need to pass this value
		 * to the underlying PDF generator. For backwards compatibility we've included this in case any
		 * user-land code makes use of it in their custom middleware.
		 */
		$settings['pdf_action'] = $action;

		/*
		 * Authenticate the request to prevent unauthorized access to the PDF
		 *
		 * Default middleware filters include:
		 * - middle_public_access
		 * - middle_signed_url_access
		 * - middle_active
		 * - middle_conditional
		 * - middle_owner_restriction
		 * - middle_logged_out_timeout
		 * - middle_auth_logged_out_user
		 * - middle_user_capability
		 *
		 * If any of the filters return a WP_Error object the request will not be fulfilled
		 *
		 * Refer to https://docs.gravitypdf.com/v6/developers/filters/gfpdf_pdf_middleware/
		 */
		$middleware = apply_filters( 'gfpdf_pdf_middleware', false, $entry, $settings );
		if ( is_wp_error( $middleware ) ) {
			$this->log->error(
				'PDF Authentication Failure.',
				[
					'entry'            => $entry,
					'settings'         => $settings,
					'WP_Error_Message' => $middleware->get_error_message(),
					'WP_Error_Code'    => $middleware->get_error_code(),
				]
			);

			return $middleware;
		}

		/*
		 * Normalize the PDF action
		 * The PDF cache introduced in 6.12 relies on a hash generated from the form, entry, and pdf settings
		 * To prevent cache misses we need to ensure we don't unnecessarily modify the settings array
		 */
		unset( $settings['pdf_action'] );
		$action = apply_filters( 'gfpdfe_pdf_output_type', $action ); /* Backwards compat */
		$action = in_array( $action, [ 'view', 'download' ], true ) ? $action : 'view';

		/* Get the PDF document for the request */
		$form = apply_filters( 'gfpdf_current_form_object', $this->gform->get_form( $entry['form_id'] ), $entry, __FUNCTION__ );

		do_action( 'gfpdf_view_or_download_pdf', $form, $entry, $settings );

		/*
		 * Support the print dialog option
		 * The PDF document embeds this preference directly in the source code so we need to
		 * force the cache to be bypassed.
		 */
		if ( rgget( 'print' ) === '1' ) {
			$settings['print'] = true;
		}

		$path_to_pdf = $this->generate_and_save_pdf( $entry, $settings );

		/* Send error upstream for logging and output */
		if ( is_wp_error( $path_to_pdf ) ) {
			return $path_to_pdf;
		}

		do_action( 'gfpdf_post_view_or_download_pdf', $path_to_pdf, $form, $entry, $settings, $action );

		$this->send_pdf_to_browser( $path_to_pdf, $action );
	}

	/**
	 * Apply filters to particular settings to maintain backwards compatibility
	 * Note: If you want to modify the $settings array you should use the new "gfpdf_pdf_config" filter instead
	 *
	 * @param array $settings The PDF settings array
	 * @param array $entry
	 *
	 * @return array           The $settings array
	 *
	 * @since  4.0
	 */
	public function apply_backwards_compatibility_filters( $settings, $entry ) {

		$form = apply_filters( 'gfpdf_current_form_object', $this->gform->get_form( $entry['form_id'] ), $entry, __FUNCTION__ );

		$settings['filename'] = $this->misc->remove_extension_from_string( apply_filters( 'gfpdfe_pdf_name', $settings['filename'], $form, $entry ) );
		$settings['template'] = $this->misc->remove_extension_from_string( apply_filters( 'gfpdfe_template', $settings['template'], $form, $entry ), '.php' );

		if ( isset( $settings['orientation'] ) ) {
			$settings['orientation'] = apply_filters( 'gfpdf_orientation', $settings['orientation'], $form, $entry );
		}

		if ( isset( $settings['security'] ) ) {
			$settings['security'] = $this->misc->update_deprecated_config( apply_filters( 'gfpdf_security', $settings['security'], $form, $entry ) );
		}

		if ( isset( $settings['privileges'] ) ) {
			$settings['privileges'] = apply_filters( 'gfpdf_privilages', $settings['privileges'], $form, $entry );
		}

		if ( isset( $settings['password'] ) ) {
			$settings['password'] = apply_filters( 'gfpdf_password', $settings['password'], $form, $entry );
		}

		if ( isset( $settings['master_password'] ) ) {
			$settings['master_password'] = apply_filters( 'gfpdf_master_password', $settings['master_password'], $form, $entry );
		}

		if ( isset( $settings['rtl'] ) ) {
			$settings['rtl'] = $this->misc->update_deprecated_config( apply_filters( 'gfpdf_rtl', $settings['rtl'], $form, $entry ) );
		}

		return $settings;
	}

	/**
	 * Check if the current PDF trying to be viewed has public access enabled
	 * If it does, we'll remove some of our middleware filters to allow this feature
	 *
	 * @param boolean|object $action
	 * @param array          $entry    The Gravity Forms Entry
	 * @param array          $settings The Gravity Form PDF Settings
	 *
	 * @return boolean|object
	 *
	 * @since 4.0
	 */
	public function middle_public_access( $action, $entry, $settings ) {

		if ( isset( $settings['public_access'] ) && 'Yes' === $settings['public_access'] ) {
			remove_filter( 'gfpdf_pdf_middleware', [ $this, 'middle_owner_restriction' ], 40 );
			remove_filter( 'gfpdf_pdf_middleware', [ $this, 'middle_logged_out_timeout' ], 50 );
			remove_filter( 'gfpdf_pdf_middleware', [ $this, 'middle_auth_logged_out_user' ], 60 );
			remove_filter( 'gfpdf_pdf_middleware', [ $this, 'middle_user_capability' ], 70 );

			$this->log->notice(
				'Public access enabled for current PDF',
				[
					'entry_id' => $entry['id'],
					'pdf_id'   => $settings['id'],
				]
			);
		}

		return $action;
	}

	/**
	 * Check if a signed URL exists and validate. If it passes, disable the remaining middleware capabilities
	 *
	 * @param boolean|object $action
	 * @param array          $entry    The Gravity Forms Entry
	 * @param array          $settings The Gravity Form PDF Settings
	 *
	 * @return boolean|object
	 *
	 * @since 5.1
	 */
	public function middle_signed_url_access( $action, $entry, $settings ) {

		/* phpcs:ignore WordPress.Security.NonceVerification.Recommended */
		if ( isset( $_GET['expires'] ) && isset( $_GET['signature'] ) && isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
			try {
				$protocol = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
				$domain   = $_SERVER['HTTP_HOST'];
				$request  = $_SERVER['REQUEST_URI'];

				$url = esc_url_raw( $protocol . $domain . $request );

				if ( $this->url_signer->verify( $url ) ) {
					remove_filter( 'gfpdf_pdf_middleware', [ $this, 'middle_owner_restriction' ], 40 );
					remove_filter( 'gfpdf_pdf_middleware', [ $this, 'middle_logged_out_timeout' ], 50 );
					remove_filter( 'gfpdf_pdf_middleware', [ $this, 'middle_auth_logged_out_user' ], 60 );
					remove_filter( 'gfpdf_pdf_middleware', [ $this, 'middle_user_capability' ], 70 );

					$this->log->notice(
						'Valid PDF Signing Request',
						[
							'entry_id'    => $entry['id'],
							'pdf_id'      => $settings['id'],
							'url'         => $url,
							'protocol'    => $protocol, /* Logged to a plain text file */
							'domain'      => $domain, /* Logged to a plain text file */
							'request_uri' => $request, /* Logged to a plain text file */
						]
					);
				} else {
					$this->log->warning(
						'Invalid PDF Signing Request',
						[
							'entry_id'    => $entry['id'],
							'pdf_id'      => $settings['id'],
							'url'         => $url,
							'protocol'    => $protocol, /* Logged to a plain text file */
							'domain'      => $domain, /* Logged to a plain text file */
							'request_uri' => $request, /* Logged to a plain text file */
						]
					);
				}
			} catch ( InvalidSignatureKey $e ) {

			}
		}

		return $action;
	}

	/**
	 * Check if the current PDF trying to be viewed is active
	 *
	 * @param boolean|object $action
	 * @param array          $entry    The Gravity Forms Entry
	 * @param array          $settings The Gravity Form PDF Settings
	 *
	 * @return boolean|object
	 *
	 * @since 4.0
	 */
	public function middle_active( $action, $entry, $settings ) {

		if ( ! is_wp_error( $action ) ) {
			if ( $settings['active'] !== true ) {
				return new WP_Error( 'inactive', esc_html__( 'The PDF configuration is not currently active.', 'gravity-forms-pdf-extended' ) );
			}
		}

		return $action;
	}

	/**
	 * Check if the current PDF trying to be viewed has conditional logic which passes
	 *
	 * @param boolean|object $action
	 * @param array          $entry    The Gravity Forms Entry
	 * @param array          $settings The Gravity Form PDF Settings
	 *
	 * @return boolean|object
	 *
	 * @since 4.0
	 */
	public function middle_conditional( $action, $entry, $settings ) {

		if ( ! is_wp_error( $action ) ) {
			if ( isset( $settings['conditionalLogic'] ) && ! $this->misc->evaluate_conditional_logic( $settings['conditionalLogic'], $entry ) ) {
				return new WP_Error( 'conditional_logic', esc_html__( 'PDF conditional logic requirements have not been met.', 'gravity-forms-pdf-extended' ) );
			}
		}

		return $action;
	}

	/**
	 * If the owner is restricted and the user is not logged in, prompt to log in
	 *
	 * @param boolean|object $action
	 * @param array          $entry    The Gravity Forms Entry
	 * @param array          $settings The Gravity Form PDF Settings
	 *
	 * @return boolean|object
	 *
	 * @throws Exception
	 * @since 4.0
	 */
	public function middle_owner_restriction( $action, $entry, $settings ) {

		/* ensure another middleware filter hasn't already done validation */
		if ( ! is_wp_error( $action ) ) {
			/* get the setting */
			$owner_restriction = $settings['restrict_owner'] ?? 'No';

			if ( $owner_restriction === 'Yes' && ! is_user_logged_in() ) {

				$this->log->notice(
					'Restrict Owner Global Setting Enabled. Prompting logged-out user to login.',
					[
						'entry_id'    => $entry['id'],
						'settings_id' => $settings['id'],
					]
				);

				/* prompt user to login */
				auth_redirect();
			}
		}

		return $action;
	}

	/**
	 * Check the "Logged Out Timeout" global setting and validate it against the current user
	 *
	 * @param boolean|object $action
	 * @param array          $entry    The Gravity Forms Entry
	 * @param array          $settings The Gravity Form PDF Settings
	 *
	 * @return boolean|object
	 *
	 * @throws Exception
	 * @since 4.0
	 */
	public function middle_logged_out_timeout( $action, $entry, $settings ) {

		/* ensure another middleware filter hasn't already done validation */
		if ( ! is_wp_error( $action ) ) {

			/* only check if PDF timed out if our logged out restriction is not 'Yes' and the user is not logged in */
			if ( ! is_user_logged_in() && $this->is_current_pdf_owner( $entry, 'logged_out' ) === true ) {
				/* get the global PDF settings */
				$timeout = (int) $this->options->get_option( 'logged_out_timeout', '20' );

				/* if '0' there is no timeout, or if the logged out restrictions are enabled we'll ignore this */
				if ( $timeout !== 0 ) {

					$timeout_stamp   = 60 * $timeout; /* 60 seconds multiplied by number of minutes */
					$entry_created   = strtotime( $entry['date_created'] ); /* get entry timestamp */
					$timeout_expires = $entry_created + $timeout_stamp; /* get the timeout expiry based on the entry created time */
					$current_time    = time();

					/* compare our two timestamps and throw error if outside the timeout */
					if ( $current_time > $timeout_expires ) {

						/* if there is no user account assigned to this entry throw error */
						if ( empty( $entry['created_by'] ) ) {
							$this->log->notice(
								'Logged Out Timeout Expired. Showing Error Message.',
								[
									'entry_id'        => $entry['id'],
									'settings_id'     => $settings['id'],
									'current_time'    => $current_time,
									'timeout_expires' => $timeout_expires,
								]
							);

							return new WP_Error( 'timeout_expired', esc_html__( 'Your PDF is no longer accessible.', 'gravity-forms-pdf-extended' ) );
						} else {

							$this->log->notice(
								'Logged Out Timeout Expired but user assigned to the entry. Redirecting to Login.',
								[
									'entry_id'        => $entry['id'],
									'settings_id'     => $settings['id'],
									'current_time'    => $current_time,
									'timeout_expires' => $timeout_expires,
								]
							);

							/* prompt to login */
							auth_redirect();
						}
					}
				}
			}
		}

		return $action;
	}

	/**
	 * Check if the current user attempting to access is the PDF owner
	 *
	 * @param array  $entry The Gravity Forms Entry
	 * @param string $type  The authentication type we should use
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function is_current_pdf_owner( $entry, $type = 'all' ) {
		$owner = false;
		/* check if the user is logged in and the entry is assigned to them */
		if ( $type === 'all' || $type === 'logged_in' ) {
			if ( is_user_logged_in() && (int) $entry['created_by'] === get_current_user_id() ) {
				$owner = true;
				$this->log->notice(
					'Current logged-in user is the owner of the entry',
					[
						'entry_id'   => $entry['id'],
						'created_by' => $entry['created_by'],
					]
				);
			}
		}

		if ( $type === 'all' || $type === 'logged_out' ) {
			$user_ip   = filter_var( GFFormsModel::get_ip(), FILTER_VALIDATE_IP );
			$server_ip = filter_var( $_SERVER['SERVER_ADDR'] ?? '127.0.0.1', FILTER_VALIDATE_IP );
			$entry_ip  = filter_var( $entry['ip'], FILTER_VALIDATE_IP );

			/* check if the user IP matches the entry IP */
			if (
				! empty( $entry_ip ) &&
				$entry_ip === $user_ip &&
				$entry_ip !== $server_ip
			) {

				$owner = true;
				$this->log->notice(
					'Current logged-out user matches the entry IP address',
					[
						'entry_id'  => $entry['id'],
						'user_ip'   => $user_ip,
						'server_ip' => $server_ip,
					]
				);
			}
		}

		return $owner;
	}

	/**
	 * Check if the user is logged out and authenticate as needed
	 *
	 * @param boolean|object $action
	 * @param array          $entry    The Gravity Forms Entry
	 * @param array          $settings The Gravity Form PDF Settings
	 *
	 * @return boolean|object
	 *
	 * @throws Exception
	 * @since 4.0
	 */
	public function middle_auth_logged_out_user( $action, $entry, $settings ) {

		if ( ! is_wp_error( $action ) ) {

			/* check if the user is not the current entry owner */
			if ( ! is_user_logged_in() && $this->is_current_pdf_owner( $entry, 'logged_out' ) === false ) {
				/* check if there is actually a user who owns entry */
				if ( ! empty( $entry['created_by'] ) ) {

					$this->log->notice(
						'The logged out security checks failed, but there is a logged-in user assigned to the entry. Prompting user to login.',
						[
							'entry_id'    => $entry['id'],
							'settings_id' => $settings['id'],
						]
					);

					/* prompt user to login to get access */
					auth_redirect();
				} else {
					$this->log->warning(
						'The logged out security checks failed and there is no logged-in user assigned to the entry.',
						[
							'entry_id'    => $entry['id'],
							'settings_id' => $settings['id'],
						]
					);

					/* there's no returning, throw generic error */

					return new WP_Error( 'access_denied', esc_html__( 'You do not have access to view this PDF.', 'gravity-forms-pdf-extended' ) );
				}
			}
		}

		return $action;
	}

	/**
	 * Verify the logged-in user can view the PDF
	 *
	 * If owner restrictions are enabled, check if the user as correct capability to view
	 * If owner restrictions are disabled, check if the user is the entry owner
	 *
	 * @param boolean|object $action
	 * @param array          $entry    The Gravity Forms Entry
	 * @param array          $settings The Gravity Form PDF Settings
	 *
	 * @return boolean|object
	 *
	 * @since 4.0
	 */
	public function middle_user_capability( $action, $entry, $settings ) {

		if ( ! is_wp_error( $action ) ) {
			/* check if the user is logged in but is not the current owner */
			$owner_restriction = $settings['restrict_owner'] ?? 'No';

			if (
				is_user_logged_in() &&
				! $this->can_user_view_pdf_with_capabilities() &&
				(
					$owner_restriction === 'Yes' ||
					$this->is_current_pdf_owner( $entry, 'logged_in' ) === false
				)
			) {

				return new WP_Error( 'access_denied', esc_html__( 'You do not have access to view this PDF.', 'gravity-forms-pdf-extended' ) );
			}
		}

		return $action;
	}

	/**
	 * Check if the logged in user has permission to view the PDF
	 *
	 * @param int|null $user_id
	 *
	 * @return bool
	 *
	 * @since 6.8
	 */
	public function can_user_view_pdf_with_capabilities( $user_id = null ) {
		$admin_permissions = $this->options->get_option( 'admin_capabilities', [ 'gravityforms_view_entries' ] );

		/* loop through permissions and check if the current user has any of those capabilities */
		$can_user_view_pdf = false;
		foreach ( $admin_permissions as $permission ) {
			if ( $this->gform->has_capability( $permission, $user_id ) ) {
				$can_user_view_pdf = true;
				break;
			}
		}

		return $can_user_view_pdf;
	}

	/**
	 * Display PDF on Gravity Form entry list page
	 *
	 * @param integer $form_id  Gravity Form ID
	 * @param integer $field_id Current field ID
	 * @param mixed   $value    Current value of field
	 * @param array   $entry    Entry Information
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function view_pdf_entry_list( $form_id, $field_id, $value, $entry ) {

		/* Only show the PDF metabox if a user has permission to view the documents */
		if ( ! $this->can_user_view_pdf_with_capabilities() ) {
			return;
		}

		$controller = $this->getController();
		$pdf_list   = $this->get_pdf_display_list( $entry );

		if ( empty( $pdf_list ) ) {
			return;
		}

		if ( count( $pdf_list ) > 1 ) {
			$args = [
				'pdfs' => $pdf_list,
				'view' => strtolower( $this->options->get_option( 'default_action' ) ),
			];

			$controller->view->entry_list_pdf_multiple( $args );
		} else {
			/* Only one PDF for this form so display a simple 'View PDF' link */
			$args = [
				'pdf'  => array_shift( $pdf_list ),
				'view' => strtolower( $this->options->get_option( 'default_action' ) ),
			];

			$controller->view->entry_list_pdf_single( $args );
		}
	}

	/**
	 * Get a preformatted list of active PDFs with name and URL
	 *
	 * @param array $entry
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_pdf_display_list( $entry ) {

		/* Stores our formatted PDFs */
		$args = [];

		/* Check if we have any PDFs */
		$form = apply_filters( 'gfpdf_current_form_object', $this->gform->get_form( $entry['form_id'] ), $entry, __FUNCTION__ );
		$pdfs = ( isset( $form['gfpdf_form_settings'] ) ) ? $this->get_active_pdfs( $form['gfpdf_form_settings'], $entry ) : [];

		if ( ! empty( $pdfs ) ) {

			foreach ( $pdfs as $settings ) {

				$args[] = [
					'name'     => $this->get_pdf_name( $settings, $entry ),
					'view'     => $this->get_pdf_url( $settings['id'], $entry['id'], false ),
					'download' => $this->get_pdf_url( $settings['id'], $entry['id'], true ),
					'settings' => $settings,
					'entry_id' => $entry['id'],
					'form_id'  => $form['id'],
					'class'    => 'gravitypdf-download-link',
				];
			}
		}

		/**
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_get_pdf_display_list/ for usage
		 *
		 * @since 4.2
		 */
		return apply_filters( 'gfpdf_get_pdf_display_list', $args, $entry, $form );
	}

	/**
	 * Filter out inactive PDFs and those who don't meet the conditional logic
	 *
	 * @param array $pdfs  The PDF settings array
	 * @param array $entry The current entry information
	 *
	 * @return array       The filtered PDFs
	 *
	 * @since 4.0
	 */
	public function get_active_pdfs( $pdfs, $entry ) {

		$filtered = [];
		$form     = apply_filters( 'gfpdf_current_form_object', $this->gform->get_form( $entry['form_id'] ), $entry, __FUNCTION__ );

		foreach ( $pdfs as $pdf ) {
			if ( $pdf['active'] && ( empty( $pdf['conditionalLogic'] ) || $this->misc->evaluate_conditional_logic( $pdf['conditionalLogic'], $entry ) ) ) {
				$filtered[ $pdf['id'] ] = $pdf;
			}
		}

		/**
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_get_active_pdfs/ for usage
		 *
		 * @since 4.2
		 */
		return apply_filters( 'gfpdf_get_active_pdfs', $filtered, $pdfs, $entry, $form );
	}

	/**
	 * Generate the PDF Name
	 *
	 * @param array $settings The PDF Form Settings
	 * @param array $entry    The Gravity Form entry details
	 *
	 * @return string      The PDF Name
	 *
	 * @since  4.0
	 */
	public function get_pdf_name( $settings, $entry ) {

		$form = apply_filters( 'gfpdf_current_form_object', $this->gform->get_form( $entry['form_id'] ), $entry, __FUNCTION__ );
		$name = $this->gform->process_tags( $settings['filename'], $form, $entry );

		/* Decode HTML entities */
		$name = wp_specialchars_decode( $name, ENT_QUOTES );

		/*
		 * Add filter to modify PDF name
		 *
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_pdf_filename/ for more details about this filter
		 */
		$name = apply_filters( 'gfpdf_pdf_filename', $name, $form, $entry, $settings );

		/* Backwards compatible filter */
		$name = apply_filters( 'gfpdfe_pdf_filename', $name, $form, $entry, $settings );

		/* Remove any characters that cannot be present in a filename */
		$name = $this->misc->strip_invalid_characters( $name );

		return $name;
	}

	/**
	 * Create a PDF Link based on the current PDF settings and entry
	 *
	 * @param integer $pid      The PDF Form Settings ID
	 * @param integer $id       The Gravity Form entry ID
	 * @param boolean $download Whether the PDF should be downloaded or not
	 * @param boolean $print    Whether we should mark the PDF to be printed
	 * @param boolean $esc      Whether to escape the URL or not
	 *
	 * @return string       Direct link to the PDF
	 *
	 * @since  4.0
	 */
	public function get_pdf_url( $pid, $id, $download = false, $print = false, $esc = true ) {
		global $wp_rewrite;

		if ( $esc !== true ) {
			_doing_it_wrong( __METHOD__, '$esc has been deprecated. Late-escape the returned value where appropriate.', '6.4.0' );
		}

		/*
		 * Patch for WPML which can include the default language as a GET parameter
		 * See https://github.com/GravityPDF/gravity-pdf/issues/550
		 */
		$home_url = untrailingslashit( strtok( home_url(), '?' ) );

		/* Check if permalinks are enabled, otherwise fall back to our ugly link structure for 4.0 (not the same as our v3 links) */
		if ( $wp_rewrite->using_permalinks() ) {
			$url  = $home_url . '/' . $wp_rewrite->root; /* Handle "almost pretty" permalinks - fix for IIS servers without modrewrite  */
			$url .= 'pdf/' . $pid . '/' . $id . '/';

			if ( $download ) {
				$url .= 'download/';
			}

			$url = user_trailingslashit( $url );

			if ( $print ) {
				$url .= '?print=1';
			}
		} else {
			$url = $home_url . '/?gpdf=1&pid=' . $pid . '&lid=' . $id;

			if ( $download ) {
				$url .= '&action=download';
			}

			if ( $print ) {
				$url .= '&print=1';
			}
		}

		/*
		 * @since 4.2
		 */
		$url = apply_filters( 'gfpdf_get_pdf_url', $url, $pid, $id, $download, $print, $esc );

		return esc_url_raw( $url );
	}

	/**
	 * Display the PDF links on the entry detailed section of the admin area
	 *
	 * @param array $args Combined form and entry array
	 *
	 * @return void
	 *
	 * @since  4.0
	 */
	public function view_pdf_entry_detail( $args ) {
		$controller = $this->getController();
		$pdf_list   = $this->get_pdf_display_list( $args['entry'] );

		if ( empty( $pdf_list ) ) {
			$controller->view->entry_no_valid_pdf();

			return;
		}

		$pdfs = [
			'pdfs' => $pdf_list,
		];

		$controller->view->entry_detailed_pdf( $pdfs );
	}

	/**
	 * Display the PDF metabox in the Gravity Flow inbox
	 *
	 * @param array $form
	 * @param array $entry
	 * @param $current_step
	 * @param $args
	 *
	 * @return void
	 *
	 * @since 6.8
	 */
	public function view_pdf_gravityflow_inbox( $form, $entry, $current_step, $args ) {
		/* Only show the PDF metabox if a user has permission to view the documents */
		if ( ! $this->can_user_view_pdf_with_capabilities() ) {
			return;
		}

		$active_pdfs = array_filter(
			$form['gfpdf_form_settings'] ?? [],
			function( $pdf ) {
				return $pdf['active'] === true;
			}
		);

		/* Only show the metabox if there's an active PDF */
		if ( count( $active_pdfs ) === 0 ) {
			return;
		}

		?>
		<style type="text/css">
		  div.gf_entry_wrap #poststuff #gravitypdf-pdf-box-container .inside {
			margin: 0;
			padding: 0;
			max-height: 18rem;
			overflow-y: auto;
			line-height: 1.4;
			font-size: 13px;
		  }

		  #gravitypdf-pdf-box-container ul {
			margin: 0;
			padding: 0;
		  }

		  #gravitypdf-pdf-box-container li {
			margin-bottom: 0.25rem;
			border-bottom: 1px solid #EBEBF2;
			padding: 0.5rem 0.75rem;
		  }

		  #gravitypdf-pdf-box-container li:last-of-type {
			border-bottom: none;
			margin-bottom: 0;
		  }
		</style>
		
		<div id="gravitypdf-pdf-box-container" class="postbox">

			<h3 class="hndle" style="cursor:default;">
				<span><?php esc_html_e( 'Gravity PDF', 'gravity-forms-pdf-extended' ); ?></span>
			</h3>

			<div class="inside">
				<?php $this->view_pdf_entry_detail( [ 'entry' => $entry ] ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Add the pdf meta box to the entry detail page.
	 *
	 * @param array $meta_boxes The properties for the meta boxes.
	 * @param array $entry      The entry currently being viewed/edited.
	 * @param array $form       The form object used to process the current entry.
	 *
	 * @return array
	 *
	 * @since 6.0
	 */
	public function register_pdf_meta_box( $meta_boxes, $entry, $form ) {

		$active_pdfs = array_filter(
			$form['gfpdf_form_settings'] ?? [],
			function( $pdf ) {
				return $pdf['active'] === true;
			}
		);

		/* Don't display meta box if no active or valid PDFs for the form */
		if ( count( $active_pdfs ) === 0 ) {
			return $meta_boxes;
		}

		$meta = [
			'gfpdf-entry-details-list' => [
				'title'         => esc_html__( 'PDFs', 'gravity-forms-pdf-extended' ),
				'callback'      => function( $args ) {
					/* Only show the PDF metabox if a user has permission to view the documents */
					if ( ! $this->can_user_view_pdf_with_capabilities() ) {
						return;
					}
					$this->view_pdf_entry_detail( $args );
				},
				'context'       => 'side',
				'callback_args' => [
					'form'  => $form,
					'entry' => $entry,
				],
			],
		];

		/* Ensure the PDF meta box is inserted right after the Entry box */

		return array_merge(
			array_slice( $meta_boxes, 0, 1 ),
			$meta,
			array_slice( $meta_boxes, 1 )
		);
	}

	/**
	 * Check if the form has any PDFs, generate them and attach to the notification
	 *
	 * @param array $notifications Gravity Forms Notification Array
	 * @param array $form
	 * @param array $entry
	 *
	 * @return array
	 *
	 * @throws Exception
	 * @since 4.0
	 */
	public function notifications( $notifications, $form, $entry ) {

		/*
		 * Ensure our entry is stored in the database by checking it has an ID
		 * This resolves any issues with the "Save and Continue" feature
		 * See https://github.com/GravityPDF/gravity-pdf/issues/360
		 */
		if ( empty( $entry['id'] ) ) {
			return $notifications;
		}

		$pdfs = ( isset( $form['gfpdf_form_settings'] ) ) ? $this->get_active_pdfs( $form['gfpdf_form_settings'], $entry ) : [];

		if ( count( $pdfs ) > 0 ) {

			/* Ensure our notification has an array setup for the attachments key */
			$notifications['attachments'] = ( isset( $notifications['attachments'] ) ) ? $notifications['attachments'] : [];

			/* Loop through each PDF config and generate */
			foreach ( $pdfs as $pdf ) {

				/* Pass it through the config filters */
				$settings = $this->options->get_pdf( $entry['form_id'], $pdf['id'] );

				/* Reset the variables each loop */
				$filename        = '';
				$tier_2_filename = '';

				if ( $this->maybe_attach_to_notification( $notifications, $settings, $entry, $form ) ) {

					/* Generate our PDF */
					do_action( 'gfpdf_pre_generate_and_save_pdf_notification', $form, $entry, $settings, $notifications );
					$filename = $this->generate_and_save_pdf( $entry, $settings );
					do_action( 'gfpdf_post_generate_and_save_pdf_notification', $form, $entry, $settings, $notifications );

					if ( ! is_wp_error( $filename ) ) {
						$notifications['attachments'][] = $filename;
					}
				}
			}

			$this->log->notice(
				'Gravity Forms Attachments',
				[
					'attachments'  => $notifications['attachments'],
					'notification' => $notifications,
				]
			);
		}

		return $notifications;
	}

	/**
	 * Determine if the PDF should be attached to the current notification
	 *
	 * @param array $notification The Gravity Form Notification currently being processed
	 * @param array $settings     The current Gravity PDF Settings
	 * @param array $form         Added to 4.2
	 * @param array $entry        Added to 4.2
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function maybe_attach_to_notification( $notification, $settings, $entry = [], $form = [] ) {

		$attach = false;
		if ( isset( $settings['notification'] ) && is_array( $settings['notification'] ) ) {
			if ( in_array( $notification['id'], $settings['notification'], true ) ) {
				$attach = true;
			}
		}

		/**
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_maybe_attach_to_notification/ for usage
		 *
		 * @since 4.2
		 */
		return apply_filters( 'gfpdf_maybe_attach_to_notification', $attach, $notification, $settings, $entry, $form );
	}

	/**
	 * Generate and save the PDF to disk
	 *
	 * @param array $entry        The Gravity Forms entry (from \GFAPI::get_entry)
	 * @param array $pdf_settings The Gravity PDF settings (from GPDFAPI::get_pdf())
	 *
	 * @return string|WP_Error  Return the full path to the PDF, or a WP_Error on failure
	 *
	 * @since 4.0
	 * @since 6.12 The view/download endpoints route through this method
	 *
	 * @see \GPDFAPI::create_pdf() We recommend third-party developers use the API to generate PDFs
	 */
	public function generate_and_save_pdf( $entry, $pdf_settings ) {

		$form         = apply_filters( 'gfpdf_current_form_object', $this->gform->get_form( $entry['form_id'] ), $entry, __FUNCTION__ );
		$entry        = apply_filters( 'gfpdf_current_entry_object', $entry, $form, $pdf_settings, __FUNCTION__ );
		$pdf_settings = apply_filters( 'gfpdf_current_pdf_settings_object', $pdf_settings, $form, $entry, __FUNCTION__ );
		$filename     = $this->get_pdf_name( $pdf_settings, $entry );

		do_action( 'gfpdf_pre_generate_and_save_pdf', $form, $entry, $pdf_settings );

		$pdf_generator = new Helper_PDF( $entry, $pdf_settings, $this->gform, $this->data, $this->misc, $this->templates, $this->log );
		$pdf_generator->set_filename( $filename );
		$pdf_generator = apply_filters( 'gfpdf_pdf_generator_pre_processing', $pdf_generator );

		if ( ! $this->process_and_save_pdf( $pdf_generator ) ) {
			return new WP_Error( 'pdf_generation_failure', esc_html__( 'There was a problem creating the PDF', 'gravity-forms-pdf-extended' ) );
		}

		do_action( 'gfpdf_post_generate_and_save_pdf', $form, $entry, $pdf_settings );

		return $pdf_generator->get_full_pdf_path();
	}

	/**
	 * Generate and save PDF to disk
	 *
	 * @param Helper_PDF $pdf_generator The Helper_PDF object
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function process_and_save_pdf( Helper_PDF $pdf_generator ) {

		/**
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_override_pdf_bypass/ for usage
		 *
		 * @since 4.2
		 */
		$pdf_override = apply_filters( 'gfpdf_override_pdf_bypass', false, $pdf_generator );

		/* If cached PDF already exists then return early */
		if ( ! $pdf_override && $this->does_pdf_exist( $pdf_generator ) ) {
			return true;
		}

		/* Get required parameters */
		$entry    = $pdf_generator->get_entry();
		$settings = $pdf_generator->get_settings();
		$form     = $pdf_generator->get_form();

		do_action( 'gfpdf_pre_pdf_generation', $form, $entry, $settings, $pdf_generator );

		/*
		 * Load our arguments that should be accessed by our PDF template
		 */
		$args = $this->templates->get_template_arguments(
			$form,
			$this->misc->get_fields_sorted_by_id( $form['id'] ),
			$entry,
			$this->get_form_data( $entry ),
			$settings,
			$this->templates->get_config_class( $settings['template'] ),
			$this->misc->get_legacy_ids( $entry['id'], $settings )
		);

		/* Add backwards compatibility support */
		$GLOBALS['wp']->query_vars['pid'] = $settings['id'];
		$GLOBALS['wp']->query_vars['lid'] = $entry['id'];

		try {

			/* Initialise our PDF helper class */
			$pdf_generator->init();
			$pdf_generator->set_template();
			$pdf_generator->set_output_type( 'save' );

			/* Add Backwards compatibility support for our v3 Tier 2 Add-on */
			if ( isset( $settings['advanced_template'] ) && strtolower( $settings['advanced_template'] ) === 'yes' ) {

				/* Check if we should process this document using our legacy system */
				if ( $this->handle_legacy_tier_2_processing( $pdf_generator, $entry, $settings, $args ) ) {
					return true;
				}
			}

			/* Render the PDF template HTML */
			$pdf_generator->render_html( $args );

			/* Generate and save the PDF */
			$pdf_generator->save_pdf( $pdf_generator->generate() );

			do_action( 'gfpdf_post_pdf_generation', $form, $entry, $settings, $pdf_generator );

			return true;
		} catch ( Exception $e ) {

			$this->log->error(
				'PDF Generation Error',
				[
					'pdf'       => $pdf_generator,
					'exception' => $e->getMessage(),
				]
			);

			return false;
		}
	}

	/**
	 * Check if the current PDF to be processed already exists on disk
	 *
	 * @param Helper_PDF $pdf_generator The Helper_PDF Object
	 *
	 * @return boolean
	 *
	 * @since  4.0
	 */
	public function does_pdf_exist( Helper_PDF $pdf_generator ) {

		if ( is_file( $pdf_generator->get_full_pdf_path() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Generates our $data array
	 *
	 * @param array $entry The Gravity Form Entry
	 *
	 * @return array        The $data array
	 *
	 * @throws Exception
	 * @since 4.0
	 */
	public function get_form_data( $entry ) {

		$entry = apply_filters( 'gfpdf_entry_pre_form_data', $entry );

		if ( ! isset( $entry['form_id'] ) ) {
			return [];
		}

		$form = apply_filters( 'gfpdf_current_form_object', $this->gform->get_form( $entry['form_id'] ), $entry, __FUNCTION__ );

		if ( ! is_array( $form ) ) {
			return [];
		}

		/* Setup our basic structure */
		$data = [
			'misc'               => [],
			'field'              => [],
			'field_descriptions' => [],
		];

		/**
		 * Create a product class for use
		 *
		 * @var Field_Products
		 */
		$products = new Field_Products( new GF_Field(), $entry, $this->gform, $this->misc );

		/* Get the form details */
		$form_meta = $this->get_form_data_meta( $form, $entry );

		/* Get the survey, quiz and poll data if applicable */
		$quiz   = $this->get_quiz_results( $form, $entry );
		$survey = $this->get_survey_results( $form, $entry );
		$poll   = $this->get_poll_results( $form, $entry );

		/* Merge in the meta data and survey, quiz and poll data */
		$data = array_replace_recursive( $data, $form_meta, $quiz, $survey, $poll );

		/*
		 * Loop through the form data, call the correct field object and
		 * save the data to our $data array
		 */
		if ( isset( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {

				/* Skip over captcha, password and page fields */
				$fields_to_skip = [ 'captcha', 'password', 'page' ];
				$fields_to_skip = apply_filters( 'gfpdf_form_data_skip_fields', $fields_to_skip );
				$fields_to_skip = apply_filters( 'gfpdf_blacklisted_fields', $fields_to_skip );

				if ( in_array( $field->type, $fields_to_skip, true ) ) {
					continue;
				}

				/* Include any field descriptions */
				$data['field_descriptions'][ $field->id ] = ( ! empty( $field->description ) ) ? $field->description : '';

				/* Get our field object */
				$class = $this->get_field_class( $field, $form, $entry, $products );

				/* Merge in the field object form_data() results */
				$data = array_replace_recursive( $data, $class->form_data() );
			}
		}

		/* Load our product array if products exist */
		if ( ! $products->is_empty() ) {
			$data = array_replace_recursive( $data, $products->form_data() );
		}

		/* Re-order the array keys to make it more readable */
		$order = apply_filters(
			'gfpdf_form_data_key_order',
			[
				'misc',
				'field',
				'list',
				'signature_details_id',
				'products',
				'products_totals',
				'repeater',
				'poll',
				'survey',
				'quiz',
				'pages',
				'html_id',
				'section_break',
				'field_descriptions',
				'signature',
				'signature_details',
				'html',
			]
		);

		foreach ( $order as $key ) {

			/* If item exists pop it onto the end of the array */
			if ( isset( $data[ $key ] ) ) {
				$item = $data[ $key ];
				unset( $data[ $key ] );
				$data[ $key ] = $item;
			}
		}

		/**
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_form_data/ for usage
		 *
		 * @since 4.2
		 */
		return apply_filters( 'gfpdf_form_data', $data, $entry, $form );
	}

	/**
	 * Handles the loading and running of our legacy Tier 2 PDF templates
	 *
	 * @param Helper_PDF $pdf_generator The Helper_PDF object
	 * @param array      $entry         The Gravity Forms raw entry data
	 * @param array      $settings      The Gravity PDF settings
	 * @param array      $args          The data that should be passed directly to a PDF template
	 *
	 * @return bool
	 *
	 * @since 4.0
	 */
	public function handle_legacy_tier_2_processing( Helper_PDF $pdf_generator, $entry, $settings, $args ) {

		$form = apply_filters( 'gfpdf_current_form_object', $this->gform->get_form( $entry['form_id'] ), $entry, __FUNCTION__ );

		$prevent_main_pdf_loader = apply_filters(
			'gfpdfe_pre_load_template',
			$form['id'],
			$entry['id'],
			basename( $pdf_generator->get_template_path() ),
			$form['id'] . $entry['id'],
			$this->misc->backwards_compat_output( $pdf_generator->get_output_type() ),
			$pdf_generator->get_filename(),
			$this->misc->backwards_compat_conversion( $settings, $form, $entry ),
			$args
		); /* Backwards Compatibility */

		return $prevent_main_pdf_loader === true;
	}

	/**
	 * Return our general $data information
	 *
	 * @param array $form  The Gravity Form
	 * @param array $entry The Gravity Form Entry
	 *
	 * @return array        The $data array
	 *
	 * @since 4.0
	 */
	public function get_form_data_meta( $form, $entry ) {
		$data = [];

		/* Add form_id and entry_id for convenience */
		$data['form_id']  = isset( $entry['form_id'] ) ? $entry['form_id'] : 0;
		$data['entry_id'] = isset( $entry['id'] ) ? $entry['id'] : 0;

		/* Set title and description */
		$data['form_title']       = isset( $form['title'] ) ? $form['title'] : '';
		$data['form_description'] = isset( $form['description'] ) ? $form['description'] : '';

		/* Include page names */
		$data['pages'] = isset( $form['pagination']['pages'] ) ? $form['pagination']['pages'] : [];

		/* Add date fields */
		if ( isset( $entry['date_created'] ) ) {
			$data['date_created']     = GFCommon::format_date( $entry['date_created'], false, 'j/n/Y', false );
			$data['date_created_usa'] = GFCommon::format_date( $entry['date_created'], false, 'n/j/Y', false );

			$data['misc']['date_time'] = GFCommon::format_date( $entry['date_created'], false, 'Y-m-d H:i:s', false );
			$data['misc']['time_24hr'] = GFCommon::format_date( $entry['date_created'], false, 'H:i', false );
			$data['misc']['time_12hr'] = GFCommon::format_date( $entry['date_created'], false, 'g:ia', false );
		}

		$include = [
			'is_starred',
			'is_read',
			'ip',
			'source_url',
			'post_id',
			'currency',
			'payment_status',
			'payment_date',
			'transaction_id',
			'payment_amount',
			'is_fulfilled',
			'created_by',
			'transaction_type',
			'user_agent',
			'status',
		];

		foreach ( $include as $item ) {
			$data['misc'][ $item ] = ( isset( $entry[ $item ] ) ) ? $entry[ $item ] : '';
		}

		return $data;
	}

	/**
	 * Pull the Quiz Results into the $form_data array
	 *
	 * @param array $form  The Gravity Form
	 * @param array $entry The Gravity Form Entry
	 *
	 * @return array        The results
	 *
	 * @since  4.0
	 */
	public function get_quiz_results( $form, $entry ) {

		$data = [];

		if ( class_exists( 'GFQuiz' ) && $this->check_field_exists( 'quiz', $form ) ) {

			/* Get quiz fields */
			$fields = GFCommon::get_fields_by_type( $form, [ 'quiz' ] );

			/* Store the quiz pass configuration */
			$data['quiz']['config']['grading']     = ( isset( $form['gravityformsquiz']['grading'] ) ) ? $form['gravityformsquiz']['grading'] : '';
			$data['quiz']['config']['passPercent'] = ( isset( $form['gravityformsquiz']['passPercent'] ) ) ? $form['gravityformsquiz']['passPercent'] : '';
			$data['quiz']['config']['grades']      = ( isset( $form['gravityformsquiz']['grades'] ) ) ? $form['gravityformsquiz']['grades'] : '';

			/* Store the user's quiz results */
			$data['quiz']['results']['score']   = rgar( $entry, 'gquiz_score' );
			$data['quiz']['results']['percent'] = rgar( $entry, 'gquiz_percent' );
			$data['quiz']['results']['is_pass'] = rgar( $entry, 'gquiz_is_pass' );
			$data['quiz']['results']['grade']   = rgar( $entry, 'gquiz_grade' );

			/* Poll for the global quiz overall results */
			$data['quiz']['global'] = $this->get_quiz_overall_data( $form, $fields );

		}

		return $data;
	}

	/**
	 * Pull the Survey Results into the $form_data array
	 *
	 * @param array $form  The Gravity Form
	 * @param array $entry The Gravity Form Entry
	 *
	 * @return array        The results
	 *
	 * @since  4.0
	 */
	public function get_survey_results( $form, $entry ) {

		$data = [];

		if ( class_exists( 'GFSurvey' ) && $this->check_field_exists( 'survey', $form ) ) {

			/* Get survey fields */
			$fields = GFCommon::get_fields_by_type( $form, [ 'survey' ] );

			/* Include the survey score, if any */
			if ( isset( $entry['gsurvey_score'] ) ) {
				$data['survey']['score'] = $entry['gsurvey_score'];
			}

			$results = $this->get_addon_global_data( $form, [], $fields );

			if ( count( $results ) > 0 ) {

				/* Loop through the global survey data and convert information correctly */
				foreach ( $fields as $field ) {

					/* Check if we have a multifield likert and replace the row key */
					if ( isset( $field['gsurveyLikertEnableMultipleRows'] ) && $field['gsurveyLikertEnableMultipleRows'] === true ) {

						foreach ( $field['gsurveyLikertRows'] as $row ) {

							$results['field_data'][ $field->id ] = $this->replace_key( $results['field_data'][ $field->id ], $row['value'], $row['text'] );

							if ( isset( $field->choices ) && is_array( $field->choices ) ) {
								foreach ( $field->choices as $choice ) {
									$results['field_data'][ $field->id ][ $row['text'] ] = $this->replace_key( $results['field_data'][ $field->id ][ $row['text'] ], $choice['value'], $choice['text'] );
								}
							}
						}
					}

					/* Replace the standard row data */
					if ( isset( $field->choices ) && is_array( $field->choices ) ) {
						foreach ( $field->choices as $choice ) {
							$results['field_data'][ $field->id ] = $this->replace_key( $results['field_data'][ $field->id ], $choice['value'], $choice['text'] );
						}
					}
				}

				$data['survey']['global'] = $results;
			}
		}

		return $data;
	}

	/**
	 * Pull the Poll Results into the $form_data array
	 *
	 * @param array $form  The Gravity Form
	 * @param array $entry The Gravity Form Entry
	 *
	 * @return array        The results
	 *
	 * @since  4.0
	 */
	public function get_poll_results( $form, $entry ) {

		$data = [];

		if ( class_exists( 'GFPolls' ) && $this->check_field_exists( 'poll', $form ) ) {

			/* Get poll fields and the overall results */
			$fields  = GFCommon::get_fields_by_type( $form, [ 'poll' ] );
			$results = $this->get_addon_global_data( $form, [], $fields );

			if ( count( $results ) > 0 ) {

				/* Loop through our fields and update the results as needed */
				foreach ( $fields as $field ) {

					/* Add the field name to a new 'misc' array key */
					$results['field_data'][ $field->id ]['misc']['label'] = $field->label;

					/* Loop through the field choices */
					foreach ( $field->choices as $choice ) {
						$results['field_data'][ $field->id ] = $this->replace_key( $results['field_data'][ $field->id ], $choice['value'], $choice['text'] );
					}
				}

				$data['poll']['global'] = $results;
			}
		}

		return $data;
	}

	/**
	 * Pass in a Gravity Form Field Object and get back a Gravity PDF Field Object
	 *
	 * @param GF_Field       $field    Gravity Form Field Object
	 * @param array          $form     The Gravity Form Array
	 * @param array          $entry    The Gravity Form Entry
	 * @param Field_Products $products A Field_Products Object
	 * @param array          $config   Should contain the keys 'meta' and 'settings'. Added in v6.9
	 *
	 * @return Helper_Abstract_Fields
	 *
	 * @throws Exception
	 * @since 4.0
	 */
	public function get_field_class( $field, $form, $entry, Field_Products $products, $config = [] ) {

		$class_name = $this->misc->get_field_class( $field->type );

		try {
			/* if we have a valid class name... */
			if ( class_exists( $class_name ) ) {

				/**
				 * Developer Note
				 *
				 * We've purposefully not added any filters to the Field_* child classes directly.
				 * Instead, if you want to change how one of the fields are displayed or output (without effecting Gravity Forms itself) you should tap
				 * into one of the filters below and override or extend the entire class.
				 *
				 * Your class MUST extend the \GFPDF\Helper\Helper_Abstract_Fields abstract class - either directly or by extending an existing \GFPDF\Helper\Fields class.
				 * eg. class Fields_New_Text extends \GFPDF\Helper\Helper_Abstract_Fields or Fields_New_Text extends \GFPDF\Helper\Fields\Field_Text
				 *
				 * To make your life more simple you should either use the same namespace as the field classes (\GFPDF\Helper\Fields) or import the class directly (use \GFPDF\Helper\Fields\Field_Text)
				 * We've tried to make the fields as modular as possible. If you have any feedback about this approach please submit a ticket on GitHub (https://github.com/GravityPDF/gravity-pdf/issues)
				 */

				$class = new $class_name( $field, $entry, $this->gform, $this->misc );

				if ( $class instanceof Helper_Abstract_Field_Products ) {
					$class->set_products( $products );
				}

				/*
				 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_field_class/ for more details about these filters
				 */
				$class = apply_filters( 'gfpdf_field_class', $class, $field, $entry, $form );
				$class = apply_filters( 'gfpdf_field_class_' . $field->type, $class, $field, $entry, $form );
			}

			if ( empty( $class ) || ! ( $class instanceof Helper_Abstract_Fields ) ) {
				throw new Exception( 'Class not found' );
			}
		} catch ( Exception $e ) {

			$this->log->warning(
				sprintf(
					'Gravity PDF does not have native support for this field type "%s". Falling back to default Gravity Forms output.',
					$field->type
				),
				[
					'field' => $field,
				]
			);

			/* Exception thrown. Load generic field loader */
			$class = apply_filters( 'gfpdf_field_default_class', new Field_Default( $field, $entry, $this->gform, $this->misc ), $field, $entry, $form );
		}

		if ( $class instanceof Helper_Interface_Field_Pdf_Config ) {
			$class->set_pdf_config( $config );
		}

		return $class;
	}

	/**
	 * Sniff the form fields and determine if there are any of the $type available
	 *
	 * @param string $type the field type we are looking for
	 * @param array  $form the form array
	 *
	 * @return boolean       Whether there is a match or not
	 *
	 * @since 4.0
	 */
	public function check_field_exists( $type, $form ) {

		if ( isset( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				if ( $field['type'] === $type ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Parse the Quiz Overall Results
	 *
	 * @param array $form   The Gravity Form
	 * @param array $fields The quiz fields
	 *
	 * @return array         The parsed results
	 *
	 * @since 4.0
	 */
	public function get_quiz_overall_data( $form, $fields ) {

		if ( ! class_exists( 'GFQuiz' ) ) {
			return [];
		}

		/* GFQuiz is a singleton. Get the instance */
		$quiz = GFQuiz::get_instance();

		/* Create our callback to add additional data to the array specific to the quiz plugin */
		$options['callbacks']['calculation'] = [
			$quiz,
			'results_calculation',
		];

		$results = $this->get_addon_global_data( $form, $options, $fields );

		if ( count( $results ) > 0 ) {
			/* Loop through our fields and update our global results */
			foreach ( $fields as $field ) {

				/* Replace ['totals'] key with ['misc'] key */
				$results['field_data'][ $field->id ] = $this->replace_key( $results['field_data'][ $field->id ], 'totals', 'misc' );

				/* Add the field name to the ['misc'] key */
				$results['field_data'][ $field->id ]['misc']['label'] = $field->label;

				/* Loop through the field choices */
				if ( is_array( $field->choices ) ) {
					foreach ( $field->choices as $choice ) {
						$results['field_data'][ $field->id ] = $this->replace_key( $results['field_data'][ $field->id ], $choice['value'], $choice['text'] );

						/* Check if this is the correct field */
						if ( isset( $choice['gquizIsCorrect'] ) && $choice['gquizIsCorrect'] === true ) {
							$results['field_data'][ $field->id ]['misc']['correct_option_name'][] = esc_html( $choice['text'] );
						}
					}
				}
			}
		}

		return $results;
	}

	/**
	 * Pull Gravity Forms global results Data
	 *
	 * @param array $form    The Gravity Form array
	 * @param array $options The global query options
	 * @param array $fields  The field array to use in our query
	 *
	 * @return array          The results
	 *
	 * @since 4.0
	 */
	private function get_addon_global_data( $form, $options, $fields ) {

		/**
		 * Disable aggregate addon data (speeds up PDF generation time)
		 *
		 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_disable_global_addon_data/
		 *
		 * @since 5.1
		 */
		if ( apply_filters( 'gfpdf_disable_global_addon_data', false, $form, $options, $fields ) ) {
			return [];
		}

		/* If the results class isn't loaded, load it */
		if ( ! class_exists( 'GFResults' ) ) {
			require_once GFCommon::get_base_path() . '/includes/addon/class-gf-results.php';
		}

		$form_id = $form['id'];

		/* Add form filter to keep in line with GF standard */
		$form = apply_filters( 'gform_form_pre_results', $form );
		$form = apply_filters( 'gform_form_pre_results_' . $form_id, $form );

		/* Initiate the results class */
		$gf_results = new GFResults( '', $options );

		/* Ensure that only active leads are queried */
		$search = [
			'field_filters' => [ 'mode' => '' ],
			'status'        => 'active',
		];

		/* Get the results */
		$data = $gf_results->get_results_data( $form, $fields, $search );

		/* Unset some array keys we don't need */
		unset( $data['status'] );
		unset( $data['timestamp'] );

		return $data;
	}

	/**
	 * Swap out the array key
	 *
	 * @param array  $array           The array to be modified
	 * @param string $key             The key to remove
	 * @param string $replacement_key The new array key
	 *
	 * @return array        The modified array
	 *
	 * @since 4.0
	 */
	public function replace_key( $array, $key, $replacement_key ) {
		if ( $key !== $replacement_key && isset( $array[ $key ] ) ) {

			/* Replace the array key with the actual field name */
			$array[ $replacement_key ] = $array[ $key ];
			unset( $array[ $key ] );
		}

		return $array;
	}

	/**
	 * Creates a PDF on every submission, except when the PDF is already created during the notification hook
	 *
	 * @param array $entry The GF Entry Details
	 * @param array $form  The Gravity Form
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since 4.0
	 */
	public function maybe_save_pdf( $entry, $form ) {

		/* Exit early if background processing is enabled */
		if ( $this->options->get_option( 'background_processing', 'No' ) === 'Yes' ) {
			return;
		}

		$pdfs = ( isset( $form['gfpdf_form_settings'] ) ) ? $this->get_active_pdfs( $form['gfpdf_form_settings'], $entry ) : [];

		if ( count( $pdfs ) > 0 ) {

			/* Loop through each PDF config */
			foreach ( $pdfs as $pdf ) {
				$settings = $this->options->get_pdf( $entry['form_id'], $pdf['id'] );

				/* Only generate if the PDF wasn't created during the notification process */
				if ( ! is_wp_error( $settings ) && $this->maybe_always_save_pdf( $settings, $entry['form_id'] ) ) {
					$this->generate_and_save_pdf( $entry, $settings );
				}
			}
		}
	}

	/**
	 * Determine if the PDF should be saved to disk
	 *
	 * @param array $settings The current Gravity PDF Settings
	 * @param int   $form_id  The current Form ID
	 *
	 * @since 4.0
	 */
	public function maybe_always_save_pdf( array $settings, int $form_id = 0 ): bool {

		$save = has_filter( 'gfpdf_post_save_pdf' ) || has_filter( 'gfpdf_post_save_pdf_' . $form_id );

		/* Legacy / Backwards compatible */
		if ( strtolower( $settings['save'] ?? '' ) === 'yes' ) {
			$save = true;
		}

		/**
		 * @since 4.2
		 */
		return apply_filters( 'gfpdf_maybe_always_save_pdf', $save, $settings, $form_id );
	}

	/**
	 * Trigger Post PDF Generation Action
	 *
	 * @param array      $form     The Gravity Form
	 * @param array      $entry    The Gravity Form Entry
	 * @param array      $settings The Gravity PDF Settings
	 * @param Helper_PDF $pdf      The Helper_PDF object
	 *
	 * @since 5.2
	 */
	public function trigger_post_save_pdf( $form, $entry, $settings, $pdf ) {
		$pdf_path = $pdf->get_full_pdf_path();

		if ( is_file( $pdf_path ) ) {
			/* Add appropriate filters so developers can access the PDF when it is generated */
			$form     = apply_filters( 'gfpdf_current_form_object', $this->gform->get_form( $entry['form_id'] ), $entry, __FUNCTION__ );
			$filename = basename( $pdf_path );

			do_action( 'gfpdf_post_pdf_save', $form['id'], $entry['id'], $settings, $pdf_path ); /* Backwards compatibility */

			/* See https://docs.gravitypdf.com/v6/developers/actions/gfpdf_post_save_pdf for more details about these actions */
			do_action( 'gfpdf_post_save_pdf', $pdf_path, $filename, $settings, $entry, $form );
			do_action( 'gfpdf_post_save_pdf_' . $form['id'], $pdf_path, $filename, $settings, $entry, $form );
		}
	}

	/**
	 * Clean-up our tmp directory every 12 hours
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function cleanup_tmp_dir() {
		$max_file_age  = time() - 3600; /* Max age is 1 hour old */
		$tmp_directory = $this->data->template_tmp_location;

		if ( ! is_dir( $tmp_directory ) ) {
			return;
		}

		try {
			$directory_list = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $tmp_directory, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ( $directory_list as $file ) {
				if ( in_array( $file->getFilename(), [ '.htaccess', 'index.html' ], true ) || strpos( realpath( $file->getPathname() ), realpath( $this->data->mpdf_tmp_location ) ) !== false ) {
					continue;
				}

				if ( $file->isReadable() && $file->getMTime() < $max_file_age ) {
					( $file->isDir() ) ? $this->misc->rmdir( $file->getPathName() ) : unlink( $file->getPathName() );
				}
			}
		} catch ( Exception $e ) {
			$this->log->error(
				'Filesystem Delete Error',
				[
					'dir'       => $tmp_directory,
					'exception' => $e->getMessage(),
				]
			);
		}
	}

	/**
	 * Triggered after the Gravity Form entry is updated
	 *
	 * @param array $form
	 * @param int   $entry_id
	 *
	 * @deprecated 6.12 Caching layer + auto-purge added
	 */
	public function cleanup_pdf_after_submission( $form, $entry_id ) {
		_doing_it_wrong( __METHOD__, 'This method is deprecated and no alternative is available. The temporary cache is automatically cleaned every hour using the WP Cron.', '6.12' );

		/* Exit if background processing is enabled */
		if ( $this->options->get_option( 'background_processing', 'No' ) === 'Yes' ) {
			return;
		}

		$entry = $this->gform->get_entry( $entry_id );

		/* Exit if GF async notifications is enabled */
		$notifications = array_column( $form['notifications'] ?? [], 'id' );
		if ( $this->is_gform_asynchronous_notifications_enabled( $notifications, $form, $entry ) ) {
			return;
		}

		$this->cleanup_pdf( $entry, $form );
	}

	/**
	 * Remove the generated PDF from the server to save disk space
	 *
	 * @param array $entry The GF Entry Data
	 * @param array $form  The Gravity Form
	 *
	 * @return void
	 *
	 * @since 4.0
	 *
	 * @deprecated 6.12 Caching layer + auto-purge added
	 */
	public function cleanup_pdf( $entry, $form ) {
		_doing_it_wrong( __METHOD__, 'This method is deprecated and no alternative is available. The temporary cache is automatically cleaned every hour using the WP Cron.', '6.12' );

		$pdfs = $this->get_active_pdfs( $form['gfpdf_form_settings'] ?? [], $entry );

		if ( count( $pdfs ) === 0 ) {
			return;
		}

		$tmp_path_directory = realpath( $this->data->template_tmp_location );

		/* loop through each PDF config */
		foreach ( $pdfs as $pdf ) {
			$pdf_generator = new Helper_PDF( $entry, $pdf, $this->gform, $this->data, $this->misc, $this->templates, $this->log );
			$path          = $pdf_generator->get_path();

			/* Verify we are only deleting files in the designated tmp directory */
			$path_to_test = realpath( $path );
			if ( $path_to_test === false || strpos( $path_to_test, $tmp_path_directory ) !== 0 || ! is_dir( $path ) ) {
				continue;
			}

			$this->misc->rmdir( $path );
		}
	}

	/**
	 * Clean-up any PDFs stored on disk before we resend any notifications
	 *
	 * @param array $form    The Gravity Forms object
	 * @param array $entries An array of Gravity Form entry IDs
	 *
	 * @return array We tapped into a filter so we need to return the form object
	 * @since 4.0
	 * @deprecated 6.12 Caching layer + auto-purge added
	 */
	public function resend_notification_pdf_cleanup( $form, $entries ) {
		_doing_it_wrong( __METHOD__, 'This method is deprecated and no alternative is available. The temporary cache is automatically cleaned every hour using the WP Cron.', '6.12' );

		foreach ( $entries as $entry_id ) {
			$entry = $this->gform->get_entry( $entry_id );
			$this->cleanup_pdf( $entry, $form );
		}

		return $form;
	}

	/**
	 * Check if any of the form's notification is set to asynchronous
	 *
	 * @param array $notifications An array containing the IDs of the notifications to be sent.
	 * @param array $form          The form being processed.
	 * @param array $entry         The entry being processed.
	 * @param array $data          An array of data which can be used in the notifications via the generic {object:property} merge tag. Defaults to empty array.
	 *
	 * @return string
	 *
	 * @since 6.11.0
	 *
	 * @see   https://docs.gravityforms.com/gform_is_asynchronous_notifications_enabled/
	 */
	public function is_gform_asynchronous_notifications_enabled( $notifications, $form, $entry, $data = [] ) {
		return gf_apply_filters(
			[ 'gform_is_asynchronous_notifications_enabled', $form['id'] ],
			false,
			'form_submission',
			$notifications,
			$form,
			$entry,
			$data
		);
	}

	/**
	 * An mPDF filter which will register our custom font data with mPDF
	 *
	 * @param array $fonts The registered fonts
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function register_custom_font_data_with_mPDF( $fonts ) {

		$custom_fonts = $this->options->get_custom_fonts();

		foreach ( $custom_fonts as $font ) {

			$fonts[ $font['id'] ] = array_filter(
				[
					'R'          => basename( $font['regular'] ),
					'B'          => basename( $font['bold'] ),
					'I'          => basename( $font['italics'] ),
					'BI'         => basename( $font['bolditalics'] ),
					'useOTL'     => $font['useOTL'] ?? 0x00,
					'useKashida' => $font['useKashida'] ?? 0,
				]
			);
		}

		return $fonts;
	}

	/**
	 * Read all fonts from our fonts directory and auto-load them into mPDF if they are not found
	 *
	 * @param array $fonts The registered fonts
	 *
	 * @return array
	 * @since 4.0
	 */
	public function add_unregistered_fonts_to_mPDF( $fonts ) {

		$user_fonts = glob( $this->data->template_font_location . '*.[tT][tT][fF]', GLOB_NOSORT );
		$user_fonts = ( is_array( $user_fonts ) ) ? $user_fonts : [];

		$flattened_fonts_array = [];
		array_walk_recursive(
			$fonts,
			function( $val ) use ( &$flattened_fonts_array ) {
				$flattened_fonts_array[] = $val;
			}
		);

		foreach ( $user_fonts as $font ) {

			/* Get font shortname */
			$font_name  = basename( $font );
			$short_name = $this->options->get_font_short_name( substr( $font_name, 0, -4 ) );

			/* Check if it exists already, otherwise add it */
			if ( ! isset( $fonts[ $short_name ] ) && array_search( $font_name, $flattened_fonts_array, true ) === false ) {
				$fonts[ $short_name ] = [
					'R' => $font_name,
				];
			}
		}

		return $fonts;
	}

	/**
	 * Attempts to find a configuration which matches the legacy routing method
	 *
	 * @param array $config
	 *
	 * @return mixed
	 *
	 * @since  4.0
	 * @deprecated 4.0 Added for backwards compatibility, but ideally should not be used
	 */
	public function get_legacy_config( $config ) {
		_doing_it_wrong( __METHOD__, 'Legacy PDF URLs are deprecated. Replace with the [gravitypdf] shortcode or PDF merge tags. See https://docs.gravitypdf.com/v6/users/shortcodes-and-mergetags for usage instructions.', '4.0' );

		/* Get the form settings */
		$pdfs = $this->options->get_form_pdfs( $config['fid'] );

		if ( is_wp_error( $pdfs ) ) {
			return $pdfs;
		}

		/* Reindex the $pdfs keys */
		$pdfs = array_values( $pdfs );

		/* Use the legacy aid to determine which PDF to load */
		if ( isset( $config['aid'] ) && $config['aid'] !== false ) {
			$selector = $config['aid'] - 1;

			if ( isset( $pdfs[ $selector ] ) && $pdfs[ $selector ]['template'] === $config['template'] ) {
				return $pdfs[ $selector ]['id'];
			}
		}

		/* The aid method failed so lets load the first matching configuration */
		foreach ( $pdfs as $pdf ) {
			if ( $pdf['active'] === true && $pdf['template'] === $config['template'] ) {
				return $pdf['id'];
			}
		}

		return new WP_Error( 'pdf_configuration_error', esc_html__( 'Could not find PDF configuration requested', 'gravity-forms-pdf-extended' ) );
	}

	/**
	 * Do any preprocessing to our arguments before they are sent to the template
	 *
	 * @param array $args
	 *
	 * @return array
	 *
	 * @since  4.0
	 */
	public function preprocess_template_arguments( $args ) {

		if ( isset( $args['settings']['header'] ) ) {
			$args['settings']['header'] = $this->gform->process_tags( $args['settings']['header'], $args['form'], $args['entry'] );
			$args['settings']['header'] = $this->misc->fix_header_footer( $args['settings']['header'] );
		}

		if ( isset( $args['settings']['first_header'] ) ) {
			$args['settings']['first_header'] = $this->gform->process_tags( $args['settings']['first_header'], $args['form'], $args['entry'] );
			$args['settings']['first_header'] = $this->misc->fix_header_footer( $args['settings']['first_header'] );
		}

		if ( isset( $args['settings']['footer'] ) ) {
			$args['settings']['footer'] = $this->gform->process_tags( $args['settings']['footer'], $args['form'], $args['entry'] );
			$args['settings']['footer'] = $this->misc->fix_header_footer( $args['settings']['footer'] );
		}

		if ( isset( $args['settings']['first_footer'] ) ) {
			$args['settings']['first_footer'] = $this->gform->process_tags( $args['settings']['first_footer'], $args['form'], $args['entry'] );
			$args['settings']['first_footer'] = $this->misc->fix_header_footer( $args['settings']['first_footer'] );
		}

		/**
		 * @since 4.2
		 */
		return apply_filters( 'gfpdf_preprocess_template_arguments', $args );
	}

	/**
	 * Skip over any fields with a class of "exclude"
	 *
	 * @param bool     $action
	 * @param GF_Field $field
	 * @param array    $entry
	 * @param array    $form
	 * @param array    $config
	 *
	 * @return bool
	 *
	 * @since 4.2
	 */
	public function field_middle_exclude( $action, $field, $entry, $form, $config ) {
		if ( $action === false ) {
			$skip_marked_fields = ( isset( $config['meta']['exclude'] ) ) ? $config['meta']['exclude'] : true;

			if ( $skip_marked_fields !== false && strpos( $field->cssClass, 'exclude' ) !== false ) {
				return true;
			}
		}

		return $action;
	}

	/**
	 * Determine if we should skip fields hidden with conditional logic
	 *
	 * @param bool     $action
	 * @param GF_Field $field
	 * @param array    $entry
	 * @param array    $form
	 * @param array    $config
	 *
	 * @return bool
	 *
	 * @since 4.2
	 */
	public function field_middle_conditional_fields( $action, $field, $entry, $form, $config ) {
		if ( $action === false ) {
			$skip_conditional_fields = ( isset( $config['meta']['conditional'] ) ) ? $config['meta']['conditional'] : true;
			if ( $skip_conditional_fields === true && GFFormsModel::is_field_hidden( $form, $field, [], $entry ) ) {
				return true;
			}
		}

		return $action;
	}

	/**
	 * Determine if we should skip product fields (by default they are grouped at the end of the form)
	 *
	 * @param bool     $action
	 * @param GF_Field $field
	 * @param array    $entry
	 * @param array    $form
	 * @param array    $config
	 *
	 * @return bool
	 *
	 * @since 4.2
	 */
	public function field_middle_product_fields( $action, $field, $entry, $form, $config ) {
		if ( $action === false ) {
			$show_individual_product_fields = ( isset( $config['meta']['individual_products'] ) ) ? $config['meta']['individual_products'] : false;
			if ( $show_individual_product_fields === false && GFCommon::is_product_field( $field->type ) ) {
				return true;
			}
		}

		return $action;
	}

	/**
	 * Determine if we should skip HTML fields
	 *
	 * @param bool     $action
	 * @param GF_Field $field
	 * @param array    $entry
	 * @param array    $form
	 * @param array    $config
	 *
	 * @return bool
	 *
	 * @since 4.2
	 */
	public function field_middle_html_fields( $action, $field, $entry, $form, $config ) {
		if ( $action === false ) {
			$show_html_fields = ( isset( $config['meta']['html_field'] ) ) ? $config['meta']['html_field'] : false;
			if ( $show_html_fields === false && $field->type === 'html' ) {
				return true;
			}
		}

		return $action;
	}

	/**
	 * Determine if we should skip Page fields
	 *
	 * @param bool     $action
	 * @param GF_Field $field
	 * @param array    $entry
	 * @param array    $form
	 * @param array    $config
	 *
	 * @return bool
	 *
	 * @since 6.10.1
	 */
	public function field_middle_page( $action, $field, $entry, $form, $config ) {
		if ( $action === false ) {
			$show_page_names = $config['meta']['page_names'] ?? false;
			if ( $show_page_names === false && $field->get_input_type() === 'page' ) {
				return true;
			}
		}

		return $action;
	}

	/**
	 * Check if the field is on our blacklist and skip
	 *
	 * @param bool           $action
	 * @param GF_Field       $field
	 * @param array          $entry
	 * @param array          $form
	 * @param array          $config
	 * @param Field_Products $products
	 * @param array          $blacklisted
	 *
	 * @return bool
	 *
	 * @since 4.2
	 */
	public function field_middle_blacklist( $action, $field, $entry, $form, $config, $products, $blacklisted ) {
		if ( $action === false ) {
			if ( in_array( $field->type, $blacklisted, true ) ) {
				return true;
			}
		}

		return $action;
	}

	/**
	 * Set the watermark font to the current PDF font
	 *
	 * @param Mpdf  $mpdf
	 * @param array $form
	 * @param array $entry
	 * @param array $settings
	 *
	 * @return Mpdf
	 *
	 * @since 5.0
	 */
	public function set_watermark_font( $mpdf, $form, $entry, $settings ) {
		$mpdf->watermark_font = ( isset( $settings['watermark_font'] ) ) ? $settings['watermark_font'] : $settings['font'];

		return $mpdf;
	}

	/**
	 * Replace any Gravity Perk Populate Anything live merge tags with their standard equivalent (i.e without the @ symbol)
	 * Include support for the `fallback` option
	 *
	 * @param string $text
	 * @param array  $form
	 * @param array  $entry
	 *
	 * @return string
	 *
	 * @since 5.3
	 */
	public function process_gp_populate_anything( $text, $form, $entry ) {
		if ( ! class_exists( 'GP_Populate_Anything_Live_Merge_Tags' ) ) {
			return $text;
		}

		$gp = GP_Populate_Anything_Live_Merge_Tags::get_instance();

		$this->disable_gp_populate_anything();
		$text = $gp->replace_live_merge_tags_static( $text, $form, $entry );
		$this->enable_gp_populate_anything();

		return $text;
	}

	/**
	 * At the end of the PDF generation, remove filter to replace merge tags for Gravity Perk Populate Anything
	 *
	 * @since 5.3
	 */
	public function disable_gp_populate_anything() {
		add_filter( 'gppa_allow_all_lmts', '__return_true' );
		remove_filter( 'gform_pre_replace_merge_tags', [ $this, 'process_gp_populate_anything' ] );
	}

	/**
	 * At the start of the PDF generation, filter all Gravity Perk Populate Anything merge tag replacement calls
	 *
	 * @since 5.3
	 */
	public function enable_gp_populate_anything() {
		remove_filter( 'gppa_allow_all_lmts', '__return_true' );
		add_filter( 'gform_pre_replace_merge_tags', [ $this, 'process_gp_populate_anything' ], 10, 3 );
	}

	/**
	 * Register Legal Signing path of additional font directory with mPDF
	 *
	 * @param array $config
	 *
	 * @return array
	 *
	 * @since
	 */
	public function register_legal_signing_font_path_with_mpdf( $config ) {
		if ( ! isset( $config['fontDir'] ) || ! is_array( $config['fontDir'] ) ) {
			$config['fontDir'] = [];
		}

		$config['fontDir'][] = WP_PLUGIN_DIR . '/' . dirname( FG_LEGALSIGNING_PLUGIN_BASENAME ) . '/dist/fonts/';

		return $config;
	}

	/**
	 * Register Legal Signing font files in mPDF
	 *
	 * @param array $fonts
	 *
	 * @return array
	 *
	 * @since 6.10
	 */
	public function register_legal_signing_fonts_with_mpdf( $fonts ) {
		$signature_fonts = glob( WP_PLUGIN_DIR . '/' . dirname( FG_LEGALSIGNING_PLUGIN_BASENAME ) . '/dist/fonts/*.[tT][tT][fF]', GLOB_NOSORT );
		$signature_fonts = is_array( $signature_fonts ) ? $signature_fonts : [];

		foreach ( $signature_fonts as $font ) {
			$font_id = basename( strtolower( $font ), '.ttf' );
			/* Skip if font ID already exists */
			if ( isset( $fonts[ $font_id ] ) ) {
				continue;
			}

			$fonts[ $font_id ] = [
				'R' => basename( $font ),
			];
		}

		return $fonts;
	}

	/**
	 * If the form has page fields, prepare for output in the PDF
	 *
	 * @param array $form
	 *
	 * @return array
	 *
	 * @since 6.10.1
	 */
	public function register_page_fields( $form ) {
		if ( ! isset( $form['pagination']['pages'][0] ) ) {
			return $form;
		}

		array_unshift(
			$form['fields'],
			new \GF_Field_Page(
				[
					'id'         => 0,
					'formId'     => $form['id'],
					'pageNumber' => 1,
					'cssClass'   => $form['firstPageCssClass'] ?? '',
				]
			)
		);

		array_map(
			function( $item ) use ( $form ) {
				$item->label   = sprintf( esc_html__( 'Page %d', 'gravity-forms-pdf-extended' ), $item->pageNumber );
				$item->content = $form['pagination']['pages'][ $item->pageNumber - 1 ] ?? '';
			},
			\GFAPI::get_fields_by_type( $form, 'page', true )
		);

		return $form;
	}

	/**
	 * Hydrate the form with Populate Anything data
	 * For performance, the results are cached by the form/entry ID combo
	 *
	 * @param array $form
	 * @param array $entry
	 *
	 * @return array
	 *
	 * @since 6.10.2
	 */
	public function gp_populate_anything_hydrate_form( $form, $entry ) {
		static $cache = [];

		$form_id  = $form['id'] ?? '';
		$entry_id = $entry['id'] ?? '';

		$key = $form_id . $entry_id;

		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		$hydrated_form = gp_populate_anything()->populate_form( $form, false, [], $entry );
		$cache[ $key ] = $hydrated_form;

		return $hydrated_form;
	}

	/**
	 * Send a PDF file to the browser
	 *
	 * @param string $path_to_pdf Absolute path to PDF on disk
	 * @param string $action Either "view" or "download"
	 *
	 * @since 6.12
	 */
	public function send_pdf_to_browser( $path_to_pdf, $action = 'view' ) {
		/* Verify the PDF can be sent to the client */
		if ( headers_sent( $filename, $linenumber ) ) {
			$this->log->error(
				'Server headers already sent',
				[
					'filename'   => $filename,
					'linenumber' => $linenumber,
				]
			);

			return new WP_Error( 'headers_sent', __( 'The PDF cannot be displayed because the server headers have already been sent.', 'gravity-forms-pdf-extended' ) );
		}

		/* Force any active buffers to close and delete its content */
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		/* Send the PDF to the client */
		header( 'Content-Type: application/pdf' );

		/*
		 * Set the filename, supporting the new utf-8 syntax + backwards compatibility
		 * Refer to RFC 8187 https://www.rfc-editor.org/rfc/rfc8187.html
		 */
		header(
			sprintf(
				'Content-Disposition: %1$s; filename="%2$s"; filename*=utf-8\'\'%2$s',
				$action === 'view' ? 'inline' : 'attachment',
				rawurlencode( basename( $path_to_pdf ) ),
			)
		);

		/* only add the length if the server is not using compression */
		if ( empty( $_SERVER['HTTP_ACCEPT_ENCODING'] ) ) {
			header( sprintf( 'Content-Length: %d', filesize( $path_to_pdf ) ) );
		}

		/* Tell client to download the file */
		if ( $action !== 'view' ) {
			header( 'Content-Description: File Transfer' );
			header( 'Content-Transfer-Encoding: binary' );
		}

		/* Set appropriate headers for local browser caching */
		$last_modified_time = filemtime( $path_to_pdf );
		$etag               = md5( $path_to_pdf ); /* the file path includes a unique hash that automatically changes when a PDF does */

		header( sprintf( 'Last-Modified: %s GMT', gmdate( 'D, d M Y H:i:s', $last_modified_time ) ) );
		header( sprintf( 'Etag: %s', $etag ) );
		header( 'Cache-Control: no-cache, private' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		/* Tell client they can display the PDF from the local cache if it is still current */
		if ( ! empty( $_SERVER['HTTP_IF_NONE_MATCH'] ) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag ) {
			header( 'HTTP/1.1 304 Not Modified' );
			exit;
		}

		readfile( $path_to_pdf ); /* phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile */

		exit;
	}
}
