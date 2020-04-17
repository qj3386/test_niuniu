<?php
// 配置文件
return [
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => APP_PATH . 'mobile' . DS . 'view' . DS . 'common' . DS . 'jump.html',
    'dispatch_error_tmpl'    => APP_PATH . 'mobile' . DS . 'view' . DS . 'common' . DS . 'jump.html',
    
    'http_exception_template' => [
        // 定义404错误的重定向页面地址
        404 =>  APP_PATH . 'mobile' . DS . 'view' . DS . 'common' . DS . '500.html',
        500 =>  APP_PATH . 'mobile' . DS . 'view' . DS . 'common' . DS . '500.html',
        // 还可以定义其它的HTTP status
        //401 =>  ROOT_PATH.config('template.view_path').config('index.model_name').'/'.config('index.default_template').'/401.html',
    ],
];