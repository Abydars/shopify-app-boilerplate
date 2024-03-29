<?php

session_start();

ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );
error_reporting( E_ALL );

require_once dirname( __FILE__ ) . '/config.php';
require_once dirname( __FILE__ ) . '/definitions.php';
require_once dirname( __FILE__ ) . '/tools.php';
require_once dirname( __FILE__ ) . '/libs/DB/vendor/autoload.php';
require_once dirname( __FILE__ ) . '/libs/Shopify/actions.php';
require_once dirname( __FILE__ ) . '/dboperate.php';

$mysqli = new mysqli( DB_HOST, DB_USER, DB_PASS, DB_NAME );

if ( $mysqli->connect_errno ) {
	echo "Failed to connect to MySQL: " . $mysqli->connect_error;
	exit();
}

$db          = new MysqliDb( $mysqli );
$site_url    = SITE_URL;

global $g;

$g                 = [];
$g['me']           = a_get_logged_in_store();
$g['is_logged_in'] = a_is_logged_in();
$g['shopify']      = $shopify = null;

if ( not_empty( $g['is_logged_in'] ) ) {
	$g['shopify'] = $shopify = new ShopifyActions( $g['me']['shop'], $g['me']['token'] );
}
