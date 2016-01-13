<?php

GFForms::include_feed_addon_framework();

class GFMadMimi extends GFFeedAddOn {

	protected $_version = GF_MADMIMI_VERSION;
	protected $_min_gravityforms_version = '1.9.6.10';
	protected $_slug = 'gravityformsmadmimi';
	protected $_path = 'gravityformsmadmimi/madmimi.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Gravity Forms Mad Mimi Add-On';
	protected $_short_title = 'Mad Mimi';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_madmimi', 'gravityforms_madmimi_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_madmimi';
	protected $_capabilities_form_settings = 'gravityforms_madmimi';
	protected $_capabilities_uninstall = 'gravityforms_madmimi_uninstall';
	protected $_enable_rg_autoupgrade = true;

	protected $api = null;
	private static $_instance = null;

	public static function get_instance() {
		
		if ( self::$_instance == null )
			self::$_instance = new GFMadMimi();

		return self::$_instance;
		
	}
		
	/* Settings Page */
	public function plugin_settings_fields() {
						
		return array(
			array(
				'title'       => '',
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'              => 'email_address',
						'label'             => __( 'Email Address', 'gravityformsmadmimi' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' )
					),
					array(
						'name'              => 'api_key',
						'label'             => __( 'API Key', 'gravityformsmadmimi' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' )
					),
					array(
						'type'              => 'save',
						'messages'          => array(
							'success' => __( 'Mad Mimi settings have been updated.', 'gravityformsmadmimi' )
						),
					),
				),
			),
		);
		
	}

	/* Prepare plugin settings description */
	public function plugin_settings_description() {
		
		$description  = '<p>';
		$description .= sprintf(
			__( 'Mad Mimi makes it easy to send email newsletters to your customers, manage your subscriber lists, and track campaign performance. Use Gravity Forms to collect customer information and automatically add them to your Mad Mimi audience list. If you don\'t have a Mad Mimi account, you can %1$s sign up for one here.%2$s', 'gravityformsmadmimi' ),
			'<a href="http://www.madmimi.com/" target="_blank">', '</a>'
		);
		$description .= '</p>';
		
		if ( ! $this->initialize_api() ) {
			
			$description .= '<p>';
			$description .= sprintf(
				__( 'Gravity Forms Mad Mimi Add-On requires your account email address and API key, which can be found in the API tab on %1$sthe account page.%2$s', 'gravityformsmadmimi' ),
				'<a href="https://madmimi.com/user/edit?account_info_tabs=account_info_personal" target="_blank">', '</a>'
			);
			
			$description .= '</p>';
			
		}
				
		return $description;
		
	}

