<?php

require_once 'includes/common.inc';
require_once 'modules/crawler/crawler.php';
require_once 'includes/daemon.inc';

use LinkHub\Modules\Crawler as Crawler;

$daemon = new Daemon(80,80, new Crawler());
$daemon->main($argv);
