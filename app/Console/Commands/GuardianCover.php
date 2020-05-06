<?php

namespace App\Console\Commands;

use App\Entities\UserHost;
use App\Models\Users;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class GuardianCover extends Command
{
    protected $users;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guardian:cover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '守護功能 - 主播海報資料建立';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cursor = null;

        do {
            $userHostIds = Redis::hscan('hroom_ids', $cursor);
            $cursor = $userHostIds[0];

            foreach ($userHostIds[1] as $k => $v) {
                /* 取得用戶資料 */
                $user = Users::find($k);
                if (empty($user) || empty($user->cover)) {
                    $this->info('查無用戶(' . $user->uid . ')資料，故略過');
                    Log::info('查無用戶(' . $user->uid . ')資料，故略過');
                    continue;
                }

                if (!empty(UserHost::find($k))) {
                    $this->info('用戶ID (' . $user->uid . ') 已存在主播海報檔，故略過');
                    Log::info('用戶ID (' . $user->uid . ') 已存在主播海報檔，故略過');
                    continue;
                }

                /* 檔案上傳 */
//                dd(Storage::path('uploads/s88888/anchor/' . $user->cover));
                /* 因應zimg上傳方法需做resource處理 */
                $fo = fopen(Storage::path('uploads/s88888/anchor/' . $user->cover), 'r');
                if (false === $fo) {
                    $this->info('用戶ID (' . $user->uid . ') 無法取得海報檔案，故略過');
                    Log::info('用戶ID (' . $user->uid . ') 無法取得海報檔案，故略過');
                    continue;
                }

                /* 主播海報檔案處理並更新主播資料 */
                $imgData = $this->upload($user, $fo);
                fclose($fo);

                Log::info('主播海報zimg資訊: ' . var_export($imgData, true));

                if (empty($imgData['ret'])) {
                    $this->info('用戶ID (' . $user->uid . ') zimg上传失败');
                    Log::info('用戶ID (' . $user->uid . ') zimg上传失败');
                    continue;
                }

                /* 更新主播海報資訊 */
                UserHost::insert(['id' => $user->uid, 'cover' => $imgData['info']['md5']]);
            }

        } while ($cursor);
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
    private function upload($user, $file = 'Filedata')
    {
        if (!$user) return ['status' => 0, 'msg' => '用户信息获取失败!'];

        //获取上传图片服务器地址
        if ($imgServer = Redis::hget('hsite_config:1', 'img_host')) {
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
            if (!$files || !$files->getClientOriginalName()) return ['status' => 0, 'msg' => '上传图片错误'];
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
        return json_decode($result, true);
    }
}
