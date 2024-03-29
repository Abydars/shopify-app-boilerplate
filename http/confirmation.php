<?php

$shop = $_GET['shop'] ?? false;
$code = $_GET['code'] ?? false;

if ( $shop && $code && a_verify_hmac() ) {
	$response = do_curl( "https://$shop/admin/oauth/access_token", "POST", [
		'client_id'     => APP_CLIENT_ID,
		'client_secret' => APP_CLIENT_SECRET,
		'code'          => $code,
	] );

	$error    = "Failed to connect store. Please try doing it manually.";
	$response = json_decode( $response, true );
	$token    = $response['access_token'] ?? false;

	if ( $token ) {
		$session_id = a_generate_session_id();
		a_create_update_store_token( $shop, $token, $session_id );
		$_SESSION['session_id'] = $session_id;
		do_redirect( a_link( '/dashboard' ) );
	}

	do_redirect( a_link( '/auth' ) . '?error=' . urlencode( $error ) );
} else if ( $g['is_logged_in'] ) {
	do_redirect( a_link( '/dashboard' ) );
} else {
	do_redirect( a_link( '/auth' ) );
}
die;
