<?php

namespace app\mobile\controller;

use think\Db;
use think\Request;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\UserProjectLogic;
use app\common\logic\UserProject as UserProjectModel;

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

    //投资明细-列表
    public function index()
    {
        //参数
        $pagesize = 15;
        $offset = 0;
        if (isset($_REQUEST['page'])) {
            $offset = ($_REQUEST['page'] - 1) * $pagesize;
        }
        //获取投资明细列表
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
                    $html .= '<li><a href="' . url('detail') . '?id=' . $v['id'] . '">';
                    $html .= '<span style="font-size:14px;';
                    if ($v['status'] == 0) {
                        $html .= 'color:#eeb026;';
                    } else {
                        $html .= 'color:#04ba06;';
                    }
                    $html .= '">' . $v['status_text'] . '</span>';
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

    //投资详情
    public function detail()
    {
        if (!checkIsNumber(input('id', null))) {
            Helper::http404();
        }
        $id = input('id');
        $res = logic('UserProject')->getOne(array('id' => $id, 'user_id' => $this->login_info['id']));
        if (empty($res)) {
            Helper::http404();
        }
        $assign_data['post'] = $res;
        $assign_data['user_project_income_list'] = logic('UserProjectIncome')->getAll(['user_project_id' => $id]);
        //dd($assign_data);
        $this->assign($assign_data);
        return $this->fetch();
    }

    //投资协议
    public function agreement()
    {
        if (!checkIsNumber(input('id', null))) {
            Helper::http404();
        }
        $id = input('id');
        $res = logic('UserProject')->getOne(array('id' => $id, 'user_id' => $this->login_info['id']));
        if (empty($res)) {
            Helper::http404();
        }
		$res['project'] = model('Project')->getOne(array('id' => $res['project_id']));
        $assign_data['post'] = $res;
        //dd($assign_data['post']);
        $this->assign($assign_data);
        return $this->fetch();
    }

    //投资协议下载
    public function download_agreement_pdf()
    {
        require EXTEND_PATH . "mpdf/mpdf.php";
        header('Content-type: application/pdf');

        $agreement_id = input('agreement_id', null);
        if (!checkIsNumber($agreement_id)) {
            Helper::http404();
        }
        $_obj_mpdf = new \mPDF('zh-cn', 'A4', 0, '', 10, 10, 15, 15); //左右上下
        //设置PDF页眉内容 (自定义编辑样式)
        $header = '<table width="95%" style="margin:0 auto;border-bottom: 1px solid #4F81BD; vertical-align: middle; font-family:serif; font-size: 9pt; color: #000088;">
        <tr><td width="10%"></td><td width="80%" align="center" style="font-size:16px;color:#A0A0A0">页眉</td><td width="10%" style="text-align: right;"></td></tr></table>';
        //设置PDF页脚内容 (自定义编辑样式)
        $footer = '<table width="100%" style=" vertical-align: bottom; font-family:serif; font-size: 9pt; color: #000088;"><tr style="height:30px"></tr><tr>
        <td width="10%"></td><td width="80%" align="center" style="font-size:14px;color:#A0A0A0">页脚</td><td width="10%" style="text-align: left;">
        页码：{PAGENO}/{nb}</td></tr></table>';
        //添加页眉和页脚到PDF中
        //$_obj_mpdf->SetHTMLHeader($header);
        //$_obj_mpdf->SetHTMLFooter($footer);
        $_obj_mpdf->SetDisplayMode('fullpage');//设置PDF显示方式
        $_obj_mpdf->useAdobeCJK = true;
        $_obj_mpdf->autoScriptToLang = true;
        $_obj_mpdf->autoLangToFont = true;
        //设置PDF css样式
        //$stylesheet =file_get_contents('themes/wei/css/bootstrap.min.css');
        //$_obj_mpdf->WriteHTML($stylesheet, 1);
        ini_set('user_agent', 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; SV1; .NET CLR 1.1.4322)');
        $html = file_get_contents(url('index/agreement') . '?id=' . $agreement_id);
        $_obj_mpdf->WriteHTML($html);//将$content内容写入PDF
        //输出PDF 直接下载PDF文件
        $_obj_mpdf->Output('投资协议' . date('YmdHis') . '.pdf', 'D'); //直接下载PDF文件
        //$_obj_mpdf->Output(); //输出PDF 浏览器预览文件 可右键保存
        exit;
    }
}