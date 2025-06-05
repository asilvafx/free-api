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

        global $db;
        $user_role = $f3->get('CXT')->role;
        $roles = new DB\SQL\Mapper($db, 'roles');
        $roles->load(array('id=?', $user_role));

        $user_access = $roles->access . ',restricted';
        $f3->set('USER.access', $user_access);

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

        if (file_exists($ui.'routes'.$content.'/view.css')) {
            $f3->set('VIEW_CSS', 'routes'.$content.'/view.css');
        }

        if (file_exists($ui.'routes'.$content.'/view.js')) {
            $f3->set('VIEW_JS', 'routes'.$content.'/view.js');
        }

        if(!empty($f3->get('PAGE.slug'))){
        if(!str_contains($user_access, '*')){
            if(!str_contains($user_access, $f3->get('PAGE.slug'))) {
                $f3->reroute('/'.$f3->get('SITE.uri_backend').'/restricted');
                return false;
            }
        }}

        $f3->set('CONTENT', $content);  
 
    } 
   
    
}