<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
* Hybrid_Providers_Usosweb provider adapter based on OAuth1 protocol
* Adapter to Usosweb API by Henryk Michalewski
*/

class Hybrid_Providers_Usosweb extends Hybrid_Provider_Model_OAuth1
{
	/**
	* IDp wrappers initializer 
	*/
	/* Required scopes. The only functionality of this application is to say hello,
    * so it does not really require any. But, if you want, you may access user's
    * email, just do the following:
    * - put array('email') here,
    * - append 'email' to the 'fields' argument of 'services/users/user' method,
    *   you will find it below in this script.
    */
	
	function initialize()
	{
		parent::initialize();
		
        $scopes = array('email','studies');

		// Provider api end-points 
		$this->api->api_base_url      = "https://usosapps.uw.edu.pl/";
		$this->api->request_token_url = "https://usosapps.uw.edu.pl/services/oauth/request_token?scopes=".implode("|", $scopes);
		$this->api->access_token_url  = "https://usosapps.uw.edu.pl/services/oauth/access_token";
		$this->api->authorize_url = "https://usosapps.uw.edu.pl/services/oauth/authorize";

	}
	
	
    /**
	* begin login step 
	*/
	function loginBegin()
	{
		$tokens = $this->api->requestToken( $this->endpoint ); 

		// request tokens as received from provider
		$this->request_tokens_raw = $tokens;
		
		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 5 );
		}

		if ( ! isset( $tokens["oauth_token"] ) ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid oauth_token.", 5 );
		}

		$this->token( "request_token"       , $tokens["oauth_token"] ); 
		$this->token( "request_token_secret", $tokens["oauth_token_secret"] ); 

		# redirect the user to the provider authentication url
		Hybrid_Auth::redirect( $this->api->authorizeUrl( $tokens ) );
	}
		

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$response = $this->api->get( 'https://usosapps.uw.edu.pl/services/users/user?fields=id|first_name|last_name|sex|homepage_url|profile_url|email' );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		if ( ! is_object( $response ) || ! isset( $response->id ) ){
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}

		# store the user profile. 
		# written without a deeper study what is really going on in Usosweb API
		 
		$this->user->profile->identifier  = (property_exists($response,'id'))?$response->id:"";
		$this->user->profile->displayName = (property_exists($response,'first_name') && property_exists($response,'last_name'))?$response->first_name." ".$response->last_name:"";
		$this->user->profile->lastName   = (property_exists($response,'last_name'))?$response->last_name:""; 
		$this->user->profile->firstName   = (property_exists($response,'first_name'))?$response->first_name:""; 
        $this->user->profile->gender = (property_exists($response,'sex'))?$response->sex:""; 
		$this->user->profile->profileURL  = (property_exists($response,'profile_url'))?$response->profile_url:"";
		$this->user->profile->webSiteURL  = (property_exists($response,'homepage_url'))?$response->homepage_url:""; 
		$this->user->profile->email = (property_exists($response,'email'))?$response->email:""; 
		
		global $wpdb;

		$date = date('Y-m-d', time() + 3600);
		$subjectCode = "1000-213bBAD";
		$termID = "2014Z";
		$courseUnitId = "258822";
		$groupNumber = "3";
		$lectureID = "258821";
		$lectureGroupNr = 1;
		
		//https://usosweb.uw.edu.pl/kontroler.php?_action=actionx:katalog2/przedmioty/pokazZajecia(zaj_cyk_id:258821;gr_nr:1)
		
		//lista wszystkich zajec dotyczacych danego kursu(np. bazy danych)
		$response1 = $this->api->get( 'https://usosapps.uw.edu.pl/services/tt/course_edition?course_id='. $subjectCode . '&term_id=' . $termID . '&start=' . $date . '&days=7&fields=start_time|end_time|name');
		
		//nazwa kursu
		//$response2 = $this->api->get( 'https://usosapps.uw.edu.pl/services/courses/course?course_id=' . $subjectCode . '&fields=id|name');
		
		//informacje o grupie(np. laboratoryjnej,cwiczeniowej)- nazwa prowadzacego, lista studentow
		//$response3 = $this->api->get( 'https://usosapps.uw.edu.pl/services/groups/group?course_unit_id=' . $courseUnitId . '&group_number=' . $groupNumber . '&fields=course_unit_id|group_number|class_type|lecturers|participants');
		
		//termin grupy (np. laboratoryjnej,cwiczeniowej) ww.
		$response4 = $this->api->get( 'https://usosapps.uw.edu.pl/services/tt/classgroup?unit_id='. $courseUnitId . '&group_number=' . $groupNumber . '&term_id=' . $termID . '&start=' . $date . '&days=7');

		
		/*
		//lista prowadzacych + koordynator danego kursu
		$response5 = $this->api->get( 'https://usosapps.uw.edu.pl/services/courses/course_edition?course_id='. $subjectCode . '&term_id=' . $termID . '&fields=course_id|course_name|coordinators|lecturers');

		
		//nazwa wykladowcy
		$response6 = $this->api->get( 'https://usosapps.uw.edu.pl/services/groups/group?course_unit_id=' . $lectureID . '&group_number=' . $lectureGroupNr . '&fields=lecturers');

		
		//lista prowadzacych + koordynator danego kursu
		$response8 = $this->api->get( 'https://usosapps.uw.edu.pl/services/tt/course_edition?course_id='. $subjectCode . '&term_id=' . $termID );

		*/
		
		//termin wykladu
		$response7 = $this->api->get( 'https://usosapps.uw.edu.pl/services/tt/classgroup?unit_id='. $lectureID . '&group_number=' . $lectureGroupNr . '&term_id=' . $termID . '&start=' . $date . '&days=7');
		
		
		//patterns has to be en name for the event saved in Usos(e.g. 'Lecture' instead of 'Wykład')
		function insertEvents($resp, $pattern){
			global $wpdb;
			$myCal = $wpdb->prefix . 'my_calendar';
			$myCalEvents = $wpdb->prefix . 'my_calendar_events';
			
			
			$res = "(SELECT event_id FROM $myCal WHERE event_title = '$pattern')";
			$wpdb->query("DELETE FROM $myCalEvents WHERE occur_event_id in $res;");
			
			$wpdb->query("DELETE FROM $myCal WHERE event_title = '$pattern';");
			
			foreach($resp as $activity){
				$name = $activity->name->{'en'};
				if(strpos($name,$pattern) === 0 || strpos($name,$pattern) == TRUE){

					$start = $activity->{'start_time'};
					$end = $activity->{'end_time'};
					
					$startPieces = explode(" ",$start);		
					$endPieces = explode(" ",$end);
					
					$wpdb->query("INSERT INTO $myCal(event_begin,event_end,event_title,event_open,event_time,event_endtime,event_recur,event_repeats,event_status,event_approved) 
					VALUES ('$startPieces[0]','$endPieces[0]','$pattern',2,'$startPieces[1]','$endPieces[1]','S1',0,1,1); ");
					$lastID = $wpdb->insert_id;
					$wpdb->query("INSERT INTO $myCalEvents(occur_event_id,occur_begin,occur_end,occur_group_id) VALUES($lastID,'$start','$end',0); ");
					
				}
			}
		}
		insertEvents($response1,"Lecture");
		insertEvents($response4,"Lab");
		
		return $this->user->profile;
 	}
}
?>
