<?php

namespace app\admin\controller;

use GuzzleHttp\Client;
use think\facade\App;
use think\facade\Cache;
use think\facade\Env;

class Index extends BaseAdmin
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $site_name = config('site.site_name');
        $url = config('site.url');
        $img_site = config('site.img_site');
        $salt = config('site.salt');
        $api_key = config('site.api_key');
        $redis_host = config('cache.host');
        $redis_port = config('cache.port');
        $redis_auth = config('cache.password');
        $redis_prefix = config('cache.prefix');
        $front_tpl = config('site.tpl');
        $payment = config('site.payment');

        $this->assign([
            'site_name' => $site_name,
            'url' => $url,
            'img_site' => $img_site,
            'salt' => $salt,
            'api_key' => $api_key,
            'redis_host' => $redis_host,
            'redis_port' => $redis_port,
            'redis_auth' => $redis_auth,
            'redis_prefix' => $redis_prefix,
            'front_tpl' => $front_tpl,
            'payment' => $payment
        ]);
        return view();
    }

    public function update()
    {
        $site_name = input('site_name');
        $url = input('url');
        $img_site = input('img_site');
        $salt = input('salt');
        $api_key = input('api_key');
        $redis_host = input('redis_host');
        $redis_port = input('redis_port');
        $redis_auth = input('redis_auth');
        $redis_prefix = input('redis_prefix');
        $front_tpl = input('front_tpl');
        $payment = input('payment');
        $site_code = <<<INFO
        <?php
        return [
            'url' => '{$url}',
            'img_site' => '{$img_site}',
            'site_name' => '{$site_name}',
            'salt' => '{$salt}',
            'api_key' => '{$api_key}', 
            'tpl' => '{$front_tpl}',
            'payment' => '{$payment}'         
        ];
INFO;
        file_put_contents(App::getRootPath() . 'config/site.php', $site_code);

        $cache_code = <<<INFO
        <?php
        return [
            // 驱动方式
            'type'   => 'redis',
            'host' => '{$redis_host}',
            'port' => {$redis_port},
            'password'   => '{$redis_auth}',
            // 缓存保存目录
            'path'   => '../runtime/cache/',
            // 缓存前缀
            'prefix' => '{$redis_prefix}',
            // 缓存有效期 0表示永久缓存
            'expire' => 600,
        ];
INFO;
        file_put_contents(App::getRootPath() . 'config/cache.php', $cache_code);
        $this->success('修改成功', 'index', '', 1);
    }

    public function clearCache()
    {
        Cache::clear('redis');
        Cache::clear('pay');
        $rootPath = App::getRootPath();
        delete_dir_file($rootPath . '/runtime/cache/') && delete_dir_file($rootPath . '/runtime/temp/');
        $this->success('清理缓存', 'index', '', 1);
    }

    public function checkupdate()
    {
        $client = new Client();
        $srcUrl = Env::get('root_path') . "/public/static/html/version.txt";
        $localVersion = (int)str_replace('.', '', file_get_contents($srcUrl));
        $server = "http://update.xhxcms.xyz";
        $serverFileUrl = $server . "/public/static/html/version.txt";
        $res = $client->request('GET', $serverFileUrl);
        $serverVersion = (int)str_replace('.', '', $res->getBody());
        $msg = array();
        array_push($msg,'<p></p>');

        if ($serverVersion > $localVersion) {
            for ($i = $localVersion + 1; $i <= $serverVersion; $i++) {
                $res = $client->request('GET',"http://config.xhxcms.xyz/" . $i . ".json");
                $json = json_decode($res->getBody(), true);

                foreach ($json['update'] as $value) {
                    $data = $client->request('GET',$server . '/' . $value)->getBody(); //根据配置读取升级文件的内容
                    $saveFileName = Env::get('root_path') . $value;
                    file_put_contents($saveFileName, $data, true); //将内容写入到本地文件
                    array_push($msg,'<p style="margin-left: 15px;color:blue">升级文件' . $value . '</p>');
                }
                foreach ($json['delete'] as $value) {
                    $flag = unlink($srcUrl . '/' . $value);
                    if ($flag) {
                        array_push($msg,'<p style="margin-left: 15px;color:blue">删除文件' . $value . '</p>');
                    } else {
                        array_push($msg,'<p style="margin-left: 15px;color:darkred">删除文件失败</p>');
                    }
                }
            }
            array_push($msg,'<p style="margin-left:15px;">升级完成</p>');
        } else {
            $msg = ['已经是最新版本！当前版本是' . $localVersion];
        }
        return implode('',$msg);
    }
}
