<?php

namespace app\common\validate;

use think\Validate;
use app\common\lib\Helper;
use app\common\lib\Validator;

class UserProject extends Validate
{
    // 验证规则
    protected $rule = [
        ['id', 'require|number|max:11', 'ID不能为空|ID必须是数字|ID格式不正确'],
        ['user_id', 'require|number|max:11', '用户ID不能为空|用户ID必须是数字|用户ID格式不正确'],
        ['project_id', 'require|number|max:11', '项目ID不能为空|项目ID必须是数字|项目ID格式不正确'],
        ['money', 'require|number|max:11', '认领份数不能为空|认领份数必须是数字|认领份数额格式不正确'],
        ['title', 'require|max:150', '项目名称不能为空|项目名称格式不正确'],
        ['dividend_mode', 'require|number|max:11', '还款方式不能为空|还款方式必须是数字|还款方式格式不正确'],
        ['daily_interest', 'require|regex:/^\d{0,10}(\.\d{0,2})?$/', '日化收益不能为空|日化收益只能带2位小数的数字'],
        ['term', 'require|number|max:11', '产品期限不能为空|产品期限必须是数字|产品期限格式不正确'],
        ['status', 'in:0,1', '分红状态：0未完结，1已完结'],
        ['note', 'max:250', '备注格式不正确'],
        ['add_time', 'require|number|max:11', '添加时间不能为空|添加时间格式不正确|添加时间格式不正确'],
        ['update_time', 'require|number|max:11', '更新时间不能为空|更新时间格式不正确|更新时间格式不正确'],
    ];

    protected $scene = [
        'add' => ['user_id', 'project_id', 'money', 'update_time', 'add_time'],
        'edit' => ['user_id', 'project_id', 'money', 'title', 'dividend_mode', 'daily_interest', 'term', 'status', 'note', 'update_time'],
        'del' => ['id'],
    ];
}