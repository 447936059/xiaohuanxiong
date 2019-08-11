<?php

namespace app\admin\controller;

use GuzzleHttp\Client;
use think\facade\App;
use think\facade\Cache;
use think\facade\Env;
use think\Request;

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
        $id_salt = config('site.id_salt');
        $api_key = config('site.api_key');
        $front_tpl = config('site.tpl');
        $payment = config('site.payment');

        $this->assign([
            'site_name' => $site_name,
            'url' => $url,
            'img_site' => $img_site,
            'salt' => $salt,
            'id_salt' => $id_salt,
            'api_key' => $api_key,
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
        $id_salt = input('id_salt');
        $api_key = input('api_key');
        $front_tpl = input('front_tpl');
        $payment = input('payment');
        $site_code = <<<INFO
        <?php
        return [
            'url' => '{$url}',
            'img_site' => '{$img_site}',
            'site_name' => '{$site_name}',
            'salt' => '{$salt}',
            'id_salt' => '{$id_salt}',
            'api_key' => '{$api_key}', 
            'tpl' => '{$front_tpl}',
            'payment' => '{$payment}'         
        ];
INFO;
        file_put_contents(App::getRootPath() . 'config/site.php', $site_code);
        $this->success('修改成功', 'index', '', 1);
    }

    public function redis(){
        if ($this->request->isPost()){
            $redis_host = input('redis_host');
            $redis_port = input('redis_port');
            $redis_auth = input('redis_auth');
            $redis_prefix = input('redis_prefix');
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
            $this->success('修改成功');
        }
        $redis_host = config('cache.host');
        $redis_port = config('cache.port');
        $redis_auth = config('cache.password');
        $redis_prefix = config('cache.prefix');
        $this->assign([
            'redis_host' => $redis_host,
            'redis_port' => $redis_port,
            'redis_auth' => $redis_auth,
            'redis_prefix' => $redis_prefix,
        ]);
        return view();
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
        //$msg = array();
        //array_push($msg, '<p></p>');
        echo '<p></p>';
        ob_flush();
        flush();

        if ($serverVersion > $localVersion) {
            for ($i = $localVersion + 1; $i <= $serverVersion; $i++) {
                $res = $client->request('GET', "http://config.xhxcms.xyz/" . $i . ".json");
                if((int)($res->getStatusCode()) == 200)
                {
                    $json = json_decode($res->getBody(), true);

                    foreach ($json['update'] as $value) {
                        $data = $client->request('GET', $server . '/' . $value)->getBody(); //根据配置读取升级文件的内容
                        $saveFileName = Env::get('root_path') . $value;
                        $dir = dirname($saveFileName);
                        if (!file_exists($dir)) {
                            mkdir($dir, 0777,true);
                        }
                        file_put_contents($saveFileName, $data, true); //将内容写入到本地文件
                        //array_push($msg, '<p style="margin-left: 15px;color:blue">升级文件' . $value . '</p>');
                        echo '<p style="margin-left: 15px;color:blue">升级文件' . $value . '</p>';
                        ob_flush();
                        flush();
                    }
                    foreach ($json['delete'] as $value) {
                        $flag = unlink(Env::get('root_path') . '/' . $value);
                        if ($flag) {
                            //array_push($msg, '<p style="margin-left: 15px;color:blue">删除文件' . $value . '</p>');
                            echo '<p style="margin-left: 15px;color:blue">删除文件' . $value . '</p>';
                            ob_flush();
                            flush();
                        } else {
                            //array_push($msg, '<p style="margin-left: 15px;color:darkred">删除文件失败</p>');
                            echo '<p style="margin-left: 15px;color:darkred">删除文件失败</p>';
                            ob_flush();
                            flush();
                        }
                    }
                }
            }
           // array_push($msg, '<p style="margin-left:15px;">升级完成</p>');
            echo '<p style="margin-left:15px;">升级完成</p>';
        } else {
            //$msg = ['已经是最新版本！当前版本是' . $localVersion];
            echo '已经是最新版本！当前版本是' . $localVersion;
            ob_flush();
            flush();
        }
        //return implode('', $msg);
    }

    public function kamiconfig(Request $request)
    {
        if ($request->isPost()) {
            $data = $request->param();
            $validate = new \app\admin\validate\Vipcode();
            if ($validate->check($data)) {
                $str = <<<INFO
        <?php
        return [
            'salt' => 'salt',
            'vipcode' => [
                'day' => '{$data["vipcode_day"]}',
                'num' => '{$data["vipcode_num"]}'
            ],
            'chargecode' => [
                'money' => '{$data["chargecode_money"]}',
                'num' => '{$data["chargecode_num"]}'
            ]
        ];
INFO;
                file_put_contents(App::getRootPath() . 'config/kami.php', $str);
                $this->success('保存成功');
            } else {
                $this->error($validate->getError());
            }
        } else {
            $this->assign([
                'salt' => config('kami.salt'),
                'vipcode_day' => config('kami.vipcode.day'),
                'vipcode_num' => config('kami.vipcode.num'),
                'chargecode_money' => config('kami.chargecode.money'),
                'chargecode_num' => config('kami.chargecode.num')
            ]);
            return view();
        }
    }
}