	/* Setup feed settings fields */
	public function feed_settings_fields() {	        

		$settings = array(
			array(
				'title' =>	'',
				'fields' =>	array(
					array(
						'name'           => 'feed_name',
						'label'          => __( 'Name', 'gravityformsmadmimi' ),
						'type'           => 'text',
						'class'          => 'medium',
						'required'       => true,
						'tooltip'        => '<h6>'. __( 'Name', 'gravityformsmadmimi' ) .'</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityformsmadmimi' )
					),
					array(
						'name'           => 'list',
						'label'          => __( 'Mad Mimi List', 'gravityformsmadmimi' ),
						'type'           => 'select',
						'required'       => true,
						'choices'        => $this->lists_for_feed_setting(),
						'tooltip'        => '<h6>'. __( 'Mad Mimi List', 'gravityformsmadmimi' ) .'</h6>' . __( 'Select which Mad Mimi list this feed will add contacts to.', 'gravityformsmadmimi' )
					),
					array(
						'name'           => 'fields',
						'label'          => __( 'Map Fields', 'gravityformsmadmimi' ),
						'type'           => 'field_map',
						'field_map'      => $this->fields_for_feed_mapping(),
						'tooltip'        => '<h6>'. __( 'Map Fields', 'gravityformsmadmimi' ) .'</h6>' . __( 'Select which Gravity Form fields pair with their respective Mad Mimi fields.', 'gravityformsmadmimi' )
					),
					array(
						'name'           => 'custom_fields',
						'label'          => __( 'Custom Fields', 'gravityformsmadmimi' ),
						'type'           => 'dynamic_field_map',
						'tooltip'        => '<h6>'. __( 'Custom Fields', 'gravityformsmadmimi' ) .'</h6>' . __( 'Select or create a new custom Mad Mimi field to pair with Gravity Forms fields. Any non-alphanumeric characters in custom field names will be converted to underscores. If multiple custom fields use the same name, only the last one using the same name will be exported to Mad Mimi.', 'gravityformsmadmimi' )
					),
					array(
						'name'           => 'feedCondition',
						'label'          => __( 'Opt-In Condition', 'gravityformsmadmimi' ),
						'type'           => 'feed_condition',
						'checkbox_label' => __( 'Enable', 'gravityformsmadmimi' ),
						'instructions'   => __( 'Export to Mad Mimi if', 'gravityformsmadmimi' ),
						'tooltip'        => '<h6>'. __( 'Opt-In Condition', 'gravityformsmadmimi' ) .'</h6>' . __( 'When the opt-in condition is enabled, form submissions will only be exported to Mad Mimi when the condition is met. When disabled, all form submissions will be exported.', 'gravityformsmadmimi' )

					)
				)
			)
		);

