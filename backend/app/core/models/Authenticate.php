<?php 

class Authenticate extends MainController  {

    function Base($f3, $args){

        if($f3->get('SESSION.loggedin') === true){
            $f3->reroute($f3->get('SITE.uri_backend'));
            return false;
        }
        
        $slug=empty($args[0])?'':$args[0];

        $auth_uri = '/'.$f3->get('SITE.uri_auth');
        if (str_contains($slug, $auth_uri)) { 
            $slug = str_replace($auth_uri, '',$slug);
        } 
        
        $content = '/login'; 
        $ui = 'app/views/auth/';

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

    function Logout($f3){
        $f3->set('SESSION.loggedin', false);
        $f3->clear('SESSION.username');
        $f3->clear('SESSION.timestamp');
        $f3->clear('SESSION.rememberme');
        $f3->reroute('/'.$f3->get('SITE.uri_auth'));
    }

}