<?php
// 404 Page set Header Respond
header("HTTP/1.0 404 Not Found");
?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>404 Not Found</title>
</head>
<body>
<div class="top_placeholder_title"><h1>404 - Page not found</h1></div>
<div class="top_placeholder_description">
    <p>No sense sticking around here for very long. Check out the search box and try a different search term or click the home tab, find some of our feature articles and see our lovely sponsors.</p>
    <p style="font-size:0.8em;">If you feel you've reached this page as an error on our part then please <a href="http://www.top4.com.au/contactus.php" style="color:#fff000;text-decoration:underline;">contact us</a>. Have a nice day from the Geeks at Top4.</p>
</div>
<pre>
<?php
if (!defined('PATH_SITE_BASE')) define('PATH_SITE_BASE', dirname(__FILE__).DIRECTORY_SEPARATOR);
include_once('system/config/config.php');
print_r($_SERVER);
print_r($GLOBALS['global_preference']);
$page_content = new content();
print_r($page_content);
?>
</pre>
</body>
</html>
<?php
// Force Exit, Stop further rendering of page
exit();