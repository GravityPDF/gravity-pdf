<?php

declare( strict_types=1 );

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Model\Model_System_Report;
use GFPDF\Statics\Deprecation;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
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
	 * @deprecated 6.19.0 The System Report leaves its icons to Gravity Forms
	 */
	public function maybe_get_active_icon( bool $results ): string {
		_deprecated_function( __METHOD__, '6.19.0' );

		if ( ! $results ) {
			return '';
		}

		return $this->markup_yes;
	}

	/**
	 * @since 6.0
	 * @deprecated 6.19.0 The System Report leaves its icons to Gravity Forms
	 */
	public function get_icon( bool $results ): string {
		_deprecated_function( __METHOD__, '6.19.0' );

		return $results ? $this->markup_yes : $this->markup_no;
	}

	/**
	 * @param int|float $memory
	 *
	 * @return string
	 *
	 * @since 6.0
	 * @deprecated 6.19.0 The System Report builds its rows with get_check()
	 */
	public function memory_limit_markup( $memory ): string {
		_deprecated_function( __METHOD__, '6.19.0' );

		if ( $memory === -1 ) {
			return esc_html__( 'Unlimited', 'gravity-pdf' ) . ' ' . $this->markup_yes;
		}

		$output  = $memory . 'MB ';
		$output .= ( $memory >= 128 ) ? $this->markup_yes : $this->markup_warning;
		if ( $memory < 128 ) {
			$output .= '<br />';
			/* translators: 1: Opening <a> tag, 2: Closing </a> tag */
			$output .= sprintf( esc_html__( 'We strongly recommend you have at least 128MB of available WP Memory (RAM) assigned to your website. %1$sFind out how to increase this limit%2$s.', 'gravity-pdf' ), '<br /><a href="https://docs.gravitypdf.com/users/increasing-memory-limit">', '</a>' );
		}

		return $output;
	}

	/**
	 * @since 6.0
	 * @deprecated 6.19.0 The System Report builds its rows with get_check()
	 */
	public function get_allow_url_fopen( bool $allow_url_fopen ): string {
		_deprecated_function( __METHOD__, '6.19.0' );

		$output = $allow_url_fopen ? $this->markup_yes : $this->markup_warning;

		if ( ! $allow_url_fopen ) {
			/* translators: 1: Opening <a><code> tags, 2: Closing </code></a> tags */
			$output .= ' ' . sprintf( esc_html__( 'We detected the PHP runtime configuration setting %1$sallow_url_fopen%2$s is disabled.', 'gravity-pdf' ), '<a href="https://www.php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen"><code>', '</code></a>' );
			$output .= ' ' . esc_html__( 'You may notice image display issues in your PDFs. Contact your web hosting provider for assistance enabling this feature.', 'gravity-pdf' );
		}

		return $output;
	}

	/**
	 * @since 6.0
	 * @deprecated 6.19.0 The System Report builds its rows with get_check()
	 */
	public function get_temp_folder_protected( bool $is_protected ): string {
		_deprecated_function( __METHOD__, '6.19.0' );

		$output = ( $is_protected ? $this->markup_yes : $this->markup_no );

		if ( ! $is_protected ) {
			$output .= ' ' . sprintf( esc_html__( "Gravity PDF's temporary directory is publicly accessible.", 'gravity-pdf' ) );
			/* translators: 1: Opening <a> tag, 2: Closing </a> tag */
			$output .= ' ' . sprintf( esc_html__( 'It is recommended to %1$smove the folder outside the public server directory%2$s.', 'gravity-pdf' ), '<a href="https://docs.gravitypdf.com/developers/filters/gfpdf_tmp_location/">', '</a>' );
		}

		return $output;
	}

	/**
	 * @param string[] $folders The folders that can't be written to, relative to the site
	 *
	 * @since 6.18.0
	 * @deprecated 6.19.0 The System Report builds its rows with get_check()
	 */
	public function get_folder_permissions( array $folders ): string {
		_deprecated_function( __METHOD__, '6.19.0' );

		$writable = $folders === [];
		$output   = ( $writable ? esc_html__( 'Writable', 'gravity-pdf' ) : esc_html__( 'Not writable', 'gravity-pdf' ) ) . ' ' . ( $writable ? $this->markup_yes : $this->markup_no );

		foreach ( $folders as $folder ) {
			$output .= '<br /><code>' . esc_html( $folder ) . '</code>';
		}

		return $output;
	}

	/**
	 * Prepare message for outdated template file(s)
	 *
	 * @param string $path The path to the outdated PDF template file
	 * @param string $template_version The current version of the outdated PDF template file
	 * @param string $core_version The latest Core template version
	 *
	 * @since 6.0
	 * @deprecated 6.19.0 The System Report builds its rows with get_check()
	 */
	public function get_template_check_message( string $path, string $template_version, string $core_version ): array {
		_deprecated_function( __METHOD__, '6.19.0' );

		/* translators: 1: Template file path, 2: Current template version (wrapped in styled <span>), 3: Latest core version */
		$message = sprintf( esc_html__( '%1$s version %2$s is out of date. The core version is %3$s', 'gravity-pdf' ), $path, '<span style="color: #ff0000;font-weight:bold">' . $template_version . '</span>', $core_version );

		$export_message = sprintf( '%1$s version %2$s is out of date. The core version is %3$s', $path, $template_version, $core_version );

		return [
			'value'        => $message . $this->markup_no . '<hr>',
			'value_export' => $export_message . "   &#10008;\n",
		];
	}

	/**
	 * Prepare message on how to update outdated template file(s)
	 *
	 * @since 6.0
	 * @deprecated 6.19.0 The System Report builds its rows with get_check()
	 */
	public function get_template_upgrade_message(): string {
		_deprecated_function( __METHOD__, '6.19.0' );

		$learn_more_url = 'https://docs.gravitypdf.com/developers/template-hierarchy';

		return $this->markup_warning . ' <a href="' . esc_url( $learn_more_url ) . '">' . esc_html__( 'Learn how to update', 'gravity-pdf' ) . '</a>';
	}

	/**
	 * Add a check's result to its row. Gravity Forms draws the ✔ or ✘, and the fix after a failing value.
	 *
	 * @param array  $row     The row's label and value, and their `_export` copies
	 * @param string $summary What the check is listed as in the passed row
	 * @param string $fix     Escaped advice for a failing check, empty when it passes
	 *
	 * @since 6.19.0
	 */
	public function get_check( array $row, string $summary, string $fix = '' ): array {
		$row['is_valid'] = $fix === '';
		$row['summary']  = esc_html( $summary );

		if ( $fix !== '' ) {
			$row['validation_message'] = $fix;

			/* The copied report is for support, so it keeps the facts and leaves out the fix */
			$row['validation_message_export'] = '';
		}

		return $row;
	}

	/**
	 * A short fix for a failing check, with a link to the guide when there is one
	 *
	 * @since 6.19.0
	 */
	public function get_fix( string $message, string $url = '', string $link = '' ): string {
		$fix = esc_html( $message );

		if ( $url !== '' ) {
			$fix .= ' <a href="' . esc_url( $url ) . '">' . esc_html( $link ) . '</a>';
		}

		return $fix;
	}

	/**
	 * One code item per line
	 *
	 * @param string[] $items
	 *
	 * @since 6.19.0
	 */
	public function get_code_lines( array $items ): string {
		return implode(
			'<br>',
			array_map(
				static function ( $item ) {
					return '<code>' . esc_html( $item ) . '</code>';
				},
				$items
			)
		);
	}

	/**
	 * On or Off, as shown on screen
	 *
	 * @since 6.19.0
	 */
	public function get_on_off( bool $on ): string {
		return $on ? esc_html__( 'On', 'gravity-pdf' ) : esc_html__( 'Off', 'gravity-pdf' );
	}

	/**
	 * One row on screen listing the checks that passed, which copies as the first of them. The others copy on lines of
	 * their own.
	 *
	 * @param array[] $passed The rows of the checks that passed
	 * @param int     $total  How many checks there are
	 *
	 * @since 6.19.0
	 */
	public function get_passed_rows( array $passed, int $total ): array {
		if ( $passed === [] ) {
			return [];
		}

		/* translators: %d: How many checks there are */
		$label = sprintf( __( 'All %d checks passed', 'gravity-pdf' ), $total );

		if ( count( $passed ) < $total ) {
			/* translators: 1: How many checks passed, 2: How many checks there are */
			$label = sprintf( __( '%1$d of %2$d checks passed', 'gravity-pdf' ), count( $passed ), $total );
		}

		$first = $passed[0];
		$rows  = [
			[
				'label'        => esc_html( $label ),
				'label_export' => $first['label_export'] ?? wp_strip_all_tags( $first['label'] ),
				'value'        => implode( ' · ', array_column( $passed, 'summary' ) ),
				'value_export' => $first['value_export'] ?? $first['value'],
				'is_valid'     => true,
			],
		];

		foreach ( array_slice( $passed, 1 ) as $row ) {
			$rows[] = [ 'export_only' => true ] + $row;
		}

		return $rows;
	}

	/**
	 * Describe one of the deprecated features detected on this site
	 *
	 * Shared by the Gravity Forms system report and the Site Health test, which present the same detections
	 * differently.
	 *
	 * @param string $key    The signal key from Deprecation::get_signals()
	 * @param array  $signal The detections recorded for that signal
	 *
	 * @return array The `label` this feature is known by, one `lines` entry per detection, plus the `notice` its
	 *               registration states and the `url` explaining it. A feature may also carry `html`, one line of
	 *               escaped markup per detection that the display surfaces show in place of `lines`. Every other
	 *               value is unescaped, as one of the three consumers is the Site Health Info tab, which escapes
	 *               what it's given.
	 * @since 6.17.0
	 */
	public function get_deprecated_feature( string $key, array $signal ): array {
		$feature      = Deprecation::get_feature( $key );
		$descriptions = $this->get_deprecated_feature_descriptions();

		if ( isset( $descriptions[ $key ] ) ) {
			$description = call_user_func( $descriptions[ $key ], $signal, $feature );
		} elseif ( Deprecation::is_hook_only( $feature ) ) {
			$description = $this->get_deprecated_filter_feature( $signal );
		} else {
			$description = $this->get_default_feature_description( $signal );
		}

		/* Whatever the description leaves out, the feature's own registration answers */
		return $description + [
			'label'  => $feature['label'],
			'notice' => Deprecation::get_feature_notice( $feature ),
			'url'    => $feature['url'],
		];
	}

	/**
	 * The method that describes each feature's detections, keyed by the feature it describes
	 *
	 * A feature with no entry here is described by its listener counts if it's hook-only, otherwise generically.
	 *
	 * @return array<string, callable>
	 * @since 6.17.0
	 */
	protected function get_deprecated_feature_descriptions(): array {
		return [
			'legacy_templates'        => [ $this, 'get_legacy_templates_feature' ],
			'business_plus_templates' => [ $this, 'get_legacy_templates_feature' ],
			'legacy_endpoint'         => [ $this, 'get_legacy_endpoint_feature' ],
		];
	}

	/**
	 * Describe a feature's detections when it carries no copy of its own
	 *
	 * @param array $signal The detections recorded for the feature
	 *
	 * @since 6.17.0
	 */
	protected function get_default_feature_description( array $signal ): array {
		$lines = [];

		/* A detection is named by its key where it has one, as the hooks and the templates are, or is the value
		   itself. A keyed detection's value is the feature's own business, so it is left to describe it */
		foreach ( $signal as $key => $detection ) {
			$lines[] = is_string( $key ) ? $key : (string) $detection;
		}

		return [ 'lines' => $lines ];
	}

	/**
	 * Describe any installed legacy (v3) template file(s), and the forms each is configured on
	 *
	 * A template no form selects is named as such rather than left bare, so the reader isn't left deciding whether
	 * the forms were checked or simply not listed.
	 *
	 * @param array $templates Absolute template path mapped to the forms it is configured on
	 *
	 * @since 6.17.0
	 */
	protected function get_legacy_templates_feature( array $templates ): array {
		$lines = [];
		$html  = [];

		foreach ( $templates as $path => $form_ids ) {
			/* Templates live in one directory the report states of its own, so the file name is enough to find them */
			$name = basename( $path );

			if ( $form_ids === [] ) {
				/* translators: %1$s: The template file name */
				$format = __( '%1$s (not configured on a form)', 'gravity-pdf' );
			} else {
				/* translators: 1: The template file name, 2: Comma separated list of form IDs */
				$format = _n( '%1$s (form ID %2$s)', '%1$s (form IDs %2$s)', count( $form_ids ), 'gravity-pdf' );
			}

			/* One format, applied twice: the surfaces taking the plain text get the IDs, the ones taking markup
			   get them linked. The unused sentence names no forms and ignores the second argument */
			$lines[] = sprintf( $format, $name, implode( ', ', $form_ids ) );
			$html[]  = sprintf( esc_html( $format ), esc_html( $name ), implode( ', ', $this->get_form_settings_links( $form_ids ) ) );
		}

		return [
			'lines' => $lines,
			/* Every display surface links to the PDF settings of each form, which is where the template is changed */
			'html'  => $html,
		];
	}

	/**
	 * Describe the forms that still hand out a legacy `?gf_pdf=1` download URL
	 *
	 * @param array $form_ids The forms the URLs were found on
	 * @param array $feature  The feature's registration
	 *
	 * @since 6.17.0
	 */
	protected function get_legacy_endpoint_feature( array $form_ids, array $feature ): array {
		/* The URLs no longer resolve, so what the form still hands out is a broken link rather than a feature in use */
		/* translators: %s: Comma separated list of form IDs */
		$in_use = __( 'Broken link stored on form ID %s', 'gravity-pdf' );

		return [
			'lines' => [ sprintf( $in_use, implode( ', ', $form_ids ) ) ],
			/* Every display surface links to the PDF settings of each form, which is where the URLs are replaced */
			'html'  => [ sprintf( esc_html( $in_use ), implode( ', ', $this->get_form_settings_links( $form_ids ) ) ) ],
		];
	}

	/**
	 * Link each form to its Gravity PDF settings, which is where what was detected on it is changed
	 *
	 * @param array $form_ids The forms to link
	 *
	 * @return string[] One escaped link per form, in the order given
	 * @since 6.17.0
	 */
	protected function get_form_settings_links( array $form_ids ): array {
		return array_map(
			static function ( $form_id ) {
				$url = admin_url( sprintf( 'admin.php?page=gf_edit_forms&view=settings&subview=PDF&id=%d', $form_id ) );

				return '<a href="' . esc_url( $url ) . '">' . (int) $form_id . '</a>';
			},
			$form_ids
		);
	}

	/**
	 * Describe the deprecated hooks that still have a listener attached
	 *
	 * @param array $filters Hook name mapped to the number of listeners
	 *
	 * @since 6.17.0
	 */
	protected function get_deprecated_filter_feature( array $filters ): array {
		$lines = [];

		foreach ( $filters as $name => $count ) {
			$lines[] = sprintf(
				/* translators: 1: Filter name, 2: Number of callbacks attached to it */
				_n( '%1$s has %2$d listener', '%1$s has %2$d listeners', $count, 'gravity-pdf' ),
				$name,
				$count
			);
		}

		return [ 'lines' => $lines ];
	}

	/**
	 * Assemble the Gravity Forms system report row for a detected feature: one detection per line, and a short fix
	 *
	 * @param string $key    The signal key from Deprecation::get_signals()
	 * @param array  $signal The detections recorded for that signal
	 *
	 * @since 6.17.0
	 * @since 6.19.0 Names the feature's group, and says when it is removed rather than the whole notice
	 */
	public function get_deprecated_feature_report_item( string $key, array $signal ): array {
		$feature      = $this->get_deprecated_feature( $key, $signal );
		$registration = Deprecation::get_feature( $key );
		$group        = static::get_deprecated_groups()[ $registration['group'] ]['label'] ?? '';

		/* translators: 1: The feature's name, 2: Its group, e.g. "Unsupported" */
		$label = sprintf( __( '%1$s (%2$s)', 'gravity-pdf' ), $feature['label'], $group );

		$fix = $registration['group'] === Deprecation::GROUP_UNSUPPORTED
			/* translators: %s: The Gravity PDF version, e.g. "7.0" */
			? __( 'Removed in Gravity PDF %s, so it no longer runs.', 'gravity-pdf' )
			/* translators: %s: The Gravity PDF version, e.g. "7.0" */
			: __( 'Will be removed in Gravity PDF %s.', 'gravity-pdf' );

		$row = [
			'label'        => esc_html( $label ),
			'label_export' => $label,
		] + $this->get_deprecated_feature_values( $feature, '<br>' );

		return $this->get_check( $row, '', $this->get_fix( sprintf( $fix, $registration['removed_in'] ), $feature['url'], __( 'How to upgrade', 'gravity-pdf' ) ) );
	}

	/**
	 * Assemble the Site Health account of a detected feature
	 *
	 * Both Site Health screens have room for every detection, so they list them in full: the test takes the escaped
	 * values and the Info tab the `_export` ones.
	 *
	 * @param string $key    The signal key from Deprecation::get_signals()
	 * @param array  $signal The detections recorded for that signal
	 *
	 * @since 6.17.0
	 */
	public function get_deprecated_feature_detail( string $key, array $signal ): array {
		return $this->get_deprecated_feature_values( $this->get_deprecated_feature( $key, $signal ) );
	}

	/**
	 * Assemble the display and export values a surface shows for a detected feature
	 *
	 * @param array  $feature   The feature, from self::get_deprecated_feature()
	 * @param string $separator What the displayed detections are joined with
	 *
	 * @since 6.17.0
	 * @since 6.19.0 Takes the separator rather than the detections, which are always the feature's own
	 */
	protected function get_deprecated_feature_values( array $feature, string $separator = ', ' ): array {
		return [
			'label'                     => esc_html( $feature['label'] ),
			'label_export'              => $feature['label'],
			'value'                     => implode( $separator, $feature['html'] ?? array_map( 'esc_html', $feature['lines'] ) ),
			'value_export'              => implode( ', ', $feature['lines'] ),
			'validation_message'        => $this->get_removal_message( $feature['notice'], $feature['url'] ),
			'validation_message_export' => $feature['notice'] . ' ' . $feature['url'],
		];
	}

	/**
	 * The name each group is known by, what belonging to it means, and how the report's passed row says nothing was found
	 *
	 * Both Site Health screens show the description; the system report has no room for it and names the group in
	 * each feature's label. Deprecation::get_groups() decides which of them a surface sees.
	 *
	 * @return array<string, array<string, string>>
	 * @since 6.17.0
	 * @since 6.19.0 Added `none`
	 */
	public static function get_deprecated_groups(): array {
		$names = [
			Deprecation::GROUP_UNSUPPORTED => [
				'label'       => __( 'Unsupported', 'gravity-pdf' ),
				'description' => __( 'These features have been removed and any Gravity PDF document that relied on them will stop working.', 'gravity-pdf' ),
				'none'        => __( 'no unsupported features', 'gravity-pdf' ),
			],

			Deprecation::GROUP_DEPRECATED  => [
				'label'       => __( 'Deprecated', 'gravity-pdf' ),
				'description' => __( "Legacy functionality that will be removed in an upcoming release. It's important to fix each item to keep your PDFs working correctly.", 'gravity-pdf' ),
				'none'        => __( 'no deprecated features', 'gravity-pdf' ),
			],
		];

		return array_intersect_key( $names, array_flip( Deprecation::get_groups() ) );
	}

	/**
	 * Build the Site Health test result for the deprecated features detected on this site
	 *
	 * Unlike the one-time admin notice, this surface can't be dismissed: it clears itself once the site stops
	 * using the deprecated functionality, and returns if it starts again.
	 *
	 * @param array $signals The signals from Deprecation::get_signals()
	 *
	 * @since 6.17.0
	 */
	public function get_deprecated_features_test( array $signals ): array {
		/* One result covers every round of removals at once, so it names no version: each item below names its own */
		$result = [
			'label'       => esc_html__( 'Your PDFs are ready for the next Gravity PDF release', 'gravity-pdf' ),
			'status'      => 'good',
			'badge'       => $this->get_site_health_badge(),
			'description' => '<p>' . esc_html__( 'Nothing on this site uses Gravity PDF functionality that is scheduled for removal.', 'gravity-pdf' ) . '</p>',
			'actions'     => '',
			'test'        => 'gravity_pdf_deprecated_features',
		];

		if ( $signals === [] ) {
			return $result;
		}

		/* Something already removed is breaking PDFs now, rather than at some point in the future */
		if ( Deprecation::has_unsupported_feature( array_keys( $signals ) ) ) {
			$result['status'] = 'critical';
			$result['label']  = esc_html__( 'Your site uses Gravity PDF functionality that has been removed', 'gravity-pdf' );
		} else {
			$result['status'] = 'recommended';
			$result['label']  = esc_html__( 'Your site uses Gravity PDF functionality that is scheduled for removal', 'gravity-pdf' );
		}

		/* The group headings and their descriptions introduce the list, so nothing else does */
		$result['description'] = '';

		$groups = static::get_deprecated_groups();
		foreach ( Deprecation::group_signals( $signals ) as $group => $group_signals ) {
			$result['description'] .= '<h4>' . esc_html( $groups[ $group ]['label'] ) . '</h4>';
			$result['description'] .= '<p>' . esc_html( $groups[ $group ]['description'] ) . '</p>';

			foreach ( $group_signals as $key => $signal ) {
				$message = $this->get_deprecated_feature_detail( $key, $signal );

				$result['description'] .= '<p><strong>' . $message['label'] . '</strong><br>' .
					$message['value'] . '. ' . $message['validation_message'] . '</p>';
			}
		}

		$result['actions'] = $this->get_report_link_action();

		return $result;
	}

	/**
	 * Build the Site Health result for the PDF cache cleanup
	 *
	 * @param bool $stalled Whether the cache holds PDFs but WP-Cron isn't removing the expired ones
	 *
	 * @since 7.0
	 */
	public function get_cache_cleanup_test( bool $stalled ): array {
		$result = [
			'label'       => esc_html__( 'Expired PDFs are being removed from the Gravity PDF cache', 'gravity-pdf' ),
			'status'      => 'good',
			'badge'       => $this->get_site_health_badge(),
			'description' => '<p>' . esc_html__( 'Gravity PDF caches generated PDFs, and removes them with WP-Cron once the cache duration runs out.', 'gravity-pdf' ) . '</p>',
			'actions'     => '',
			'test'        => 'gravity_pdf_cache_cleanup',
		];

		if ( ! $stalled ) {
			return $result;
		}

		$result['status']       = 'recommended';
		$result['label']        = esc_html__( 'Expired PDFs are not being removed from the Gravity PDF cache', 'gravity-pdf' );
		$result['description'] .= '<p>' . esc_html__( 'WP-Cron does not appear to be running, so cached PDFs, and the entry data they hold, stay on disk after they expire. Check with your web host that WP-Cron, or a system cron that replaces it, runs on this site.', 'gravity-pdf' ) . '</p>';
		$result['actions']      = $this->get_report_link_action();

		return $result;
	}

	/**
	 * Build the Site Health result for the core fonts the site's PDFs need
	 *
	 * @param array $health See Model_System_Report::get_core_font_health()
	 *
	 * @since 6.19.0
	 */
	public function get_core_fonts_test( array $health ): array {
		$intro = esc_html__( 'Gravity PDF automatically downloads fonts needed to correctly render PDFs.', 'gravity-pdf' );

		$result = [
			'label'       => esc_html__( 'Gravity PDF has the core fonts your PDFs need', 'gravity-pdf' ),
			'status'      => 'good',
			'badge'       => $this->get_site_health_badge(),
			'description' => '<p>' . $intro . '</p>',
			'actions'     => '',
			'test'        => 'gravity_pdf_core_fonts',
		];

		if ( $health['failed'] === [] && $health['blocker'] === null ) {
			return $result;
		}

		$result['status']      = 'recommended';
		$result['label']       = esc_html__( 'Some core fonts your PDFs need are missing', 'gravity-pdf' );
		$result['description'] = '<p>' . $intro . ' ' . esc_html( View_Actions::get_missing_core_fonts_warning() ) . '</p>';
		$result['actions']     = $this->get_report_link_action( $health['failed'] !== [] ? Model_System_Report::FONTS_FAILED_ANCHOR : Model_System_Report::FONT_DOWNLOADS_ANCHOR ) .
			'<p><a href="' . View_Actions::CORE_FONTS_DOCS_URL . '">' . esc_html__( 'Install the fonts by hand', 'gravity-pdf' ) . '</a></p>';

		if ( $health['failed'] !== [] ) {
			/* A give-up needs someone to act and can cost PDFs their text; turning downloads off was a choice */
			$result['status']       = 'critical';
			$result['label']        = esc_html__( 'Gravity PDF could not download required fonts', 'gravity-pdf' );
			$result['description'] .= '<p>' . esc_html__( 'Fonts that failed to download:', 'gravity-pdf' ) . '</p>' . View_Actions::get_code_list( array_keys( $health['failed'] ) );
		}

		if ( $health['blocker'] !== null ) {
			$others = array_diff( $health['missing'], array_keys( $health['failed'] ) );
			$list   = '';

			/* translators: %s: The GPDF_AUTO_INSTALL_FONTS constant or the gfpdf_auto_install_fonts filter */
			$off = sprintf( esc_html__( 'Core font downloads are turned off by %s.', 'gravity-pdf' ), preg_replace( '/^\S+/', '<code>$0</code>', esc_html( $health['blocker'] ) ) );
			if ( $others !== [] ) {
				$off .= ' ' . esc_html__( 'These fonts are missing:', 'gravity-pdf' );
				$list = View_Actions::get_code_list( $others );
			}

			$result['description'] .= '<p>' . $off . '</p>' . $list;

			$result['actions'] .= '<p><a href="https://docs.gravitypdf.com/developers/filters/gfpdf_auto_install_fonts/">' . esc_html__( 'Learn how to turn font downloads back on', 'gravity-pdf' ) . '</a></p>';
		}

		return $result;
	}

	/**
	 * @since 7.0
	 */
	protected function get_site_health_badge(): array {
		return [
			'label' => esc_html__( 'Gravity PDF', 'gravity-pdf' ),
			'color' => 'blue',
		];
	}

	/**
	 * A Site Health action linking to Gravity PDF's section of the system report
	 *
	 * @since 7.0
	 */
	protected function get_report_link_action( string $anchor = Model_System_Report::SECTION_ANCHOR ): string {
		return '<p><a href="' . esc_url( Model_System_Report::get_report_url( $anchor ) ) . '">' . esc_html__( 'View the Gravity Forms system report', 'gravity-pdf' ) . '</a></p>';
	}

	/**
	 * Prepare the advice Gravity Forms shows after a deprecated feature's detections
	 *
	 * @param string $notice What happens to the feature
	 * @param string $url    Where to read more about that action
	 *
	 * @since 6.17.0
	 */
	protected function get_removal_message( string $notice, string $url ): string {
		return esc_html( $notice ) . ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Learn how to upgrade', 'gravity-pdf' ) . '</a>';
	}

	/**
	 * Build the Site Health Info tab sections for the features detected on this site
	 *
	 * A section is the only thing Site Health gives a heading and an intro paragraph of its own, so each group
	 * takes one and heads it with its own name. This is the tab users copy into a support ticket, so a section is
	 * present whether or not its group has anything to report: an empty one says so, rather than leaving the
	 * reader to guess whether the check ran. The fields report what the system report exports, which keeps the two
	 * plain-text surfaces reading the same by construction.
	 *
	 * @param array $signals The signals from Deprecation::get_signals()
	 *
	 * @return array One Site Health Info section per group, keyed by the ID it is registered under
	 * @since 6.17.0
	 */
	public function get_deprecated_debug_information( array $signals ): array {
		$grouped  = Deprecation::group_signals( $signals );
		$sections = [];

		foreach ( static::get_deprecated_groups() as $group => $names ) {
			$detections = $grouped[ $group ] ?? [];
			$fields     = [];

			foreach ( $detections as $key => $signal ) {
				$message = $this->get_deprecated_feature_detail( $key, $signal );

				$fields[ $key ] = [
					'label' => $message['label_export'],
					'value' => $message['value_export'] . '. ' . $message['validation_message_export'],
				];
			}

			if ( $fields === [] ) {
				$fields[ $group ] = [
					/* translators: %s: The group name, as get_deprecated_groups() gives it */
					'label' => sprintf( __( '%s functionality', 'gravity-pdf' ), $names['label'] ),
					'value' => __( 'None detected', 'gravity-pdf' ),
				];
			}

			/* Site Health prints this above the table, unescaped, so the group heads its own panel here */
			$description = sprintf(
				'<h4>%s</h4>',
				/* translators: %s: The group name, as get_deprecated_groups() gives it */
				esc_html( sprintf( __( '%s Features', 'gravity-pdf' ), $names['label'] ) )
			);

			/* The intro tells the reader what to do about a list, so a group with nothing detected goes without it */
			if ( $detections !== [] ) {
				$description .= '<p>' . esc_html( $names['description'] ) . '</p>';
			}

			$sections[ 'gravity-pdf-' . $group ] = [
				'label'       => __( 'Gravity PDF', 'gravity-pdf' ),
				'description' => $description,
				'fields'      => $fields,
			];
		}

		return $sections;
	}
}
