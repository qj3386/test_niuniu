<?php

namespace app\fladmin\controller;

use think\Db;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\UserLogLogic;

class UserLog extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new UserLogLogic();
    }

    //列表
    public function index()
    {
        $where = array();
        if (!empty($_REQUEST["keyword"])) {
            $where['content'] = array('like', '%' . $_REQUEST['keyword'] . '%');
        }
        //用户ID
        if (isset($_REQUEST['user_id'])) {
            $where['user_id'] = $_REQUEST['user_id'];
        }
        //IP
        if (isset($_REQUEST['ip'])) {
            $where['ip'] = $_REQUEST['ip'];
        }
        //请求方式
        if (isset($_REQUEST['http_method'])) {
            $where['http_method'] = $_REQUEST['http_method'];
        }
        $list = $this->getLogic()->getPaginate($where, ['id' => 'desc']);

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
            if ($res['code'] == ReturnData::SUCCESS) {
                $this->success($res['msg'], url('index'), '', 1);
            }

            $this->error($res['msg']);
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
            if ($res['code'] == ReturnData::SUCCESS) {
                $this->success($res['msg'], url('index'), '', 1);
            }

            $this->error($res['msg']);
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
        if ($res['code'] == ReturnData::SUCCESS) {
            $this->success('删除成功');
        }

        $this->error($res['msg']);
    }

    //清空
    public function clear()
    {
        // 截断表
        Db::execute('truncate table `fl_user_log`');
        $this->success('操作成功', url('index'), '', 1);
    }
}