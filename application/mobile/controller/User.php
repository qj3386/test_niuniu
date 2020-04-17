<?php

namespace app\mobile\controller;

use think\Db;
use think\Request;
use think\Loader;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\UserLogic;
use app\common\model\User as UserModel;

class User extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new UserLogic();
    }

    //个人中心
    public function index()
    {
        //计算收益
        logic('UserProject')->revenue_calculation($this->login_info['id']);
        //获取用户信息
        $res = $this->getLogic()->getUserInfo(['id' => $this->login_info['id']]);
        if (!$res) {
            $this->error(ReturnData::getCodeText(ReturnData::RECORD_NOT_EXIST));
        }
		if ($res['status'] != 0) {
			session('mobile_user_info', null);
			header('Location: ' . url('login/index'));
			exit;
		}

        if ($res['head_img']) {
            $res['head_img'] = (substr($res['head_img'], 0, strlen('http')) === 'http') ? $res['head_img'] : get_site_cdn_address() . $res['head_img'];
        }

        $this->login_info = array_merge($this->login_info, $res);
        session('mobile_user_info', $this->login_info);
        $this->assign('login_info', $this->login_info);

        //待收本金
        $this->assign('principal_recovered', db('user_project')->where(['status' => 0, 'user_id' => $this->login_info['id']])->sum('money'));
        return $this->fetch();
    }

    //个人中心-设置
    public function setting()
    {
        $res = $this->getLogic()->getUserInfo(['id' => $this->login_info['id']]);
        $this->login_info = array_merge($this->login_info, $res);
        session('mobile_user_info', $this->login_info);
        $this->assign('login_info', $this->login_info);
        return $this->fetch();
    }

    //资金管理
    public function account()
    {
        $res = $this->getLogic()->getUserInfo(['id' => $this->login_info['id']]);
        $this->login_info = array_merge($this->login_info, $res);
        session('mobile_user_info', $this->login_info);
        $this->assign('login_info', $this->login_info);
        return $this->fetch();
    }

    //我的团队-列表
    public function myteam()
    {
        //获取会员信息
        $res = $this->getLogic()->getUserInfo(['id' => $this->login_info['id']]);
        $this->login_info = array_merge($this->login_info, $res);

        //获取用户推介资金信息
        $where['user_id'] = $this->login_info['id'];
        $assign_data['user_referral_commission'] = logic('UserReferralCommission')->getOne($where);

        //获取直属下级会员列表
        $pagesize = 15;
        $offset = 0;
        if (isset($_REQUEST['page'])) {
            $offset = ($_REQUEST['page'] - 1) * $pagesize;
        }

        $where = array();
        $where['parent_id'] = $this->login_info['id'];
        $res = $this->getLogic()->getList($where, 'id desc', 'parent_id,mobile,nickname,user_name,head_img,sex,commission,consumption_money,user_rank,status,add_time', $offset, $pagesize);
        if ($res['count'] > 0) {
            foreach ($res['list'] as $k => $v) {
                if (!empty($v['head_img'])) {
                    $res['list'][$k]['head_img'] = (substr($v['head_img'], 0, strlen('http')) === 'http') ? $v['head_img'] : get_site_cdn_address() . $v['head_img'];
                }
            }
        }
        $assign_data['list'] = $res['list'];
        //总页数
        $assign_data['totalpage'] = ceil($res['count'] / $pagesize);
        if (isset($_REQUEST['page_ajax']) && $_REQUEST['page_ajax'] == 1) {
            $html = '';

            if (!empty($res['list'])) {
                foreach ($res['list'] as $k => $v) {
                    $html .= '<li><span class="goods_thumb" style="width:72px;height:72px;"><img style="width:72px;height:72px;" alt="' . $v['user_name'] . '" src="' . $v['head_img'] . '"></span>';
                    $html .= '<div class="goods_info"><p class="goods_tit">' . $v['user_name'] . '</p>';
                    $html .= '<p style="line-height:24px;">佣金：' . $v['commission'] . '</p>';
                    $html .= '<p style="line-height:24px;">注册时间：' . date('Y-m-d', $v['add_time']) . '</p>';
                    $html .= '</div></li>';
                }
            }

            exit(json_encode($html));
        }

        $this->assign($assign_data);
        return $this->fetch();
    }

    //推介赚钱
    public function referral()
    {
        $this->is_certification();
        //获取直属下级会员列表
        $pagesize = 15;
        $offset = 0;
        if (isset($_REQUEST['page'])) {
            $offset = ($_REQUEST['page'] - 1) * $pagesize;
        }

        $where = array();
        $where['parent_id'] = $this->login_info['id'];
        $res = $this->getLogic()->getList($where, 'id desc', 'parent_id,mobile,nickname,user_name,head_img,sex,commission,consumption_money,user_rank,status,add_time', $offset, $pagesize);
        if ($res['count'] > 0) {
            foreach ($res['list'] as $k => $v) {
                if (!empty($v['head_img'])) {
                    $res['list'][$k]['head_img'] = (substr($v['head_img'], 0, strlen('http')) === 'http') ? $v['head_img'] : get_site_cdn_address() . $v['head_img'];
                }
            }
        }
        $assign_data['list'] = $res['list'];
        //总页数
        $assign_data['totalpage'] = ceil($res['count'] / $pagesize);
        if (isset($_REQUEST['page_ajax']) && $_REQUEST['page_ajax'] == 1) {
            $html = '';

            if (!empty($res['list'])) {
                foreach ($res['list'] as $k => $v) {
                    $html .= '<a href="javascript:;" class="am-list-item line-twoline">';
                    if (!empty($v['head_img'])) {
                        $html .= '<div class="am-list-thumb"><img src="' . $v['head_img'] . '" alt=""></div>';
                    }
                    $html .= '<div class="am-list-content">';
                    $html .= '<div class="am-list-title">' . substr_replace($v['mobile'], '****', 3, 4) . '</div>';
                    $html .= '<div class="am-list-brief">注册时间：' . date('Y-m-d', $v['add_time']) . '</div>';
                    $html .= '</div></a>';
                }
            }

            exit(json_encode($html));
        }

        $this->assign($assign_data);
        return $this->fetch();
    }

    //转换为帐户余额
    public function user_referral_commission_turn_user_money()
    {
        return $this->fetch();
    }

    //签到
    public function signin()
    {
        if (Helper::isPostRequest()) {
            $res = $this->getLogic()->signin(array('id' => $this->login_info['id']));
            Util::echo_json($res);
        }

        $this->is_certification();

        $res = $this->getLogic()->getUserInfo(['id' => $this->login_info['id']]);
        $this->login_info = array_merge($this->login_info, $res);
        session('mobile_user_info', $this->login_info);
        $this->assign('login_info', $this->login_info);

        $user_list = model('User')->getAll([], 'signin_num desc', 'id,mobile,signin_num', 10);
        $this->assign('user_list', $user_list);

        $sign_person_times = db('sysconfig')->where(['varname' => 'CMS_SIGN_PERSON_TIMES'])->value('value');
        $this->assign('sign_person_times', $sign_person_times);

        return $this->fetch();
    }
	
	//项目进度提升
    public function project_progress_improvement()
    {
		$project_list = model('Project')->getAll(['status'=>0,'delete_time'=>0], ['orderRaw', 'rand()'], 'id,progress', 6);
		if (!$project_list) {
			return true;
		}
		foreach ($project_list as $k=>$v) {
			model('Project')->edit(['progress'=>($v['progress'] + rand(1, 3) * 0.01)], ['id'=>$v['id']]);
		}
		return true;
	}

    //实名认证
    public function certification()
    {
        if (Helper::isPostRequest()) {
			//判断是否已实名
			if (!empty($this->login_info['idcard'])) {
				Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '您已实名'));
			}
			$post_data = input('post.');
            $user_validate = Loader::validate('User');
            $check = $user_validate->scene('certification')->check($post_data);
            if (!$check) {
                Util::echo_json(ReturnData::create(ReturnData::PARAMS_ERROR, null, $user_validate->getError()));
            }
			$true_name = $this->test_input(trim(input('true_name')));
			$idcard = $this->test_input(trim(strtoupper(input('idcard'))));

			//实名认证次数超过限制
			$user_log_where['user_id'] = $this->login_info['id'];
			$user_log_where['http_method'] = 'POST';
			$user_log_where['url'] = '/mobile/user/certification';
			$count = model('UserLog')->getCount($user_log_where);
			if ($count > 5) {
				session('mobile_user_info', null);
				model('User')->edit(['status'=>2], ['id'=>$this->login_info['id']]);
				$res = ReturnData::create(ReturnData::SUCCESS, null, '实名认证次数超过限制，请联系人工客服处理');
				$res['url'] = url('login/relogin');
				Util::echo_json($res);
			}

			$user = model('User')->getOne(['idcard'=>$idcard]);
			if ($user) {
				Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '该信息已被使用'));
			}

			$idcard_area = '';
			if (sysconfig('CMS_IS_TRUE_NAME_VERIFICATION') == 1) {
				//可以接入相应接口-身份证二要素验证
				$idcard_certification_res = $this->idcard_certification($idcard, $true_name);
				if ($idcard_certification_res['code'] != ReturnData::SUCCESS) {
					$remaining_times = 5 - $count;
					if ($remaining_times == 0) {
						Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '实名认证不通过，请联系人工客服处理'));
					}
					Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '实名认证不通过，还剩' . $remaining_times . '次机会，请谨慎操作'));
				}
				$idcard_area = $idcard_certification_res['area'] . $idcard_certification_res['birthday'];
			}
            //认证成功修改用户信息
            $res = model('User')->edit(['true_name' => $true_name, 'idcard' => $idcard, 'idcard_area' => $idcard_area], ['id' => $this->login_info['id']]);
            if (!$res) {
                Util::echo_json(ReturnData::create(ReturnData::FAIL));
            }

			//项目进度提升
			$this->project_progress_improvement();

            $res = $this->getLogic()->getUserInfo(['id' => $this->login_info['id']]);
            $this->login_info = array_merge($this->login_info, $res);
            session('mobile_user_info', $this->login_info);
            $this->assign('login_info', $this->login_info);

            $res = ReturnData::create(ReturnData::SUCCESS);
            $res['url'] = input('jump_url');
            Util::echo_json($res);
        }
        $jump_url = '';
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            $jump_url = $_SERVER['HTTP_REFERER'];
        }
        $this->assign('jump_url', $jump_url);

        $res = $this->getLogic()->getUserInfo(['id' => $this->login_info['id']]);
        $this->login_info = array_merge($this->login_info, $res);
        session('mobile_user_info', $this->login_info);
        $this->assign('login_info', $this->login_info);

        return $this->fetch();
    }

    //银行卡绑定
    public function bind_bank_card()
    {
        if (Helper::isPostRequest()) {
			//判断是否已绑定银行卡
			if (!empty($this->login_info['bank_card_number'])) {
				Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '您已绑定'));
			}
			$post_data = input('post.');
            $user_validate = Loader::validate('User');
            $check = $user_validate->scene('bind_bank_card')->check($post_data);
            if (!$check) {
				Util::echo_json(ReturnData::create(ReturnData::PARAMS_ERROR, null, $user_validate->getError()));
            }

			$bank_card_number = $this->test_input(trim(input('bank_card_number')));
            $bank_name = $this->test_input(trim(input('bank_name')));
			$bank_branch_name = $this->test_input(trim(input('bank_branch_name')));

			//银行卡绑定次数超过限制
			$user_log_where['user_id'] = $this->login_info['id'];
			$user_log_where['http_method'] = 'POST';
			$user_log_where['url'] = '/mobile/user/bind_bank_card';
			$count = model('UserLog')->getCount($user_log_where);
			if ($count > 5) {
				session('mobile_user_info', null);
				model('User')->edit(['status'=>2], ['id'=>$this->login_info['id']]);
				$res = ReturnData::create(ReturnData::SUCCESS, null, '银行卡绑定次数超过限制，请联系人工客服处理');
				$res['url'] = url('login/relogin');
				Util::echo_json($res);
			}

			$user = model('User')->getOne(['bank_card_number'=>$bank_card_number]);
			if ($user) {
				Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '该信息已被使用'));
			}

			$bank_card_type = '';
			if (sysconfig('CMS_IS_TRUE_NAME_VERIFICATION') == 1) {
				//可以接入相应接口-银行卡三要素验证
				$bank_card_certification_res = $this->bank_card_certification($bank_card_number, $this->login_info['idcard'], $this->login_info['true_name']);
				if ($bank_card_certification_res['code'] != ReturnData::SUCCESS) {
					$remaining_times = 5 - $count;
					if ($remaining_times == 0) {
						Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '银行卡信息验证不通过，请联系人工客服处理'));
					}
					Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '银行卡信息验证不通过，还剩' . $remaining_times . '次机会，请谨慎操作'));
				}
				$bank_card_type = $bank_card_certification_res['cardName'] . '-' . $bank_card_certification_res['cardType'];
			}
            //认证成功修改用户信息
            if (!empty($bank_card_certification_res['bank'])) {
                $bank_name = $bank_card_certification_res['bank'];
            }
            $res = model('User')->edit(['bank_name' => $bank_name, 'bank_branch_name' => $bank_branch_name, 'bank_card_number' => $bank_card_number, 'bank_card_type' => $bank_card_type], ['id' => $this->login_info['id']]);
            if (!$res) {
                Util::echo_json(ReturnData::create(ReturnData::FAIL));
            }

            $res = $this->getLogic()->getUserInfo(['id' => $this->login_info['id']]);
            $this->login_info = array_merge($this->login_info, $res);
            session('mobile_user_info', $this->login_info);
            $this->assign('login_info', $this->login_info);

            $res = ReturnData::create(ReturnData::SUCCESS);
            $res['url'] = input('jump_url');
            Util::echo_json($res);
        }
		$this->is_certification();
        $jump_url = '';
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            $jump_url = $_SERVER['HTTP_REFERER'];
        }
        $this->assign('jump_url', $jump_url);

        $res = $this->getLogic()->getUserInfo(['id' => $this->login_info['id']]);
        $this->login_info = array_merge($this->login_info, $res);
        session('mobile_user_info', $this->login_info);
        $this->assign('login_info', $this->login_info);

        return $this->fetch();
    }

    /**
     * 身份证实名认证-身份证二要素验证-四川涪擎大数据技术有限公司https://shop922915o2.market.aliyun.com/
     * https://market.aliyun.com/products/57000002/cmapi029522.html
     * {
     * "status": "01",                          状态码:详见状态码说明 01 通过，02不通过
     * "msg": "实名认证通过！",                 提示信息
     * "idCard": "5111261995****1111",          身份证号
     * "name": "张三",                          姓名
     * "sex": "男",                             性别
     * "area": "四川省乐山市夹江县",            身份证所在地(参考)
     * "province": "四川省",                    省(参考)
     * "city": "乐山市",                        市(参考)
     * "prefecture": "夹江县",                  区县(参考)
     * "birthday": "1995-11-11",                出生年月
     * "addrCode": "511126",                    地区代码
     * "lastCode": "1"                          身份证校验码
     * }
     */
    public function idcard_certification($idcard, $true_name)
    {
        $host = "https://idcardcert.market.alicloudapi.com";
        $path = "/idCardCert";
        $method = "GET";
        $appcode = sysconfig('CMS_ALIYUN_MARKET_APPCODE');
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "idCard=" . $idcard . "&name=" . $true_name;
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        //curl_setopt($curl, CURLOPT_HEADER, true); 如不输出json, 请打开这行代码，打印调试头部状态码。
        //状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
        if (1 == strpos("$" . $host, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $out_put = curl_exec($curl);
        $res = json_decode($out_put, true);
        $res['code'] = 1;
        if (isset($res['status']) && $res['status'] == '01') {
            $res['code'] = 0;
        }
        return $res;
    }

	//测试
    public function testx()
    {
		var_dump($this->idcard_certification('610481198504222636', '张磊乐'));
		var_dump($this->bank_card_certification('6222032604004170549', '610481198504222636', '张磊乐'));
		exit;
	}

    /**
     * 银行卡三要素验证-四川涪擎大数据技术有限公司https://shop922915o2.market.aliyun.com/
     * https://market.aliyun.com/products/57000002/cmapi028807.html
     * {
     * "status": "01",                            状态码:01 验证通过；02 验证不通过；详见状态码说明
     * "msg": "验证通过",                         信息
     * "idCard": "5111261995****1111",            身份证号
     * "accountNo": "621411111****9563",          银行卡号
     * "name": "张三",                            姓名
     * "bank": "中国建设银行",                    银行名称
     * "cardName": "龙卡通",                      银行卡名称
     * "cardType": "借记卡",                      银行卡类型
     * "sex": "男",                               性别
     * "area": "四川省乐山市夹江县",              身份证所在地址(参考)
     * "province": "四川省",                      省(参考)
     * "city": "乐山市",                          市(参考)
     * "prefecture": "夹江县",                    区县(参考)
     * "birthday": "1995-11-11",                  出生年月
     * "addrCode": "511126",                      地区代码
     * "lastCode": "1"                            校验码
     * }
     */
    public function bank_card_certification($bank_card_number, $idcard, $true_name)
    {
        $host = "https://bcard3and4.market.alicloudapi.com";
        $path = "/bank3CheckNew";
        $method = "GET";
        $appcode = sysconfig('CMS_ALIYUN_MARKET_APPCODE');
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "accountNo=" . $bank_card_number . "&idCard=" . $idcard . "&name=" . urlencode($true_name);
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        //curl_setopt($curl, CURLOPT_HEADER, true);   如不输出json, 请打开这行代码，打印调试头部状态码。
        //状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
        if (1 == strpos("$" . $host, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $out_put = curl_exec($curl);
        $res = json_decode($out_put, true);
        $res['code'] = 1;
        if (isset($res['status']) && $res['status'] == '01') {
            $res['code'] = 0;
        }
        return $res;
    }

    //用户信息修改，仅能修改一些不敏感的信息
    public function user_info_update()
    {
        if (Helper::isPostRequest()) {
            $where['id'] = $this->login_info['id'];

            $data = array();
            if (input('user_name', '') !== '') {
                $data['user_name'] = $this->test_input(input('user_name'));
            }
            if (input('email', '') !== '') {
                $data['email'] = input('email');
            }
            if (input('sex', '') !== '') {
                $data['sex'] = input('sex');
            }
            if (input('birthday', '') !== '') {
                $data['birthday'] = input('birthday');
            }
            if (input('address_id', '') !== '') {
                $data['address_id'] = input('address_id');
            }
            if (input('nickname', '') !== '') {
                $data['nickname'] = $this->test_input(input('nickname'));
            }
            if (input('group_id', '') !== '') {
                $data['group_id'] = input('group_id');
            }
            if (input('head_img', '') !== '') {
                $data['head_img'] = $this->test_input(input('head_img'));
            }
            if (input('refund_account', '') !== '') {
                $data['refund_account'] = $this->test_input(input('refund_account'));
            }
            if (input('refund_name', '') !== '') {
                $data['refund_name'] = $this->test_input(input('refund_name'));
            }

            $res = $this->getLogic()->userInfoUpdate($data, $where);
            Util::echo_json($res);
        }
    }

	// 通过这个函数来对输入数据进行检测，防止CSS注入脚本攻击
	public function test_input($str)
	{
		$str = trim($str); // 去除用户输入中的空格、tab、换行符等信息
		$str = stripcslashes($str); // 去除输入中的"/"反斜杠，防止有转义符的存在
		$str = htmlspecialchars($str); // 再次将数据转回html转义代码
		//包含特殊字符
		if (preg_match("/[\',:;?!#$%^&+=)(<>{}]|\]|\[|\\\|\"|\|/", $str)) {
			return '';
		}
		return $str;
	}

    //修改用户密码
    public function user_password_update()
    {
        if (Helper::isPostRequest()) {
            $data['password'] = input('password', '');
            $data['old_password'] = input('old_password', '');
            if ($data['password'] == $data['old_password']) {
                Util::echo_json(ReturnData::create(ReturnData::PARAMS_ERROR, null, '新旧密码相同'));
            }

            $res = $this->getLogic()->userPasswordUpdate($data, array('id' => $this->login_info['id']));
            if ($res['code'] != ReturnData::SUCCESS) {
                Util::echo_json($res);
            }

            session('mobile_history_back_url', url('user/index'));
            $res['url'] = url('login/relogin');
            Util::echo_json($res);
        }

        $res = $this->getLogic()->getUserInfo(['id' => $this->login_info['id']]);
        $this->login_info = array_merge($this->login_info, $res);
        session('mobile_user_info', $this->login_info);
        $this->assign('login_info', $this->login_info);
        return $this->fetch();
    }

    //修改用户支付密码
    public function user_pay_password_update()
    {
        if (Helper::isPostRequest()) {
            $data['pay_password'] = input('pay_password', '');
            $data['old_pay_password'] = input('old_pay_password', '');

            if ($data['pay_password'] == $data['old_pay_password']) {
                Util::echo_json(ReturnData::create(ReturnData::PARAMS_ERROR, null, '新旧支付密码相同'));
            }

            $res = $this->getLogic()->userPayPasswordUpdate($data, array('id' => $this->login_info['id']));
            $res['url'] = input('jump_url');
            Util::echo_json($res);
        }

        $jump_url = '';
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            $jump_url = $_SERVER['HTTP_REFERER'];
        }
        $this->assign('jump_url', $jump_url);
        $res = $this->getLogic()->getUserInfo(['id' => $this->login_info['id']]);
        $this->login_info = array_merge($this->login_info, $res);
        session('mobile_user_info', $this->login_info);
        $this->assign('login_info', $this->login_info);
        return $this->fetch();
    }

}