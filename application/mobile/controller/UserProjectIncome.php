<?php

namespace app\mobile\controller;

use think\Db;
use think\Request;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\UserProjectIncomeLogic;
use app\common\logic\UserProjectIncome as UserProjectIncomeModel;

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

    //收益明细-列表
    public function index()
    {
        //参数
        $pagesize = 15;
        $offset = 0;
        if (isset($_REQUEST['page']) && $_REQUEST['page'] > 0) {
            $offset = ($_REQUEST['page'] - 1) * $pagesize;
        } else {
            //计算收益
            logic('UserProject')->revenue_calculation($this->login_info['id']);
        }
        //获取收益明细列表
        $where = array();
        $orderby = 'add_time desc';
        $where['status'] = 1;
        $where['user_id'] = $this->login_info['id'];
        if (isset($_REQUEST['project_id']) && $_REQUEST['project_id'] > 0) {
            $where['project_id'] = input('project_id');
        }
        if (isset($_REQUEST['user_project_id']) && $_REQUEST['user_project_id'] > 0) {
            $where['user_project_id'] = input('user_project_id');
        }
        $res = $this->getLogic()->getList($where, $orderby, '*', $offset, $pagesize);

        $assign_data['list'] = $res['list'];
        //总页数
        $assign_data['totalpage'] = ceil($res['count'] / $pagesize);

        if (isset($_REQUEST['page_ajax']) && $_REQUEST['page_ajax'] == 1) {
            $html = '';

            if ($res['list']) {
                foreach ($res['list'] as $k => $v) {
                    $html .= '<li><a href="' . url('user_project/detail') . '?id=' . $v['user_project_id'] . '">';
                    $html .= '<span class="green">+ ' . $v['money'] . '</span>';
                    $html .= '<div class="info"><p class="tit">' . $v['title'] . '</p>';
                    $html .= '<p class="time">' . date('Y-m-d H:i:s', $v['add_time']) . '</p></div>';
                    $html .= '</a></li>';
                }
            }

            exit(json_encode($html));
        }

        $this->assign($assign_data);
        return $this->fetch();
    }
}