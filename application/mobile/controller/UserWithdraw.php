<?php

namespace app\mobile\controller;

use think\Db;
use think\Request;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\UserWithdrawLogic;
use app\common\model\UserWithdraw as UserWithdrawModel;

class UserWithdraw extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new UserWithdrawLogic();
    }

    //提现列表
    public function index()
    {
        //参数
        $pagesize = 30;
        $offset = 0;
        if (isset($_REQUEST['page'])) {
            $offset = ($_REQUEST['page'] - 1) * $pagesize;
        }
        //获取提现列表
        $where = array();
        $orderby = 'id desc';
        $where['user_id'] = $this->login_info['id'];
        $res = $this->getLogic()->getList($where, $orderby, '*', $offset, $pagesize);

        $assign_data['list'] = $res['list'];
        //总页数
        $assign_data['totalpage'] = ceil($res['count'] / $pagesize);

        if (isset($_REQUEST['page_ajax']) && $_REQUEST['page_ajax'] == 1) {
            $html = '';

            if ($res['list']) {
                foreach ($res['list'] as $k => $v) {
                    $html .= '<tr>';
                    $html .= '<td>' . floatval($v['money']) . '</td>';
                    $html .= '<td>' . date('Y-m-d H:i', $v['add_time']) . '</td>';
                    $color = '';
                    if ($v['status'] == 2) {
                        echo $color = 'color:#04ba06;';
                    } elseif ($v['status'] == 4) {
                        echo $color = 'color:red;';
                    }
                    $html .= '<td style="' . $color . '">' . $v['status_text'] . '</td>';
                    $html .= '</tr>';
                }
            }

            exit(json_encode($html));
        }

        $this->assign($assign_data);
        return $this->fetch();
    }

    //提现
    public function add()
    {
        $this->is_certification();
        if (empty($this->login_info['bank_name']) || empty($this->login_info['bank_branch_name']) || empty($this->login_info['bank_card_number'])) {
            echo '<script language="JavaScript">alert("请先绑定银行卡后再操作"); location.replace("' . url('user/bind_bank_card') . '");</script>';
            exit;
        }

        if (Helper::isPostRequest()) {
			$today_timestamp = strtotime(date('Y-m-d')); //今天日期时间戳
			$current_timestamp = time();
			//提现时间段8:00-24:00
			if ($current_timestamp > ($today_timestamp + 8 * 3600) && $current_timestamp < ($today_timestamp + 24 * 3600)) {
				
			} else {
				Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '提现时间8:00 ~ 24:00，请稍后再试'));
			}
			
            $data = input('post.');
            $data['user_id'] = $this->login_info['id'];
            $data['name'] = $this->login_info['true_name'];
            $data['money'] = $data['money'];
            $data['bank_name'] = $this->login_info['bank_name'];
            $data['bank_place'] = $this->login_info['bank_branch_name'];
            $data['account'] = $this->login_info['bank_card_number'];
            $data['method'] = 'bank'; //提现方式，weixin，bank，alipay
            $data['status'] = 1; //提现状态：0未处理,1处理中,2成功,3取消，4拒绝
            $res = $this->getLogic()->add($data);
            if ($res['code'] != ReturnData::SUCCESS) {
                Util::echo_json($res);
            }

            $res['url'] = url('user/index');
            Util::echo_json($res);
        }

        $res = logic('User')->getUserInfo(['id' => $this->login_info['id']]);
        $this->login_info = array_merge($this->login_info, $res);
        session('mobile_user_info', $this->login_info);
        $this->assign('login_info', $this->login_info);

        //是否达到可提现要求，0否
        $assign_data['is_withdraw'] = 0;
        $assign_data['min_withdraw_money'] = sysconfig('CMS_MIN_WITHDRAWAL_MONEY'); //最低可提现金额
        if ($this->login_info['money'] >= $assign_data['min_withdraw_money']) {
            $assign_data['is_withdraw'] = 1;
        }

        $this->assign($assign_data);
        return $this->fetch();
    }

}