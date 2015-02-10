<?php
/**
 * Plugin Name: Mail Sender.
 * Plugin URI: no uri - sorry
 * Description: Enables to send mails
 * Version: 3.0
 * Author: Patryk Uchman
 * Author URI: no uri
 * Network: Optional. Whether the plugin can only be activated network wide. Example: true
 * License: A short license name. Example: GPL2
 */

 add_action( 'init', 'register_shortcode_mail');
 
function register_shortcode_mail(){
   add_shortcode('mail-sender', 'mail_sender_function');
}

function mail_sender_function(){
	
	echo "<form name='myForm' method='POST'>
		Subject: <input type='text' name='subject'>
		<textarea rows = '10' cols = '100' name = 'message'>
		</textarea>
		<input type = 'Submit' name='btn' value = 'Wyślij'>
	</form>";
	
	if($_SERVER["REQUEST_METHOD"] == "POST"){
		$sub = $_POST['subject'];
		$mes = $_POST['message'];
		
		global $wpdb;
		$wp_users = $wpdb->prefix . 'users';
		$users = $wpdb->get_results("SELECT id, user_email FROM $wp_users;");
	
		$current_user = wp_get_current_user();
		$mes .= " \r\nFirst name: " . $current_user->user_firstname . "\r\nLast name: " . $current_user->last_name . 
							"\r\nDisplay name: " . $current_user->display_name . "\r\nEmail: " . $current_user->user_email;  
		if(current_user_can( 'manage_options' )){
			foreach($users as $u){
				//echo "$u->id $u->user_email <br/>";
				$userdata = get_userdata($u->id);
				$capabilities = $userdata->{$wpdb->prefix . 'capabilities'};
				if(!isset($wp_roles))
					$wp_roles = new WP_Roles();
					
				foreach ( $wp_roles->role_names as $role => $name ){

					if ( array_key_exists( $role, $capabilities ) ){
						if($role != 'administrator'){
							$to = $u->user_email;
							$status = wp_mail($to, $sub, $mes);
							if($status) echo "MAIL SENT";
							else echo "Error sending mail";
						}
					}
				}
			}
		}else {
			foreach($users as $u){
				//echo "$u->id $u->user_email <br/>";
				$userdata = get_userdata($u->id);
				$capabilities = $userdata->{$wpdb->prefix . 'capabilities'};
				if(!isset($wp_roles))
					$wp_roles = new WP_Roles();
					
				foreach ( $wp_roles->role_names as $role => $name ){

					if ( array_key_exists( $role, $capabilities ) ){
						if($role == 'administrator'){
							$to = $u->user_email;
							$status = wp_mail($to, $sub, $mes);
							if($status) echo "MAIL SENT";
							else echo "Error sending mail";
						}
					}
				}
			
			}
		}
		
		
	}
}

?>