<?php

namespace app\fladmin\controller;

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

    //列表
    public function index()
    {
        $where = array();
        $where['delete_time'] = UserWithdrawModel::USER_WITHDRAW_UNDELETE;
        if (!empty($_REQUEST['keyword'])) {
            $where['name'] = array('like', '%' . $_REQUEST['keyword'] . '%');
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
        if (isset($_REQUEST['user_id'])) {
            $where['user_id'] = $_REQUEST['user_id'];
        }
        //提现状态：0未处理,1处理中,2成功,3取消，4拒绝
        if (isset($_REQUEST['status'])) {
            $where['status'] = $_REQUEST['status'];
        }
        $list = $this->getLogic()->getPaginate($where, 'id desc');

        $this->assign('page', $list->render());
        $this->assign('list', $list);
        //echo '<pre>';print_r($list);exit;

		//当天累计提现金额
		$time = strtotime(date('Y-m-d')); //今天日期时间戳
		$cumulative_amount_today = db('user_withdraw')->where(['add_time'=> [['>=',$time],['<', ($time + 3600 * 24)]], 'status'=>2])->sum('money');
		$this->assign('cumulative_amount_today', $cumulative_amount_today);

		//当天几个人提现
		$withdraw_people_today = db('user_withdraw')->group('user_id')->where(['add_time'=> [['>=',$time],['<', ($time + 3600 * 24)]], 'status'=>2])->count();
		$this->assign('withdraw_people_today', $withdraw_people_today);

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

        $res = model('UserWithdraw')->edit(['is_ignore'=>1], $where);
        if (!$res) {
            $this->error('操作失败');
        }

        $this->success('操作成功');
    }

    //提现审核
    public function change_status()
    {
        if (!empty($_POST['id'])) {
            $id = $_POST['id'];
            unset($_POST['id']);
        } else {
            $id = '';
            exit;
        }

        if (!isset($_POST['type'])) {
            return ReturnData::create(ReturnData::PARAMS_ERROR);
        }

        $user_withdraw = model('UserWithdraw')->getOne(array('id' => $id, 'status' => ['<', 2]));
        if (!$user_withdraw) {
            return ReturnData::create(ReturnData::RECORD_NOT_EXIST);
        }

        //0拒绝，1成功
        $edit_user_withdraw = array();
		$edit_user_withdraw['update_time'] = time();
        if ($_POST['type'] == 0) {
            $edit_user_withdraw['status'] = 4;
			$edit_user_withdraw['re_note'] = trim(input('re_note', ''));
            //增加用户余额及余额记录
            $user_money_data['user_id'] = $user_withdraw['user_id'];
            $user_money_data['type'] = 0;
            $user_money_data['money'] = $user_withdraw['money'];
            $user_money_data['desc'] = '提现失败-返还余额';
            $user_money = logic('UserMoney')->add($user_money_data);
        } elseif ($_POST['type'] == 1) {
            $edit_user_withdraw['status'] = 2;
			//更改累计提现金额
			model('Sysconfig')->setIncrement(['varname' => 'CMS_CUMULATIVE_WITHDRAWAL'], 'value', $user_withdraw['money']);
        }

        if (!$edit_user_withdraw) {
            return ReturnData::create(ReturnData::FAIL);
        }

        $res = model('UserWithdraw')->edit($edit_user_withdraw, array('id' => $id));
        if (!$res) {
            return ReturnData::create(ReturnData::FAIL);
        }

        return ReturnData::create(ReturnData::SUCCESS);
    }
}