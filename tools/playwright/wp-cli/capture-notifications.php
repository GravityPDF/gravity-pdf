<?php

/* `wp eval-file <this> <form id> <entry id>...`: send each entry's notifications now and print what the mail catcher saved */

$gpdf_form = GFAPI::get_form( absint( array_shift( $args ) ) );
$gpdf_mail = [];

add_action(
	'gpdf_mail_captured',
	static function ( $dir, $mail ) use ( &$gpdf_mail ) {
		foreach ( $mail['attachments'] as &$attachment ) {
			$attachment['content'] = $attachment['copy'] ? base64_encode( file_get_contents( $dir . '/' . $attachment['copy'] ) ) : '';
		}

		$gpdf_mail[ array_key_last( $gpdf_mail ) ][] = $mail;
	},
	10,
	2
);

/* Gravity Forms 2.10+ queues notifications in the background by default; send them in this request instead */
add_filter( 'gform_is_asynchronous_notifications_enabled', '__return_false' );

foreach ( array_map( 'absint', $args ) as $gpdf_entry_id ) {
	$gpdf_mail[] = [];
	GFAPI::send_notifications( $gpdf_form, GFAPI::get_entry( $gpdf_entry_id ) );
}

/* Delimited, as `wp-env run` wraps the output in its own */
echo '<gpdf-mail>' . wp_json_encode( $gpdf_mail ) . '</gpdf-mail>';
