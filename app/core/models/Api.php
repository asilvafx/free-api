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
        global $db; // Use the global $db for API key verification
        $response = new Response;

        if ($f3->get('SITE.enable_api') !== true) {
            $response->json('error', 'API disabled.');
            return false;
        }

        try {
            // Check if there are any records in the 'api' table
            $totalItems = $this->crud->read('api'); // Use the read method from Crud

            if (empty($totalItems)) {
                return false;
            }

            if (empty($key)) {
                $response->json('error', 'API Key missing.');
                return false;
            }

            // Decode the API key from the authorization header
            $key = htmlspecialchars_decode($key);

            // Load the API key from the database
            $api = new DB\SQL\Mapper($db, 'api'); // Use the global $db
            $api->load(array('api_key=?', $key));

            if ($api->dry()) {
                $response->json('error', 'Invalid API Key.');
                return false;
            } elseif ($api->status === 0) {
                $response->json('error', 'API Key disabled.');
                return false;
            } else {
                $api->api_usage++;
                $api->save();
                return true;
            }
        } catch (Exception $e) {
            $response->json('error', 'Error fetching request. ' . $e->getMessage());
            return false;
        }
    }

    function Base($f3, $args)
    {
        global $siteDb; // Use the global $siteDb for CRUD operations
        $body = json_decode(file_get_contents('php://input'), true);
        $slug = empty($args['slug']) ? '' : htmlspecialchars_decode($args['slug']);
        $search = empty($args['search']) ? '' : htmlspecialchars_decode($args['search']);
        $key = isset($_SERVER['HTTP_AUTHORIZATION']) ? trim(str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'])) : $f3->get('POST.key');
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
                    $id = $crud->create($tableName, $body);
                    $response->json('success', ['id' => $id, 'message' => 'Data added successfully.']);
                } else {
                    $response->json('error', 'Missing or invalid data in request body.');
                }
                break;

            case 'GET':
                $crud = new Crud($siteDb); // Use the global $siteDb for CRUD operations

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
                break;
                
            case 'PUT':
                if (!empty($search) && is_numeric($search) && !empty($body) && is_array($body)) {
                    $crud = new Crud($siteDb); // Use the global $siteDb for CRUD operations
                    $result = $crud->update($requestData["collection"], $search, $body);
                    if ($result['status'] === 'success') {
                        $response->json('success', $result['message']);
                    } else {
                        $response->json('error', $result['message']);
                    }
                } else {
                    $response->json('error', 'Invalid data or values.');
                }
                break;

            case 'DELETE':
                if (!empty($search) && is_numeric($search)) {
                    $crud = new Crud($siteDb); // Use the global $siteDb for CRUD operations
                    $result = $crud->erase($requestData["collection"], $search);
                    if ($result['status'] === 'success') {
                        $response->json('success', $result['message']);
                    } else {
                        $response->json('error', $result['message']);
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
}