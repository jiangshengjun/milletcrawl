<?php


require_once dirname(__FILE__)."/config.php";
require_once dirname(__FILE__)."/rolling-curl/RollingCurl.php";

require_once dirname(__FILE__)."/spiders/ProxySooipSpider.php";

$proxySpider = new ProxySooipSpider();
$proxySpider->run();
