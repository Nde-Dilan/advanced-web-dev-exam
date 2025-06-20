<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'event_booking_system';
    private $username = 'event_booking_system';
    private $password = 't92x.7a!lJZEtGjB';
    private $conn;

    //  private $host = 'sql107.infinityfree.com';
    // private $db_name = 'if0_39103695_event_booking_system';
    // private $username = 'if0_39103695';
    // private $password = 'GRjT7hEFkQ';
    // private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();
$db = $database->getConnection();


/**
 * 
 * User password GRjT7hEFkQ
 */


?>


