<?php
// A minimal test file to check routing
echo "<h1>Router Test Page</h1>";
echo "<p>This is a simple test page with no includes or redirects.</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<pre>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</pre>";
exit;
