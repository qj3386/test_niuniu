<?php

namespace app\mobile\controller;

use think\Db;
use think\Log;
use think\Request;
use app\common\lib\Helper;
use app\common\controller\CommonController;

class Common extends CommonController
{
    protected $login_info;

    /**
     * 初始化
     * @param void
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();

		/* if (!Helper::is_mobile_access()) {
            header('Location: /'); exit;
        } */

        if (strlen($_SERVER['REQUEST_URI']) > 100) {
            header("HTTP/1.1 404 Not Found");
            header("Status: 404 Not Found");
            exit;
        }

        $this->login_info = session('mobile_user_info');
        $this->assign('login_info', $this->login_info);

        $this->isWechatBrowser = Helper::isWechatBrowser();
        $this->assign('isWechatBrowser', $this->isWechatBrowser);

        //请求日志
        Log::info('【请求地址】：' . request()->ip() . ' [' . date('Y-m-d H:i:s') . '] ' . request()->method() . ' ' . '/' . request()->module() . '/' . request()->controller() . '/' . request()->action());
        Log::info('【请求参数】：' . json_encode(request()->param(), JSON_UNESCAPED_SLASHES));
        Log::info('【请求头】：' . json_encode(request()->header(), JSON_UNESCAPED_SLASHES));
    }

    //判断是否登录
    public function isLogin()
    {
        //哪些方法不需要TOKEN验证
        $uncheck = array(
            'article/index',
            'article/detail',
            'articletype/index',
            'articletype/detail'
        );
        if (!in_array(strtolower(request()->controller() . '/' . request()->action()), $uncheck)) {
            $mobile_user_info = session('mobile_user_info');
            if (!$mobile_user_info) {
                session('mobile_user_info', null);

                //登录后跳转链接
                /* $return_url = url(request()->controller() . '/' . request()->action());
                if ($_SERVER['QUERY_STRING']) {
                    $return_url = $return_url . '?' . $_SERVER['QUERY_STRING'];
                } */
				$return_url = request()->url();
                session('mobile_history_back_url', $return_url);

                header('Location: ' . url('login/index'));
                exit;
            }
			//判断用户状态是否正常
			if ($mobile_user_info['status'] != 0) {
				session('mobile_user_info', null);
				header('Location: ' . url('login/index'));
				exit;
			}
        }
    }

    //判断是否实名认证
    public function is_certification()
    {
        if (empty($this->login_info['idcard']) || empty($this->login_info['true_name'])) {
            echo '<script language="JavaScript">alert("请实名认证后再操作"); location.replace("' . url('user/certification') . '");</script>';
            exit;
        }
    }

    //设置空操作
    public function _empty()
    {
        Helper::http404();
        //abort(404, '页面异常'); // 抛出404异常
    }
}