<?php

namespace app\mobile\controller;

use think\Db;
use think\Request;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\UserMoneyLogic;
use app\common\logic\UserMoney as UserMoneyModel;

class UserMoney extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new UserMoneyLogic();
    }

    //余额明细-列表
    public function index()
    {
        //参数
        $pagesize = 15;
        $offset = 0;
        if (isset($_REQUEST['page'])) {
            $offset = ($_REQUEST['page'] - 1) * $pagesize;
        }
        //获取余额明细列表
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
                    $html .= '<li>';
                    if ($v['type'] == 0) {
                        $html .= '<span class="green">+ ' . $v['money'] . '</span>';
                    } else {
                        $html .= '<span>- ' . $v['money'] . '</span>';
                    }
                    $html .= '<div class="info"><p class="tit">' . $v['desc'] . '</p>';
                    $html .= '<p class="time">' . date('Y-m-d H:i:s', $v['add_time']) . '</p></div>';
                    $html .= '</li>';
                }
            }

            exit(json_encode($html));
        }

        $this->assign($assign_data);
        return $this->fetch();
    }
}