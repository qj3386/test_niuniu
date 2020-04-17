<?php

namespace app\common\validate;

use think\Validate;
use app\common\lib\Helper;
use app\common\lib\Validator;

class UserLog extends Validate
{
    // 验证规则
    protected $rule = [
        ['id', 'require|number|gt:0', 'ID不能为空|ID必须是数字|ID格式不正确'],
        ['ip', 'require|max:15|ip', 'IP不能为空|IP不能超过15个字符|IP格式不正确'],
        ['name', 'max:30', '用户名称不能超过30个字符'],
        ['user_id', 'number|gt:0', '用户ID必须是数字|用户ID格式不正确'],
        ['url', 'require|max:250', 'URL不能为空|URL不能超过250个字符'],
        ['http_method', 'require|max:10', '请求方式不能为空|请求方式不能超过10个字符'],
        ['domain_name', 'max:60', '域名不能超过60个字符'],
        ['content', 'max:250', '内容不能超过250个字符'],
        ['http_referer', 'max:250', '上一个页面URL不能超过250个字符'],
        ['add_time', 'require|number|egt:0', '添加时间不能为空|添加时间格式不正确|添加时间格式不正确'],
    ];

    protected $scene = [
        'add' => ['ip', 'name', 'user_id', 'url', 'http_method', 'domain_name', 'content', 'http_referer', 'add_time'],
        'edit' => ['ip', 'name', 'user_id', 'url', 'http_method', 'domain_name', 'content', 'http_referer'],
        'del' => ['id'],
    ];
}