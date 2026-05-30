<?php
// Simple health check endpoint - no database required
header('Content-Type: text/plain');
http_response_code(200);
echo "OK";
?>
