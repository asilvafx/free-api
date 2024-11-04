<?php

class Backend extends MainController  {
    
    function Base($f3,$args) { 
        
        if($f3->get('SESSION.loggedin') !== true){
            $f3->reroute('/'.$f3->get('SITE.uri_auth'));
        }

        $slug=empty($args[0])?'':$args[0];

        $admin_uri = '/'.$f3->get('SITE.uri_backend');

        if (str_contains($slug, $admin_uri)) { 
            $slug = str_replace($admin_uri, '',$slug);
        } 

        // Load user information
        $userClass = new User;
        $userClass->Info($f3);  

        $content = "/dashboard";
        $ui = 'app/views/admin/';

        $f3->set('UI', $ui);   

        if(file_exists($ui.'routes'.$slug.'/view.htm')){
            $content = $slug;
        } 

		if(file_exists($ui.'index.php')){ 
			require_once($ui.'index.php');
		} 
 
        if (file_exists($ui.'routes'.$content.'/view.php')) { 
            require_once($ui.'routes'.$content.'/view.php');
        } 

        $f3->set('CONTENT', $content);  
 
    } 
   
    
}