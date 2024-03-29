<?php
$shop  = $_POST['store_url'] ?? false;
$token = $_POST['token'] ?? false;
$data  = [ 'status' => 0 ];

if ( $shop && $token ) {
	$session_id             = a_generate_session_id();
	$iu                     = a_create_update_store_token( $shop, $token, $session_id );
	$_SESSION['session_id'] = $session_id;
	$data['status']         = 1;
}
