<?php

$shop         = $_GET['shop'];
$api_key      = APP_CLIENT_ID;
$scopes       = APP_SCOPES;
$redirect_uri = SITE_URL . "/confirmation";
$install_url  = "https://" . $shop . "/admin/oauth/authorize?client_id=" . $api_key . "&scope=" . $scopes . "&redirect_uri=" . urlencode( $redirect_uri );

header( "Location: " . $install_url );
die();
