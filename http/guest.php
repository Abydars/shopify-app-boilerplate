<?php

if ( isset( $_GET['shop'] ) ) {
	$shop         = $_GET['shop'];
	$client_id    = APP_CLIENT_ID;
	$redirect_uri = APP_REDIRECT_URI;
	$scopes       = APP_SCOPES;

	do_redirect( "https://{$shop}/admin/oauth/authorize?client_id={$client_id}&scope={$scopes}&redirect_uri={$redirect_uri}" );
} else {
	$error = "Failed to connect store. Please try doing it manually.";
	do_redirect( a_link( '/auth' ) . '?error=' . urlencode( $error ) );
}   
