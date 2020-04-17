<?php

namespace app\mobile\controller;

use think\Db;
use think\Request;
use think\Validate;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\UserLogic;
use app\common\lib\wechat\WechatAuth;

class Login extends Common
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new UserLogic();
    }

    //登录
    public function index()
    {
        $mobile_user_info = session('mobile_user_info');
        if ($mobile_user_info) {
            if (isset($_SERVER['HTTP_REFERER'])) {
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit;
            }
            header('Location: ' . url('user/index'));
            exit;
        }

        $return_url = '';
        if (isset($_REQUEST['return_url']) && !empty($_REQUEST['return_url'])) {
            $return_url = $_REQUEST['return_url'];
            session('mobile_history_back_url', $return_url);
        }
        if ($return_url == '' && session('mobile_history_back_url')) {
            $return_url = session('mobile_history_back_url');
        }

		$post_data = input('post.');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if ($post_data['user_name'] == '') {
                $this->error('账号不能为空');
            }

            if ($post_data['password'] == '') {
                $this->error('密码不能为空');
            }

            $login_data = array(
                'user_name' => $post_data['user_name'],
                'password' => $post_data['password']
            );
            $res = logic('User')->login($login_data);
            if ($res['code'] != ReturnData::SUCCESS) {
                $this->error($res['msg']);
            }

            session('mobile_user_info', $res['data']);
            session('mobile_history_back_url', null);

            if ($return_url != '') {
                header('Location: ' . $return_url);
                exit;
            }
            header('Location: ' . url('user/index'));
            exit;
        }

        return $this->fetch();
    }

    //注册
    public function register()
    {
        if (Helper::isPostRequest()) {
            $data = input('post.');
            $data['add_time'] = $data['update_time'] = time();
            $validate_data = [
                ['mobile', 'require|/^[1][3,4,5,6,7,8,9][0-9]{9}$/', '手机号不能为空|手机号格式不正确'],
                ['password', 'require|length:6,16|alphaNum', '密码不能为空|密码6-16位|密码只能包含字母/数字'],
                ['re_password', 'require|confirm:password', '确认密码不能为空|确认密码不正确'],
                /* ['smscode', 'require|length:4', '短信验证码不能为空|短信验证码格式不正确'], */
                ['parent_id', 'max:11', '推荐人ID格式不正确'],
            ];
			//短信注册验证开关，0关闭，1开启
			if (sysconfig('CMS_IS_SMS_REGISTRATION_VERIFICATION') == 1) {
				$validate_data = [
					['mobile', 'require|/^[1][3,4,5,6,7,8,9][0-9]{9}$/', '手机号不能为空|手机号格式不正确'],
					['password', 'require|length:6,16|alphaNum', '密码不能为空|密码6-16位|密码只能包含字母/数字'],
					['re_password', 'require|confirm:password', '确认密码不能为空|确认密码不正确'],
					['smscode', 'require|length:4', '短信验证码不能为空|短信验证码格式不正确'],
					['parent_id', 'max:11', '推荐人ID格式不正确'],
				];
			}
            $validate = new Validate($validate_data);
            if (!$validate->check($data)) {
                Util::echo_json(ReturnData::create(ReturnData::FAIL, null, $validate->getError()));
            }

			if ($data['mobile'] == $data['password']) {
				Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '密码不能是手机号码'));
			}
			
			if ($data['password'] == '123456') {
				Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '密码过于简单，请更改后提交'));
			}

            //判断推荐人ID是否存在，如果是11位则是推荐人手机号
            if (isset($data['parent_id']) && $data['parent_id'] > 0) {
                $parent_user = model('User')->getOne(array('id' => $data['parent_id']));
                if (!$parent_user) {
                    if (mb_strlen($data['parent_id'], "utf-8") == 11) {
                        if ($data['parent_id'] == $data['mobile']) {
                            Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '推荐人不能是自己'));
                        }
                        $user = model('User')->getOne(array('mobile' => $data['parent_id']));
                        if (!$user) {
                            Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '推荐人不存在'));
                        }
                        $data['parent_id'] = $user['id'];
                    } else {
						Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '推荐人不存在'));
					}
                }
            }

            //判断手机号
            if (model('User')->getOne(array('mobile' => $data['mobile']))) {
                Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '手机号已存在'));
            }

            $data['pay_password2'] = $data['password2'] = $data['password']; //明文密码
            $data['pay_password'] = $data['password'] = logic('User')->passwordEncrypt($data['password']); //注册时，登录密码跟支付密码一致

            //短信注册验证开关，0关闭，1开启
			if (sysconfig('CMS_IS_SMS_REGISTRATION_VERIFICATION') == 1) {
				$verify_code = model('VerifyCode')->isVerify(['mobile' => $data['mobile'], 'type' => 1, 'code' => $data['smscode']]);
				if (!$verify_code) {
					Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '短信验证码不正确或已过期'));
				}
			}

            $user_id = model('User')->add($data);
            if (!$user_id) {
                Util::echo_json(ReturnData::create(ReturnData::SYSTEM_FAIL, null, '操作失败，请稍后再试'));
            }

            //修改用户名
            model('User')->edit(['user_name' => 'u' . $user_id], ['id' => $user_id]);

            //注册奖励
            $user_money_data['user_id'] = $user_id;
            $user_money_data['type'] = 0; // 0增加,1减少
            $user_money_data['money'] = sysconfig('CMS_USER_REGISTRATION_REWARD');
            $user_money_data['desc'] = '新会员注册奖励';
            logic('UserMoney')->add($user_money_data);

            //消息通知
            $user_message_data['user_id'] = $user_id;
            $user_message_data['title'] = '注册成功，欢迎您的到来';
            logic('UserMessage')->add($user_message_data);

            $res = ReturnData::create(ReturnData::SUCCESS, $user_id, '注册成功');
            $res['url'] = url('login/index');
            Util::echo_json($res);
        }

        if (session('mobile_user_info')) {
            if (isset($_SERVER["HTTP_REFERER"])) {
                header('Location: ' . $_SERVER["HTTP_REFERER"]);
                exit;
            }
            header('Location: ' . url('user/index'));
            exit;
        }

        $return_url = '';
        if (isset($_REQUEST['return_url']) && !empty($_REQUEST['return_url'])) {
            session('mobile_history_back_url', $_REQUEST['return_url']);
        }
        //推荐人ID存在session，首页入口也存了一次
        if (isset($_REQUEST['invite_code']) && !empty($_REQUEST['invite_code'])) {
            session('mobile_user_invite_code', $_REQUEST['invite_code']);
        }

        return $this->fetch();
    }

    /**
     * 忘记密码
     */
    public function resetpwd()
    {
        if (Helper::isPostRequest()) {
            $data = input('post.');
            $validate = new Validate([
                ['mobile', 'require|/^[1][3,4,5,6,7,8,9][0-9]{9}$/', '手机号不能为空|手机号格式不正确'],
                ['password', 'require|length:6,16|alphaNum', '新密码不能为空|密码6-16位|密码只能包含字母/数字'],
                ['re_password', 'require|confirm:password', '确认密码不能为空|确认密码不正确'],
                ['smscode', 'require|length:4', '短信验证码不能为空|短信验证码格式不正确'],
            ]);
            if (!$validate->check($data)) {
                Util::echo_json(ReturnData::create(ReturnData::FAIL, null, $validate->getError()));
            }

			if ($data['mobile'] == $data['password']) {
				Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '密码不能是手机号码'));
			}
			
			if ($data['password'] == '123456') {
				Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '密码过于简单，请更改后提交'));
			}

            //判断手机号
            $record = model('User')->getOne(array('mobile' => $data['mobile']));
            if (!$record) {
                Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '手机号不存在'));
            }

			if ($record['status'] != 0) {
                Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '该账户暂时不能操作，请联系客服'));
            }

            $edit_user_data['update_time'] = time();
            $edit_user_data['pay_password2'] = $edit_user_data['password2'] = $data['password']; //明文密码
            $edit_user_data['pay_password'] = $edit_user_data['password'] = logic('User')->passwordEncrypt($data['password']); //登录密码跟支付密码一致

            $verify_code = model('VerifyCode')->isVerify(['mobile' => $data['mobile'], 'type' => 3, 'code' => $data['smscode']]);
            if (!$verify_code) {
                Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '短信验证码不正确或已过期'));
            }

            $res = model('User')->edit($edit_user_data, ['id' => $record['id']]);
            if (!$res) {
                Util::echo_json(ReturnData::create(ReturnData::SYSTEM_FAIL, null, '操作失败，请稍后再试'));
            }

            $res = ReturnData::create(ReturnData::SUCCESS);
            $res['url'] = url('login/index');
            Util::echo_json($res);
        }

        if (session('mobile_user_info')) {
            if (isset($_SERVER["HTTP_REFERER"])) {
                header('Location: ' . $_SERVER["HTTP_REFERER"]);
                exit;
            }
            header('Location: ' . url('user/index'));
            exit;
        }

        return $this->fetch();
    }

    /**
     * 注册获取短信验证码
     * @param $mobile 手机号
     * @param $captcha 验证码
     * @return string 成功失败信息
     */
    public function getsmscode()
    {
        $data = input('get.');
        $validate = new Validate([
            ['mobile', 'require|/^[1][3,4,5,6,7,8,9][0-9]{9}$/', '手机号不能为空|手机号格式不正确'],
            ['captcha', 'require|length:4', '校验码不能为空|校验码不正确'],
            ['type', 'require|number|in:1,3,4', '验证码类型不能为空|验证码类型格式不正确|验证码类型格式不正确'],
        ]);
        if (!$validate->check($data)) {
            Util::echo_json(ReturnData::create(ReturnData::FAIL, null, $validate->getError()));
        }
        //图形验证码验证
        if (!captcha_check($data['captcha'])) {
            exit(json_encode(ReturnData::create(ReturnData::FAIL, null, '校验码错误或已过期')));
        }

        $res = model('VerifyCode')->getVerifyCodeBySmsbao($data['mobile'], $data['type']);
        if ($res['code'] != ReturnData::SUCCESS) {
            exit(json_encode(ReturnData::create(ReturnData::FAIL, null, $res['msg'])));
        }

        exit(json_encode(ReturnData::create(ReturnData::SUCCESS)));
    }

    //微信网页授权登录
    public function wx_oauth()
    {
        $weixin_oauth = session('weixin_oauth');
        if (!isset($weixin_oauth['userinfo'])) {
            $wechat_auth = new WechatAuth(sysconfig('CMS_WX_APPID'), sysconfig('CMS_WX_APPSECRET'));

            // 获取code码，用于和微信服务器申请token。 注：依据OAuth2.0要求，此处授权登录需要用户端操作
            if (!isset($_GET['code'])) {
                $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                $callback_url = $http_type . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; //回调地址，当前页面
                //生成唯一随机串防CSRF攻击
                $state = md5(uniqid(rand(), true));
                session('weixin_oauth.state', $state); //存到SESSION
                $authorize_url = $wechat_auth->get_authorize_url($callback_url, $state);

                header("Location: $authorize_url");
                exit;
            }

            // 依据code码去获取openid和access_token，自己的后台服务器直接向微信服务器申请即可
            session('weixin_oauth.code', $_GET['code']);

            if ($_GET['state'] != session('weixin_oauth.state')) {
                $this->error('您访问的页面不存在或已被删除');
            }

            //得到 access_token 与 openid
            session('weixin_oauth.token', $wechat_auth->get_access_token($_GET['code']));
            // 依据申请到的access_token和openid，申请Userinfo信息。
            session('weixin_oauth.userinfo', $wechat_auth->get_user_info(session('weixin_oauth.token')['access_token'], session('weixin_oauth.token')['openid']));
        }

        $post_data = array(
            'openid' => session('weixin_oauth.userinfo')['openid'],
            'unionid' => isset(session('weixin_oauth.userinfo')['unionid']) ? session('weixin_oauth.userinfo')['unionid'] : '',
            'nickname' => isset(session('weixin_oauth.userinfo')['nickname']) ? Helper::filterEmoji(session('weixin_oauth.userinfo')['nickname']) : '',
            'sex' => session('weixin_oauth.userinfo')['sex'],
            'head_img' => session('weixin_oauth.userinfo')['headimgurl'],
            'parent_id' => session('mobile_user_invite_code') ? session('mobile_user_invite_code') : 0,
            'parent_mobile' => '',
            'mobile' => ''
        );
        $url = get_api_url_address() . '/login/wx_login';
        $res = Util::curl_request($url, $post_data, 'POST');
        if ($res['code'] != ReturnData::SUCCESS) {
            $this->error('操作失败');
        }

        session('mobile_user_info', $res['data']);
        header('Location: ' . url('user/index'));
        exit;
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        //session_unset();
        //session_destroy(); // 退出登录，清除session
        session('mobile_user_info', null);
        $this->success('退出成功', url('index/index'));
    }

    /**
     * 重新登录
     */
    public function relogin()
    {
        //session_unset();
        //session_destroy(); // 退出登录，清除session
        session('mobile_user_info', null);
        header('Location: ' . url('login/index'));
        exit;
    }
}