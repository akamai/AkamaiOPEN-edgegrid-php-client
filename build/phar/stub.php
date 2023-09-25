<?php
if (class_exists('Phar')) {
   Phar::mapPhar('akamai-open-edgegrid-client.phar');
}

Phar::interceptFileFuncs();
require_once 'phar://' .__FILE__. '/vendor/autoload.php';
// Run the CLI if called directly
if (PHP_SAPI == 'cli' && basename($_SERVER['argv'][0]) == basename(__FILE__)) {
    (new \Akamai\Open\EdgeGrid\Cli())->run();
    exit;
}
__HALT_COMPILER(); ?>