<?php

/**
 * Run with `wp eval-file`: imports the all-form-fields form and entry, renders its PDF, and prints
 * "form-id entry-id pdf-id pdf-url" for smoke.sh.
 */

$data = PDF_PLUGIN_DIR . 'tools/phpunit/data/';

$form    = json_decode( (string) file_get_contents( $data . 'forms/all-form-fields.json' ), true );
$form_id = GFAPI::add_form( $form );
if ( is_wp_error( $form_id ) ) {
	WP_CLI::error( $form_id );
}

$entry            = json_decode( (string) file_get_contents( $data . 'entries/all-form-fields-entries.json' ), true )[0];
$entry['form_id'] = $form_id;
$entry['id']      = null;
$entry_id         = GFAPI::add_entry( $entry );
if ( is_wp_error( $entry_id ) ) {
	WP_CLI::error( $entry_id );
}

$pdf_id = '555ad84787d7e';
GPDFAPI::update_pdf( $form_id, $pdf_id, array_merge( GPDFAPI::get_pdf( $form_id, $pdf_id ), [ 'template' => 'zadani', 'conditional' => '', 'conditionalLogic' => '' ] ) );

$file = GPDFAPI::create_pdf( $entry_id, $pdf_id, true );
if ( is_wp_error( $file ) || ! is_file( $file ) ) {
	WP_CLI::error( 'The PDF was not generated: ' . ( is_wp_error( $file ) ? $file->get_error_message() : $file ) );
}

$url = GPDFAPI::get_mvc_class( 'Model_PDF' )->get_pdf_url( $pdf_id, $entry_id );

echo "$form_id $entry_id $pdf_id $url\n";
