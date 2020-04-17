<?php

namespace app\fladmin\controller;

class Index extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $this->assign('menus', model('Menu')->getPermissionsMenu($this->admin_info['role_id']));

        $this->assign('module_name', request()->module());

        return $this->fetch();
    }

    public function welcome()
    {
        return $this->fetch();
    }

    public function upcache()
    {
        dir_delete(APP_PATH . '../runtime/');
        $this->success('缓存更新成功');
    }

    /**
     * 更新配置文件 / 更新系统缓存
     */
    public function updateconfig()
    {
        $str_tmp = "<?php\r\n"; //得到php的起始符。$str_tmp将累加
        $str_end = "?>"; //php结束符
        $str_tmp .= "//全站配置文件\r\n";

        $param = db("sysconfig")->select();
        foreach ($param as $row) {
            $str_tmp .= 'define("' . $row['varname'] . '","' . $row['value'] . '"); // ' . $row['info'] . "\r\n";
        }

        $str_tmp .= $str_end; //加入结束符
        //保存文件
        $sf = APP_PATH . "common.inc.php"; //文件名
        $fp = fopen($sf, "w"); //写方式打开文件
        fwrite($fp, $str_tmp); //存入内容
        fclose($fp); //关闭文件
        return $sf;
    }

	// 获取充值/提现消息
    public function get_recharge_withdrawal_info()
    {
		$three_minutes = time() - 60; //1分钟自动忽略
		$has_recharge = 0; //有人充值
		$has_withdraw = 0; //有人提现
		$res = model('UserRecharge')->getOne(['status'=>0, 'is_ignore'=>0, 'delete_time'=>0]);
		if ($res) {
			$has_recharge = 1;
			if ($res['add_time'] < $three_minutes) {
				model('UserRecharge')->edit(['is_ignore'=>1], ['id'=>$res['id']]);
			}
		}
		$res = model('UserWithdraw')->getOne(['status'=>1, 'is_ignore'=>0, 'delete_time'=>0]);
		if ($res) {
			$has_withdraw = 1;
			if ($res['add_time'] < $three_minutes) {
				model('UserWithdraw')->edit(['is_ignore'=>1], ['id'=>$res['id']]);
			}
		}
        exit(json_encode(array('code' => 0, 'msg' => '操作成功', 'data' => ['has_recharge'=>$has_recharge, 'has_withdraw'=>$has_withdraw])));
    }

	/**
     * 文件上传
     */
    public function file_upload()
    {
        return $this->fetch();
    }
}