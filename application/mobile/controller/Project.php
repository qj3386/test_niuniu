<?php

namespace app\mobile\controller;

use think\Db;
use think\Validate;
use think\Request;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\ProjectLogic;
use app\common\model\Project as ProjectModel;

class Project extends Common
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new ProjectLogic();
    }

    //列表
    public function index()
    {
        $id = input('id');
        $uri = $_SERVER["REQUEST_URI"]; //获取当前url的参数
        //分类
        $post = cache("mobile_project_index_post_" . md5($uri));
        if (!$post) {
            $post = logic('ProjectType')->getOne(array('id' => $id));
            cache("mobile_project_index_post_" . md5($uri), $post, 3600 * 24 * 30);
        }
        $assign_data['post'] = $post;
        //var_dump($assign_data['post']);exit;

        //获取所有分类
		$project_type = logic('ProjectType')->getAll(['delete_time'=>0], 'id,name');
		if ($project_type) {
			foreach ($project_type as $k => $v) {
				$project_type[$k]['project_list'] = logic('Project')->getAll(['type_id'=>$v['id'], 'delete_time'=>0, 'status'=>0]);
			}
		}
		$assign_data['project_type'] = $project_type;

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

        $res = logic('Project')->getOne(array('id' => $id));
        if (empty($res)) {
            Helper::http404();
        }
        $res['content'] = preg_replace('/src=\"\/uploads\/allimg/', "src=\"" . http_host() . "/uploads/allimg", $res['content']);
        $assign_data['post'] = $res;
        //dd($assign_data['post']);
        $this->assign($assign_data);
        return $this->fetch();
    }

    //详情
    public function buy()
    {
        //判断是否登录
        $this->isLogin();

        if (Helper::isPostRequest()) {
            //表单令牌验证
            /* $validate = new Validate([
                ['__token__', 'require|token', '非法提交|请不要重复提交表单'],
            ]);
            if (!$validate->check($_POST)) {
                $this->error($validate->getError());
            } */
            $user = model('User')->getOne(['id' => $this->login_info['id']]);
            if (!$user['pay_password']) {
                //$this->error('请设置支付密码', url('user/setting'));
                $res = ReturnData::create(ReturnData::FAIL, null, '请设置支付密码');
                $res['url'] = url('user/setting');
                Util::echo_json($res);
            }

            //判断支付密码
            if (isset($_POST['pay_password']) && !empty($_POST['pay_password'])) {
                $pay_password = logic('User')->passwordEncrypt($_POST['pay_password']);
                if ($pay_password != $user['pay_password']) {
                    //$this->error('支付密码不正确，请重新输入');
                    Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '支付密码不正确，请重新输入'));
                }
            } else {
                //$this->error('请输入支付密码');
                Util::echo_json(ReturnData::create(ReturnData::FAIL, null, '请输入支付密码'));
            }

            //用户投资项目
            $add_data['user_id'] = $this->login_info['id'];
            $add_data['project_id'] = input('id');
            $add_data['money'] = input('money');
            $res = logic('UserProject')->add($add_data);
            if ($res['code'] != ReturnData::SUCCESS) {
                //$this->error($res['msg']);
                Util::echo_json($res);
            }

            //$this->success($res['msg'], url('user_project/index'));
            $res['url'] = url('user_project/index');
            Util::echo_json($res);
        }

        $this->is_certification();
        if (!checkIsNumber(input('id', null))) {
            Helper::http404();
        }
        $id = input('id');

        $res = logic('Project')->getOne(array('id' => $id));
        if (empty($res)) {
            Helper::http404();
        }
        $assign_data['post'] = $res;
        //dd($assign_data['post']);
        $this->assign($assign_data);
        $this->assign('id', $id);

        $res = logic('User')->getUserInfo(['id' => $this->login_info['id']]);
        $this->login_info = array_merge($this->login_info, $res);
        session('mobile_user_info', $this->login_info);
        $this->assign('login_info', $this->login_info);

        return $this->fetch();
    }
}