		return $settings;
	
	}

	/* Prepare fields for feed field mapping */
	public function fields_for_feed_mapping() {
		
		/* Setup initial field map */
		$field_map = array(
			array(	
				'name'       => 'email',
				'label'      => __( 'Email Address', 'gravityformsmadmimi' ),
				'required'   => true,
				'field_type' => array( 'email' )
			),
			array(	
				'name'       => 'firstname',
				'label'      => __( 'First Name', 'gravityformsmadmimi' ),
				'required'   => false
			),
			array(	
				'name'       => 'lastname',
				'label'      => __( 'Last Name', 'gravityformsmadmimi' ),
				'required'   => false
			),
			array(	
				'name'       => 'title',
				'label'      => __( 'Title', 'gravityformsmadmimi' ),
				'required'   => false
			),
			array(	
				'name'       => 'company',
				'label'      => __( 'Company', 'gravityformsmadmimi' ),
				'required'   => false
			),
			array(	
				'name'       => 'phone',
				'label'      => __( 'Phone Number', 'gravityformsmadmimi' ),
				'required'   => false
			),
			array(	
				'name'       => 'address',
				'label'      => __( 'Address', 'gravityformsmadmimi' ),
				'required'   => false
			),
			array(	
				'name'       => 'city',
				'label'      => __( 'City', 'gravityformsmadmimi' ),
				'required'   => false
			),
			array(	
				'name'       => 'state',
				'label'      => __( 'State', 'gravityformsmadmimi' ),
				'required'   => false
			),
			array(	
				'name'       => 'zip',
				'label'      => __( 'Zip Code', 'gravityformsmadmimi' ),
				'required'   => false
			),
			array(	
				'name'       => 'country',
				'label'      => __( 'Country', 'gravityformsmadmimi' ),
				'required'   => false
			),
		);
				
		return $field_map;
		
	}

	/* Setup feed list columns */
	public function feed_list_columns() {
		
		return array(
			'feed_name' => __( 'Name', 'gravityformsmadmimi' ),
			'list'      => __( 'Mad Mimi List', 'gravityformsmadmimi' )
		);
		
	}

	/* Hide "Add New" feed button if API credentials are invalid */		
	public function feed_list_title() {
		
		if ( $this->initialize_api() )
			return parent::feed_list_title();
			
		return sprintf( __( '%s Feeds', 'gravityforms' ), $this->get_short_title() );
		
	}

	/* Notify user to configure add-on before setting up feeds */
	public function feed_list_message() {

		$message = parent::feed_list_message();
		
		if ( $message !== false )
			return $message;

		if ( ! $this->initialize_api() )
			return $this->configure_addon_message();

		return false;
		
	}
	
	/* Feed list message for user to configure add-on */
	public function configure_addon_message() {
		
		$settings_label = sprintf( __( '%s Settings', 'gravityformsmadmimi' ), $this->get_short_title() );
		$settings_link  = sprintf( '<a href="%s">%s</a>', esc_url( $this->get_plugin_settings_url() ), $settings_label );

		return sprintf( __( 'To get started, please configure your %s.', 'gravityformsmadmimi' ), $settings_link );
		
	}

	/* Change value of list feed column to list name */
	public function get_column_value_list( $item ) {
			
		/* If Mad Mimi instance is not initialized, return campaign ID. */
		if ( ! $this->initialize_api() )
			return $item['meta']['list'];
		
		/* Get campaign and return name */
		$list = $this->api->get_list( $item['meta']['list'] );
		return ( is_null( $list ) ) ? $item['meta']['list'] : $list['name'];
		
	}

	/* Prepare lists for feed field */
	public function lists_for_feed_setting() {
			
		/* If Mad Mimi API instance is not initialized, return an empty array. */
		if ( ! $this->initialize_api() )
			return array();
		
		/* Get the lists */
		$lists = $this->api->lists();
		
		/* Add lists to the choices array */
		if ( ! empty( $lists ) ) {
			
			foreach ( $lists as $list ) {
				
				$choices[] = array(
					'label'		=>	$list['name'],
					'value'		=>	$list['id']
				);
				
			}
			
		}
		
		return $choices;
	}

	/* Process feed */
	public function process_feed( $feed, $entry, $form ) {
		
		$this->log_debug( __METHOD__ . '(): Processing feed.' );
		
		/* If Mad Mimi instance is not initialized, exit. */
		if ( ! $this->initialize_api() )
			return $entry;
		
		/* Prepare audience member import array. */
		$audience_member = array();
		
		/* If a list is chosen for this feed, add it to the audience member array. */
		$audience_member['add_list'] = $feed['meta']['list'];
				
		/* Find all fields mapped and push them to the audience member array. */
		foreach ( $this->get_field_map_fields( $feed, 'fields' ) as $field_name => $field_id ) {
			
			$field_value = $this->get_field_value( $form, $entry, $field_id );
							
			if ( ! rgblank( $field_value ) )
				$audience_member[$field_name] = $field_value;
			
		}
		
		/* If email address is empty, return. */
		if ( rgblank( $audience_member['email'] ) ) {
			
			$this->log_error( __METHOD__ . '(): Email address not provided.' );
			return;			
			
		}
		
		/* Push any custom fields to the audience member array. */
		if ( ! empty( $feed['meta']['custom_fields'] ) ) {
			
			foreach ( $feed['meta']['custom_fields'] as $custom_field ) {
				
				/* If field map field is not paired to a form field, skip. */
				if ( rgblank( $custom_field['value'] ) )
					continue;
					
				$field_value = $this->get_field_value( $form, $entry, $custom_field['value'] );
				
				if ( ! rgblank( $field_value ) ) {
					
					$field_name = ( $custom_field['key'] == 'gf_custom' ) ? $custom_field['custom_key'] : $custom_field['key'];
					$audience_member[$field_name] = $field_value;
					
				}
				
			}
			
		}

		/* Check if audience member already exists. */
		$this->log_debug( __METHOD__ . "(): Checking to see if {$audience_member['email']} is already on the list." );
		$member_search = $this->api->search( $audience_member['email'] );
		$member_exists = ( $member_search['success'] && $member_search['result']['count'] > 0 );
		
		/* If the audience member exists, add them to the list and update information. If the audience member does not exist, add audience member.  */
		if ( $member_exists ) {
			
			/* Fork audience member array to remove email and list information. */
			$updated_info = $audience_member;
			$updated_info['first_name'] = $updated_info['firstname'];
			$updated_info['last_name'] = $updated_info['lastname'];
			unset( $updated_info['add_list'] );
			unset( $updated_info['email'] );
			unset( $updated_info['firstname'] );
			unset( $updated_info['lastname'] );
			
			/* If a list is chosen, check if they exist on list. */
			if ( isset( $audience_member['add_list'] ) ) {

				$is_member_on_list = $this->is_member_on_list( $audience_member['email'], $audience_member['add_list'], $member_search );

				if ( ! $is_member_on_list ) {
								
					$this->log_debug( __METHOD__ . "(): {$audience_member['email']} exists, but is not on list; adding audience member to list." );
					$this->api->add_membership( $audience_member['add_list'], $audience_member['email'], $updated_info );

				} else {
					
					$this->log_debug( __METHOD__ . "(): {$audience_member['email']} exists on list; updating info." );
					$this->api->update_member( $audience_member['email'], $updated_info );
					
				}
				
			} else {
				
				$this->log_debug( __METHOD__ . "(): {$audience_member['email']} exists; updating info." );
				$this->api->update_member( $audience_member['email'], $updated_info );
				
			}
									
		} else {
			
			$this->log_debug( __METHOD__ . "(): {$audience_member['email']} does not exist; adding audience member to list." );
			
			$this->api->add_member( $audience_member );
						
		}
		
		return $entry;
									
	}
	
	/* Check if audience member is on a specific audience list. */
	public function is_member_on_list( $email, $list, $search_results = null ) {
		
		/* If Mad Mimi instance is not initialized, exit. */
		if ( ! $this->initialize_api() )
			return false;
		
		/* If search results are not provided, do a search. */
		if ( is_null( $search_results ) )
			$search_results = $this->api->search( $email );
		
		/* If search was not a success or result count is 0, return false. */
		if ( ! $search_results['success'] || ( $search_results['success'] && $search_results['result']['count'] == 0 ) )
			return false;
		
		/* Loop through results until member is found. */
		foreach ( $search_results['result']['audience'] as $member ) {
			
			if ( $member['email'] == $email ) {
				
				/* If the member is not subscribed to any lists, return false. */
				if ( empty( $member['lists'] ) ) {
					return false;
				}
				
				foreach ( $member['lists'] as $_list ) {
					
					if ( $_list['name'] == $list ) {
						return true;
					}
					
				}
				
			}
			
		}
		
		/* If member was not in search results, return false. */
		return false;
		
	}

	/* Checks validity of Mad Mimi API credentials and initializes API if valid. */
	public function initialize_api() {
			
		if ( ! is_null( $this->api ) )
			return true;

		/* Load the API library. */
		require_once( 'includes/class-madmimi.php' );

		/* Get plugin settings */
		$settings = $this->get_plugin_settings();
		
		/* If the API key or email address is not set, do not run a validation check. */
		if ( rgblank( $settings['api_key'] ) || rgblank( $settings['email_address'] ) )
			return null;

		$this->log_debug( __METHOD__ . "(): Validating API info for {$settings['email_address']} / {$settings['api_key']}." );

		$mad_mimi = new MadMimi( $settings['email_address'], $settings['api_key'] );

		/* Attempt to request a list of promotions and return API credential validation based on response. */
		if ( $mad_mimi->promotions() === 'Unable to authenticate' ) {
		
			/* Log that test failed. */
			$this->log_error( __METHOD__ . '(): API credentials are invalid.' );
			
			return false;			
			
		} else {
			
			/* Log that test passed. */
			$this->log_debug( __METHOD__ . '(): API credentials are valid.' );
	
			/* Assign Mad Mimi object to the class. */
			$this->api = $mad_mimi;
	
			return true;
			
		}

					
	}
	
}
