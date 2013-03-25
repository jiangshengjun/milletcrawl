<?php


require_once dirname(__FILE__)."/config.php";
require_once dirname(__FILE__)."/rolling-curl/RollingCurl.php";

require_once dirname(__FILE__)."/spiders/TestProxy.php";

$testProxy = new TestProxy();
$testProxy->run();
