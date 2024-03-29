<?php

class ShopifyActions
{
	private $shopifyStoreUrl;
	private $accessToken;
	private $cust_id;
	private $user_id;

	public function __construct( $shopifyStoreUrl, $accessToken )
	{
		$this->shopifyStoreUrl = $shopifyStoreUrl;
		$this->accessToken     = $accessToken;
	}

	function findCustomerByEmail( $email )
	{
		$email = urlencode( $email );
		$url   = "https://$this->shopifyStoreUrl/admin/api/2023-04/customers/search.json?query=email:{$email}";
		$ch    = curl_init( $url );

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			"X-Shopify-Access-Token: $this->accessToken",
			"Content-Type: application/json"
		) );

		$response = curl_exec( $ch );
		curl_close( $ch );

		$this->logResponse( $response, "findCustomerByEmail" );

		$response = json_decode( $response, true );
		$customer = false;

		if ( ! empty( $response['customers'][0] ) ) {
			$customer = $response['customers'][0];
		}

		return $customer;
	}

	function createUpdate( $row, $customer_id = false )
	{
		$this->logResponse( "Trying to update customer {$customer_id}", "createUpdate" );
		$url = "https://$this->shopifyStoreUrl/admin/api/2023-04/customers.json";
		//$phone        = $this->formatPhone( $row['phone'] );
		$data = [
			'first_name'            => $row['FirstName'],
			'last_name'             => $row['LastName'],
			'email'                 => $row['EmailAddress'],
			'verified_email'        => true,
			'addresses'             => [
				[
					'address1'   => $row['PhysicalLocation'],
					'last_name'  => $row['LastName'],
					'first_name' => $row['FirstName'],
				]
			],
			'password'              => 'abcd1234',
			'password_confirmation' => 'abcd1234',
			'send_email_welcome'    => false
		];

		if ( not_empty( $row['Tags'] ) ) {
			$data['tags'] = implode( ',', $row['Tags'] );
		}

		$customerData = [ 'customer' => $data ];

		if ( $customer_id ) {
			$url = "https://$this->shopifyStoreUrl/admin/api/2023-04/customers/$customer_id.json";
		}

		$dataJSON = json_encode( $customerData );
		$ch       = curl_init( $url );

		if ( $customer_id ) {
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PUT" );
		} else {
			curl_setopt( $ch, CURLOPT_POST, true );
		}

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $dataJSON );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			"X-Shopify-Access-Token: $this->accessToken",
			"Content-Type: application/json"
		) );

		$response = curl_exec( $ch );
		curl_close( $ch );

		$this->cust_id = $customer_id;

		$this->logResponse( $response, "createUpdate" );

		return json_decode( $response, true );
	}

	function setCustomerId( $customer_id )
	{
		$this->cust_id = $customer_id;
	}

	function setUserId( $customer_id )
	{
		$this->user_id = $customer_id;
	}

	function formatPhone( $phone )
	{
		preg_match_all( '/[0-9]+/', $phone, $matches );

		return implode( '', $matches[0] );
	}

	function findCustomerByQuery( $key, $value )
	{
		$query    = a_get_graphql( 'findCustomerByQuery', [ 'query' => "{$key}:${value}" ] );
		$response = $this->postGraphQL( $query );
		$this->logResponse( $response, 'findCustomerByQuery' );
		$response = json_decode( $response, true );
		$customer = false;

		if ( ! empty( $response['data']['customers']['edges'] ) ) {
			$customer = array_column( $response['data']['customers']['edges'], 'node' );
			$customer = $customer[0] ?? false;
		}

		return $customer;
	}

	function getCompanyAndLocationByExternalId( $external_id )
	{
		$companyID         = null;
		$companyLocationID = null;
		$query_get_id      = '{"query":"query {\\r\\n    companyLocations(first: 10, query: \"external_id:' . $external_id . '\") {\\r\\n        edges {\\r\\n            node {\\r\\n                company {\\r\\n                    id\\r\\n                }\\r\\n                id\\r\\n                name\\r\\n                externalId\\r\\n                metafields(first: 20) {\\r\\n                    edges {\\r\\n                        node {\\r\\n                            id\\r\\n                            key\\r\\n                            value\\r\\n                        }\\r\\n                    }\\r\\n                }\\r\\n            }\\r\\n        }\\r\\n    }\\r\\n}","variables":{}}';
		$response          = $this->postGraphQL( $query_get_id );
		$this->logResponse( "Query: {$query_get_id}", 'getCompanyAndLocationByExternalId' );
		$this->logResponse( "Provided External ID: {$external_id}", 'getCompanyAndLocationByExternalId' );
		$this->logResponse( $response, 'getCompanyAndLocationByExternalId' );
		$responseData = json_decode( $response, true );

		if ( isset( $responseData['data']['companyLocations']['edges'] ) ) {
			$edges = $responseData['data']['companyLocations']['edges'];

			foreach ( $edges as $edge ) {
				$node = $edge['node'];
				if ( isset( $node['externalId'] ) && $node['externalId'] === $external_id ) {
					$companyID         = $node['company']['id'];
					$companyLocationID = $node['id'];
					break;
				}
			}
		} else {
			$this->logResponse( "Company Locations not found", 'Error' );
		}

		return [ $companyID, $companyLocationID ];
	}

	function addToCompany( $companyId, $customerId )
	{
		$query_add_company = '{"query":"mutation companyAssignCustomerAsContact($companyId: ID!, $customerId: ID!) {\\r\\n  companyAssignCustomerAsContact(companyId: $companyId, customerId: $customerId) {\\r\\n    companyContact {\\r\\n        id\\r\\n        roleAssignments(first: 20) {\\r\\n            edges {\\r\\n                node {\\r\\n                    id\\r\\n                }\\r\\n            }\\r\\n        }\\r\\n      # CompanyContact fields\\r\\n    }\\r\\n    userErrors {\\r\\n      field\\r\\n      message\\r\\n    }\\r\\n  }\\r\\n}","variables":{"companyId":"' . $companyId . '","customerId":"gid://shopify/Customer/' . $customerId . '"}}';
		$response1         = $this->postGraphQL( $query_add_company );
		$this->logResponse( $response1, 'addToCompany' );
		$responseData1    = json_decode( $response1, true );
		$companyContactID = false;

		if ( isset( $responseData1['data']['companyAssignCustomerAsContact']['companyContact'] ) ) {
			$companyContactID = $responseData1['data']['companyAssignCustomerAsContact']['companyContact']['id'];
			$this->logResponse( "Company Contact Created", 'Success' );
		} else {
			$this->logResponse( "No companyContact found in the response.", 'Error' );
		}

		return $companyContactID;
	}

	function getCompanyContactIdByCustomer( $customer_id, $company_id, $locationExternalId )
	{
		//$query_get_company_contact = '{"query":"query {\\r\\n    customer(id:\\"gid://shopify/Customer/' . $customer_id . '\\") {\\r\\n        companyContactProfiles {\\r\\n            id\\r\\n            company     {\\r\\n                id\\r\\n            }\\r\\n        }\\r\\n    }\\r\\n}","variables":{}}';
		$customer_gid              = 'gid://shopify/Customer/' . $customer_id;
		$query_get_company_contact = a_get_graphql( 'getCompanyContactIdByCustomer', [
			'customerId' => $customer_gid,
			//'companyId' => $company_id,
		] );
		$response1                 = $this->postGraphQL( $query_get_company_contact );
		$this->logResponse( $response1, 'getCompanyContactIdByCustomer' );
		$responseData1                  = json_decode( $response1, true );
		$companyContactID               = false;
		$companyContactRoleAssignmentId = false;
		$role                           = false;

		if ( isset( $responseData1['data']['customer']['companyContactProfiles'] ) ) {
			$contactProfiles = $responseData1['data']['customer']['companyContactProfiles'];
			foreach ( $contactProfiles as $contactProfile ) {
				$roleAssignments = $contactProfile['roleAssignments']['edges'];
				if ( $contactProfile['company']['id'] == $company_id ) {
					$companyContactID = $contactProfile['id'];
				}

				foreach ( $roleAssignments as $roleAssignment ) {
					if ( $roleAssignment['node']['companyLocation']['externalId'] == $locationExternalId ) {
						$companyContactRoleAssignmentId = $roleAssignment['node']['id'];
						$role                           = $roleAssignment['node']['role'];
						break;
					}
				}
			}
		} else {
			$this->logResponse( "No companyContactProfiles found.", 'Error' );
		}

		return [ $companyContactID, $companyContactRoleAssignmentId, $role ];
	}

	function getCompanyRoleId( $companyId, $roleName = "Ordering only" )
	{
		$query_get_role = '{"query":"query {\\r\\n    company(id: \\"' . $companyId . '\\") {\\r\\n        contactRoles(first: 10) {\\r\\n            edges {\\r\\n                node {\\r\\n                    id\\r\\n                    name\\r\\n                }\\r\\n            }\\r\\n        }\\r\\n    }\\r\\n}","variables":{}}';
		$response2      = $this->postGraphQL( $query_get_role );
		$this->logResponse( $response2, 'getCompanyRoleId' );
		$responseData2 = json_decode( $response2, true );
		$roleID        = false;

		if ( isset( $responseData2['data']['company']['contactRoles']['edges'] ) ) {
			$edges1 = $responseData2['data']['company']['contactRoles']['edges'];

			foreach ( $edges1 as $edge1 ) {
				$node1 = $edge1['node'];
				if ( isset( $node1['name'] ) && $node1['name'] === $roleName ) {
					$roleID = $node1['id'];
					break;
				}
			}
		} else {
			$this->logResponse( "No contact roles found", 'Error' );
		}

		return $roleID;
	}

	function assignCompanyRoleToCustomer( $companyContactId, $roleId, $companyLocationId )
	{
		$query_add_location = '{"query":"mutation companyContactAssignRole($companyContactId: ID!, $companyContactRoleId: ID!, $companyLocationId: ID!) {\\r\\n  companyContactAssignRole(companyContactId: $companyContactId, companyContactRoleId: $companyContactRoleId, companyLocationId: $companyLocationId) {\\r\\n    companyContactRoleAssignment {\\r\\n        id\\r\\n      # CompanyContactRoleAssignment fields\\r\\n    }\\r\\n    userErrors {\\r\\n      field\\r\\n      message\\r\\n    }\\r\\n  }\\r\\n}","variables":{"companyContactId":"' . $companyContactId . '","companyContactRoleId":"' . $roleId . '","companyLocationId":"' . $companyLocationId . '"}}';
		$response3          = $this->postGraphQL( $query_add_location );

		$this->logResponse( $response3, 'assignCompanyRoleToCustomer' );
		$this->logResponse( "Customer added to company location", 'Success' );
	}

	function revokeCompanyRoleToCustomer( $companyContactId, $companyContactRoleAssignmentId )
	{
		$query_remove_location = a_get_graphql( 'removeCustomerFromCompanyLocation', [
			'companyContactId'               => $companyContactId,
			'companyContactRoleAssignmentId' => $companyContactRoleAssignmentId
		] );
		$response3             = $this->postGraphQL( $query_remove_location );

		$this->logResponse( $response3, 'revokeCompanyRoleToCustomer' );
		$this->logResponse( "Customer revoked from company location", 'Success' );
	}

	function removeCustomerFromCompany( $locationId, $customerId )
	{
		list( $companyId, $companyLocationId ) = $this->getCompanyAndLocationByExternalId( $locationId );

		if ( $companyId !== null && $companyLocationId !== null ) {

			list( $companyContactId, $companyContactRoleAssignmentId ) = $this->getCompanyContactIdByCustomer( $customerId, $companyId, $locationId );

			if ( $companyContactId ) {

				$query_remove_location = a_get_graphql( 'removeCustomerFromCompany', [
					'companyContactId' => $companyContactId
				] );
				$response3             = $this->postGraphQL( $query_remove_location );

				$this->logResponse( $response3, 'removeCustomerFromCompany' );
				$this->logResponse( "Customer removed from company", 'Success' );

			} else {
				$this->logResponse( "Company Contact ID or RoleAssignmentId not Found.", 'Error' );
			}
		} else {
			$this->logResponse( "Company ID or Location ID not Found.", 'Error' );
		}
	}

	function addCustomerToCompanyLocation( $locationId, $customerId, $roleName = "Ordering only" )
	{
		$this->logResponse( "Trying to add {$customerId} in {$locationId} with role {$roleName}" );
		list( $companyId, $companyLocationId ) = $this->getCompanyAndLocationByExternalId( $locationId );

		if ( $companyId !== null && $companyLocationId !== null ) {

			$companyContactId = $this->addToCompany( $companyId, $customerId );

			if ( ! $companyContactId ) {
				list( $companyContactId, $companyContactRoleAssignmentId, $role ) = $this->getCompanyContactIdByCustomer( $customerId, $companyId, $locationId );
			}

			if ( $companyContactId ) {
				$roleId = $this->getCompanyRoleId( $companyId, $roleName );

				if ( $roleId !== null ) {

					$this->assignCompanyRoleToCustomer( $companyContactId, $roleId, $companyLocationId );

				} else {
					$this->logResponse( "No matching customer role found.", 'Error' );
				}
			} else {
				$this->logResponse( "Company Contact ID or not Found.", 'Error' );
			}
		} else {
			$this->logResponse( "Company ID or Location ID not Found.", 'Error' );
		}
	}

	function checkIsInLocation( $customerId, $locationExternalId, &$return_role )
	{
		list( $companyId, $companyLocationId ) = $this->getCompanyAndLocationByExternalId( $locationExternalId );

		if ( $companyId !== null && $companyLocationId !== null ) {

			list( $companyContactId, $companyContactRoleAssignmentId, $role ) = $this->getCompanyContactIdByCustomer( $customerId, $companyId, $locationExternalId );
			if ( $role ) { //&& $role['name'] == 'Location admin'
				$return_role = $role;

				return true;
			}
		}

		return false;
	}

	function removeCustomerFromCompanyLocation( $locationId, $customerId )
	{
		list( $companyId, $companyLocationId ) = $this->getCompanyAndLocationByExternalId( $locationId );

		if ( $companyId !== null && $companyLocationId !== null ) {

			list( $companyContactId, $companyContactRoleAssignmentId ) = $this->getCompanyContactIdByCustomer( $customerId, $companyId, $locationId );

			if ( $companyContactId && $companyContactRoleAssignmentId ) {

				$this->revokeCompanyRoleToCustomer( $companyContactId, $companyContactRoleAssignmentId );
				//$this->removeCustomerFromCompany( $companyContactId );

			} else {
				$this->logResponse( "Company Contact ID or RoleAssignmentId not Found.", 'Error' );
			}
		} else {
			$this->logResponse( "Company ID or Location ID not Found.", 'Error' );
		}
	}

	function postGraphQL( $query )
	{
		$curl    = curl_init();
		$options = array(
			CURLOPT_URL            => 'https://' . $this->shopifyStoreUrl . '/admin/api/2023-04/graphql.json',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_POSTFIELDS     => $query,
			CURLOPT_HTTPHEADER     => array(
				'X-Shopify-Access-Token: ' . $this->accessToken,
				'Content-Type: application/json'
			),
		);

		curl_setopt_array( $curl, $options );
		$response = curl_exec( $curl );
		curl_close( $curl );

		return $response;
	}

	function logResponse( $response, $logFileName = false, $customer_id = false )
	{
		global $db;

		if ( $customer_id === false && not_empty( $this->cust_id ) ) {
			$db->where( 'shopify_user_id', $this->cust_id );
			$customer_id = $db->getValue( T_CUSTOMERS, 'id' );
		}

		if ( $customer_id === false && not_empty( $this->user_id ) ) {
			$customer_id = $this->user_id;
		}

		if ( not_empty( $customer_id ) ) {
			$db->where( 'id', $customer_id );
			$record = $db->getOne( T_CUSTOMERS );

			$logs = $record['logs'];
			$logs .= "=== " . $logFileName . " ===" . PHP_EOL . $response . PHP_EOL . PHP_EOL . PHP_EOL;

			$db->where( 'id', $customer_id );
			$db->update( T_CUSTOMERS, [ 'logs' => $logs ] );
			//echo $logs;
		}

//		echo $response . PHP_EOL;
//		$logDirectory = 'logs';
//		if ( ! is_dir( $logDirectory ) ) {
//			mkdir( $logDirectory, 0755, true );
//		}
//		$logFilePath = $logDirectory . '/' . $logFileName . '.txt';
//		file_put_contents( $logFilePath, $response . "\n", FILE_APPEND );
	}

	function updateCustomerMetaField( $customer_id, $key, $value )
	{
		$curl = curl_init();

		curl_setopt_array( $curl, array(
			CURLOPT_URL            => 'https://' . $this->shopifyStoreUrl . '/admin/api/2023-10/customers/' . $customer_id . '/metafields.json',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => '{"metafield":{"namespace":"custom","key":"' . $key . '","value":"' . $value . '"}}',
			CURLOPT_HTTPHEADER     => array(
				'X-Shopify-Access-Token: ' . $this->accessToken,
				'Content-Type: application/json'
			),
		) );

		$response = curl_exec( $curl );
		curl_close( $curl );
		$this->logResponse( $response, 'updateCustomerMetaField' );
	}

	function getShopifyCustomer( $customer_id )
	{
		$curl = curl_init();

		curl_setopt_array( $curl, array(
			CURLOPT_URL            => 'https://' . $this->shopifyStoreUrl . '/admin/api/2023-10/customers/' . $customer_id . '.json',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_HTTPHEADER     => array(
				'X-Shopify-Access-Token: ' . $this->accessToken,
				'Content-Type: application/json'
			),
		) );

		$response = curl_exec( $curl );
		$response = json_decode( $response, true );

		curl_close( $curl );

		return $response['customer'] ?? [];
	}

	function getShopifyCustomerMetafields( $customer_id )
	{
		$curl = curl_init();

		curl_setopt_array( $curl, array(
			CURLOPT_URL            => 'https://' . $this->shopifyStoreUrl . '/admin/api/2023-10/customers/' . $customer_id . '/metafields.json',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_HTTPHEADER     => array(
				'X-Shopify-Access-Token: ' . $this->accessToken,
				'Content-Type: application/json'
			),
		) );

		$response = curl_exec( $curl );
		$response = json_decode( $response, true );

		curl_close( $curl );

		return $response['metafields'] ?? [];
	}

	function getResource( $type, $id )
	{
		$curl = curl_init();

		curl_setopt_array( $curl, array(
			CURLOPT_URL            => 'https://' . $this->shopifyStoreUrl . '/admin/api/2023-10/' . $type . '/' . $id . '.json',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_HTTPHEADER     => array(
				'X-Shopify-Access-Token: ' . $this->accessToken,
				'Content-Type: application/json'
			),
		) );

		$response = curl_exec( $curl );
		$response = json_decode( $response, true );

		curl_close( $curl );

		return $response;
	}

	function updateResource( $type, $id, $data )
	{
		$curl = curl_init();

		curl_setopt_array( $curl, array(
			CURLOPT_URL            => 'https://' . $this->shopifyStoreUrl . '/admin/api/2023-10/' . $type . '/' . $id . '.json',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CUSTOMREQUEST  => 'PUT',
			CURLOPT_POSTFIELDS     => json_encode( $data ),
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_HTTPHEADER     => array(
				'X-Shopify-Access-Token: ' . $this->accessToken,
				'Content-Type: application/json'
			),
		) );

		$response = curl_exec( $curl );

		curl_close( $curl );

		return $response;
	}

	function deleteResource( $type, $id )
	{
		$curl = curl_init();

		curl_setopt_array( $curl, array(
			CURLOPT_URL            => 'https://' . $this->shopifyStoreUrl . '/admin/api/2023-10/' . $type . '/' . $id . '.json',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CUSTOMREQUEST  => 'DELETE',
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_HTTPHEADER     => array(
				'X-Shopify-Access-Token: ' . $this->accessToken,
				'Content-Type: application/json'
			),
		) );

		$response = curl_exec( $curl );

		curl_close( $curl );

		return json_decode( $response, true );
	}

	function getMetaFieldById( $type, $id, $key )
	{
		$curl = curl_init();

		curl_setopt_array( $curl, array(
			CURLOPT_URL            => 'https://' . $this->shopifyStoreUrl . '/admin/api/2023-10/' . $type . '/' . $id . '/metafields.json',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => 'GET',
			CURLOPT_HTTPHEADER     => array(
				'X-Shopify-Access-Token: ' . $this->accessToken,
				'Content-Type: application/json'
			),
		) );

		$response = curl_exec( $curl );

		curl_close( $curl );

		$metas = json_decode( $response, true );
		$metas = array_values( array_filter( $metas['metafields'], function ( $m ) use ( &$key ) {
			return $m['key'] == $key;
		} ) );

		return $metas[0]['value'] ?? false;
	}

	function updateMetaField( $type, $id, $key, $value )
	{
		$curl = curl_init();

		curl_setopt_array( $curl, array(
			CURLOPT_URL            => 'https://' . $this->shopifyStoreUrl . '/admin/api/2023-10/' . $type . '/' . $id . '/metafields.json',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_POST           => 'POST',
			CURLOPT_POSTFIELDS     => '{"metafield":{"namespace":"custom","key":"' . $key . '","value":"' . $value . '"}}',
			CURLOPT_HTTPHEADER     => array(
				'X-Shopify-Access-Token: ' . $this->accessToken,
				'Content-Type: application/json'
			),
		) );

		$response = curl_exec( $curl );

		curl_close( $curl );

		return $response;
	}

	function changeDateFormat( $date )
	{
		$timestamp = strtotime( $date );

		if ( $timestamp !== false ) {
			$response = date( "Y-m-d", $timestamp );
		} else {
			$response = "Invalid date format";
		}
		$this->logResponse( $response, 'updateCustomerMetaField' );

		return $response;
	}

	function getCustomers( $manager_id, $location_id, $page = false, $limit = 10 )
	{
		$args = [
			'locationId' => "gid://shopify/CompanyLocation/$location_id",
			'limit'      => intval( $limit ),
			'after'      => $page,
		];

		if ( empty( $args['after'] ) ) {
			unset( $args['after'] );
		}

		$q             = a_get_graphql( 'getLocationCustomers', $args );
		$response      = $this->postGraphQL( $q );
		$responce_data = json_decode( $response, true );
		$data          = $responce_data['data']['companyLocation']['roleAssignments'] ?? false;
		$edges         = $data['edges'] ?? false;
		$pageInfo      = $data['pageInfo'] ?? false;
		$customers     = [
			'customers'   => [],
			'hasNextPage' => $pageInfo['hasNextPage'],
			'endCursor'   => $pageInfo['endCursor']
		];
		$is_admin      = false;

		if ( $edges ) {
			foreach ( $edges as $role ) {
				if ( ! $is_admin && $role['node']['companyContact']['customer']['id'] == "gid://shopify/Customer/$manager_id" ) {
					$is_admin = $role['node']['role']['name'] == 'Location admin';

					if ( $is_admin ) {
//						continue;
					}
				}

				$customer   = $role['node']['companyContact']['customer'];
				$metafields = [];

				if ( isset( $customer['metafields']['edges'] ) ) {
					foreach ( $customer['metafields']['edges'] as $metafield ) {
						$node                       = $metafield['node'];
						$metafields[ $node['key'] ] = $node['value'];
					}
				}

				$customer['metafields']   = $metafields;
				$customer['is_admin']     = $is_admin;
				$customers['customers'][] = $customer;
			}
		}

//		if ( ! $is_admin ) {
//			$customers = [];
//		}

		return $customers;
	}
}
