<?php

class Response extends \Prefab  {
    
    function json($status, $message=null){
        $response = array('status' => $status, 'message' => $message);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    } 

}
