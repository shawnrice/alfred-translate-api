<?php

require_once( 'Alphred.phar' );
require_once( 'languageCodes.php' );

$args = json_decode( $argv[1], true );

$alphred = new Alphred();
$languages = explode( ',', $alphred->config_read( 'languages' ) );

if ( 'set-api-key' === $args['action'] ) {
	$api_key = get_api_key_dialog();
	if ( ! empty( trim( $api_key ) ) && 'canceled' !== trim( $api_key ) ) {
		$alphred->config_set( 'api_key', $api_key );
	}
	$alphred->send_notification( 'API Key Set' );
	exit(0);
}

if ( 'set-language' === $args['action'] ) {


	if ( 'disable' === $args['subaction'] ) {
		unset( $languages[ array_search( $args['code'], $languages ) ] );
	} else {
		if ( array_key_exists( $args['code'], $codes ) ) {
			array_push( $languages, $args['code'] );
		}
	}

	$languages = array_unique( $languages );

	$alphred->config_set( 'languages', implode( ',', $languages ) );

	$message = [];
	foreach( $languages as $language ) :
		array_push( $message, $codes[ $language ] );
	endforeach;

	$message = implode( ', ', $message );

	$alphred->send_notification( "Changed translation language(s) to {$message}." );
	exit(0);
}

// All other options have already been taken care of, so just pass the translation along to the next
// workflow object.
print $args['translation'];

function get_api_key_dialog() {

	// Create hidden answer AppleScript dialog
	$dialog = new \Alphred\Dialog([
		'text'           => 'Enter your API key from Google',
		'title'          => \Alphred\Globals::get( 'alfred_workflow_name' ),
		'default_answer' => '',
		'hidden_answer'  => false,
	]);

	$dialog->set_icon( __DIR__ . '/icon.png' );
	// Execute the dialog and return the result
	return $dialog->execute();
}
