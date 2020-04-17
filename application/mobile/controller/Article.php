<?php

namespace app\mobile\controller;

use think\Db;
use think\Request;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\ArticleLogic;

class Article extends Common
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new ArticleLogic();
    }

    //列表
    public function index()
    {
        $id = input('id');
        //分类
        $assign_data['post'] = logic('ArticleType')->getOne(array('id' => $id));
        //var_dump($assign_data['post']);exit;
        $pagesize = 15;
        $offset = 0;
        if (isset($_REQUEST['page'])) {
            $offset = ($_REQUEST['page'] - 1) * $pagesize;
        }
        $where['delete_time'] = 0;
        $where['add_time'] = ['<', time()];
        if ($id) {
            $where['type_id'] = $id;
        }
        $res = logic('Article')->getList($where, 'id desc', ['content'], $offset, $pagesize);
        if ($res['list']) {
            foreach ($res['list'] as $k => $v) {

            }
        }
        $assign_data['list'] = $res['list'];
        $totalpage = ceil($res['count'] / $pagesize);
        $this->assign('totalpage', $totalpage);
        if (isset($_REQUEST['page_ajax']) && $_REQUEST['page_ajax'] == 1) {
            $html = '';
            if ($res['list']) {
                foreach ($res['list'] as $k => $v) {
                    $html .= '<a href="' . url('article/detail') . '?id=' . $v['id'] . '" class="am-list-item">';
                    if (!empty($v['litpic'])) {
                        $html .= '<div class="am-list-thumb"><img src="' . $v['litpic'] . '" alt=""></div>';
                    }
                    $html .= '<div class="am-list-content">' . $v['title'] . '</div>';
                    $html .= '<div class="am-list-arrow" aria-hidden="true"><span class="am-icon arrow horizontal"></span></div>';
                    $html .= '</a>';
                }
            }

            exit(json_encode($html));
        }

        //dd($assign_data);
        $this->assign($assign_data);
        return $this->fetch();
    }

    //详情
    public function detail()
    {
        if (!checkIsNumber(input('id', null))) {
            Helper::http404();
        }
        $id = input('id');

        $res = logic('Article')->getOne(array('id' => $id));
        if (empty($res)) {
            Helper::http404();
        }
        $res['content'] = preg_replace('/src=\"\/uploads\/allimg/', "src=\"" . http_host() . "/uploads/allimg", $res['content']);
        $assign_data['post'] = $res;
        //dd($assign_data['post']);
        $this->assign($assign_data);
        return $this->fetch();
    }
}