<?php
require_once dirname(__FILE__)."/config.php";
require_once dirname(__FILE__)."/rolling-curl/RollingCurl.php";

class TestProxy {
    public function run() {

        $start_time = microtime(true);

        $rc = new RollingCurl(array($this,"test_proxy"));
        $option = array();
        $option[CURLOPT_PROXY] = "218.188.13.237";
        $option[CURLOPT_PROXYPORT] = "8888";

        $option[CURLOPT_PROXYTYPE] = DEF_CURLOPT_PROXYTYPE;
        $option[CURLOPT_USERAGENT] = DEF_CURLOPT_USERAGENT;
        $option[CURLOPT_CONNECTTIMEOUT] = DEF_CURLOPT_CONNECTTIMEOUT;
        $option[CURLOPT_TIMEOUT] = DEF_CURLOPT_TIMEOUT;
        print_r($option);
        $request = new RollingCurlRequest("http://www.diapers.com/?num=".time(),"GET",null,null,$option);
        $rc->add($request);

        $rc->execute(DEF_MAX_THREADS);
        $rc->get_callback_data();

        $end_time = microtime(true);

        echo "Used time is ".($end_time - $start_time);
    }

    public function test_proxy($response,$info,$request) {

        echo $response;
    }
}
