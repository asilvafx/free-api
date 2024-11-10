<?php

class Api extends PostController
{

  function verifyKey($key)
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
      $totalItems = $db->exec('SELECT COUNT(api_key) as totalItems FROM api');

      if ($totalItems[0]['totalItems'] == 0) {
        // If there are no records in the 'api' table, return false
        return false;
      }

      // If $key is empty, return an error
      if (empty($key)) {
        $response->json('error', 'API Key missing.');
        return false;
      }

      // Decode the API key from the authorization header
      $key = htmlspecialchars_decode($key);

      // Load the API key from the database
      $api = new DB\SQL\Mapper($db, 'api');
      $api->load(array('api_key=?', $key));

      // If the API key is not found or disabled, return an error
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
    global $siteDb;
    $body = json_decode(file_get_contents('php://input'), true);
    $slug = empty($args['slug']) ? '' : htmlspecialchars_decode($args['slug']);
    $search = empty($args['search']) ? '' : htmlspecialchars_decode($args['search']);
    $value = empty($args['value']) ? '' : htmlspecialchars_decode($args['value']);
    $limit = isset($body['limit']) ? $body['limit'] : $f3->get('GET.limit');
    $data = isset($body['data']) ? $body['data'] : $f3->get('POST.data');
    $values = isset($body['values']) ? $body['values'] : $f3->get('POST.values');
    $key = isset($_SERVER['HTTP_AUTHORIZATION']) ? trim(str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION'])) : $f3->get('POST.key');
    $requestData = array();
    $response = new Response;


    switch ($_SERVER['REQUEST_METHOD']) {
      case 'POST':
        $requestData["method"] = 'post';
        break;
      case 'GET':
        $requestData["method"] = 'get';
        break;
      case 'DELETE':
        $requestData["method"] = 'delete';
        break;
      case 'PUT':
        parse_str(file_get_contents('php://input'), $requestData);
        if (!is_array($requestData)) {
          $requestData = array();
        }
        $requestData["method"] = 'put';
        break;
      default:
        $response->json('error', 'Invalid Endpoint');
        exit;
    }

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

    $set_limit = !empty($limit) ? 'LIMIT ' . intval($limit) : "";
    $fields_cast = null;
    try {
      if ($requestData["method"] === 'get') {

        if ($requestData["collection"] === '@all') {
          $collections = $siteDb->exec('SELECT * FROM sqlite_master WHERE type="table"');
        } else {
          $collections = $siteDb->exec('SELECT * FROM sqlite_master WHERE type="table" AND name=?', [$requestData["collection"]]);
        }

        if (!$collections) {
          $response->json('error', 'Collection not found.');
          exit;
        }

        if (!empty($search) && !empty($value)) {
          $fields_cast = $siteDb->exec('SELECT * FROM ' . $requestData["collection"] . ' WHERE ' . $search . '=? ' . $set_limit, [$value]);

        } else {
          $fields_cast = $siteDb->exec('SELECT * FROM ' . $requestData["collection"] . ' ' . $set_limit);
        }

      if ($fields_cast) {
          $response->json('success', $fields_cast);
      } else {
          $response->json('error', null);
      }

      } elseif ($requestData["method"] === 'post') {
          if ($requestData["collection"] === 'UPLOAD' && isset($_FILES['file'])) {
              $utils = new Utils;
              $dir = isset($body['dir']) ? $body['dir'] : $f3->get('POST.dir');
              $uploadDir = !empty($dir) ? $dir : 'public/uploads/'; // Directory where files will be uploaded

              $uploadResult = $utils->uploadFile($_FILES['file'], $uploadDir); // Call the uploadFile function

              if (!$uploadResult) {
                  $response->json('error', 'Failed to upload file.');
              } else {
                  $response->json('success', $uploadResult); // Return the uploaded file URL
              }
              exit;
          }
          // Validate that the body is not empty
          if (!empty($body) && is_array($body)) {
              $tableName = $requestData["collection"];

              // Extract columns and values dynamically
              $columns = array_keys($body); // Get column names
              $values = array_values($body); // Get corresponding values

              // Quote string values manually and convert arrays/objects to JSON
              $quotedValues = array_map(function($value) {
                  if (is_array($value) || is_object($value)) {
                      return "'" . addslashes(json_encode($value)) . "'"; // Convert arrays/objects to JSON
                  } elseif (is_string($value)) {
                      return "'" . addslashes($value) . "'"; // Add quotes around strings
                  }
                  return $value; // Leave other types (numbers) as is
              }, $values);

              // Implode columns and quoted values to create the SQL statement
              $sql = 'INSERT INTO ' . $tableName . ' (' . implode(",", $columns) . ') VALUES (' . implode(",", $quotedValues) . ')';

              // Execute the query
              $result = $siteDb->exec($sql);

              if ($result) {
                  $response->json('success', 'Data added successfully.');
              } else {
                  $response->json('error', 'Failed to add data.');
              }
          } else {
              $response->json('error', 'Missing or invalid data in request body.');
              exit;
          }
      } elseif ($requestData["method"] === 'put') {
          if (!empty($search) && !empty($value) && !empty($body) && is_array($body)) {
              $tableName = $requestData["collection"];

              // Extract columns and values dynamically
              $columns = array_keys($body); // Get column names
              $values = array_values($body); // Get corresponding values

              // Prepare the SET part of the SQL statement
              $setParts = [];
              foreach ($columns as $index => $column) {
                  if (is_array($values[$index]) || is_object($values[$index])) {
                      // Convert arrays/objects to JSON
                      $setParts[] = "$column = '" . addslashes(json_encode($values[$index])) . "'";
                  } elseif (is_string($values[$index])) {
                      // Quote string values
                      $setParts[] = "$column = '" . addslashes($values[$index]) . "'";
                  } else {
                      // Leave other types (numbers) as is
                      $setParts[] = "$column = " . $values[$index];
                  }
              }
              $setString = implode(", ", $setParts);

              // Prepare the SQL statement
              $sql = "UPDATE $tableName SET $setString WHERE $search = :value";

              // Prepare and execute the statement
              $stmt = $siteDb->prepare($sql);
              $stmt->bindParam(':value', $value); // Bind the search value
              $result = $stmt->execute();

              if ($result) {
                  $response->json('success', 'Data updated successfully.');
              } else {
                  $response->json('error', 'Failed to update data.');
              }
          } else {
              $response->json('error', 'Missing data or values.');
          }
      } elseif ($requestData["method"] === 'delete') {
        if ($data) {
          $siteDb->exec('DELETE FROM ' . $requestData["collection"] . ' WHERE ' . $data);
          $response->json('success', 'Removed from collection.');
        } else {
          $response->json('error', 'Missing data.');
        }
        exit;
      }

    } catch (Exception $e) {
      $response->json('error', 'Invalid data. Error: ' . $e->getMessage());
    }

    exit;
  }
}
