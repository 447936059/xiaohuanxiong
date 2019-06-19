<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/2/26
 * Time: 13:47
 */

namespace app\admin\controller;


use app\model\User;
use app\service\UserService;
use think\App;

class Users extends BaseAdmin
{
    protected $userService;

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        $this->userService = new UserService();
    }

    public function index()
    {
        $data = $this->userService->getAdminPagedUsers(1);
        $this->assign([
            'users' => $data['users'],
            'count' => $data['count']
        ]);
        return view();
    }

    public function disabled()
    {
        $data = $this->userService->getAdminPagedUsers(0);
        $this->assign([
            'users' => $data['users'],
            'count' => $data['count']
        ]);
        return view();
    }

    public function disable()
    {
        $id = input('id');
        if (empty($id) || is_null($id)) {
            return ['status' => 0];
        }
        $user = User::get($id);
        $result = $user->delete();
        if ($result) {
            return ['status' => 1];
        } else {
            return ['status' => 0];
        }
    }

    public function enable(){
        $id = input('id');
        if (empty($id) || is_null($id)) {
            return ['status' => 0];
        }
        $user = User::onlyTrashed()->find($id);
        $result = $user->restore();
        if ($result) {
            return ['status' => 1];
        } else {
            return ['status' => 0];
        }
    }
}