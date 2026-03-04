<?php

class Database
{
    private $host = 'localhost';  // Standard MySQL host
    private $port = 3306;  // XAMPP MySQL port
    private $db_name = 'teacher_management_system';
    private $db_user = 'root';
    private $db_pass = '';  // XAMPP default: empty password
    private $connection;

    public function connect()
    {
        $this->connection = new mysqli(
            $this->host,
            $this->db_user,
            $this->db_pass,
            $this->db_name,
            $this->port
        );

        // Check connection
        if ($this->connection->connect_error) {
            die('Connection Failed: ' . $this->connection->connect_error .
                '<br><br>Please verify your MySQL credentials in config/Database.php' .
                '<br>Default XAMPP MySQL: username=root, password=root or empty');
        }

        return $this->connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
?>