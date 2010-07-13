<?php
echo file_get_contents('http://blog.figaronron.com/direct/?proximusmoblog=cron');
exit;
require_once dirname(__FILE__).'/../../../wp-load.php';
$response = wp_remote_get(get_bloginfo('url').'/?proximusmoblog=cron');
echo '<pre>';
print_r($response);
echo '</pre>';
?>