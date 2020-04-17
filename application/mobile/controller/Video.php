<?php

namespace app\mobile\controller;

use think\Db;
use think\Request;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\VideoLogic;

class Video extends Common
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new VideoLogic();
    }

    //列表
    public function index()
    {
        $pagesize = 15;
        $offset = 0;
        if (isset($_REQUEST['page'])) {
            $offset = ($_REQUEST['page'] - 1) * $pagesize;
        }
        $where['delete_time'] = 0;
        $where['add_time'] = ['<', time()];
        $res = logic('Video')->getList($where, 'id desc', ['content'], $offset, $pagesize);
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
                    $html .= '<a class="aerial-item" href="javascript:;">';
                    $html .= '<div style="width:100%;height:220px;" class="video_box">' . $v['url'] . '</div>';
					$html .= '<div class="txt">';
					$html .= '<div class="name">' . $v['title'] . '</div>';
					$html .= '<div class="desc">' . $v['description'] . '</div>';
					$html .= '</div>';
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

        $res = logic('Video')->getOne(array('id' => $id));
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