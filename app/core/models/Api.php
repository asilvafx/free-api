<?php

class Api extends PostController
{
    private $crud;

    public function __construct()
    {
        global $db; // Use the global $db for API key verification
        $this->crud = new Crud($db);
    }

    function verifyKey($key): bool
    {
        global $f3;
        global $db;
        $response = new Response;

        if ($f3->get('SITE.enable_api') !== true) {
            $response->json('error', 'API disabled.');
            return false;
        }

        try {
            if (empty($key)) {
                $response->json('error', 'API Key missing.');
                return false;
            }

            // Decode the API key
            $key = htmlspecialchars_decode($key);

            // Load API key from DB
            $api = new DB\SQL\Mapper($db, 'api');
            $api->load(['api_key = ?', $key]);

            if ($api->dry()) {
                $response->json('error', 'Invalid API Key.');
                return false;
            }

            // Check if API key is enabled
            if ((int)$api->status === 0) {
                $response->json('error', 'API Key disabled.');
                return false;
            }

            // Check allowed IPs if set
            $client_ip = $f3->get('CLIENT.ip');
            $client_address = gethostbyaddr($client_ip);
            $ips = $api->api_allowed_domains;
            if (!empty($ips) && is_string($ips)) {
                $allowed_ips = array_map('trim', explode(',', $ips));

                if (!in_array($client_ip, $allowed_ips) && !in_array($client_address, $allowed_ips) && !in_array('*', $allowed_ips)) {
                    $response->json('error', 'IP: '.$client_ip.'/'.$client_address.' not allowed.');
                    return false;
                }
            } else {
                $response->json('error', 'IP: '.$client_ip.'/'.$client_address.' not allowed.');
                return false;
            }

            // Increment usage
            $api->api_usage++;
            $api->save();

            return true;

        } catch (Exception $e) {
            $response->json('error', 'Error fetching request. ' . $e->getMessage());
            return false;
        }
    }

    function Mail($f3)
    {
        $key = isset($_SERVER['HTTP_AUTHORIZATION'])
            ? trim(str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']))
            : null;

        $response = new Response;
        $mail     = new Mail;

        // Verify API Key
        if (!$this->verifyKey($key)) {
            exit;
        }

        // Only allow POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response->json('error', 'Only POST method is allowed.');
            exit;
        }

        $body = json_decode(file_get_contents('php://input'), true);

        // Gather inputs
        $to      = $body['address'] ?? $f3->get('POST.address');
        $subject = $body['subject'] ?? $f3->get('POST.subject');
        $content = $body['content'] ?? $f3->get('POST.content');
        $type    = $body['method'] ?? $f3->get('POST.method');
        $options    = $body['options'] ?? $f3->get('POST.options');

        if (!$to || !$subject || !$content) {
            $response->json('error', 'Missing email fields (address, subject, content).');
            exit;
        }

        if(!empty($options) && $options === 'crypt'){
            $crypt = new Crypt();
            $content = $crypt->JsDecrypt($content);
        }

        $params = ['message' => $content];

        if($type === 'otp'){
            $params = ['otp' => $content];
        } else if(!empty($type)){
            $params = $content;
        }

        // Send email
        $mail->sendEmail(
            $to,
            $subject,
            $type,
            $params
        );

        // success response
        $response->json('success', 'Email sent.');

        global $db;
        $data = new DB\SQL\Mapper($db, 'mail');
        $data->mail_to = $to;
        $data->mail_from = $f3->get('SITE.smtp_user');
        $data->subject = $subject;
        $data->created_at = time();
        $data->save();

        exit;
    }

    function Upload($f3)
    {
        $key = isset($_SERVER['HTTP_AUTHORIZATION']) ? trim(str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'])) : null;
        $response = new Response;
        $utils = new Utils();

        // Verify API Key
        if (!$this->verifyKey($key)) {
            exit;
        }

        // Check if it's a POST request with files
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $response->json('error', 'Only POST method is allowed for file uploads.');
            exit;
        }

        // Check if files were uploaded
        if (empty($_FILES) || !isset($_FILES['file'])) {
            $response->json('error', 'No files were uploaded.');
            exit;
        }

        // Get target directory for uploads
        $uploadDir = 'public/uploads/';

        // Get optional file renaming parameter
        $fileRename = $f3->get('POST.filename') ?: null;

        // Handle file upload
        $uploadedFile = $utils->uploadFile($_FILES['file'], $uploadDir, $fileRename);

        if(isset($_SERVER['HTTPS'])){
            $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
        }
        else{
            $protocol = 'http';
        }

        if ($uploadedFile) {
            // Return success with file path
            $relativePath = str_replace(BASE_PATH, '', $uploadedFile);
            $response->json('success', [
                'file' => $protocol . "://" . $_SERVER['HTTP_HOST'] . "/" .$relativePath,
                'message' => 'File uploaded successfully.'
            ]);
        } else {
            $response->json('error', 'Failed to upload file. Check file size or permissions.');
        }

        exit;
    }

