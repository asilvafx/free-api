<?php

class Crud {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Function to check if a column exists in a table
    private function columnExists(string $tableName, string $columnName): bool
    {
        $sql = "PRAGMA table_info($tableName)";
        $stmt = $this->db->query($sql);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            if ($column['name'] === $columnName) {
                return true;
            }
        }
        return false;
    }

    // Function to add a new column to a table
    private function addColumn(string $tableName, string $columnName, string $columnType): void
    {
        $sql = "ALTER TABLE $tableName ADD COLUMN $columnName $columnType";
        $this->db->exec($sql);
    }

    // Function to create a table if it doesn't exist
    private function createTableIfNotExists(string $tableName): void
    {
        $createTableSQL = "CREATE TABLE IF NOT EXISTS $tableName (id INTEGER PRIMARY KEY AUTOINCREMENT)";
        $this->db->exec($createTableSQL);
    }

    // CREATE
    public function create(string $tableName, array $data): int
    {
        // Create table if it doesn't exist
        $this->createTableIfNotExists($tableName);

        // Prepare the insert statement
        $columns = array_keys($data);
        $placeholders = implode(", ", array_fill(0, count($columns), '?'));
        $values = array_values($data);

        // Check and add columns if they don't exist
        foreach ($columns as $column) {
            if (!$this->columnExists($tableName, $column)) {
                // Assuming all new columns are TEXT for simplicity
                $this->addColumn($tableName, $column, 'TEXT');
            }
        }

        // Now insert the data
        $sql = "INSERT INTO $tableName (" . implode(", ", $columns) . ") VALUES ($placeholders)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            echo json_encode([
                "status" => "error",
                "message" => "Error inserting data: " . $e->getMessage()
            ]);
            return 0; // or throw an exception
        }
    }

    // READ
    public function read(string $tableName): array
    {
        $sql = "SELECT * FROM $tableName";
        try {
            $stmt = $this->db->query($sql);
            return [
                "status" => "success",
                "message" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (PDOException $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ];
        }
    }

    // Function to read a specific item by ID
    public function readById(string $tableName, int $id): array
    {
        $sql = "SELECT * FROM $tableName WHERE id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return [
                    "status" => "success",
                    "message" => $result
                ];
            } else {
                return [
                    "status" => "error",
                    "message" => "No item found with ID: $id"
                ];
            }
        } catch (PDOException $e) {
            return [
                "status" => "error",
                "message" => "Error reading data: " . $e->getMessage()
            ];
        }
    }

    // UPDATE
    public function update(string $tableName, int $id, array $data): array // Change return type to array
    {
        $updates = implode(", ", array_map(fn($key) => "$key = ?", array_keys($data)));
        $sql = "UPDATE $tableName SET $updates WHERE id = ?";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([...array_values($data), $id]);
            return [
                "status" => "success",
                "message" => "Data updated successfully."
            ]; // Return success message
        } catch (PDOException $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ]; // Return error message
        }
    }

    // DELETE
    public function erase(string $tableName, int $id): array // Change return type to array
    {
        $sql = "DELETE FROM $tableName WHERE id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return [
                "status" => "success",
                "message" => "Deleted from collection successfully."
            ]; // Return success message
        } catch (PDOException $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ]; // Return error message
        }
    }

    // DELETE ALL
    public function eraseAll(string $tableName): array // Change return type to array
    {
        $sql = "DROP TABLE IF EXISTS $tableName";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return [
                "status" => "success",
                "message" => "Collection deleted successfully."
            ]; // Return success message
        } catch (PDOException $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage()
            ]; // Return error message
        }
    }

    // Get the database connection
    public function getDb() {
        return $this->db; // Return the current database connection
    }
}