<?php

namespace app\common\validate;

use think\Validate;

class Video extends Validate
{
    // 验证规则
    protected $rule = [
        ['id', 'require|number|gt:0', 'ID不能为空|ID必须是数字|ID格式不正确'],
        ['click', 'number|egt:0', '点击量必须是数字|点击量格式不正确'],
        ['title', 'require|max:150', '标题不能为空|标题不能超过150个字符'],
        ['keywords', 'max:60', '关键词不能超过60个字符'],
        ['seotitle', 'max:150', 'seo标题不能超过150个字符'],
        ['description', 'require|max:250', '描述不能为空|描述不能超过60个字符'],
        ['url', 'require|max:250', '视频地址不能为空|视频地址不能超过250个字符'],
        ['poster', 'require|max:250', '视频海报不能为空|视频海报不能超过250个字符'],
        ['add_time', 'require|number|egt:0', '添加时间不能为空|添加时间格式不正确|添加时间格式不正确'],
        ['update_time', 'require|number|egt:0', '更新时间不能为空|更新时间格式不正确|更新时间格式不正确'],
    ];

    protected $scene = [
        'add' => ['title', 'click', 'add_time', 'update_time', 'keywords', 'seotitle', 'description', 'url', 'poster'],
        'edit' => ['title', 'click', 'update_time', 'keywords', 'seotitle', 'description', 'url', 'poster'],
        'del' => ['id'],
    ];
}