<?php
namespace app\mobile\controller;

use think\Db;
use think\Validate;
use think\Request;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\FeedbackLogic;

class Feedback extends Base
{
    public function _initialize()
	{
		parent::_initialize();
    }
    
    public function getLogic()
    {
        return new FeedbackLogic();
    }

    //详情
    public function add()
	{
        if (Helper::isPostRequest()) {
            //表单令牌验证
            $validate = new Validate([
                ['__token__', 'require|token', '非法提交|请不要重复提交表单'],
            ]);
			$post_data = input('post.');
            if (!$validate->check($post_data)) {
                $this->error($validate->getError());
            }
            $post_data['user_id'] = $this->login_info['id'];
			$post_data['content'] = $this->test_input($post_data['content']);
            $res = $this->getLogic()->add($post_data);
            if ($res['code'] != ReturnData::SUCCESS) {
                $this->error($res['msg']);
            }

            $this->success($res['msg'], url('user/index'));
        }
        
        return $this->fetch();
    }

	// 通过这个函数来对输入数据进行检测，防止CSS注入脚本攻击
	public function test_input($data)
	{
		$data = trim($data); // 去除用户输入中的空格、tab、换行符等信息
		$data = stripcslashes($data); // 去除输入中的"/"反斜杠，防止有转义符的存在
		$data = htmlspecialchars($data); // 再次将数据转回html转义代码
		return $data;
	}
}