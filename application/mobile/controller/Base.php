<?php

namespace app\mobile\controller;

class Base extends Common
{
    /**
     * 初始化
     * @param void
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();

        //判断是否登录
        $this->isLogin();
    }

}