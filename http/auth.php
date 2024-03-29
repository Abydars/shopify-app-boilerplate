<?php

if ( $g['is_logged_in'] ) {
	do_redirect( a_link( '/dashboard' ) );
}

$g["statics"] = [
	"styles"  => [],
	"scripts" => [
		a_asset( "js/auth.js" ),
	]
];

$g["page_title"] = "Store Authorization";
