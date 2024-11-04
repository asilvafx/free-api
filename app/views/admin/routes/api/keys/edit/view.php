<?php

$f3->set('PAGE.title', 'API');
$f3->set('PAGE.slug', 'api');

global $db;

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $response = new Response;

    $body = json_decode(file_get_contents('php://input'), true);

    // Validate CSRF Token
    $csrf = (isset($body['token']) ? $body['token'] : $f3->get('POST.token'));

    if ($csrf != $f3->get('SESSION.token')) {
        $response->json('error', 'CSRF token mismatch error. Please reload your browser and try again.');
        exit;
    }

    // Retrieve the schema data
    $schema = (isset($body['schema']) ? $body['schema'] : $f3->get('POST.schema'));

    // Define Table Name
    $tableName = 'api';

    if (!empty($schema)) {

        if (isset($_GET['addDomain']) || isset($_GET['removeDomain'])) {
            $slug = isset($schema['slug']) ? base64_decode($schema['slug']) : null;
            $domain = isset($schema['domain']) ? htmlspecialchars_decode($schema['domain']) : null;
            if (empty($slug) || empty($domain)) {
                $response->json('error', 'Missing Data. Please fill all required fields and try again.');
                exit;
            }
            $query = new DB\SQL\Mapper($db, $tableName);
            $query->load(array('api_slug=?', $slug));

            if ($query->dry()) {
                $response->json('error', 'API Key was not found in our system.');
                exit;
            }

            try {
                $currentDomains = $query->api_allowed_domains;
                $domains_array = array();

                if (!empty($currentDomains)) {
                    $domains_array = explode(',', $currentDomains);
                }

                if (isset($_GET['addDomain'])) {
                    if (($key = array_search($domain, $domains_array)) !== false) {
                        $response->json('error', 'Domain already exists in our records.');
                        exit;
                    }

                    array_push($domains_array, $domain);
                } else {
                    if (($key = array_search($domain, $domains_array)) !== false) {
                        unset($domains_array[$key]);
                    }
                }

                $domains_str = implode(',', $domains_array);

                // Execute the query    
                $query->api_allowed_domains = $domains_str;
                $query->save();
                if (isset($_GET['addDomain'])) {
                    $response->json('success', 'New domain was successfully added.');
                } else {
                    $response->json('success', 'Domain was successfully removed.');
                }
            } catch (Exception $e) {
                // Handle any SQL errors
                $response->json('error', 'Error adding entry: ' . $e->getMessage());
            }
        } else  
        if (isset($_GET['status'])) {
            $slug = isset($schema['slug']) ? base64_decode($schema['slug']) : null;
            $status = isset($schema['status']) ? html_entity_decode($schema['status']) : null;
            if (is_null($slug) || is_null($status)) {
                $response->json('error', 'Missing Data. Please fill all required fields and try again.');
                exit;
            }

            $query = new DB\SQL\Mapper($db, $tableName);
            $query->load(array('api_slug=?', $slug));

            if ($query->dry()) {
                $response->json('error', 'API Key was not found in our system.');
                exit;
            }
            try {
                // Execute the query    
                $query->status = $status;
                $query->save();

                $status_msg = $status == 1 ? "enabled" : "disabled";
                $response->json('success', 'API Key was successfully ' . $status_msg . '.');
            } catch (Exception $e) {
                // Handle any SQL errors
                $response->json('error', 'Error adding entry: ' . $e->getMessage());
            }
        } else  
        if (isset($_GET['delete'])) {
            $slug = isset($schema['slug']) ? base64_decode($schema['slug']) : null;
            if (empty($slug)) {
                $response->json('error', 'Missing Data. Please fill all required fields and try again.');
                exit;
            }
            $query = new DB\SQL\Mapper($db, $tableName);
            $query->load(array('api_slug=?', $slug));

            if ($query->dry()) {
                $response->json('error', 'API Key was not found in our system.');
                exit;
            }

            try {
                // Execute the query    
                $query->erase();

                $response->json('success', 'API Key was successfully deleted.');
            } catch (Exception $e) {
                // Handle any SQL errors
                $response->json('error', 'Error adding entry: ' . $e->getMessage());
            }
        } else 
        if (isset($_GET['rename'])) {

            $slug = isset($schema['slug']) ? base64_decode($schema['slug']) : null;
            $name = isset($schema['name']) ? htmlspecialchars_decode($schema['name']) : null;

            if (empty($slug) || empty($name)) {
                $response->json('error', 'Missing Data. Please fill all required fields and try again.');
                exit;
            }

            $query = new DB\SQL\Mapper($db, $tableName);
            $query->load(array('api_slug=?', $slug));

            if ($query->dry()) {
                $response->json('error', 'API Key was not found in our system.');
                exit;
            }

            try {
                // Execute the query   
                $query->api_slug = $name;
                $query->save();

                $response->json('success', 'API Key was successfully updated.');
            } catch (Exception $e) {
                // Handle any SQL errors
                $response->json('error', 'Error adding entry: ' . $e->getMessage());
            }
        }
    } else {
        $response->json('error', 'No data provided for the new entry.');
    }

    exit;
}
