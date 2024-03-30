<?php

function a_link( $path )
{
	global $site_url;

	return "{$site_url}{$path}";
}

function a_asset( $path )
{
	return a_link( "/assets/{$path}" );
}

function a_full_path( $path )
{
	return PATH . $path;
}

function a_template( $path )
{
	global $g;

	$content = "";
	$path    = a_full_path( $path );

	if ( file_exists( $path ) ) {
		ob_start();
		include_once $path;

		$content = ob_get_clean();
	}

	return $content;
}

function a_delete_col( &$array, $key )
{
	return array_walk( $array, function ( &$v ) use ( $key ) {
		unset( $v[ $key ] );
	} );
}

function not_empty( $data )
{
	return ! empty( $data );
}

function do_redirect( $url )
{
	header( "location: {$url}" );
	die;
}

function do_curl( $url, $method, $data )
{
	$ch = curl_init( $url );

	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

	if ( $method == 'POST' ) {
		curl_setopt( $ch, CURLOPT_POST, true );
	} else {
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
	}

	if ( not_empty( $data ) ) {
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
	}

	$response = curl_exec( $ch );
	curl_close( $ch );

	return $response;
}

function a_rand_str( $length = 10 )
{
	$characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen( $characters );
	$randomString     = '';
	for ( $i = 0; $i < $length; $i ++ ) {
		$randomString .= $characters[ random_int( 0, $charactersLength - 1 ) ];
	}

	return $randomString;
}

function a_generate_session_id()
{
	return a_rand_str( 10 );
}

function a_is_logged_in()
{
	global $g;

	return not_empty( $g['me'] );
}

function a_get_logged_in_store()
{
	if ( isset( $_GET['session_id'] ) && not_empty( $_GET['session_id'] ) ) {
		$store = a_get_store_by_session_id( $_GET['session_id'] );

		if ( not_empty( $store ) ) {
			return $store;
		}
	} else if ( isset( $_GET['shop'] ) && a_verify_hmac() ) {
		$shop  = $_GET['shop'];
		$store = a_get_store_by_shop( $shop );

		if ( not_empty( $store ) ) {
			return $store;
		}
	}

	return false;
}

function a_verify_hmac()
{
	if ( ! isset( $_GET['hmac'] ) ) {
		return false;
	}

	$shared_secret = APP_CLIENT_SECRET;
	$params        = $_GET;
	$hmac          = $_GET['hmac'];

	unset( $params['app'] );

	$params = array_diff_key( $params, array( 'hmac' => '' ) );

	ksort( $params );

	$computed_hmac = hash_hmac( 'sha256', http_build_query( $params ), $shared_secret );

	return hash_equals( $hmac, $computed_hmac );
}

function a_json( $data, $decode = false )
{
	if ( $decode ) {
		return json_decode( $data, true );
	}

	return json_encode( $data );
}

function a_get_graphql( $name, $data = [] )
{
	$file = a_full_path( "graphQLs/{$name}.graphql" );
	$q    = "";

	if ( file_exists( $file ) ) {
		ob_start();
		require( $file );
		$q = ob_get_contents();
		ob_end_clean();
	}

	return json_encode( [
		                    "query"     => $q,
		                    "variables" => $data
	                    ] );
}
