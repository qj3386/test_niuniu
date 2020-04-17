<?php

namespace app\fladmin\controller;

use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\UserRechargeLogic;
use app\common\model\UserRecharge as UserRechargeModel;

class UserRecharge extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new UserRechargeLogic();
    }

    //列表
    public function index()
    {
        $where = array();
        if (isset($_REQUEST['keyword']) && !empty($_REQUEST['keyword'])) {
            $where['recharge_sn'] = array('like', '%' . $_REQUEST['keyword'] . '%');
        }
		//起止时间
        if (!empty($_REQUEST['start_date']) && !empty($_REQUEST['end_date'])) {
            $start_date = strtotime(date($_REQUEST['start_date']));
			$end_date = strtotime(date($_REQUEST['end_date']));
			if ($start_date > $end_date) {
				$this->error('起止时间不正确');
			}
			
			$end_date = $end_date + 24 * 3600;
			$where['add_time'] = [['>=',$start_date],['<=', $end_date]];
        }
        //用户ID
        if (isset($_REQUEST['user_id']) && $_REQUEST['user_id'] > 0) {
            $where['user_id'] = $_REQUEST['user_id'];
        }
        //充值状态：0处理中，1成功，2失败
        if (isset($_REQUEST['status'])) {
            $where['status'] = $_REQUEST['status'];
        }
        //充值类型：1微信，2支付宝
        if (isset($_REQUEST['pay_type']) && $_REQUEST['pay_type'] > 0) {
            $where['pay_type'] = $_REQUEST['pay_type'];
        }
        $list = $this->getLogic()->getPaginate($where, 'id desc');

        $this->assign('page', $list->render());
        $this->assign('list', $list);
        //echo '<pre>';print_r($list);exit;

		//当天累计提现金额
		$time = strtotime(date('Y-m-d')); //今天日期时间戳
		$cumulative_amount_today = db('user_recharge')->where(['add_time'=> [['>=',$time],['<', ($time + 3600 * 24)]], 'status'=>1])->sum('money');
		$this->assign('cumulative_amount_today', $cumulative_amount_today);

		//当天几个人充值
		$recharge_people_today = db('user_recharge')->group('user_id')->where(['add_time'=> [['>=',$time],['<', ($time + 3600 * 24)]], 'status'=>1])->count();
		$this->assign('recharge_people_today', $recharge_people_today);

        return $this->fetch();
    }

    //添加
    public function add()
    {
        if (Helper::isPostRequest()) {
            $res = $this->getLogic()->add($_POST);
            if ($res['code'] != ReturnData::SUCCESS) {
                $this->error($res['msg']);
            }

            $this->success($res['msg'], url('index'), '', 1);
        }

        return $this->fetch();
    }

    //修改
    public function edit()
    {
        if (Helper::isPostRequest()) {
            $where['id'] = $_POST['id'];
            unset($_POST['id']);

            $res = $this->getLogic()->edit($_POST, $where);
            if ($res['code'] != ReturnData::SUCCESS) {
                $this->error($res['msg']);
            }

            $this->success($res['msg'], url('index'), '', 1);
        }

        if (!checkIsNumber(input('id', null))) {
            $this->error('参数错误');
        }
        $where['id'] = input('id');
        $this->assign('id', $where['id']);

        $post = $this->getLogic()->getOne($where);
        $this->assign('post', $post);

        return $this->fetch();
    }

    //删除
    public function del()
    {
        if (!checkIsNumber(input('id', null))) {
            $this->error('删除失败！请重新提交');
        }
        $where['id'] = input('id');

        $res = $this->getLogic()->del($where);
        if ($res['code'] != ReturnData::SUCCESS) {
            $this->error($res['msg']);
        }

        $this->success('删除成功');
    }

	//忽略
    public function ignore()
    {
        if (!checkIsNumber(input('id', null))) {
            $this->error('参数错误');
        }
        $where['id'] = input('id');

        $res = model('UserRecharge')->edit(['is_ignore'=>1], $where);
        if (!$res) {
            $this->error('操作失败');
        }

        $this->success('操作成功');
    }

	//充值结果操作
    public function change_status()
    {
        if (!checkIsNumber(input('id', null)) || input('status', '') == '') {
            $this->error('参数错误');
        }
        $where['id'] = input('id');
		$where['status'] = 0;
		$data['status'] = input('status');
		$user_recharge = model('UserRecharge')->getOne($where);
		if (!$user_recharge) {
            $this->error('记录不存在');
        }

		//充值状态：0处理中，1成功，2失败
		if ($data['status'] == 1) {
			//增加用户余额及余额记录
			$user_money_data['user_id'] = $user_recharge['user_id'];
			$user_money_data['type'] = 0;
			$user_money_data['money'] = $user_recharge['money'];
			$user_money_data['desc'] = '充值';
			$user_money = logic('UserMoney')->add($user_money_data);
			if ($user_money['code'] != ReturnData::SUCCESS) { $this->error('操作失败'); }
			//更改累计充值金额
			model('Sysconfig')->setIncrement(['varname' => 'CMS_CUMULATIVE_RECHARGE'], 'value', $user_recharge['money']);

			$user = db('user')->where(['id'=>$user_recharge['user_id']])->find();
			logic('Tool')->notice(substr($user['mobile'],1) . '-' . $user['true_name'] .'-CZ成功，' . $user_recharge['money'] . '，' . str_replace(".", "", $_SERVER['SERVER_NAME']) . sysconfig('CMS_WEBNAME') . '累计：' . sysconfig('CMS_CUMULATIVE_RECHARGE'));
		} elseif ($data['status'] == 2) {

		} else {
			$this->error('参数错误');
		}

        $res = model('UserRecharge')->edit(['status'=>$data['status'], 'update_time'=>time()], $where);
        if (!$res) {
            $this->error('操作失败');
        }

        $this->success('操作成功');
    }
}