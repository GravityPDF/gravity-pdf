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
			return esc_html__( 'Unlimited', 'gravity-pdf' ) . ' ' . $this->markup_yes;
		}

		$output  = $memory . 'MB ';
		$output .= ( $memory >= 128 ) ? $this->markup_yes : $this->markup_warning;
		if ( $memory < 128 ) {
			$output .= '<br />';
			$output .= sprintf( esc_html__( 'We strongly recommend you have at least 128MB of available WP Memory (RAM) assigned to your website. %1$sFind out how to increase this limit%2$s.', 'gravity-pdf' ), '<br /><a href="https://docs.gravitypdf.com/users/increasing-memory-limit">', '</a>' );
		}

		return $output;
	}

	/**
	 * @since 6.0
	 */
	public function get_allow_url_fopen( bool $allow_url_fopen ): string {
		$output = $allow_url_fopen ? $this->markup_yes : $this->markup_warning;

		if ( ! $allow_url_fopen ) {
			$output .= ' ' . sprintf( esc_html__( 'We detected the PHP runtime configuration setting %1$sallow_url_fopen%2$s is disabled.', 'gravity-pdf' ), '<a href="https://www.php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen"><code>', '</code></a>' );
			$output .= ' ' . esc_html__( 'You may notice image display issues in your PDFs. Contact your web hosting provider for assistance enabling this feature.', 'gravity-pdf' );
		}

		return $output;
	}

	/**
	 * @since 6.0
	 */
	public function get_temp_folder_protected( bool $is_protected ): string {
		$output = $this->get_icon( $is_protected );

		if ( ! $is_protected ) {
			$output .= ' ' . sprintf( esc_html__( "We've detected the PDFs saved in Gravity PDF's %1\$stmp%2\$s directory can be publicly accessed.", 'gravity-pdf' ), '<code>', '</code>' );
			$output .= ' ' . sprintf( esc_html__( 'We recommend you use our %1$sgfpdf_tmp_location%2$s filter to %3$smove the folder outside your public website directory%4$s.', 'gravity-pdf' ), '<code>', '</code>', '<a href="https://docs.gravitypdf.com/developers/filters/gfpdf_tmp_location/">', '</a>' );
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
	 */
	public function get_template_check_message( string $path, string $template_version, string $core_version ): array {
		$message = sprintf( esc_html__( '%1$s version %2$s is out of date. The core version is %3$s', 'gravity-pdf' ), $path, '<span style="color: #ff0000;font-weight:bold">' . $template_version . '</span>', $core_version );

		$export_message = sprintf( '%1$s version %2$s is out of date. The core version is %3$s', $path, $template_version, $core_version );

		return [
			'value'        => $message . $this->get_icon( false ) . '<hr>',
			'value_export' => $export_message . "   &#10008;\n",
		];
	}

	/**
	 * Prepare message on how to update outdated template file(s)
	 *
	 * @since 6.0
	 */
	public function get_template_upgrade_message(): string {
		$learn_more_url = 'https://docs.gravitypdf.com/developers/template-hierarchy';

		return $this->markup_warning . ' <a href="' . esc_url( $learn_more_url ) . '">' . esc_html__( 'Learn how to update', 'gravity-pdf' ) . '</a>';
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
	 *               registration states and the `url` explaining it. A feature may also carry `html`, escaped
	 *               markup the display surfaces show in place of `lines`. Every other value is unescaped, as one
	 *               of the three consumers is the Site Health Info tab, which escapes what it's given.
	 * @since 6.17.0
	 */
	public function get_deprecated_feature( string $key, array $signal ): array {
		$feature      = Deprecation::get_feature( $key );
		$descriptions = $this->get_deprecated_feature_descriptions();

		$description = isset( $descriptions[ $key ] )
			? call_user_func( $descriptions[ $key ], $signal, $feature )
			: $this->get_default_feature_description( $signal );

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
	 * A feature with no entry here is described generically, so a newly registered one reports something usable
	 * before it has copy of its own.
	 *
	 * @return array<string, callable>
	 * @since 6.17.0
	 */
	protected function get_deprecated_feature_descriptions(): array {
		return [
			'legacy_templates'        => [ $this, 'get_legacy_templates_feature' ],
			'business_plus_templates' => [ $this, 'get_legacy_templates_feature' ],
			'legacy_endpoint'         => [ $this, 'get_legacy_endpoint_feature' ],
			'deprecated_filters'      => [ $this, 'get_deprecated_filter_feature' ],
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
			'html'  => implode( ', ', $html ),
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
		/* translators: %s: Comma separated list of form IDs */
		$in_use = __( 'In use on form ID %s', 'gravity-pdf' );

		return [
			'lines' => [ sprintf( $in_use, implode( ', ', $form_ids ) ) ],
			/* Every display surface links to the PDF settings of each form, which is where the URLs are replaced */
			'html'  => sprintf( esc_html( $in_use ), implode( ', ', $this->get_form_settings_links( $form_ids ) ) ),
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
	 * Describe the deprecated v3 filters that have a third-party listener attached
	 *
	 * @param array $filters Hook name mapped to the number of listeners
	 * @param array $feature The feature's registration
	 *
	 * @since 6.17.0
	 */
	protected function get_deprecated_filter_feature( array $filters, array $feature ): array {
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
	 * Assemble the Gravity Forms system report row for a detected feature
	 *
	 * The report keeps a row to a single line, so a feature offering a terser set of detections is listed by that
	 * instead of the whole story the Site Health screens tell.
	 *
	 * @param string $key    The signal key from Deprecation::get_signals()
	 * @param array  $signal The detections recorded for that signal
	 *
	 * @since 6.17.0
	 */
	public function get_deprecated_feature_report_item( string $key, array $signal ): array {
		$feature = $this->get_deprecated_feature( $key, $signal );

		/* Every row is handed over as a failure, so Gravity Forms draws the cross and the red message itself */
		return [ 'is_valid' => false ] + $this->get_deprecated_feature_values( $feature, $feature['lines'] );
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
		$feature = $this->get_deprecated_feature( $key, $signal );

		return $this->get_deprecated_feature_values( $feature, $feature['lines'] );
	}

	/**
	 * Assemble the display and export values a surface shows for a detected feature
	 *
	 * @param array $feature The feature, from self::get_deprecated_feature()
	 * @param array $lines   The detections the surface has room to list
	 *
	 * @since 6.17.0
	 */
	protected function get_deprecated_feature_values( array $feature, array $lines ): array {
		$value = implode( ', ', $lines );

		return [
			'label'                     => esc_html( $feature['label'] ),
			'label_export'              => $feature['label'],
			'value'                     => $feature['html'] ?? esc_html( $value ),
			'value_export'              => $value,
			'validation_message'        => $this->get_removal_message( $feature['notice'], $feature['url'] ),
			'validation_message_export' => $feature['notice'] . ' ' . $feature['url'],
		];
	}

	/**
	 * The name each group is known by, and what belonging to it means
	 *
	 * Both Site Health screens show the description; the system report has no room for it and titles its sections
	 * with the name alone. Deprecation::get_groups() decides which of them a surface sees.
	 *
	 * @return array<string, array<string, string>>
	 * @since 6.17.0
	 */
	public static function get_deprecated_groups(): array {
		$names = [
			Deprecation::GROUP_UNSUPPORTED => [
				'label'       => __( 'Unsupported', 'gravity-pdf' ),
				'description' => __( 'These features have been removed and any Gravity PDF document that relied on them will stop working.', 'gravity-pdf' ),
			],

			Deprecation::GROUP_DEPRECATED  => [
				'label'       => __( 'Deprecated', 'gravity-pdf' ),
				'description' => __( "Legacy functionality that will be removed in an upcoming release. It's important to fix each item to keep your PDFs working correctly.", 'gravity-pdf' ),
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
			'badge'       => [
				'label' => esc_html__( 'Gravity PDF', 'gravity-pdf' ),
				'color' => 'blue',
			],
			'description' => '<p>' . esc_html__( 'Nothing on this site relies on Gravity PDF functionality that is scheduled for removal, so there is nothing to do.', 'gravity-pdf' ) . '</p>',
			'actions'     => '',
			'test'        => 'gravity_pdf_deprecated_features',
		];

		if ( $signals === [] ) {
			return $result;
		}

		$result['status'] = 'recommended';
		$result['label']  = esc_html__( 'Your site uses Gravity PDF functionality that is scheduled for removal', 'gravity-pdf' );

		$result['description'] = '<p>' . esc_html__( 'Update each item below before the release that removes it, so the PDFs that rely on it will continue working. Each one links to instructions to upgrade.', 'gravity-pdf' ) . '</p>';

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

		$result['actions'] = '<p><a href="' . esc_url( Model_System_Report::get_report_url() ) . '">' . esc_html__( 'View the Gravity Forms system report', 'gravity-pdf' ) . '</a></p>';

		return $result;
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
	 * This is the tab users copy into a support ticket, so a section is present whether or not its group has
	 * anything to report: an empty one says so, rather than leaving the reader to guess whether the check ran.
	 * They report what the system report exports, which keeps the two plain-text surfaces reading the same by
	 * construction.
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
			$fields = [];

			foreach ( $grouped[ $group ] ?? [] as $key => $signal ) {
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

			$sections[ 'gravity-pdf-' . $group ] = [
				/* translators: %s: The group name, as get_deprecated_groups() gives it */
				'label'       => sprintf( __( 'Gravity PDF - %s Functionality', 'gravity-pdf' ), $names['label'] ),
				'description' => esc_html( $names['description'] ),
				'fields'      => $fields,
			];
		}

		return $sections;
	}
}
