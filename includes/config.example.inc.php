<?php
/* config.example.inc.php - the TEMPLATE for the database settings.

   1. Copy this file to includes/config.inc.php (same folder).
   2. Fill in the MariaDB user and password for the bookclub_jill database.

   config.inc.php holds a password, so it is listed in .gitignore and must
   never be committed. This example has no real password, so it is safe. */

define('DB_HOST', 'localhost');
define('DB_USER', 'jill_app');      // a user allowed only SELECT, INSERT, UPDATE, DELETE
define('DB_PASS', 'change-me');     // that user's password
define('DB_NAME', 'bookclub_jill'); // created by setup.sql
