<?php
/**
 * 模拟登录进行灌水操作
 * Created by PhpStorm.
 * User: Orino
 * Date: 15-04-29
 * Time: 下午16:31
 */
//101120054 是orino1010@qq.com对应的uid也是私信的发起者 101116444是私信的接受者uid
//命令行下php curlLogin.php orino1010@qq.com orino1010 domsg 20 101120054 101116444 测试而已
//上述表达的意思是说用户101120054向用户101116444在登录状态下发送了20次私信，内饰是测试而已+计数器
if( count($argv) < 4){
    echo <<<EOF
前2个参数分别是用户名和密码，第4个参数是要执行的次数的，必须大于0
第3个参数，如果是domsg，应该再传3个参数分别是发送者uid,接受者uid,发送内容

EOF;
    exit;
}
if( !function_exists( $argv[3]) ){
    exit('传入的方法还未实现，请联系web开发组');
}
$cookie_file=tempnam('./app/logs','cookies');//cookie保存的目录和文件名

$login_url='http://www.1room.org/login';//登录的接口
//$post_fields = 'uname='.$_GET['uname'].'&password='.$_GET['password'].'&_m=test';//提交的数据
$post_fields = 'uname='.$argv[1].'&password='.$argv[2].'&_m=test';//提交的数据
$ch = curl_init($login_url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch,CURLOPT_POST,1);
curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);
curl_setopt($ch,CURLOPT_COOKIEJAR,$cookie_file);//保存cookie
curl_exec($ch);
curl_close($ch);

function domsg($login_url,$cookie_file){
    static $domsgi = 0;
    global $argv;
    $domsgUrl = 'http://www.1room.org/member/domsg';
    //$post_fields = urldecode('content='.$_GET['content'].'&tid='.$_GET['tid'].'&fid='.$_GET['fid']);
   // $post_fields = 'content='.$_GET['content'].'&tid='.$_GET['tid'].'&fid='.$_GET['fid'];
    $post_fields = 'fid='.$argv[5].'&tid='.$argv[6].'&content='.$argv[7].$domsgi;
    $ch = curl_init($domsgUrl);
    curl_setopt($ch,CURLOPT_HEADER,0);
    curl_setopt($ch, CURLOPT_REFERER, $login_url);//伪造来源url
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_COOKIEFILE,$cookie_file);//发送cookie
    curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);

    $contents=curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if( $err ){
        echo ($domsgi++).':';var_dump($err);echo PHP_EOL;
    }else{
        echo ($domsgi++).':'.$contents,PHP_EOL;
    }
}
/*if( isset($_GET['domsg']) && intval($_GET['domsg']) > 0 ){
    $len = intval($_GET['domsg']);
    for( $i=0; $i<$len; $i++ ){
        domsg($login_url,$cookie_file);
    }
}*/
$len = intval($argv[4]);
if( $len == 0 ){
    exit('次数为0');
}
for( $i=0; $i<$len; $i++ ){
    $argv[3]($login_url,$cookie_file);
}


