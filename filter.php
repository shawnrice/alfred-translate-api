<?php

require_once( 'Alphred.phar' );
require_once( 'languageCodes.php' );

$alphred      = new Alphred( [ 'error_on_empty' => true ] );
$separator    = '›';
$set_language = "{$separator} set language {$separator}";
$set_api_key  = "{$separator} set api_key {$separator}";

$languages = explode( ',', $alphred->config_read( 'languages' ) );

if ( empty( $languages ) ) {
	// If nothing is set, then we'll set the target language to French
	$languages = [ 'fr', 'de', 'af', 'es' ];
	$alphred->config_set( 'languages', implode( ',', $languages ) );
}

$api_key = $alphred->config_read( 'api_key' );

// If the API key is empty, then we need to set it before doing anything else
if ( empty( $api_key ) ) {
	$alphred->add_result([
		'title'    => 'Translate: Set API Key to use this workflow',
		'subtitle' => 'All requests need to have a valid API key',
		'arg'      => json_encode( [ 'action' => 'set-api-key' ] ),
		'valid'    => true,
	]);
	// Print and exit because, well, that's all we can do.
	print $alphred->to_xml();
	exit(0);
}

// We're doing this in order to make a nice pretty string to display
$concat_languages = [];
foreach ( $languages as $language ) :
	array_push( $concat_languages, $codes[ $language ] );
endforeach;
$concat_languages = implode( ', ', $concat_languages );

// No arguments are present, so display the initial options
if ( ( ! isset( $argv[1] ) ) || ( empty( $argv[1] ) ) ) {
	// Give them the queue to translate
	$alphred->add_result([
		'title' => 'Type to translate...',
		'valid' => false,
	]);
	// Set translation languages option
	$alphred->add_result([
		'title'        => 'Set Translation Languages',
		'autocomplete' => $set_language,
		'subtitle'     => "Currently translating to `{$concat_languages}`",
		'valid'        => false,
	]);
	// (Re)set the API key
	$alphred->add_result([
		'title'    => 'Set Google Translate API Key',
		'subtitle' => 'API Key is currently set to "' . $api_key . '"',
		'arg'      => json_encode( [ 'action' => 'set-api-key' ] ),
		'valid'    => true,
	]);

	// Print the XML and exit
	print $alphred->to_xml();
	exit(0);
}

// Check if the argument is to set languages
if ( false !== strpos( $argv[1], $set_language ) ) {
	// Remove the command so we just have the query
	$pos     = strpos( $argv[1], $set_language ) + strlen( $set_language );
	$query   = trim( substr( $argv[1], $pos ) );
	// Filter out non-matching languages is there is a query
	$options = $alphred->filter( $codes, $query );

	// Go through all the matching languages (or all if query is empty) and print them
	foreach ( $options as $name ) :
		// Get the code from the names
		$code = array_search( $name, $codes );

		$alphred->add_result([
			// Add in a + to enable and a - to disable
			'title'    => ( ( in_array( $code, $languages ) ) ? '- ' : ' + ' ) . $name,
			// Clarify the enable/disable command
			'subtitle' => ( ( in_array( $code, $languages ) ) ? 'Disable' : 'Enable') . " {$name} ({$code})",
			'valid'    => true,
			// Encode the arguments so that the action.php script can understand them
			'arg'      => json_encode( [
				// Main action
				'action'    => 'set-language',
				// Enable / disable subaction
				'subaction' => ( in_array( $code, $languages ) ) ? 'disable' : 'enable',
				// Language code
				'code'      => $code
			] ),
			// Add in the pretty icon
			'icon'     => "icons/{$code}.png",
		]);
	endforeach;

	// Print the XML and leave
	print $alphred->to_xml();
	exit(0);
}

// If we've reached this point, then we're doing a translation.

// The text to be translated
$text = $argv[1];

// Cycle through every enabled language and get a translation
foreach ( $languages as $language ) :
	// Base API url
	$query  = 'https://www.googleapis.com/language/translate/v2?q=';
	// Make sure the text is urlencoded, add in the target language code and the API key
	$query .= urlencode( $text ) . "&target={$language}&key={$api_key}";

	// We'll add in the user-agent. I don't think that this is necessary for the API, but it doesn't hurt.
	$user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.1 Safari/537.36';

	// Grab the information from Google Translate and cache it for about a month.
	$data = json_decode( $alphred->get( $query, [ 'user_agent' => $user_agent ], 2419200 ), true );

	// From my tests, the API just returns one translation, so we'll just grab the first.
	// Also, some characters seem to be encoded as HTML entities, so we'll decode these.
	$string = html_entity_decode( $data['data']['translations'][0]['translatedText'], ENT_QUOTES, 'UTF-8' );

	// Add the result for the script filter
	$alphred->add_result([
		'title'        => trim( str_replace( "\n", ' ', $string ) ),
		'subtitle'     => "{$codes[$language]}: {$text}",
		'icon'         => "icons/{$language}.png",
		'arg'          => json_encode( [
				'action'      => 'translate', // This is a generic one
				'translation' => trim( $string ),
				'original'    => $text,
		    'code'        => $language,
		] ),
		'valid'          => true,
		'subtitle_cmd'   => 'Open in Web Browser',
	]);
endforeach;

// Print the results and leave
print $alphred->to_xml();
exit(0);