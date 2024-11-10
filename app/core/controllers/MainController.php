<?php

//! Main controller
class MainController {

	protected
		$db;

	//! HTTP route pre-processor
	function beforeroute($f3) { 
 
		// Get server session auto_logout time
		if($f3->get('SESSION.loggedin'))
		{ 
			$logout_time = $f3->get('auto_logout');
			// Check if remember me true, set logout_time = 5 days
			if($f3->get('SESSION.rememberme')){ $logout_time = "432000"; }
			if(time() - $f3->get('SESSION.timestamp') > $logout_time) 
			{
				// Clear user session and redirect to login page
				$f3->set('SESSION.loggedin', false);
        		$f3->reroute('/'.$f3->get('SITE.uri_auth'));
			} 
			else { 
				// Set timer
				$f3->set('SESSION.timestamp', time());
			}
		}      

	}

	//! HTTP route post-processor
	function afterroute($f3) { 

		// Store CSRF in user session
		$f3->set('SESSION.csrf', $f3->get('CSRF'));
		// Store current time  
		$f3->set('time', time());  
		// Render HTML layout  
		$index_render = 'index.htm';
		if(!file_exists($f3->get('UI').$index_render)){ 
			$index_render = 'index.html';
		}
		if(!file_exists($f3->get('UI').$index_render)){
			echo 'Invalid index file.'; die;
		}
		echo Template::instance()->render($index_render); 
	}

	//! Instantiate class
	function __construct() {
		$f3=Base::instance();  

		$csrf = md5(uniqid(mt_rand(), true));
		$f3->set('CSRF', $csrf); 
 
	}

}
