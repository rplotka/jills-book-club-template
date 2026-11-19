<?php
/* init.inc.php - included FIRST by every page, before any HTML is sent.
   Loads the settings and helpers, then connects to MariaDB.

   After this file runs you have:
     $db       the connection (a mysqli object), or null if we couldn't connect
     $dbError  '' if all is well, otherwise a message to show on the page */

$configFile = __DIR__ . '/config.inc.php';
if (!file_exists($configFile)) {
   http_response_code(500);
   exit('Missing includes/config.inc.php - copy includes/config.example.inc.php '
      . 'to includes/config.inc.php and fill in the database user and password.');
}
require $configFile;
require __DIR__ . '/functions.inc.php';

// Database errors become exceptions, so they can't be silently ignored.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = null;
$dbError = '';
try {
   $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
   $db->set_charset('utf8mb4');   // send and receive text as UTF-8
} catch (mysqli_sql_exception $e) {
   $db = null;
   $dbError = 'Could not connect to the database: ' . $e->getMessage();
}
