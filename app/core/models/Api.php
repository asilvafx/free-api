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

    function Payment($f3, $args)
    { 
        if($f3->get('SITE.stripe_status') !== 1){
            echo json_encode(['error' => 'Stripe not enabled.']);
            return;
        }
        $stripe_sk = $f3->get('SITE.stripe_sk');
        require_once ROOT . 'lib/vendor/stripe-php/init.php';

        // Set proper headers for CORS if needed
        header('Content-Type: application/json');

        // Parse the JSON body
        $body = json_decode($f3->get('BODY'), true);

        // Log incoming request for debugging
        error_log('Payment request: ' . json_encode($body));

        if (!$body) {
            echo json_encode(['error' => 'Invalid request body']);
            return;
        }

        // Extract parameters from the request body
        $amount = isset($body['amount']) ? intval($body['amount']) : 0;
        $currency = $body['currency'] ?? 'usd';
        $email = $body['email'] ?? '';
        $useAutomaticMethods = $body['automatic_payment_methods'] ?? false;

        // Validate required fields
        if ($amount <= 0) {
            echo json_encode(['error' => 'Invalid amount']);
            return;
        }

        if (empty($email)) {
            echo json_encode(['error' => 'Email is required']);
            return;
        }

        try {
            // Initialize Stripe with your secret key
            $stripe = new \Stripe\StripeClient($stripe_sk);

            // Create a customer
            $customer = $stripe->customers->create([
                'email' => $email,
                'description' => 'Customer for ' . $email,
            ]);

            // Prepare payment intent parameters
            $paymentIntentParams = [
                'amount' => $amount,
                'currency' => $currency,
                'customer' => $customer->id,
                'metadata' => [
                    'customer_email' => $email
                ],
            ];

            // Use automatic payment methods if specified
            if ($useAutomaticMethods) {
                $paymentIntentParams['automatic_payment_methods'] = ['enabled' => true];
            } else {
                $paymentIntentParams['payment_method_types'] = ['card'];
            }

            // Create a payment intent
            $paymentIntent = $stripe->paymentIntents->create($paymentIntentParams);

            // Log the client secret for debugging (remove in production)
            error_log('Created PaymentIntent: ' . $paymentIntent->id);

            // Return the client secret to the client
            echo json_encode([
                'client_secret' => $paymentIntent->client_secret,
                'customer_id' => $customer->id,
                'payment_intent_id' => $paymentIntent->id
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Handle Stripe API errors
            error_log('Stripe API Error: ' . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
            http_response_code(400);
        } catch (\Exception $e) {
            // Handle other errors
            error_log('Server Error: ' . $e->getMessage());
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
            http_response_code(500);
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
            $response->json('error', 'Invalid API key.');
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
        $body    = $body['body'] ?? $f3->get('POST.body');

        if (!$to || !$subject || !$body) {
            $response->json('error', 'Missing email fields (address, subject, body).');
            exit;
        }

        // Send!
        $ok = $mail->sendEmail(
            $to,
            $subject,
            'default',
            ['message' => $body]
        );

        if ($ok) {
            $response->json('success', 'Email sent.');
        } else {
            $response->json('error', 'Email could not be sent.');
        }

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
                if (!empty($search)) {
                    try {
                    if(!is_numeric($search)){
                        $response->json('error', 'Invalid query value.');
                        exit;
                    }
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
                     /* Uncomment block to allow delete collection (All items)
                        try {
                            $result = $crud->eraseAll($requestData["collection"]);
                            if ($result['status'] === 'success') {
                                $response->json('success', $result['message']);
                            } else {
                                $response->json('error', $result['message']);
                            }
                        } catch (Exception $e) {
                            $response->json('error', $e->getMessage());
                        }
                    */
                    }
                break;

            default:
                $response->json('error', 'Invalid Endpoint');
                exit;
        }

        exit;
    }
}