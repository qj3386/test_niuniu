<?php

namespace app\fladmin\controller;

use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\UserProjectLogic;
use app\common\model\UserProject as UserProjectModel;

class UserProject extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new UserProjectLogic();
    }

    //列表
    public function index()
    {
        $where = array();
        if (!empty($_REQUEST['keyword'])) {
            $where['title'] = array('like', '%' . $_REQUEST['keyword'] . '%');
        }
        //用户ID
        if (isset($_REQUEST['user_id'])) {
            $where['user_id'] = $_REQUEST['user_id'];
        }
        //项目ID
        if (isset($_REQUEST['project_id'])) {
            $where['project_id'] = $_REQUEST['project_id'];
        }
        //分红状态：0投资中，1已完结
        if (isset($_REQUEST['status'])) {
            $where['status'] = $_REQUEST['status'];
        }
        //还款方式，0到期还本还息，1每日返息到期返本，7每周返息到期返本，10000每日复利到期返本返息
        if (isset($_REQUEST['dividend_mode'])) {
            $where['dividend_mode'] = $_REQUEST['dividend_mode'];
        }
        $list = $this->getLogic()->getPaginate($where, 'id desc');

        $this->assign('page', $list->render());
		if ($list) {
			foreach ($list as $k=>$v) {
				$list[$k]['user'] = model('User')->getOne(['id'=>$v['user_id']]);
			}
		}
		
        $this->assign('list', $list);
        //echo '<pre>';print_r($list);exit;
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
        $id = $where['id'] = input('id');

		$user_project = model('UserProject')->getOne(['id' => $id]);
        if (!$user_project) {
			$this->error('记录不存在');
        }

        $res = model('UserProject')->del($where);
        if (!$res) {
            $this->error('操作失败');
        }
		//用户投资的项目收益明细
		model('UserProjectIncome')->del(['user_project_id' => $id]);
		//减少用户累计消费金额
		model('User')->setDecrement(array('id' => $user_project['user_id']), 'consumption_money', $user_project['money']);

        //修改用户等级
        logic('User')->user_rank_calculation($user_project['user_id']);

        $this->success('删除成功');
    }
}