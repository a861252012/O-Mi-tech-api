<?php
/**

c h
modth
status
follow
agent
referer
url
error
instance
retry=0
header

h()
post(data)
follow()
getStatus()
agent($u)
rerferer()
header($data)
file()
cookie()
retry($time)
getError()

submit(url)
 */
namespace Pay;
class HttpUrl{
    private $modth="get";
    private $status="";
    private $follow="";
    private $agent="";
    private $referer="";
    private $show_header=false;
    private $header="";
    private $url="";
    private $timeout = 30;
    private $error="";
    private $data="";
    private $info="";
    private $returntransfer = true;
    private $response = "";
    private static $instance = null;

    public static function init(){
        if(self::$instance==null)
            self::$instance = new self;
        return self::$instance;
    }
    function post($data){
        $this->data= $data;
        $this->modth = "post";
        return $this;
    }
    function follow(){
        $this->follow = true;
        return $this;
    }
    function getInfo(){
        return $this->info;
    }
    function getStatus(){
        return $this->info['http_code'];
    }
    function agent($u){
        $this->agent = $u;
        return $this;
    }
    function referer($u){
        $this->referer = $u;
        return $this;
    }
    function header($data){
        $this->header = $data;
        return $this;
    }
    function file(){

    }
    function getResponse(){
        return $this->response;
    }
    function getArrayResponse(){
        return json_decode($this->response,true);
    }
    function cookie(){

    }
    function retry($time){
        $this->retry = $time;
        return $this;
    }
    function getError(){
        return $this->error;
    }
    function timeout($time){
        $this->timeout;
        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    function submit($url=""){

        $this->url = $url;
        $adb_handle = curl_init();

        $opt = [
            CURLOPT_URL=>$this->url,
            CURLOPT_HEADER=>$this->show_header,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_RETURNTRANSFER => $this->returntransfer
        ];

        if($this->header && in_array('Content-Type:application/json',$this->header)){
            $this->data = json_encode($this->data);
            $this->header[] = 'Content-Length:'.strlen($this->data);
        }
        if($this->header){
            $opt[CURLOPT_HTTPHEADER]=$this->header;
        }

        if($this->modth=='post'){
            $opt[CURLOPT_POST] = true;
            $opt[CURLOPT_POSTFIELDS] = $this->data;
        }


        curl_setopt_array($adb_handle,$opt);

        $this->response = curl_exec($adb_handle);

        $errno = curl_errno($adb_handle);

        $this->info = curl_getinfo($adb_handle);

        $this->error = $errno ? curl_error($adb_handle) : '';

        curl_close($adb_handle);

        return $this;
    }
}

//$a = '{"app":"testapp","aoid":"t1234567890","r_url":"","pw":"qqpay","p":10,"m1":"","m2":"","v_code":"4f66a9c35e7b42a580a3939687eb7bd9"}';
//$http = HttpUrl::init()->header([
//    'Content-Type:application/json'
//])->post(json_decode($a,true))->submit('http://api.lulurepay.com/CreateOrder/Json');
//print_r( $http->getArrayResponse()).PHP_EOL;

//$url = "http://product.co/aaa";
//$http = HttpUrl::init()->post(['pay_id'=>time()])->submit($url);
//echo $http->response().PHP_EOL;
//echo $http->getError().PHP_EOL;
//echo $http->getStatus().PHP_EOL;
?>