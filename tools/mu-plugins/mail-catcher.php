<?php

/* Dev/E2E sites can't send mail: save each email, with copies of its attachments, to uploads/mail-capture/ instead */

/* Excluded from production build. Skipped under PHPUnit (MockPHPMailer) or when GPDF_DISABLE_MAIL_CAPTURE is set */
if ( defined( 'WP_TESTS_DOMAIN' ) || ( defined( 'GPDF_DISABLE_MAIL_CAPTURE' ) && GPDF_DISABLE_MAIL_CAPTURE ) || getenv( 'GPDF_DISABLE_MAIL_CAPTURE' ) ) {
	return;
}

add_filter(
	'pre_wp_mail',
	static function ( $short_circuit, $atts ) {
		if ( $short_circuit !== null || ! is_array( $atts ) ) {
			return $short_circuit;
		}

		$dir = wp_get_upload_dir()['basedir'] . '/mail-capture/' . gmdate( 'Ymd-His' ) . '-' . wp_generate_password( 6, false );
		if ( ! wp_mkdir_p( $dir ) ) {
			return null;
		}

		/* Copy attachments now: Gravity PDF deletes its tmp PDFs once the notification is sent */
		$attachments = $atts['attachments'] ?? [];
		if ( ! is_array( $attachments ) ) {
			$attachments = explode( "\n", str_replace( "\r\n", "\n", (string) $attachments ) );
		}

		$atts['attachments'] = [];
		foreach ( array_filter( $attachments ) as $file ) {
			$exists                = is_file( $file );
			$atts['attachments'][] = [
				'path'   => $file,
				'exists' => $exists,
				'size'   => $exists ? filesize( $file ) : 0,
			];

			if ( $exists && wp_mkdir_p( $dir . '/attachments' ) ) {
				copy( $file, $dir . '/attachments/' . wp_unique_filename( $dir . '/attachments', basename( $file ) ) );
			}
		}

		$saved = file_put_contents( $dir . '/mail.json', wp_json_encode( $atts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );

		/* Fall through to the real wp_mail() if the capture couldn't be written */
		return $saved ? true : null;
	},
	PHP_INT_MAX,
	2
);
