<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2017/10/24
 * Time: 9:10
 */

$postdata['money'] = 10;
$postdata['channel'] = "RockPay.Web";
$postdata['order_id'] = time();
$postdata['notice'] = "http://peach.co/charge/notice2";

$data=[
    'money'=>$postdata['money'],
    'channel'=>$postdata['channel'],
    'order_id'=>$postdata['order_id'],
    'notice'=>$postdata['notice'],
];
$json = json_encode($data);

?>
<html>

<form method="post" type="" action="PayController.php">
    <input type="text" name="postDatas" value="<?php echo $json; ?>" />
    <input type="submit" value="提交">
</form>
</html>
