<?php

// Include Alphred and the language codes
require_once( 'Alphred.phar' );
require_once( 'languageCodes.php' );

// Decode the argument passed from json to a usable array
$args = json_decode( $argv[1], true );

// Instantiate an Alphred helper object
$alphred = new Alphred();

// Get the set languages and explode the string
$languages = explode( ',', $alphred->config_read( 'languages' ) );

///// Actions
//////////////

// Are we setting the API key?
if ( 'set-api-key' === $args['action'] ) {
	// Create the dialog and get the API key
	$api_key = get_api_key_dialog();
	// Make sure the user didn't enter a blank API key or press the cancel button.
	if ( ! empty( trim( $api_key ) ) && 'canceled' !== trim( $api_key ) ) {
		// Trim and set the API key
		$alphred->config_set( 'api_key', trim( $api_key ) );
	}
	// Send a notification that the API key is set
	$alphred->send_notification( 'API Key Set' );
	// We're done, so exit
	exit(0);
}

// Are we setting new languages?
if ( 'set-language' === $args['action'] ) {

	// Disable or enable?
	if ( 'disable' === $args['subaction'] ) {
		// Remove the old language from the languages array
		unset( $languages[ array_search( $args['code'], $languages ) ] );
	} else if ( 'enable' === $args['subaction'] ) {
		// Make sure that the language isn't already set
		if ( array_key_exists( $args['code'], $codes ) ) {
			// Push the new language into the languages array
			array_push( $languages, $args['code'] );
		}
	} else {
		// Bad subaction. We should never get here. Send an error message and get out.
		$alphred->console( 'Invalid subaction when setting language. Options are enable/disable only.', 4 );
		exit(1);
	}

	// Extra redundant check to make sure that the array is unique
	$languages = array_unique( $languages );

	// Push the languages into the config file
	$alphred->config_set( 'languages', implode( ',', $languages ) );

	// Create a string of languages to be used in a message
	$message = [];
	foreach( $languages as $language ) :
		array_push( $message, $codes[ $language ] );
	endforeach;

	// Push the array into a string
	$message = implode( ', ', $message );

	// Send a notification and leave
	$alphred->send_notification( "Changed translation language(s) to {$message}." );
	exit(0);
}

// All other options have already been taken care of, so just pass the translation along to the next
// workflow object.
print $args['translation'];

/**
 * Shows an AppleScript dialog to enter the API key
 *
 * @uses  \Alphred\Dialog the Alphred AppleScript dialog interface
 * @return [type] [description]
 */
function get_api_key_dialog() {

	// Create hidden answer AppleScript dialog
	$dialog = new \Alphred\Dialog([
		'text'           => 'Enter your API key from Google',
		'title'          => \Alphred\Globals::get( 'alfred_workflow_name' ),
		'default_answer' => '',
		'hidden_answer'  => false,
	]);

	// Set the icon to our icon
	$dialog->set_icon( __DIR__ . '/icon.png' );
	// Execute the dialog and return the result
	return $dialog->execute();
}