    function Base($f3, $args)
    {
        global $siteDb; // Use the global $siteDb for CRUD operations
        $body = json_decode(file_get_contents('php://input'), true);
        $slug = empty($args['slug']) ? '' : htmlspecialchars_decode($args['slug']);
        $search = empty($args['search']) ? '' : htmlspecialchars_decode($args['search']);
        $value = empty($args['value']) ? '' : htmlspecialchars_decode($args['value']);
        $key = isset($_SERVER['HTTP_AUTHORIZATION']) ? trim(str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'])) : null;
        $response = new Response;

        // Verify API Key
        if (!$this->verifyKey($key)) {
            exit;
        }

        if (!empty($slug)) {
            $requestData["collection"] = htmlspecialchars_decode($slug);
        } else {
            $response->json('error', 'Collection set missing.');
            exit;
        }

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                if (!empty($body) && is_array($body)) {
                    $tableName = $requestData["collection"];
                    $crud = new Crud($siteDb); // Use the global $siteDb for CRUD operations
                    try {
                        $id = $crud->create($tableName, $body);
                        $response->json('success', ['id' => $id, 'message' => 'Data added successfully.']);
                    } catch (Exception $e) {
                        $response->json('error', $e->getMessage());
                    }
                } else {
                    $response->json('error', 'Missing or invalid data in request body.');
                }
                break;

            case 'GET':
                $crud = new Crud($siteDb); // Use the global $siteDb for CRUD operations

                try {
                    if (!empty($value) && !empty($search)) {
                        // If search is set and is numeric, fetch the specific item by ID
                        $data = $crud->readByKeyValue($requestData["collection"], (string)$search, (string)$value);
                    } else
                    if (!empty($search) && is_numeric($search)) {
                        // If search is set and is numeric, fetch the specific item by ID
                        $data = $crud->readById($requestData["collection"], (int)$search);
                    } else {
                        // Otherwise, fetch all items from the table
                        $data = $crud->read($requestData["collection"]);
                    }

                    // Check if the response indicates an error
                    if ($data['status'] === 'error') {
                        // Return the error response directly
                        $response->json('error', $data['message']);
                    } else {
                        // Return the success response
                        $response->json('success', $data['message']);
                    }
                } catch (Exception $e) {
                    $response->json('error', $e->getMessage());
                }
                break;
                
            case 'PUT':
                if (!empty($search) && is_numeric($search) && !empty($body) && is_array($body)) {
                    $crud = new Crud($siteDb); // Use the global $siteDb for CRUD operations
                    try {
                        $result = $crud->update($requestData["collection"], $search, $body);
                        if ($result['status'] === 'success') {
                            $response->json('success', $result['message']);
                        } else {
                            $response->json('error', $result['message']);
                        }
                    } catch (Exception $e) {
                        $response->json('error', $e->getMessage());
                    }
                } else {
                    $response->json('error', 'Invalid data or values.');
                }
                break;

            case 'DELETE':
                $crud = new Crud($siteDb);
                if( !empty($search) ){
                    try {
                        $result = $crud->erase($requestData["collection"], $search);
                        if ($result['status'] === 'success') {
                            $response->json('success', $result['message']);
                        } else {
                            $response->json('error', $result['message']);
                        }
                    } catch (Exception $e) {
                        $response->json('error', $e->getMessage());
                    }
                } else {
                     $response->json('error', 'Invalid data or values.');
                }
                break;

            default:
                $response->json('error', 'Invalid Endpoint');
                exit;
        }

        exit;
    }

    function Invalid($f3) {
        $response = new Response;

        $response->json('error', 'Invalid Endpoint');
        exit;
    }
}