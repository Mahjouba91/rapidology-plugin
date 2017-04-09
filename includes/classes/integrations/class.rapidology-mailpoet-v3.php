<?php

if ( ! class_exists( 'RAD_Dashboard' ) ) {
	require_once( RAD_RAPIDOLOGY_PLUGIN_DIR . 'rapidology.php' );
}

class rapidology_mailpoet_v3 extends RAD_Rapidology {
	
	public function __contruct() {
		parent::__construct();
		$this->permissionsCheck();
	}
	
	public function draw_ontraport_form( $form_fields, $service, $field_values ) {
		
	}
	
	/**
	 * Retrieves the lists from MailPoet table and updates the data in DB.
	 * @return string
	 */
	function get_mailpoet_lists( $name ) {
		$lists = array();
		
		if ( ! class_exists( 'MailPoet\Models\Segment' ) ) {
			$error_message = __( 'MailPoet v3 plugin is not installed or not activated', 'rapidology' );
		} else {
			$all_lists_array      = \MailPoet\Models\Segment::getSegmentsWithSubscriberCount();
			$error_message = 'success';
			
			if ( ! empty( $all_lists_array ) ) {
				foreach ( $all_lists_array as $list_details ) {
					$lists[ $list_details['id'] ]['name'] = $list_details['name'];
					
					$lists[ $list_details['id'] ]['subscribers_count'] = $list_details['subscribers'];
					
					$lists[ $list_details['id'] ]['growth_week'] = $this->calculate_growth_rate( 'mailpoet-v3_' . $list_details['id'] );
				}
			}
			
			$this->update_account( 'mailpoet-v3', $name, array(
				'lists'         => $lists,
				'is_authorized' => esc_html( 'true' ),
			) );
		}
		
		return $error_message;
	}
	
	/**
	 * Subscribes to MailPoet list. Returns either "success" string or error message.
	 * @return string
	 */
	function subscribe_mailpoet( $list_id, $email, $name = '', $last_name = '' ) {
		global $wpdb;
		$table_user       = $wpdb->prefix . 'mailpoet_subscribers';
		$table_user_lists = $wpdb->prefix . 'mailpoet_subscriber_segment';
		
		if ( ! class_exists( 'MailPoet\Models\Subscriber' ) ) {
			$error_message = __( 'MailPoet v3 plugin is not installed or not activated', 'rapidology' );
		} else {
			$sql_count = "SELECT COUNT(*) FROM $table_user WHERE email = %s";
			$sql_args  = array(
				$email,
			);
			
			$subscribers_count = $wpdb->get_var( $wpdb->prepare( $sql_count, $sql_args ) );
			
			if ( 0 == $subscribers_count ) {
				
				$subscriber = \MailPoet\Models\Subscriber::subscribe( array(
					'email'      => $email,
					'first_name' => $name,
					'last_name'  => $last_name,
				), array( $list_id ) );
				
				if ( false === $subscriber->getErrors() ) {
					$error_message = 'success';
				} else {
					$error_message = $subscriber->getErrors();
				}
			} else {
				$error_message = __( 'Already Subscribed', 'rapidology' );
			}
		}
		
		return $error_message;
	}
}