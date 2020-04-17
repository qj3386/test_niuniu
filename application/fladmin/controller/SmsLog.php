<?php

namespace app\fladmin\controller;

use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\SmsLogLogic;
use think\Loader;
use think\Validate;

class SmsLog extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new SmsLogLogic();
    }

    //列表
    public function index()
    {
        $where = array();
        if (!empty($_REQUEST["keyword"])) {
            $where['mobile'] = array('like', '%' . $_REQUEST['keyword'] . '%');
        }
        $list = $this->getLogic()->getPaginate($where, ['id' => 'desc']);

        $this->assign('page', $list->render());
        $this->assign('list', $list);
        //echo '<pre>';print_r($list);exit;
        return $this->fetch();
    }

    //添加
    public function add()
    {
        if (Helper::isPostRequest()) {
            //表单令牌验证
            $validate = new Validate([
                ['__token__', 'require|token', '非法提交|请不要重复提交表单'],
            ]);
            if (!$validate->check($_POST)) {
                $this->error($validate->getError());
            }

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
            //表单令牌验证
            $validate = new Validate([
                ['__token__', 'require|token', '非法提交|请不要重复提交表单'],
            ]);
            if (!$validate->check($_POST)) {
                $this->error($validate->getError());
            }

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
            $this->success("删除成功");
        }

        $this->error($res['msg']);
    }
	
	//发送短信
    public function send()
    {
		if (Helper::isPostRequest()) {
			$data = input('post.');
            if ($data['text'] == '') {
				$this->error("发送内容不能为空");
			}

			$text = $data['text'];
			$mobile_list = explode("\r\n", trim($data['mobile']));
			$mobile_list = array_filter($mobile_list);
			$mobile = implode(",", array_filter($mobile_list));
			if (!$mobile) {
				$this->error("手机号码不能为空");
			}
			
			//短信发送验证码
			$smsbao_config = explode(",", str_replace("，",",", sysconfig('CMS_SMS_WEBCHINESE')));
			//网建SMS短信通
			$url = 'http://utf8.api.smschinese.cn/?Uid=' . $smsbao_config[0] . '&Key=' . $smsbao_config[1] . '&smsMob=' . $mobile . '&smsText=' . $text;
			
			if (!$this->get_curl($url)) {
				$this->error("短信发送失败");
			}

			$time = time();
			$sms_log_data = [];
			foreach ($mobile_list as $k=>$v) {
				$temp = [];
				$temp['mobile'] = $v;
				$temp['content'] = $text;
				$temp['status'] = 1;
				$temp['add_time'] = $time;
				
				$sms_log_data[] = $temp;
			}
			//添加短信发送记录
			model('SmsLog')->add($sms_log_data, 2);

			$this->success("操作成功");
        }
		
        return $this->fetch();
    }

	public function get_curl($url)
	{
		$ch = curl_init();
		// curl_init()需要php_curl.dll扩展
		$timeout = 5;
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$file_contents = curl_exec($ch);
		curl_close($ch);
		return $file_contents;
	}
}