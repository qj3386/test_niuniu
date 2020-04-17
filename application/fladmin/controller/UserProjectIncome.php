<?php

namespace app\fladmin\controller;

use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\UserProjectIncomeLogic;
use app\common\model\UserProjectIncome as UserProjectIncomeModel;

class UserProjectIncome extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new UserProjectIncomeLogic();
    }

    //列表
    public function index()
    {
        $where = array();
        $where['status'] = 1; //状态：0未支付，1已支付
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
        $list = $this->getLogic()->getPaginate($where, 'add_time desc');

        $this->assign('page', $list->render());
		if ($list) {
			foreach ($list as $k=>$v) {
				$list[$k]['user'] = model('User')->getOne(['id'=>$v['user_id']]);
				$list[$k]['user_project'] = model('UserProject')->getOne(['id'=>$v['user_project_id']]);
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
        $where['id'] = input('id');

        $res = $this->getLogic()->del($where);
        if ($res['code'] != ReturnData::SUCCESS) {
            $this->error($res['msg']);
        }

        $this->success('删除成功');
    }

	//30天应返款明细
    public function cumulative_refund_details()
    {
		$refund_details_list = [];
		//开始日期时间戳，今天
		$time = strtotime(date('Y-m-d'));
		$limit = 50;
		for ($x = 0; $x < $limit; $x++) {
			$start_time = $time + 3600 * 24 * $x;
			$temp['date'] = date('Y-m-d', $start_time);
			$temp['money'] = db('user_project_income')->where(['add_time'=> [['>=',$start_time],['<', ($start_time + 3600 * 24)]]])->sum('money');
			$temp['amount'] = db('user_project_income')->alias('a')->join('user_project p','a.user_project_id = p.id')->field(array('a.*', 'p.money'))->where(['a.is_last' => 1, 'a.add_time' => [['>=',$start_time],['<', ($start_time + 3600 * 24)]]])->sum('p.money');
			$refund_details_list[] = $temp;
		}
		//echo '<pre>';print_r($refund_details_list);exit;
        $this->assign('list', $refund_details_list);
        $this->assign('limit', $limit);
        return $this->fetch();
    }

    //今日应返款明细
    public function today()
    {
        $where = array();
		//今天时间戳
		$time = strtotime(date('Y-m-d'));
		//日期
        if (isset($_REQUEST['date'])) {
            $time = strtotime($_REQUEST['date']);
        }
		$where['add_time'] = [['>=',$time],['<', ($time + 3600 * 24)]];
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
        //支付状态
        if (isset($_REQUEST['status'])) {
            $where['status'] = $_REQUEST['status'];
        }
        $list = $this->getLogic()->getPaginate($where, 'add_time desc');

        $this->assign('page', $list->render());
		if ($list) {
			foreach ($list as $k=>$v) {
				$list[$k]['user'] = model('User')->getOne(['id'=>$v['user_id']]);
				$list[$k]['user_project'] = model('UserProject')->getOne(['id'=>$v['user_project_id']]);
			}
		}
        $this->assign('list', $list);
        //echo '<pre>';print_r($list);exit;
        return $this->fetch();
    }

}