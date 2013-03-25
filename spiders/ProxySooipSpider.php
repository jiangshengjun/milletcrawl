<?php

/**
 *
 * 代理ip采集脚本
 * @author james
 *
 */
class ProxySooipSpider {

    CONST PROXY_DOMAIN = "http://www.sooip.cn"; //采集www.sooip.cn的最新地址

    CONST REG_PROXY_NEWLIST = "/<a href=\"(\/zuixindaili\/(\d{4}\-\d{2}\-\d{2})\/\d+\.html)\" title=\"[^\"]+\">[^<]+<\/a>/";

    CONST REG_PROXY_IP = "/\d{1,3} (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) (\d{2,4}) HTTP ([^\s]+) /";

    CONST CKECK_URL = "http://www.diapers.com/";

    CONST REG_URL = "/diapers\.com/i";

    public function run() {
        $start = microtime(true);
        $this->crawler();

        $end = microtime(true);
    }

    private function crawler () {
        $rc = new RollingCurl();
        $rc->request(self::PROXY_DOMAIN."/zuixindaili/");
        $response = $rc->execute();
        unset($rc,$request);

        $response = iconv("GBK","UTF-8",$response);
        preg_match_all(self::REG_PROXY_NEWLIST, $response, $matches);
        //print_r($matches);

        if($matches) {

            $rc = new RollingCurl(array($this,"process_proxy"));
            $min_day = date("Y-m-d",time() - 2 * 86400);
            $call = array();
            foreach ($matches[1] as $k=>$url) {
                if($matches[2][$k] >= $min_day) {
                    $call[md5($url)] = $url;
                }
            }

            foreach ($call as $k=>$url) {
                $request = new RollingCurlRequest(self::PROXY_DOMAIN.$url);
                $rc->add($request);
            }
            $rc->execute(DEF_MAX_THREADS);
            $callback_proxy_ips = $rc->get_callback_data();
            unset($rc,$request);

            if($callback_proxy_ips) {
                $proxy_ips = array();

                foreach ($callback_proxy_ips as $v) {
                    $proxy_ips = array_merge($proxy_ips,$v);
                }
                $proxy_ips = array_unique($proxy_ips);
                foreach ($proxy_ips as $k=>$v) {
                    $v = explode(" ", $v);
                    $proxy_ips[$k] = $v;
                }

                $insert_data = $this->check_proxy($proxy_ips);
                unset($proxy_ips);
                $insert_data = $this->check_proxy_score($insert_data);
                print_r($insert_data);
            }
        }
    }

    /**
     *
     * 每个正常的代理再分别执行10次，通过成功率来给每个代理打分，分值越高表明代理越稳定。
     * @param unknown_type $proxys
     */
    private function check_proxy_score ($proxys) {

        foreach ($proxys as $k=>$proxy) {

            $rc = new RollingCurl(array($this,"process_proxy_score"));
            $i = 1;
            while ($i <= 10) {
                $option = array();
                $option[CURLOPT_PROXY] = $proxy[0];
                $option[CURLOPT_PROXYPORT] = $proxy[1];

                $option[CURLOPT_PROXYTYPE] = DEF_CURLOPT_PROXYTYPE;
                $option[CURLOPT_USERAGENT] = DEF_CURLOPT_USERAGENT;
                $option[CURLOPT_CONNECTTIMEOUT] = DEF_CURLOPT_CONNECTTIMEOUT;
                $option[CURLOPT_TIMEOUT] = DEF_CURLOPT_TIMEOUT;
                //print_r($option);
                $request = new RollingCurlRequest(self::SOUFUN_CKECK_URL."?num=".(time()+$i),"GET",null,null,$option);
                $rc->add($request);

                $i++;
            }
            $rc->execute(DEF_MAX_THREADS);

            $data = $rc->get_callback_data();

            $j = 0;
            foreach ($data as $v) {
                if($v == 1) {
                    $j++;
                }
            }

            $proxy[] = $j / 10;
            $proxys[$k] = $proxy;

            unset($rc,$request);
        }

        return $proxys;
    }

    /**
     *
     * 成功返回１，不成功返回０
     * @param unknown_type $response
     * @param unknown_type $info
     * @param unknown_type $request
     */
    public function process_proxy_score ($response,$info,$request) {
        $response = iconv("GBK","UTF-8",$response);
        if(preg_match(self::REG_SOUFUN_URL, $response)) {
            return 1;
        }else{
            return 0;
        }
    }


