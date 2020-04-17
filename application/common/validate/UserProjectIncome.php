<?php

namespace app\common\validate;

use think\Validate;
use app\common\lib\Helper;
use app\common\lib\Validator;

class UserProjectIncome extends Validate
{
    // 验证规则
    protected $rule = [
        ['id', 'require|number|max:11', 'ID不能为空|ID必须是数字|ID格式不正确'],
        ['user_project_id', 'require|number|max:11', '用户投资的项目ID不能为空|用户投资的项目ID必须是数字|用户投资的项目ID格式不正确'],
        ['user_id', 'require|number|max:11', '用户ID不能为空|用户ID必须是数字|用户ID格式不正确'],
        ['project_id', 'require|number|max:11', '项目ID不能为空|项目ID必须是数字|项目ID格式不正确'],
        ['money', 'require|regex:/^\d{0,10}(\.\d{0,2})?$/', '金额不能为空|金额格式不正确'],
        ['title', 'require|max:150', '项目名称不能为空|项目名称格式不正确'],
        ['status', 'in:0,1', '状态：0未支付，1已支付'],
        ['is_last', 'in:0,1', '是否最后一期，0否1是'],
        ['add_time', 'require|number|max:11', '添加时间不能为空|添加时间格式不正确|添加时间格式不正确'],
        ['update_time', 'require|number|max:11', '更新时间不能为空|更新时间格式不正确|更新时间格式不正确'],
    ];

    protected $scene = [
        'add' => ['user_project_id', 'user_id', 'project_id', 'money', 'status', 'is_last', 'title', 'add_time'],
        'edit' => ['user_project_id', 'user_id', 'project_id', 'money', 'status', 'is_last', 'title', 'update_time'],
        'del' => ['id'],
    ];
}