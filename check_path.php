<?php
echo "<h3>Server Path Information</h3>";

// 1. Gets the root folder of the web server
echo "<b>Document Root:</b> " . $_SERVER['DOCUMENT_ROOT'] . "<br><br>";

// 2. Gets the folder where THIS specific file is located
echo "<b>Current Directory:</b> " . __DIR__ . "<br><br>";

// 3. Gets the exact file path
echo "<b>Full File Path:</b> " . __FILE__ . "<br><br>";
?>
