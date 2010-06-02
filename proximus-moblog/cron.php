<?php
// Load Wordpress core
require dirname(__FILE).'/../../../wp-load.php';
// Include cron function
require dirname(__FILE__).'/cron_function.php';

// And execute...
proximusMoblogSync_cron(true);
?>