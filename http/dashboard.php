<?php

if ( ! $g['is_logged_in'] ) {
	do_redirect( a_link( '/auth' ) );
}

$g["statics"] = [
	"styles"  => [],
	"scripts" => [
		a_asset( "js/dashboard.js" ),
	]
];

$g["page_title"] = "Dashboard";
