<?php

namespace app\mobile\controller;

use think\Db;
use think\Request;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\PageLogic;

class Page extends Common
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new PageLogic();
    }

    //列表
    public function index()
    {
        //最新
        $list = cache("mobile_page_index_list");
        if (!$list) {
            $where2['delete_time'] = 0;
            $where2['group_id'] = 0;
            $list = logic('Page')->getAll($where2, 'listorder asc', ['content'], 50);
            cache("mobile_page_index_list", $list, 3600 * 24 * 30); //1天
        }
        $this->assign('list', $list);
        return $this->fetch();
    }

    //详情
    public function detail()
    {
        $id = input('id');

        $where['filename'] = $id;
        $post = cache("mobile_page_detail_$id");
        if (!$post) {
            $where['delete_time'] = 0;
            $post = $this->getLogic()->getOne($where);
            if (!$post) {
                Helper::http404();
            }
            cache("mobile_page_detail_$id", $post, 2592000);
        }

        $this->assign('post', $post);
        return $this->fetch();
    }
}