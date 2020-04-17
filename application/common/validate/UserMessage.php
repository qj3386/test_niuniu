<?php

namespace app\common\validate;

use think\Validate;
use app\common\lib\Helper;
use app\common\lib\Validator;

class UserMessage extends Validate
{
    // 验证规则
    protected $rule = [
        ['id', 'require|number|max:11', 'ID不能为空|ID必须是数字|ID格式不正确'],
        ['user_id', 'require|number|max:11', '用户ID不能为空|用户ID必须是数字|用户ID格式不正确'],
        ['title', 'require|max:150', '标题不能为空|标题不能超过150个字符'],
        ['desc', 'max:250', '说明不能超过250个字符'],
        ['type', 'in:0,1,2,3', '消息类型不正确'],
        ['status', 'in:0,1,2,3', '查看状态：0未读，1已读'],
        ['add_time', 'require|number|max:11', '添加时间不能为空|添加时间格式不正确|添加时间格式不正确'],
    ];

    protected $scene = [
        'add' => ['user_id', 'title', 'desc', 'add_time'],
        'edit' => ['user_id', 'title', 'desc'],
        'del' => ['id'],
    ];
}