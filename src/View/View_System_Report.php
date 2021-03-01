<?php

declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class View_System_Report extends Helper_Abstract_View {

	/**
	 * @var string
	 * @since 6.0
	 */
	protected $markup_yes = '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';

	/**
	 * @var string
	 * @since 6.0
	 */
	protected $markup_no = '<mark class="error"><span class="dashicons dashicons-no"></span></mark>';

	/**
	 * @var string
	 * @since 6.0
	 */
	protected $markup_warning = '<mark style="color: #F15A2B"><span class="dashicons dashicons-warning"></span></mark>';

	/**
	 * Set the view's name
	 *
	 * @var string
	 *
	 * @since 6.0
	 */
	protected $view_type = 'SystemReport';

	/**
	 * @since 6.0
	 */
	public function maybe_get_active_icon( bool $results ): string {
		if ( ! $results ) {
			return '';
		}

		return $this->get_icon( $results );
	}

	/**
	 * @since 6.0
	 */
	public function get_icon( bool $results ): string {
		return $results ? $this->markup_yes : $this->markup_no;
	}

	/**
	 * @param int|float $memory
	 *
	 * @return string
	 *
	 * @since 6.0
	 */
	public function memory_limit_markup( $memory ): string {
		if ( $memory === -1 ) {
			return esc_html__( 'Unlimited', 'gravity-forms-pdf-extended' ) . ' ' . $this->markup_yes;
		}

		$output  = $memory . 'MB ';
		$output .= ( $memory >= 128 ) ? $this->markup_yes : $this->markup_warning;
		if ( $memory < 128 ) {
			$output .= '<br />';
			$output .= sprintf( esc_html__( 'We strongly recommend you have at least 128MB of available WP Memory (RAM) assigned to your website. %1$sFind out how to increase this limit%2$s.', 'gravity-forms-pdf-extended' ), '<br /><a href="https://gravitypdf.com/documentation/v5/user-increasing-memory-limit/">', '</a>' );
		}

		return $output;
	}

	/**
	 * @since 6.0
	 */
	public function get_allow_url_fopen( bool $allow_url_fopen ): string {
		$output = $allow_url_fopen ? $this->markup_yes : $this->markup_warning;

		if ( ! $allow_url_fopen ) {
			$output .= ' ' . sprintf( esc_html__( 'We detected the PHP runtime configuration setting %1$sallow_url_fopen%2$s is disabled.', 'gravity-forms-pdf-extended' ), '<a href="https://www.php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen"><code>', '</code></a>' );
			$output .= ' ' . esc_html__( 'You may notice image display issues in your PDFs. Contact your web hosting provider for assistance enabling this feature.', 'gravity-forms-pdf-extended' );
		}

		return $output;
	}

	/**
	 * @since 6.0
	 */
	public function get_temp_folder_protected( bool $is_protected ): string {
		$output = $this->get_icon( $is_protected );

		if ( ! $is_protected ) {
			$output .= ' ' . sprintf( esc_html__( "We've detected the PDFs saved in Gravity PDF's %1\$stmp%2\$s directory can be publicly accessed.", 'gravity-forms-pdf-extended' ), '<code>', '</code>' );
			$output .= ' ' . sprintf( esc_html__( 'We recommend you use our %1$sgfpdf_tmp_location%2$s filter to %3$smove the folder outside your public website directory%4$s.', 'gravity-forms-pdf-extended' ), '<code>', '</code>', '<a href="https://gravitypdf.com/documentation/v5/gfpdf_tmp_location/">', '</a>' );
		}

		return $output;
	}
}
