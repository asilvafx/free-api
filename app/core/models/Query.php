<?php

class Query extends \Prefab
{
    function addCollection($db, $tableName, $sql){
    $response = false;
      try {
          // Fetch the database instance (assuming you use Fat-Free or similar)
          $check_db = $db->exec(
              'SELECT * FROM sqlite_master ' .
                  'WHERE type="table" AND name="' . $tableName . '"'
          );
          if (count($check_db) === 0) {
              $sql = "CREATE TABLE `$tableName` ($sql)";
              $db->exec($sql);
              $response = true; 
          }
      } catch (Exception $e) {
          // Send error message if something went wrong
          $response = false;
      }
      return $response;
    }
    
    function load($table, $options=null, $db=null)
    { 

        $response = null;

        if(!$db){
          global $db;
        }

        $sql = 'SELECT * FROM ' . $table . ' ' . $options;

        try {
            $response = $db->exec($sql);
        } catch (Exception $e) {
            $response[] = "";
        }
    }

    function lookup($table, $search, $value, $db=null)
    {
        $response = null;

        if (is_null($table) || is_null($search) || is_null($value)) {
            return $response;
        }

        try {  
            if(!$db){ 
            global $db;
            }
            $query = new DB\SQL\Mapper($db, $table);
            $query->load(array($search . '?', $value));
            if (!$query->dry()) { 
                $response = array();
                $response = $query;
            } else { $response = null; }
        } catch (Exception $e) {
            $response = null;
        }

        return $response;
    }
}
