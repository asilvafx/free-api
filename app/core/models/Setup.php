<?php 

class Setup extends MainController  {
    
    function wizzard($f3,$args) { 

        $slug=empty($args[0])?'':$args[0];

        $setup_uri = '/setup';
        if (str_contains($slug, $setup_uri)) { 
            $slug = str_replace($setup_uri, '',$slug);
        } 

        $ui = 'app/views/setup/';

        $f3->set('UI', $ui);  

		if(file_exists($ui.'index.php')){ 
			require_once($ui.'index.php');
		} 
 
        if (file_exists($ui.'routes'.$slug.'/view.php')) { 
            require_once($ui.'routes'.$slug.'/view.php');
        } 
 
    }
     

}