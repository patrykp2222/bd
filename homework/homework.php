<?php
/**
 * Plugin Name: Homework management
 * Plugin URI: no uri - sorry
 * Description: Enables to send mails
 * Version: 3.0
 * Author: Patryk Uchman
 * Author URI: no uri
 * Network: Optional. Whether the plugin can only be activated network wide. Example: true
 * License: A short license name. Example: GPL2
 */

 add_action( 'init', 'register_shortcode_homework');
 
function register_shortcode_homework(){
   add_shortcode('add_homework', 'add_homework');
}

function add_homework(){
	global $wpdb;
	
		if(current_user_can( 'manage_options' )){
			echo "<form name='taskForm' method='POST'>
				<textarea rows = '10' cols = '100' name = 'task'></textarea><br/>
				Deadline date: <input type='text' name='dateEnd' placeholder='yy-mm-dd'><br/>
				Deadline time: <input type='text' name='timeEnd' placeholder='hh:mm:ss'><br/>
				<input type = 'submit' name='btnAddHomework' value = 'Post Homework'><br/>
				Note that deadline date and time must be in the format specified in the hint!<br/><br/>
			</form>";

			
			if($_SERVER["REQUEST_METHOD"] == "POST"){
				if (isset($_POST['btnAddHomework'])){
						$task = $_POST['task'];
						$dateEnd = $_POST['dateEnd'];
						$timeEnd = $_POST['timeEnd'];					
						$fulldate = $dateEnd . " " . $timeEnd;
						
						
						
						$myCal = $wpdb->prefix . 'my_calendar';
						$myCalEvents = $wpdb->prefix . "my_calendar_events";
						
			
						$wpdb->query("INSERT INTO $myCal(event_begin,event_end,event_title,event_desc,event_short,event_open,event_time,event_endtime,event_recur,event_repeats,event_status,event_approved) 
						VALUES ('$dateEnd','$dateEnd','pracadomowa','Termin oddawania pracy domowej','$task',2,'$timeEnd','$timeEnd','S1',0,1,1); ");
						$lastID = $wpdb->insert_id;
						$wpdb->query("INSERT INTO $myCalEvents(occur_event_id,occur_begin,occur_end,occur_group_id) VALUES($lastID,'$fulldate','$fulldate',0); ");
						
						
						//$pdtable = $wpdb->prefix . 'pracadomowa';
						//$sql = "INSERT INTO $pdtable(tresc) VALUES ('$task')";
					
				}
			}
		}
		$pdtable = $wpdb->prefix . 'my_calendar';
		$sqlhomework = $wpdb->get_results("SELECT * FROM $pdtable WHERE event_title = 'pracadomowa' ORDER BY event_id;");
		
		$homework = "<form method='post'>";
		$homework .= "Homework : <select name = 'taskOption'>";
		$i = 1;
		foreach($sqlhomework as $task){
			$homework .= "<option value = ".$task->event_id . ">Zadanie ". $i++ . "</option>";
		}
		$homework .= "</select>&nbsp&nbsp&nbsp&nbsp&nbsp<input type = 'submit' name = 'btnDisplay' value='Display Homework'>&nbsp&nbsp&nbsp&nbsp&nbsp";
		if(current_user_can( 'manage_options' )){
			$homework .= "<input type = 'submit' name = 'btnDelete' value='Delete Homework'>";
		}
		$homework .= "</form>";
		echo $homework;
		
		if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['taskOption'])){
			$selectOption = $_POST['taskOption'];
			if (isset($_POST['btnDisplay'])) {
				$resultHomework = $wpdb->get_results("SELECT * FROM $pdtable  WHERE event_id = $selectOption;");
				
				foreach($resultHomework as $row){
					$rowID = $row->event_id;
					$num = $wpdb->get_results("SELECT COUNT(event_id) as num FROM $pdtable WHERE event_title = 'pracadomowa' and event_id <= $rowID;");
					echo "<br/><strong>Zadanie " . $num[0]->num . "</strong><br/>" . $row->event_short . "<br/><br/><b>DEADLINE: " . $row->event_end . " " . $row->event_time . "</b><br/>";
				}
			}else if(isset($_POST['btnDelete'])){
				$wpdb->get_results("DELETE FROM $pdtable WHERE event_id = $selectOption");
			}
		}
		echo "<br/>";
		
		if(!current_user_can( 'manage_options' )){
			$homework2 = "<form method='post'>";
			$homework2 .= "Choose homework you want to send:<br/><select name = 'taskOption2'>";
			$date = date('Y-m-d h:i:s a', time()+3600);
			$dateParts = explode(" ",$date);
			
			$i = 1;
			foreach($sqlhomework as $task){
				$homework2 .= "<option value = ".$task->event_id . ">Zadanie ". $i++ . "</option>";
			}
			$homework2 .= "</select>&nbsp&nbsp&nbsp&nbsp&nbsp<input type = 'submit' name = 'btnSend' value='Send Homework'>";
			$homework2 .= "<textarea rows = '10' cols = '100' name = 'solution'></textarea></form>";
			echo $homework2;
			
			if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['taskOption2'])){
				if (isset($_POST['btnSend'])) {
					
					$homeID = $_POST['taskOption2'];
					$sqlhomework2 = $wpdb->get_results("SELECT event_id FROM $pdtable WHERE (event_title = 'pracadomowa') AND (event_end > '$dateParts[0]' OR (event_end = '$dateParts[0]' AND event_endtime > '$dateParts[1]')) AND (event_id = $homeID);");
					if($sqlhomework2){
						$homeExeNr = $wpdb->get_results("SELECT COUNT(event_id) as num FROM $pdtable WHERE event_title = 'pracadomowa' and event_id <= $homeID;");
					
						$current_user = wp_get_current_user();
					
						$sub = "Homework exercise nr: " . $homeExeNr[0]->num;
						$mes = $_POST['solution'];
						$mes .= " \r\nFirst name: " . $current_user->user_firstname . "\r\nLast name: " . $current_user->last_name . 
							"\r\nDisplay name: " . $current_user->display_name . "\r\nEmail: " . $current_user->user_email;  
					
						$wp_users = $wpdb->prefix . 'users';
						$users = $wpdb->get_results("SELECT id, user_email FROM $wp_users;");
						foreach($users as $u){
							$userdata = get_userdata($u->id);
							$capabilities = $userdata->{$wpdb->prefix . 'capabilities'};
							if(!isset($wp_roles))
								$wp_roles = new WP_Roles();
								
							foreach ( $wp_roles->role_names as $role => $name ){

								if ( array_key_exists( $role, $capabilities ) ){
									if($role == 'administrator'){
										$to = $u->user_email;
										$status = wp_mail($to, $sub, $mes);
										if($status) echo "Homework sent";
										else echo "Homework not sent. Error";
									}
								}
							}		
						}
					}else echo "Sorry. Too late! ";
						
				}
			}
		}
		
		
		
}
?>