    /**
     *
     * 抓取代理IP地址
     * @param unknown_type $proxys
     */
    private function check_proxy (&$proxys) {
        $rc = new RollingCurl(array($this,"process_proxy_status"));
        foreach ($proxys as $k=>$proxy) {
            $option = array();
            $option[CURLOPT_PROXY] = $proxy[0];
            $option[CURLOPT_PROXYPORT] = $proxy[1];

            $option[CURLOPT_PROXYTYPE] = DEF_CURLOPT_PROXYTYPE;
            $option[CURLOPT_USERAGENT] = DEF_CURLOPT_USERAGENT;
            $option[CURLOPT_CONNECTTIMEOUT] = DEF_CURLOPT_CONNECTTIMEOUT;
            $option[CURLOPT_TIMEOUT] = DEF_CURLOPT_TIMEOUT;
            //print_r($option);
            $request = new RollingCurlRequest(self::CKECK_URL."?num=".(time()+$k),"GET",null,null,$option);
            $rc->add($request);
        }
        $rc->execute(DEF_MAX_THREADS);

        $data = $rc->get_callback_data();
        $insert_data = array();
        if($data){
            $tmp_data = array();
            foreach ($data as $v){
                $tmp_data = array_merge($tmp_data,$v);
            }
            foreach ($proxys as $k=>$proxy) {
                if(count($proxy)==3 && $tmp_data[md5($proxy[0])]){
                    $insert_data[] = $proxy;
                    unset($proxys[$k]);
                }
            }

        }

        unset($rc,$request,$proxy_ips);

        return $insert_data;
    }

    private function check_db_proxy (&$proxys) {
        $rc = new RollingCurl(array($this,"process_proxy_db_status"));
        foreach ($proxys as $k=>$proxy) {
            $option = array();
            $option[CURLOPT_PROXY] = $proxy["ip"];
            $option[CURLOPT_PROXYPORT] = $proxy["port"];

            $option[CURLOPT_PROXYTYPE] = DEF_CURLOPT_PROXYTYPE;
            $option[CURLOPT_USERAGENT] = DEF_CURLOPT_USERAGENT;
            $option[CURLOPT_CONNECTTIMEOUT] = DEF_CURLOPT_CONNECTTIMEOUT;
            $option[CURLOPT_TIMEOUT] = DEF_CURLOPT_TIMEOUT;
            //print_r($option);
            $request = new RollingCurlRequest(self::CKECK_URL."?num=".(time()+$k),"GET",null,null,$option);
            $rc->add($request);
        }
        $rc->execute(DEF_MAX_THREADS);

        $data = $rc->get_callback_data();
        $insert_data = array();
        $delete_data = array();

        if($data){
            $tmp_data = array();
            foreach ($data as $v){
                $tmp_data += $v;
            }
            foreach ($proxys as $k=>$proxy) {
                if(key_exists($k, $tmp_data)){
                    if(!$tmp_data[$k]) {
                        $delete_data[] = $k;
                        unset($proxys[$k]);
                    }else{
                        $insert_data[] = array($proxy["ip"],$proxy["port"],$proxy["address"]);
                    }
                }else{
                    $delete_data[] = $k;
                    unset($proxys[$k]);
                }
            }
        }

        unset($rc,$request,$proxy_ips);

        return $insert_data;
    }

    public function process_proxy_db_status ($response,$info,$request){

        $response = iconv("GBK","UTF-8",$response);
        $return = array();
        $ip_hash = md5($request->options[CURLOPT_PROXY].":".$request->options[CURLOPT_PROXYPORT]);
        if(preg_match(self::REG_URL, $response)) {
            $return[$ip_hash] = true;
        }else{
            $return[$ip_hash] = false;
        }

        return $return;
    }


    /**
     *
     * 检查代理
     * @param $response
     * @param $info
     * @param $request
     */
    public function process_proxy_status ($response,$info,$request){

        $response = iconv("GBK","UTF-8",$response);
        $return = array();
        if(preg_match(self::REG_URL, $response)) {
            $return[md5($request->options[CURLOPT_PROXY])] = true;
        }else{
            $return[md5($request->options[CURLOPT_PROXY])] = false;
        }

        return $return;
    }

    /**
     *
     * 匹配页面中ip地址，把不属于国内的ip过滤掉
     * @param unknown_type $response
     * @param unknown_type $info
     * @param unknown_type $request
     */
    public function process_proxy ($response,$info,$request) {
        $response = iconv("GBK","UTF-8",$response);
        preg_match_all(self::REG_PROXY_IP, $response, $matches);
        $return = array();
        if($matches) {

            global $config;
            $citylist = $config["citylist"];
            foreach ($matches[1] as $k=>$v) {
                foreach ($citylist as $c) {
                    if(strpos($matches[3][$k], $c) !== false){
                        $return[] = $v." ".$matches[2][$k]." ".$matches[3][$k];
                        break;
                    }
                }
            }
        }

        return $return;
    }
}