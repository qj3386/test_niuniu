<?php

namespace app\common\validate;

use think\Validate;
use app\common\lib\Validator;

class Notice extends Validate
{
    // 验证规则
    protected $rule = [
        ['id', 'require|number|max:11', 'ID不能为空|ID必须是数字|ID格式不正确'],
        ['host', 'max:60', '域名格式不正确'],
        ['ip', 'max:30', 'ip格式不正确'],
        ['add_time', 'require|number|max:11', '添加时间不能为空|添加时间格式不正确|添加时间格式不正确'],
    ];

    protected $scene = [
        'add' => ['host', 'ip'],
        'del' => ['id'],
    ];

}