<?php

class Response extends \Prefab  {
    
    function json($status, $message=null){
        if(empty($message) || is_null($message)){
            $response = $status;
        } else {
            $response = array('status' => $status, 'message' => $message);
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
    } 

}
