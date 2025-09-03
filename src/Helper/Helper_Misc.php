<?php

namespace GFPDF\Helper;

use DOMElement;
use Exception;
use GFCommon;
use GFMultiCurrency;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use WP_Error;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since  4.0
 */
class Helper_Misc {

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
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Store required classes locally
	 *
	 * @param LoggerInterface      $log
	 * @param Helper_Abstract_Form $gform
	 * @param Helper_Data          $data
	 *
	 * @since 4.0
	 */
	public function __construct( LoggerInterface $log, Helper_Abstract_Form $gform, Helper_Data $data ) {

		/* Assign our internal variables */
		$this->log   = $log;
		$this->gform = $gform;
		$this->data  = $data;
	}

	/**
	 * Check if the current admin page is a Gravity PDF page
	 *
	 * @return boolean
	 * @since 4.0
	 *
	 */
	public function is_gfpdf_page() {
		if ( ! is_admin() ) {
			return false;
		}

		$page    = $_GET['page'] ?? ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$subview = $_GET['subview'] ?? ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( empty( $page ) && empty( $subview ) ) {
			return false;
		}

		if ( ! is_string( $page ) || ! is_string( $subview ) ) {
			return false;
		}

		if ( strpos( $page, 'gfpdf-' ) !== 0 && strtoupper( $subview ) !== 'PDF' ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if we are on the current global settings page / tab
	 *
	 * @param string $name The current page ID to check
	 *
	 * @return boolean
	 * @since 4.0
	 *
	 */
	public function is_gfpdf_settings_tab( $name ) {
		if ( is_admin() && $this->is_gfpdf_page() ) {
			/* phpcs:ignore WordPress.Security.NonceVerification.Recommended */
			$tab = sanitize_key( $_GET['tab'] ?? 'general' );
			if ( $name === $tab ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gravity Forms has a 'type' for each field.
	 * Based on that type, attempt to match it to Gravity PDFs field classes
	 *
	 * @param string $type The field type we are looking up
	 *
	 * @return string|boolean       The Fully Qualified Namespaced Class we matched, or false
	 *
	 * @since 4.0
	 */
	public function get_field_class( $type ) {

		/* Format the type name correctly */
		$type_array = explode( '_', $type );
		$type_array = array_map( 'ucwords', $type_array );
		$type       = implode( '_', $type_array );

		/* See if we have a class that matches */
		$fqns = 'GFPDF\Helper\Fields\Field_';

		if ( class_exists( $fqns . $type ) ) {
			return $fqns . $type;
		}

		return false;
	}

	/**
	 * Manipulate header and footer for more consistent display in PDF
	 *
	 * Changes made include:
	 *
	 * 1. Apply wpautop to content
	 *
	 * 2. Apply wp_kses_post to content
	 *
	 * 3. mPDF currently has no cascading CSS ability to target 'inline' elements. Fix image display issues in header / footer
	 * by adding a specific class name we can target
	 *
	 * 4. Convert any image URLs to local path where applicable
	 *
	 * 5. Strips out page breaks
	 *
	 * @param string $html The HTML to parse
	 *
	 * @return string
	 */
	public function fix_header_footer( $html ) {

		$html = \GFPDF\Statics\Kses::parse( $html );
		$html = trim( wpautop( $html ) );
		$html = $this->fix_header_footer_images( $html );

		/* Strip page breaks */
		$html = preg_replace( '/<pagebreak(.+?)?\/?>/', '', $html );
		$html = preg_replace( '/page-break-(before|after):( +)?(always|left|right|auto|avoid)/', '', $html );

		return $html;
	}

	/**
	 * Convert image URLs to local path (where able) and add specific class names to images for better
	 * targeting in Mpdf
	 *
	 * @param string $html
	 *
	 * @return string
	 *
	 * @since 7.0.4
	 */
	public function fix_header_footer_images( $html ) {
		try {
			/* Get the <img> from the DOM and extract required details */
			$qp      = new Helper_QueryPath();
			$wrapper = $qp->html5( $html );

			$images = $wrapper->find( 'img' );

			if ( count( $images ) > 0 ) {
				/* Loop through each matching element */
				foreach ( $images as $image ) {

					/* Get current image src */
					$image_src      = trim( $image->attr( 'src' ) );
					$image_src_path = $this->convert_url_to_path( $image_src );

					if ( false !== $image_src_path ) {
						$image->attr( 'src', $image_src_path );
					}

					/* Get the current image classes */
					$image_classes = $image->attr( 'class' );

					/* Remove width/height and add a override class */
					$image->removeAttr( 'width' )->removeAttr( 'height' )->addClass( 'header-footer-img' );

					/*
					 * Wrap in a new div that includes the image classes
					 * If the direct parent is a link, we'll wrap the link in the DIV instead
					 */
					if ( strlen( $image_classes ) > 0 ) {
						$parent_node = $image->parent()->get( 0 );
						if ( $parent_node instanceof DOMElement && $parent_node->nodeName === 'a' ) {
							$image->parent()->wrap( '<div class="' . $image_classes . '"></div>' );
						} else {
							$image->wrap( '<div class="' . $image_classes . '"></div>' );
						}
					}
				}

				$html = $wrapper->top( 'html' )->innerHTML();

				/* Remove empty <p></p> tags */
				$html = str_replace( '<p></p>', '', $html );
			}

			return $html;

		} catch ( Exception $e ) {
			/* if there was any issues we'll just return the $html */
			return $html;
		}
	}

	/**
	 * Processes a hex colour and returns an appropriately contrasting black or white
	 *
	 * @param string $hexcolor The Hex to be inverted
	 *
	 * @return string
	 *
	 * @since    4.0
	 */
	public function get_contrast( $hexcolor ) {
		$hexcolor = str_replace( '#', '', $hexcolor );

		if ( 6 !== strlen( $hexcolor ) ) {
			$hexcolor = str_repeat( substr( $hexcolor, 0, 1 ), 2 ) . str_repeat( substr( $hexcolor, 1, 1 ), 2 ) . str_repeat( substr( $hexcolor, 2, 1 ), 2 );
		}

		$r   = hexdec( substr( $hexcolor, 0, 2 ) );
		$g   = hexdec( substr( $hexcolor, 2, 2 ) );
		$b   = hexdec( substr( $hexcolor, 4, 2 ) );
		$yiq = ( ( $r * 299 ) + ( $g * 587 ) + ( $b * 114 ) ) / 1000;

		return ( $yiq >= 150 ) ? '#000' : '#FFF';
	}

	/**
	 * Change the brightness of the passed in colour
	 *
	 * $diff should be negative to go darker, positive to go lighter and
	 * is subtracted from the decimal (0-255) value of the colour
	 *
	 * @param string  $hexcolor Hex colour to be modified
	 * @param integer $diff     amount to change the color
	 *
	 * @return string hex colour
	 *
	 * @since    4.0
	 */
	public function change_brightness( $hexcolor, $diff ) {

		$hexcolor = trim( str_replace( '#', '', $hexcolor ) );

		if ( 6 !== strlen( $hexcolor ) ) {
			$hexcolor = str_repeat( substr( $hexcolor, 0, 1 ), 2 ) . str_repeat( substr( $hexcolor, 1, 1 ), 2 ) . str_repeat( substr( $hexcolor, 2, 1 ), 2 );
		}

		$rgb = str_split( $hexcolor, 2 );

		foreach ( $rgb as &$hex ) {
			$dec  = hexdec( $hex );
			$dec += $diff;
			$dec  = max( 0, min( 255, $dec ) );
			$hex  = str_pad( dechex( $dec ), 2, '0', STR_PAD_LEFT );
		}

		return '#' . implode( $rgb );
	}

	/**
	 * Pass in a background colour and get the appropriate contrasting background and border colour
	 *
	 * @param string $background_hex Hex colour to get contrast of
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_background_and_border_contrast( $background_hex ) {

		/* Get contrasting background colour */
		$background_color_contrast = $this->get_contrast( $background_hex );

		/* If the background isn't white we'll go down 20, otherwise go up 20 */
		$contrast_value = ( $background_color_contrast === '#FFF' ) ? 20 : -20;

		/* Get the new contrasting background colour */
		$contrast_background_color = $this->change_brightness( $background_hex, $contrast_value );

		/* Get the new border contrast based on the background contrast colour */
		$border_contrast = ( $background_color_contrast === '#FFF' ) ? 60 : -60;

		/* Finally get a contrasting border colour */
		$contrast_border_color = $this->change_brightness( $background_hex, $border_contrast );

		return [
			'background' => $contrast_background_color,
			'border'     => $contrast_border_color,
		];
	}

	/**
	 * Push an associative array onto the beginning of an existing array
	 *
	 * @param array  $array_to_update The array to push onto
	 * @param string $key             The key to use for the newly-pushed array
	 * @param mixed  $val             The value being pushed
	 *
	 * @return array  The modified array
	 *
	 * @since 4.0
	 */
	public function array_unshift_assoc( $array_to_update, $key, $val ) {
		$array_to_update         = array_reverse( $array_to_update, true );
		$array_to_update[ $key ] = $val;

		return array_reverse( $array_to_update, true );
	}

	/**
	 * This function recursively deletes all files and folders under the given directory, and then the directory itself
	 * equivalent to Bash: rm -r $dir
	 *
	 * @param string $dir The path to be deleted
	 * @param bool   $delete_top_level_dir Add ability to leave the top-level directory as-is. Added in 6.3.1
	 *
	 * @return bool|WP_Error
	 *
	 * @since 4.0
	 * @since 6.13.0 Directories not managed by Gravity PDF will throw an error if tried to be deleted
	 */
	public function rmdir( $dir, bool $delete_top_level_dir = true ) {

		/*
		 * Do not allow directories outside the folders managed by Gravity PDF to be deleted
		 */
		$folders = [
			$this->data->template_location,
			$this->data->template_font_location,
			$this->data->template_tmp_location,
			$this->data->mpdf_tmp_location,
		];

		if ( is_multisite() ) {
			$folders[] = $this->data->multisite_template_location;
		}

		/* Verify $dir is a real path on the current file system */
		$path_to_test = realpath( $dir );
		if ( $path_to_test === false ) {
			$this->log->error(
				'Filesystem Delete Error',
				[
					'dir'       => $dir,
					'exception' => 'Not a real path on the file system',
				]
			);

			return new WP_Error( 'gfpdf_rmdir_not_a_real_path' );
		}

		/* Check if $dir to delete falls inside one of the Gravity PDF directories */
		$allowed_to_delete = false;
		foreach ( $folders as $folder ) {

			if ( strpos( $path_to_test, realpath( $folder ) ) === 0 ) {
				$allowed_to_delete = true;
				break;
			}
		}

		if ( ! $allowed_to_delete ) {
			$this->log->error(
				'Filesystem Delete Error',
				[
					'dir'       => $dir,
					'exception' => 'Directory falls outside of approved paths',
				]
			);

			return new WP_Error( 'gfpdf_rmdir_directory_not_approved', esc_html( 'Cannot delete path. Directory falls outside of approved Gravity PDF paths: ' . $dir ) );
		}

		/* Path is managed by Gravity PDF and can be deleted */
		$this->log->notice( sprintf( 'Begin deleting directory recursively: %s', $dir ) );

		try {
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ( $files as $fileinfo ) {
				$function  = ( $fileinfo->isDir() ) ? 'rmdir' : 'unlink';
				$real_path = $fileinfo->getRealPath();
				if ( ! $function( $real_path ) ) {
					throw new Exception( esc_html( 'Could not run ' . $function . ' on  ' . $real_path ) );
				}

				$this->log->notice( sprintf( 'Successfully ran `%s` on %s', $function, $real_path ) );
			}
		} catch ( Exception $e ) {
			$this->log->error(
				'Filesystem Delete Error',
				[
					'dir'       => $dir,
					'exception' => $e->getMessage(),
				]
			);

			return new WP_Error( 'recursion_delete_problem', esc_html( $e ) );
		}

		$results = true;
		if ( $delete_top_level_dir ) {
			$results = rmdir( $dir ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			if ( ! $results ) {
				$this->log->error( sprintf( 'Could not delete the top-level directory: %s', $dir ) );
			}
		}

		$this->log->notice( sprintf( 'End deleting directory recursively: %s', $dir ) );

		return $results;
	}

	/**
	 * Wrapper function for rmdir() which ensures the top-level directory is not deleted
	 *
	 * @param string $path
	 *
	 * @since 4.0
	 *
	 * @internal Changed behaviour in 6.3.1 so the top-level directory is never deleted
	 */
	public function cleanup_dir( $path ) {
		$this->rmdir( $path, false );
	}

	/**
	 * This function recursively copies all files and folders under a given directory
	 * equivalent to Bash: cp -R $dir
	 *
	 * @param string $source      The path to be copied
	 * @param string $destination The path to copy to
	 *
	 * @return boolean|WP_Error
	 *
	 * @since 4.0
	 */
	public function copyr( $source, $destination ) {

		try {
			if ( ! is_dir( $destination ) ) {
				if ( ! wp_mkdir_p( $destination ) ) {
					$this->log->error(
						'Failed Creating Folder Structure',
						[
							'dir' => $destination,
						]
					);

					throw new Exception( 'Could not create folder structure at ' . $destination );
				}
			}

			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $source, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ( $files as $fileinfo ) {
				if ( $fileinfo->isDir() && ! file_exists( $destination . $files->getSubPathName() ) ) {
					if ( ! wp_mkdir_p( $destination . $files->getSubPathName() ) ) {
						$this->log->error(
							'Failed Creating Folder',
							[
								'dir' => $destination . $files->getSubPathName(),
							]
						);

						throw new Exception( 'Could not create folder at ' . esc_html( $destination . $files->getSubPathName() ) );
					}
				} elseif ( ! $fileinfo->isDir() ) {
					if ( ! copy( $fileinfo, $destination . $files->getSubPathName() ) ) {
						$this->log->error(
							'Failed Creating File',
							[
								'file' => $destination . $files->getSubPathName(),
							]
						);
						throw new Exception( 'Could not create file at ' . esc_html( $destination . $files->getSubPathName() ) );
					}
				}
			}
		} catch ( Exception $e ) {

			$this->log->error(
				'Filesystem Copy Error',
				[
					'source'      => $source,
					'destination' => $destination,
					'exception'   => $e->getMessage(),
				]
			);

			return new WP_Error( 'recursion_copy_problem', $e );
		}

		return true;
	}

	/**
	 * Get a path relative to the root WP directory, provided a user hasn't moved the wp-content directory outside the ABSPATH
	 *
	 * @param string $path    The relative path
	 * @param string $replace What ABSPATH should be replaced with
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function relative_path( $path, $replace = '' ) {
		return str_replace( ABSPATH, $replace, $path );
	}

	/**
	 * Modified version of get_upload_path() which just focuses on the base directory
	 * no matter if single or multisite installation
	 * We also only needed the basedir and baseurl so stripped out all the extras
	 *
	 * @return array Base dir and url for the upload directory
	 *
	 * @since 4.0
	 */
	public function get_upload_details() {

		$siteurl = get_option( 'siteurl' );

		/*
		 * Older versions of multisite installations didn't use a standardised upload path
		 * which messes with our folder structure. We'll switch to the initial primary blog (which did use the
		 * /wp-content/uploads/ directory) and then restore the current blog at the end.
		 *
		 * Note: because BLOG_ID_CURRENT_SITE can be changed and the 'upload_path' option doesn't change with it we
		 * opted to hardcode the ID instead.
		 */
		if ( is_multisite() ) {
			switch_to_blog( 1 );
		}

		$upload_path = trim( get_option( 'upload_path' ) );
		$dir         = $upload_path;

		if ( empty( $upload_path ) || 'wp-content/uploads' === $upload_path ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} elseif ( 0 !== strpos( $upload_path, ABSPATH ) ) {
			/* $dir is absolute, $upload_path is (maybe) relative to ABSPATH */
			$dir = path_join( ABSPATH, $upload_path );
		}

		$url = get_option( 'upload_url_path' );
		if ( ! $url ) {
			if ( empty( $upload_path ) || ( 'wp-content/uploads' === $upload_path ) || ( $upload_path === $dir ) ) {
				$url = WP_CONTENT_URL . '/uploads';
			} else {
				$url = trailingslashit( $siteurl ) . $upload_path;
			}
		}

		/* Resort the current multisite blog */
		if ( is_multisite() ) {
			restore_current_blog();
		}

		return [
			'path' => $dir,
			'url'  => $url,
		];
	}

	/**
	 * Attempt to convert the current URL to an internal path
	 *
	 * @param string $url The Url to convert
	 *
	 * @return string|boolean      Path on success or false on failure
	 *
	 * @since  4.0
	 */
	public function convert_url_to_path( $url ) {

		/* If $url is empty we'll return early */
		if ( empty( trim( $url ) ) ) {
			return $url;
		}

		/* Mostly we'll be accessing files in the upload directory, so attempt that first */
		$upload = wp_upload_dir();

		$try_path = str_replace( $upload['baseurl'], $upload['basedir'], $url );

		if ( is_file( $try_path ) ) {
			return $try_path;
		}

		/* If WP_CONTENT_DIR and WP_CONTENT_URL are set we'll try them */
		if ( defined( 'WP_CONTENT_DIR' ) && defined( 'WP_CONTENT_URL' ) ) {
			$try_path = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $url );

			if ( is_file( $try_path ) ) {
				return $try_path;
			}
		}

		/* Include our get_home_path functionality */
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		/* If that didn't work let's try the home url instead */
		$try_path = str_replace( home_url(), get_home_path(), $url );

		if ( is_file( $try_path ) ) {
			return $try_path;
		}

		/* If that didn't work let's try the site url instead */
		$try_path = str_replace( site_url(), ABSPATH, $url );

		if ( is_file( $try_path ) ) {
			return $try_path;
		}

		/* If we are here we couldn't locate the file */

		return false;
	}

	/**
	 * Attempt to convert the current path to a URL
	 *
	 * @param string $path The path to convert
	 *
	 * @return string|boolean      Url on success or false
	 *
	 * @since  4.0
	 */
	public function convert_path_to_url( $path ) {

		/* If $url is empty we'll return early */
		if ( empty( trim( $path ) ) ) {
			return $path;
		}

		/* Mostly we'll be accessing files in the upload directory, so attempt that first */
		$upload = wp_upload_dir();

		$try_url = str_replace( $upload['basedir'], $upload['baseurl'], $path );

		if ( $try_url !== $path ) {
			return $try_url;
		}

		/* If WP_CONTENT_DIR and WP_CONTENT_URL are set we'll try them */
		if ( defined( 'WP_CONTENT_DIR' ) && defined( 'WP_CONTENT_URL' ) ) {
			$try_url = str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $path );

			if ( $try_url !== $path ) {
				return $try_url;
			}
		}

		/* Include our get_home_path functionality */
		if ( ! function_exists( 'get_home_path' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		/* If that didn't work let's try the home path instead */
		$try_url = str_replace( get_home_path(), home_url(), $path );

		if ( $try_url !== $path ) {
			return $try_url;
		}

		/* If that didn't work let's try the ABSPATH instead */
		$try_url = str_replace( ABSPATH, site_url(), $path );

		if ( $try_url !== $path ) {
			return $try_url;
		}

		/* If we are here we couldn't locate the file */

		return false;
	}


	/**
	 * Remove any characters that are invalid in filenames (mostly on Windows systems)
	 *
	 * @param string $name The string / name to process
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function strip_invalid_characters( $name ) {
		$characters = [ '/', '\\', '"', '*', '?', '|', ':', '<', '>' ];

		return str_replace( $characters, '_', $name );
	}

	/**
	 * Backwards compatibility that allows multiple IDs to be passed to the renderer
	 *
	 * @param integer $entry_id The fallback ID if none present
	 * @param array   $settings The current PDF settings
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function get_legacy_ids( $entry_id, $settings ) {

		$leads    = rgget( 'lid' );
		$override = ( $settings['public_access'] ?? '' ) === 'Yes';

		if ( $leads && ( $override === true || $this->gform->has_capability( 'gravityforms_view_entries' ) ) ) {
			$ids = array_filter( array_map( 'intval', explode( ',', $leads ) ) );

			if ( count( $ids ) > 0 ) {
				return $ids;
			}
		}

		/* if not processing legacy endpoint, or if invalid IDs were passed we'll return the original entry ID */

		return [ $entry_id ];
	}

	/**
	 * Add support for the third-party plugin GF Multi Currency
	 * https://github.com/ilanco/gravity-forms-multi-currency
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function maybe_add_multicurrency_support() {
		if ( class_exists( 'GFMultiCurrency' ) && method_exists( 'GFMultiCurrency', 'admin_pre_render' ) ) {
			$currency = GFMultiCurrency::init();
			add_filter( 'gform_form_post_get_meta', [ $currency, 'admin_pre_render' ] );
		}
	}

	/**
	 * Remove an extension from the end of a string
	 *
	 * @param string $text
	 * @param string $type The extension to remove from the end of the string
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function remove_extension_from_string( $text, $type = '.pdf' ) {
		$type_length = mb_strlen( $type );

		if ( mb_strtolower( mb_substr( $text, -$type_length ) ) === mb_strtolower( $type ) ) {
			$text = mb_substr( $text, 0, -$type_length );
		}

		return $text;
	}

	/**
	 *  Convert our v3 boolean values into 'Yes' or 'No' responses
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 *
	 * @since  4.0
	 */
	public function update_deprecated_config( $value ) {

		if ( is_bool( $value ) ) {
			$value = ( $value ) ? 'Yes' : 'No';
		}

		return $value;
	}

	/**
	 * Determine if the logic should show or hide the item
	 *
	 * @param array $logic
	 * @param array $entry The Gravity Forms entry object
	 *
	 * @return boolean Will always return true if item should be shown, or false if should be hidden
	 *
	 * @since 4.0
	 */
	public function evaluate_conditional_logic( $logic, $entry ) {

		/* exit early if type not found */
		if ( ! isset( $logic['actionType'] ) ) {
			return true;
		}

		$form = $this->gform->get_form( $entry['form_id'] );

		/* Do the evaluation */
		$evaluation = GFCommon::evaluate_conditional_logic( $logic, $form, $entry );

		/* If the logic is to hide the item we'll invert the evaluation */
		if ( $logic['actionType'] !== 'show' ) {
			return ! $evaluation;
		}

		return $evaluation;
	}

	/**
	 * Takes a Gravity Form ID and returns the list of fields which can be accessed using their ID
	 *
	 * @param integer $form_id The Gravity Form ID
	 *
	 * @return array The field array ordered by the field ID
	 *
	 * @since 4.0
	 */
	public function get_fields_sorted_by_id( $form_id ) {
		$form   = $this->gform->get_form( $form_id );
		$fields = [];

		if ( isset( $form['fields'] ) && is_array( $form['fields'] ) ) {
			foreach ( $form['fields'] as $field ) {
				$fields[ $field->id ] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Converts the 4.x settings array into a compatible 3.x settings array
	 *
	 * @param array $settings The 4.x settings to be converted
	 * @param array $form     (since 4.0.6) The Gravity Forms array
	 * @param array $entry    (since 4.0.6) The Gravity Forms entry
	 *
	 * @return array           The 3.x compatible settings
	 *
	 * @since 4.0
	 */
	public function backwards_compat_conversion( $settings, $form, $entry ) {

		$compat                   = [];
		$compat['premium']        = ( $settings['advanced_template'] ?? '' ) === 'Yes';
		$compat['rtl']            = ( $settings['rtl'] ?? '' ) === 'Yes';
		$compat['dpi']            = (int) ( $settings['image_dpi'] ?? 96 );
		$compat['security']       = ( $settings['security'] ?? '' ) === 'Yes';
		$compat['pdf_password']   = $this->gform->process_tags( $settings['password'] ?? '', $form, $entry );
		$compat['pdf_privileges'] = $settings['privileges'] ?? '';
		$compat['pdfa1b']         = ( $settings['format'] ?? '' ) === 'PDFA1B';
		$compat['pdfx1a']         = ( $settings['format'] ?? '' ) === 'PDFX1A';

		return $compat;
	}

	/**
	 * Converts the 4.x output to into a compatible 3.x type
	 *
	 * @param string $type
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function backwards_compat_output( $type = '' ) {
		switch ( strtolower( $type ) ) {
			case 'display':
				return 'view';

			case 'download':
				return 'download';

			default:
				return 'save';
		}
	}

	/**
	 * Check if the Gravity Forms GFEntryDetail class exists, otherwise load it
	 *
	 * @since 4.0
	 */
	public function maybe_load_gf_entry_detail_class() {
		/* Ensure Gravity Forms GFEntryDetail class is loaded */
		if ( ! class_exists( 'GFEntryDetail' ) ) {
			$entry_details_file = GFCommon::get_base_path() . '/entry_detail.php';

			if ( is_file( $entry_details_file ) ) {
				require_once $entry_details_file;
			}
		}
	}

	/**
	 * A recursive function that will search a multidimensional array for the value
	 *
	 * @param mixed $needle   The value to search for
	 * @param array $haystack The multidimensional array to search in
	 * @param bool  $strict   Pass `true` to match for the value and type, false for just the type.
	 *
	 * @return bool True when found, false otherwise
	 */
	public function in_array( $needle, $haystack, $strict = true ) {
		foreach ( $haystack as $item ) {
			/* phpcs:ignore */
			if ( ( $strict ? $item === $needle : $item == $needle ) ||
				 ( is_array( $item ) && $this->in_array( $needle, $item, $strict ) )
			) {
				return true;
			}
			/* phpcs:enable */
		}

		return false;
	}

	/**
	 * Ensure an extension is added to the end of the name
	 *
	 * @param string $name      The PHP template
	 *
	 * @param string $extension The extension that should be added to the filename
	 *
	 * @return string
	 *
	 * @since  4.1
	 */
	public function get_file_with_extension( $name, $extension = '.php' ) {
		if ( substr( $name, -strlen( $extension ) ) !== $extension ) {
			$name = $name . $extension;
		}

		return $name;
	}

	/**
	 * Check the Nonce and any user capabilities required before all AJAX requests
	 * If once of these fails the appropriate response code will be sent
	 *
	 * @param string      $endpoint_desc The name of the endpoint currently being processed (for the log)
	 * @param string|bool $capability    The capability name the logged in user is required to run this endpoint, or false if no authentication needed
	 * @param string      $nonce_name    The name of the Nonce our $_POST['nonce'] should be checked against
	 *
	 * @since 4.1
	 */
	public function handle_ajax_authentication( $endpoint_desc, $capability = 'gravityforms_edit_settings', $nonce_name = 'gfpdf_ajax_nonce' ) {

		$this->log->notice(
			'Running AJAX Endpoint',
			[
				'type' => $endpoint_desc,
				/* phpcs:ignore WordPress.Security.NonceVerification.Missing */
				'post' => $_POST,
			]
		);

		/* Validate Endpoint */
		/* phpcs:ignore WordPress.Security.NonceVerification.Missing */
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', $nonce_name ) ) {

			$this->log->warning( 'Nonce Verification Failed' );

			/* Unauthorized response */
			wp_die( '401', 401 );
		}

		/* prevent unauthorized access */
		if ( $capability !== false && ! $this->gform->has_capability( $capability ) ) {

			$this->log->critical(
				'Lack of User Capabilities',
				[
					'user'              => wp_get_current_user(),
					'user_meta'         => get_user_meta( get_current_user_id() ),
					'capability_needed' => $capability,
				]
			);

			/* Unauthorized response */
			wp_die( '401', 401 );
		}
	}
}
