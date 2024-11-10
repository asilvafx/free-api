<?php

//! Post controller
class PostController {

	protected
		$db;

	//! HTTP route pre-processor
	function beforeroute($f3) {  
	}

	//! HTTP route post-processor
	function afterroute($f3) {    
        
	}

	//! Instantiate class
	function __construct() {
		$f3=Base::instance();     
	}

}
