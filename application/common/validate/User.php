<?php

namespace app\common\validate;

use think\Validate;
use app\common\lib\Validator;

class User extends Validate
{
    // 验证规则
    protected $rule = array(
        ['id', 'require|number|max:11', 'ID不能为空|ID必须是数字|ID格式不正确'],
        ['parent_id', 'number|max:11', '推荐人ID必须是数字|推荐人ID格式不正确'],
        ['mobile', 'isMobile', '手机号格式不正确'],
        ['email', 'email', '邮箱格式不正确'],
        ['nickname', 'max:30|isIncludeSpecialCharacters', '昵称不能超过30个字符'],
        ['user_name', 'require|max:30|isUserName', '用户名不能为空|用户名不能超过30个字符'],
        ['password', 'require|length:6,18|isPWD', '密码不能为空|密码6-18位'],
        ['pay_password', 'require|length:6,18|isPWD', '支付密码不能为空|支付密码6-18位'],
        ['head_img', 'max:250', '头像格式不正确'],
        ['sex', 'in:0,1,2', '性别：1男2女'],
        ['birthday', 'isDate', '生日格式不正确'],
        ['money', 'egt:0|regex:/^\d{0,10}(\.\d{0,2})?$/', '用户余额格式不正确|用户余额格式不正确'],
        ['commission', 'egt:0|regex:/^\d{0,10}(\.\d{0,2})?$/', '累积佣金格式不正确|累积佣金格式不正确'],
        ['consumption_money', 'egt:0|regex:/^\d{0,10}(\.\d{0,2})?$/', '累计消费金额格式不正确|累计消费金额格式不正确'],
        ['frozen_money', 'egt:0|regex:/^\d{0,10}(\.\d{0,2})?$/', '用户冻结资金格式不正确|用户冻结资金格式不正确'],
        ['point', 'number|max:11', '用户能用积分必须是数字|用户能用积分格式不正确'],
        ['user_rank', 'number|max:2', '用户等级必须是数字|用户等级格式不正确'],
        ['user_rank_points', 'number|max:11', '会员等级积分必须是数字|会员等级积分格式不正确'],
        ['address_id', 'number|max:11', '收货地址ID必须是数字|收货地址ID格式不正确'],
        ['openid', 'max:128', 'openid格式不正确'],
        ['unionid', 'max:128', 'openid格式不正确'],
        ['refund_account', 'max:30', '退款账户不能超过30个字符'],
        ['refund_name', 'max:20', '退款姓名不能超过20个字符'],
        ['true_name', 'require|max:20|isIncludeSpecialCharacters', '姓名不能为空|姓名格式不正确'],
        ['idcard', 'require|length:18|isIdCardNo', '身份证号不能为空|身份证号格式不正确'],
        ['bank_name', 'require|max:60|isIncludeSpecialCharacters', '银行名称不能为空|银行名称格式不正确'],
        ['bank_branch_name', 'require|max:60|isIncludeSpecialCharacters', '支行名称不能为空|支行名称格式不正确'],
        ['bank_card_number', 'require|max:30|regex:/^[0-9]+$/', '卡号不能为空|卡号格式不正确|卡号格式不正确'],
        ['signin_time', 'number|max:11', '签到时间格式不正确|签到时间格式不正确'],
        ['group_id', 'number|max:11', '分组ID必须是数字|分组ID格式不正确'],
        ['status', 'in:0,1,2,3', '用户状态：0正常，1待审，2锁定'],
        ['add_time', 'require|number|max:11', '添加时间不能为空|添加时间格式不正确|添加时间格式不正确'],
        ['update_time', 'require|number|max:11', '更新时间不能为空|更新时间格式不正确|更新时间格式不正确'],
        ['login_time', 'number|max:11', '登录时间不能为空|登录时间格式不正确|登录时间格式不正确'],
        ['delete_time', 'number|max:11', '删除时间不能为空|删除时间格式不正确|删除时间格式不正确'],
    );

    protected $scene = array(
        'add' => ['parent_id', 'mobile', 'email', 'nickname', 'user_name', 'password', 'head_img', 'sex', 'birthday', 'openid', 'status', 'add_time'],
        'register' => ['parent_id', 'mobile', 'email', 'nickname', 'user_name', 'password', 'head_img', 'sex', 'add_time'],
        'wx_register' => ['parent_id', 'mobile', 'email', 'nickname', 'user_name', 'head_img', 'sex', 'birthday', 'openid', 'add_time'],
        'user_password_update' => ['password'],
        'user_pay_password_update' => ['pay_password'],
        'del' => ['id'],
        'certification' => ['true_name', 'idcard'],
        'bind_bank_card' => ['bank_name', 'bank_branch_name', 'bank_card_number'],
    );

    // 用户名校验
    protected function isUserName($value, $rule, $data)
    {
        if (empty(trim($value))) {
            return '用户名不能为空';
        }

        $match = '/^[-_a-zA-Z0-9]{3,18}$/i';
        if (preg_match($match, $value)) {
            return true;
        }

        return '用户名格式不正确';
    }

    // 手机号校验
    protected function isMobile($value, $rule, $data)
    {
        if (empty($value)) {
            return '手机号不能为空';
        }

        if (Validator::isMobile($value)) {
            return true;
        }

        return '手机号格式不正确';
    }

    // 密码校验
    protected function isPWD($value, $rule, $data)
    {
        if (empty($value)) {
            return '密码不能为空';
        }

        if (Validator::isPWD($value)) {
            return true;
        }

        return '密码格式不正确';
    }

    // 年月日校验
    protected function isDate($value, $rule, $data)
    {
        if (empty($value)) {
            return '生日不能为空';
        }

        if (Validator::isDate($value, 'Y-m-d')) {
            return true;
        }

        return '生日格式不正确';
    }

    // 身份证号校验
    protected function isIdCardNo($value, $rule, $data)
    {
        if (Validator::isIdCardNo($value)) {
            return true;
        }

        return '身份证号格式不正确';
    }

    // 银行卡号校验
    protected function isBankCard($value, $rule, $data)
    {
        if (Validator::isBankCard($value)) {
            return true;
        }

        return '卡号格式不正确';
    }

	// 判断是否含有特殊字符
    protected function isIncludeSpecialCharacters($value, $rule, $data)
    {
        if ($this->test_input($value) != $value) {
            return '格式不正确，请不要输入特殊字符';
        }

        return true;
    }

	// 通过这个函数来对输入数据进行检测，防止CSS注入脚本攻击
	public function test_input($str)
	{
		$str = trim($str); // 去除用户输入中的空格、tab、换行符等信息
		$str = stripcslashes($str); // 去除输入中的"/"反斜杠，防止有转义符的存在
		$str = htmlspecialchars($str); // 再次将数据转回html转义代码
		//包含特殊字符
		if (preg_match("/[\',:;?!#$%^&+=)(<>{}]|\]|\[|\\\|\"|\|/", $str)) {
			return '';
		}
		return $str;
	}
}