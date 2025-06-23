<?php

class HelloWorld
{
    private $client;
    public function __construct()
    {
        global $f3;
    }

    function Test(){

        header('Content-Type: application/json');

        try {
            echo json_encode(['success' => 'Hello world!']);

        } catch (Error $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }

        exit;
    }
}

