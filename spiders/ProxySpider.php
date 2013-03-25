<?php

/**
 *
 * 采集代理ip
 * @author jiangshengjun
 *
 */

class ProxySpider {

    public function run(){

        $url_56ads = "http://www.56ads.com/proxyip/";
        $rc = new RollingCurl(array($this,"crawl_56ads_list"));
        $option = array();
        $option[CURLOPT_USERAGENT] = DEF_CURLOPT_USERAGENT;
        $option[CURLOPT_CONNECTTIMEOUT] = DEF_CURLOPT_CONNECTTIMEOUT;
        $option[CURLOPT_TIMEOUT] = DEF_CURLOPT_TIMEOUT;
        $request = new RollingCurlRequest($url_56ads,"GET",null,null,$option);
        $rc->add($request);

        $rc->execute(DEF_MAX_THREADS);
        $urls = $rc->get_callback_data();
        if(!empty($urls)) {
            $rc = new RollingCurl(array($this,"crawl_56ads_proxy"));
            foreach ($urls as $v) {
                foreach ($v as $url) {

                    $option = array();
                    $option[CURLOPT_USERAGENT] = DEF_CURLOPT_USERAGENT;
                    $option[CURLOPT_CONNECTTIMEOUT] = DEF_CURLOPT_CONNECTTIMEOUT;
                    $option[CURLOPT_TIMEOUT] = DEF_CURLOPT_TIMEOUT;
                    $request = new RollingCurlRequest($url,"GET",null,null,$option);
                    $rc->add($request);
                }

            }

            $rc->execute(DEF_MAX_THREADS);
            $proxies = $rc->get_callback_data();
        }

    }

    public function crawl_56ads_list($response,$info,$request) {
        $urls = array();

        if($info["http_code"] == 200) {
            $regstr = "/<a href=\"\/article\/(.*)\.html\" class=\"title\">/";
            preg_match_all($regstr, $response, $matches);

            if(!empty($matches)) {
                $urls[0] = "http://www.56ads.com/article/".$matches[1][0].".html";
                $urls[1] = "http://www.56ads.com/article/".$matches[1][1].".html";
            }
        }

        return $urls;
    }

    public function crawl_56ads_proxy($response,$info,$request) {
        $proxies = array();
        print_r($info);
        print_r($request);

        if($info["http_code"] == 200) {
            $regstr = "/\d\.\d\.\d\.\d:\d\s+@HTTP/i";
            preg_match_all($regstr, $response, $matches);
            print_r($matches);

        }

        return $proxies;
    }


}
