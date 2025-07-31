<?php
// ----------------------------------------------------
// MySQL Database Connection (Current Active Configuration)
// ----------------------------------------------------
$host = "localhost";
$username = "root";
$password = "";
$dbname = "b108";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/*
// ----------------------------------------------------
// Microsoft SQL Server Connection (Commented Out - Uncomment to Activate)
// ----------------------------------------------------

// Define constants for SQL Server connection
// You will need to replace placeholder values with your actual SQL Server credentials.
// Also, ensure you have the Microsoft Drivers for PHP for SQL Server extension installed and enabled in php.ini.
// For more details on driver installation, refer to Microsoft's official documentation.

define('MSSQL_SERVER', 'YourSqlServerNameOrIP'); // e.g., 'localhost', '192.168.1.100', 'SERVER\SQLEXPRESS'
define('MSSQL_DATABASE', 'YourMsSqlDatabaseName'); // e.g., 'b108_mssql'
define('MSSQL_UID', 'YourMsSqlUsername');         // e.g., 'sa' or a specific user
define('MSSQL_PWD', 'YourMsSqlPassword');         // e.g., 'your_strong_password'
define('MSSQL_PORT', 1433);                       // Default SQL Server port, change if different

try {
    // DSN for SQL Server using sqlsrv driver
    // Example DSN for local SQL Server Express instance: "sqlsrv:Server=(localdb)\MSSQLLocalDB;Database=YourMsSqlDatabaseName"
    // Example DSN for network SQL Server instance: "sqlsrv:Server=YourSqlServerNameOrIP,1433;Database=YourMsSqlDatabaseName"
    $dsn_mssql = "sqlsrv:Server=" . MSSQL_SERVER . "," . MSSQL_PORT . ";Database=" . MSSQL_DATABASE;

    // Create the PDO connection for SQL Server
    // IMPORTANT: When activating, comment out the MySQL PDO connection above
    // and uncomment the line below:
    // $pdo = new PDO($dsn_mssql, MSSQL_UID, MSSQL_PWD);

    // Set error mode to exception for better error handling
    // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: Set a timeout for the connection
    // $pdo->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, 30); // 30 seconds

} catch (PDOException $e) {
    // If connection fails, stop script execution and display error
    // IMPORTANT: When activating, uncomment the line below:
    // die("SQL Server Database connection failed: " . $e->getMessage());
}
*/

?>