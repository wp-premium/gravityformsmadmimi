<?php
	
	class MadMimi {
		
		protected $api_url = 'https://api.madmimi.com';
		
		function __construct( $username, $api_key ) {
			
			$this->username = $username;
			$this->api_key = $api_key;
			
		}
		
		/**
		 * Set default options for API requests.
		 * 
		 * @access public
		 * @return array
		 */
		function default_options() {
			
			return array(
				'username' => $this->username,
				'api_key'  => $this->api_key
			);
			
		}
		
		/**
		 * Make API request.
		 * 
		 * @access public
		 * @param string $path
		 * @param array $options
		 * @param bool $return_status (default: false)
		 * @param string $method (default: 'GET')
		 * @return void
		 */
		function make_request( $path, $options, $method = 'GET' ) {
			
			/* Build request options string. */
			$request_options = ( ( $method == 'GET' ) ? '?' : null ) . http_build_query( $options );
			
			/* Build request URL. */
			$request_url = $this->api_url . $path . ( ( $method == 'GET' ) ? $request_options : null );
			
			/* Initialize cURL session. */
			$curl = curl_init();
			
			/* Setup cURL options. */
			curl_setopt( $curl, CURLOPT_URL, $request_url );
			curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Expect:', 'Accept: application/json' ) );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
			
			/* If this is a POST request, pass the request options via cURL option. */
			if ( $method == 'POST' ) {
				
				curl_setopt( $curl, CURLOPT_POST, true );
				curl_setopt( $curl, CURLOPT_POSTFIELDS, $request_options );
				
			}

			/* If this is a PUT request, pass the request options via cURL option. */
			if ( $method == 'PUT' ) {
				
				curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'PUT' );
				curl_setopt( $curl, CURLOPT_POSTFIELDS, $request_options );
				
			}
			
			/* Execute cURL request. */
			$curl_result = curl_exec( $curl );
			
			/* If there is an error, die with error message. */
			if ( $curl_result === false ) {
				
				die( 'cURL error: '. curl_error( $curl ) );
				
			}
			
			/* Close cURL session. */
			curl_close( $curl );
			
			/* Attempt to decode JSON. If isn't JSON, return raw cURL result. */
			$json_result = json_decode( $curl_result, true );		
			return ( json_last_error() == JSON_ERROR_NONE ) ? $json_result : $curl_result;
			
		}
		
		/**
		 * Build CSV string from array.
		 * 
		 * @access public
		 * @param array $array
		 * @return string $csv
		 */
		function build_csv( $array ) {
			
			/* Start CSV string. */
			$csv = '';
			
			/* Get column headers */
			$headers = array_keys( $array );
			
			/* Add headers to CSV string. */
			for ( $i = 0; $i < count( $headers ); $i++ ) {
				
				$csv .= $this->escape_for_csv( $headers[$i] ) . ( ( ( count( $headers ) - 1 ) > $i ) ? ',' : '' );
				
			}
			
			/* Add line break. */
			$csv .= "\n";
			
			/* Get array values */
			$values = array_values( $array );
			
			/* Add array values to CSV string. */
			for ( $i = 0; $i < count( $values ); $i++ ) {
				
				$csv .= $this->escape_for_csv( $values[$i] ) . ( ( ( count( $values ) - 1 ) > $i ) ? ',' : '' );
				
			}
			
			/* Add line break. */ 
			$csv .= "\n";
			
			/* Return CSV string. */
			return $csv;
			
		}
		
		/**
		 * Escape string for CSV value.
		 * 
		 * @access public
		 * @param string $value
		 * @return string $value
		 */
		function escape_for_csv( $value ) {
			
			/* Wrap quotes in quotes. */
			$value = str_replace( '"', '""', $value );
			
			if ( preg_match( '/,/', $value ) || preg_match( '/"/', $value ) || preg_match( "/\n/", $value ) ) {

				return '"'. $value .'"';
				
			} else {
				
				return $value;
				
			}
			
		}
		
		/**
		 * Add audience member.
		 * 
		 * @access public
		 * @param mixed $member
		 * @return void
		 */
		function add_member( $member ) {
			
			return $this->import( $this->build_csv( $member ) );
			
		}

		/**
		 * Add an audience member to a list.
		 * 
		 * @access public
		 * @param string $list_name
		 * @param string $email_address
		 * @param array $additional_parameters (default: array())
		 * @return void
		 */
		function add_membership( $list_name, $email_address, $additional_parameters = array() ) {
			
			$request_options = array_merge( array( 'email' => $email_address ), $additional_parameters, $this->default_options() );
			return $this->make_request( '/audience_lists/'. rawurlencode( $list_name ) .'/add', $request_options, 'POST' );
			
		}

		/**
		 * Create a new audience list.
		 * 
		 * @access public
		 * @param mixed $list_name
		 * @return void
		 */
		function create_list( $list_name ) {
			
			$request_options = array_merge( array( 'name' => $list_name ), $this->default_options() );
			return $this->make_request( '/audience_lists', $request_options, 'POST' );
			
		}
		
		/**
		 * Delete an audience list.
		 * 
		 * @access public
		 * @param mixed $list_name
		 * @return void
		 */
		function delete_list( $list_name ) {
			
			$request_options = array_merge( array( '_method' => 'delete' ), $this->default_options() );
			return $this->make_request( '/audience_lists/'. rawurlencode( $list_name ), $request_options, 'POST' );
			
		}

		/**
		 * Get an audience list.
		 * 
		 * @access public
		 * @param mixed $list
		 * @return void
		 */
		function get_list( $list ) {
			
			/* Get all audience lists. */
			$lists = $this->lists();
			
			if ( ! empty( $lists ) ) {
				
				foreach ( $lists as $_list ) {
					
					if ( is_numeric( $list ) && $_list['id'] == $list ) {
						return $_list;
					}

					if ( ! is_numeric( $list ) && $_list['name'] == $list ) {
						return $_list;
					}
					
				}
				
			}
			
			/* List was not found, so return null. */
			return null;
			
		}

		/**
		 * Import audience members.
		 * 
		 * @access public
		 * @param mixed $data
		 * @return void
		 */
		function import( $data ) {
			
			$request_options = array_merge( array( 'csv_file' => $data ), $this->default_options() );
			return $this->make_request( '/audience_members', $request_options, 'POST' );
			
		}

		/**
		 * Get all audience lists.
		 * 
		 * @access public
		 * @return void
		 */
		function lists() {
			
			return $this->make_request( '/audience_lists/lists.json', $this->default_options() );
			
		}
		
		/**
		 * Retrieve all the lists an audience member belongs to.
		 * 
		 * @access public
		 * @param mixed $email_address
		 * @return void
		 */
		function memberships( $email_address ) {
			
			return $this->make_request( '/audience_members/'. urlencode( $email_address ) .'/lists.json', $this->default_options() );
			
		}
		
		/**
		 * Get all promotions.
		 * 
		 * @access public
		 * @param int $page (default: 1)
		 * @return void
		 */
		function promotions( $page = 1 ) {
			
			$request_options = array_merge( array( 'page' => $page ), $this->default_options() );
			return $this->make_request( '/promotions.json', $request_options );
			
		}
		
		/**
		 * Remove audience member.
		 * 
		 * @access public
		 * @param mixed $email_address
		 * @param mixed $list
		 * @return void
		 */
		function remove_member( $email_address, $list ) {
			
			$request_options = array_merge( array( 'email' => $email_address ), $this->default_options() );
			return $this->make_request( '/audience_lists/'. rawurlencode( $list ) .'/remove', $request_options, 'POST' );
			
		}
	
		/**
		 * Remove an audience member to a list.
		 * 
		 * @access public
		 * @param string $list_name
		 * @param string $email_address
		 * @return void
		 */
		function remove_membership( $list_name, $email_address ) {
			
			$request_options = array_merge( array( 'email' => $email_address ), $this->default_options() );
			return $this->make_request( '/audience_lists/'. rawurlencode( $list_name ) .'/remove', $request_options, 'POST' );
			
		}

		/**
		 * Search for audience members. Returns first 100 
		 * results unless $raw is set to true.
		 * 
		 * @access public
		 * @param string $query
		 * @param bool $raw (default: false)
		 * @return void
		 */
		function search( $query, $raw = false ) {
			
			$request_options = array_merge( array( 'query' => $query, 'raw' => $raw ), $this->default_options() );
			return $this->make_request( '/audience_members/search.json', $request_options );
			
		}

		/**
		 * Update audience member.
		 * 
		 * @access public
		 * @param mixed $email_address
		 * @param mixed $fields
		 * @return void
		 */
		function update_member( $email_address, $fields ) {
			
			$request_options = array_merge( array( 'audience_member' => $fields ), $this->default_options() );
			return $this->make_request( '/audience_members/'. rawurlencode( $email_address ), $request_options, 'PUT' );
			
		}

		
	}
	