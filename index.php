<?php

require_once dirname( __FILE__ ) . '/core/request.php';

$app_name = ( isset( $_GET["app"] ) ) ? $_GET["app"] : "guest";

if ( $app_name ) {
	if ( $app_name == 'ajax' ) {

		$action = $_GET['action'] ?? "";
		$data   = [];

		include_once( a_full_path( "/ajax/{$action}.php" ) );

		echo a_json( $data );
		die;

	} else {
		include_once( a_full_path( "/http/{$app_name}.php" ) );

		$header = 'header';
		$footer = 'footer';

		if ( in_array( $app_name, [ 'auth' ] ) ) {
			$header = 'guest-header';
			$footer = 'guest-footer';
		}

		echo a_template( "/templates/partials/{$header}.php" );
		echo a_template( "/templates/{$app_name}.php" );
		echo a_template( "/templates/partials/{$footer}.php" );
	}
}
