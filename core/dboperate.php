<?php

function a_create_update_store_token( $shop, $token, $session_id )
{
	global $db;

	$db->where( 'shop', $shop );
	$store = $db->getOne( T_STORES );

	if ( not_empty( $store ) ) {
		$db->where( 'shop', $shop );

		return $db->update( T_STORES, [ 'token' => $token, 'session_id' => $session_id ] );
	} else {
		$db->insert( T_STORES, [
			'shop'       => $shop,
			'token'      => $token,
			'session_id' => $session_id
		] );

		return $db->getInsertId();
	}
}

function a_get_store_by_session_id( $session_id )
{
	global $db;

	$db->where( 'session_id', $session_id );

	return $db->getOne( T_STORES );
}

function a_get_store_by_shop( $shop )
{
	global $db;

	$db->where( 'shop', $shop );

	return $db->getOne( T_STORES );
}

function a_get_store_by_id( $id )
{
	global $db;

	$db->where( 'id', $id );

	return $db->getOne( T_STORES );
}

function a_get_table_columns( $tbl )
{
	global $db;
	$q = "SHOW COLUMNS FROM {$tbl}";

	$t1 = $db->query( $q );

	return array_column( $t1, 'Field' );
}
