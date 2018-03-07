<?php 
function postData($url, $post)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'Content-Length:'.strlen($post))
        );
        $handles = curl_exec($ch);
        curl_close($ch);
        return $handles;
    }

$a = '{"app":"testapp","aoid":"t1234567890","r_url":"","pw":"qqpay","p":10,"m1":"","m2":"","v_code":"4f66a9c35e7b42a580a3939687eb7bd9"}';
echo postData('http://api.lulurepay.com/CreateOrder/Json',$a);
