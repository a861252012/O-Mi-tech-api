<?php

namespace App\Services\System;

use App\Facades\SiteSer;
use App\Services\Service;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package 系统支持服务
 * @author  dc
 * @version 20151118
 */
class SystemService extends Service
{


    /**
     * [download 文件下载方法]
     *
     * @author  dc <dc@wisdominfo.my>
     * @version 2015-11-09
     * @param   binary $resource 资源或文件路径(包含文件名)
     * @param   string $filename 文件名称提示下载后的文件名称，不启用新文件名请留空
     * @return  mixed
     */
    public function download($resource, $filename = null)
    {
        $filename = $filename ?: basename($resource);
        $filesize = is_file($resource) ? filesize($resource) : 0;
        //重写http头
        $response = new Response();
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', 'application/octet-stream');
        $response->headers->set('Content-Length', $filesize);

        //防止中文名乱码处理过程
        $user_agent = $this->make('request')->headers->get('user-agent');
        if (preg_match("/MSIE/", $user_agent)) {
            $response->headers->set('Content-Disposition', 'attachment; filename=' . rawurlencode($filename));
        } else
            if (preg_match("/Firefox/", $user_agent)) {
                $response->headers->set('Content-Disposition: attachment; filename*="utf8\'\'' . $filename . '"');;
            } else {
                $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
            }
        $response->sendHeaders();
        $response->setContent(readfile($resource));

        $response->send();
    }

    /**
     * 写充值的 日志
     *
     * @param string $word
     * @param string $recodeurl
     */
    public function logResult($word = '', $recodeurl = '')
    {
        if ($recodeurl) {
            $recordLog = $recodeurl;
        } else {
            $recordLog = $this->container->config['config.PAY_LOG_FILE'];
        }
        $fp = fopen($recordLog, "a");
        flock($fp, LOCK_EX);
        fwrite($fp, "执行日期：" . date("Ymd H:i:s", time()) . "\n" . $word . "\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    /**
     * [getShortUrl 生成桌面图标]
     *
     * @author  dc <dc@wisdominfo.my>
     * @version 2015-11-09
     * @param   string $filename 图标名称
     * @return  mixed
     */
    public function getShortUrl($filename = null)
    {
        $filename = $filename ?: 'desktop.url';
        $shortcut = '';
        $shortcut .= '[InternetShortcut]' . PHP_EOL;
        $shortcut .= 'URL=http://' . $this->make('request')->headers->get('http_host') . PHP_EOL;
        $shortcut .= 'IDList=[{000214A0-0000-0000-C000-000000000046}]' . PHP_EOL;
        $shortcut .= 'Prop3=19,2';

        return $this->download($shortcut, $filename);

    }


    /**
     * [getIpAddress 重写IP获取方式]
     *
     * @author  dc <dc@wisdominfo.my>
     * @version 2015-11-10
     * @param   string $type [返回类型选择 string=普通ip, long=返回整型, array=返回数组整型和字符串IP]
     * @return  string|array
     */
    public function getIpAddress($type = 'string')
    {

        $ip = $this->make('request')->getClientIp();

        $long = sprintf("%u", ip2long($ip));
        if ($type == 'long') return $long;

        if ($type == 'array') return $long ? [$ip, $long] : ['0.0.0.0', 0];

        return $ip;
    }

    /**
     * [upload 文件上传组件]
     *
     * @see     重构自原symfony中DataModel curlPostPic模块功能 Video\ProjectBundle\Services\DataModel.php
     * @param        $user array [用户信息]
     * @param string $file string|resource [可以是二进制流|也可以是表单名]
     * @example $this->upload(array('uid'=>1,'username'=>'test','pic_total_size'=>1024, 'pic_used_size'=>512), 'upload_file');
     *                     本方法依赖zIMG图片服务器上传
     * @return array|mixed
     */
    public function upload($user, $file = 'Filedata')
    {
        if (!$user) return ['status' => 0, 'msg' => '用户信息获取失败!'];

        //获取上传图片服务器地址
        if ($imgServer = SiteSer::config('img_host')) {
            if (!$imgServer) return ['status' => 0, 'msg' => '获取图片服务器失败'];
        }
        $imgServer = rtrim($imgServer, '/') . '/upload?src=1&tid=1&uid=' . $user['uid'] . '&pid=1';

        $request = request();

        //兼容二进制流上传
        if (is_resource($file)) {
            $image = stream_get_contents($file);
            $image_size = strlen($image);
            $image_extension = 'png';

            //兼容表单方式上传
        } else {
            $files = $request->files->get('Filedata');
            if (!$files->getClientOriginalName()) return ['status' => 0, 'msg' => '上传图片错误'];
            $image_extension = $files->getClientOriginalExtension();
            $image_size = $files->getClientSize();
            $image = file_get_contents($files->getpathName());
        }

        //图片类型上传限制
        if (!in_array($image_extension, ['jpg', 'png', 'gif', 'jpeg'])) return ['status' => 0, 'msg' => '图片格式错误'];

        //单张照片大小1mb = 1000*1000
        if ($image_size > 1000000) return ['status' => 0, 'msg' => '图片上传超过限制大小'];

        //个人空间剩余判断
        if ($image_size > $user['pic_total_size'] - $user['pic_used_size']) return ['status' => 0, 'msg' => '你的个人相册空间不足！'];


        //系统扩展判断
        if (!function_exists('curl_init')) return ['status' => 0, 'msg' => '系统错误,不支持上传功能!'];

        //自定义curl_file_create函数
        if (!function_exists('curl_file_create')) {
            function curl_file_create($filename, $mimetype = '', $postname = '')
            {
                return "@$filename;filename="
                    . ($postname ?: basename($filename))
                    . ($mimetype ? ";type=$mimetype" : '');
            }
        }

        //上传文件到图片服务器
        $headers = [];
        $headers[] = 'Content-Type:' . $image_extension;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $imgServer);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $image);
        curl_setopt($ch, CURLOPT_INFILESIZE, $image_size);

        $result = curl_exec($ch);//已经传送过来是json字符串
        $error = curl_error($ch);
        curl_close($ch);
        if (!$result) return ['status' => 0, 'msg' => $error];
        return json_decode($result,true);
    }
}
