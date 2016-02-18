<?php

// Decode the args
$args = json_decode( $argv[1], true );

// Create the URL
$url = "https://translate.google.com/#auto/{$args['code']}/" . urlencode( $args['original'] );

// Open it in the default web browser
exec( "open '${url}'" );
