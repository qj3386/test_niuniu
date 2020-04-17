<?php

namespace app\common\logic;

use think\Loader;
use app\common\lib\ReturnData;

class ToolLogic extends BaseLogic
{
    protected function initialize()
    {
        parent::initialize();
    }

    //SMTP发送邮件
	//hotmail服务器名称: smtp.office365.com 端口: 587
	//gmail服务器名称: smtp.gmail.com 端口: 465
    public function smtp_sendmail($content)
    {
        $text = date('Y-m-d H:i:s') . '服务器挂了，有效期30分钟。<br>' . $content;
		//发送邮件
		$smtpserver = 'smtp.sina.com';//SMTP服务器
		$smtpserverport = 25;//SMTP服务器端口
		$smtpusermail = 'qiji3386@sina.com';//SMTP服务器的用户邮箱
		$smtpemailto = 'qiji3386@hotmail.com';//发送给谁
		$smtpuser = "qiji3386@sina.com";//SMTP服务器的用户帐号
		$smtppass = "qiji123456";//SMTP服务器的用户密码
		$mailtitle = date('Y-m-d H:i:s') . '验证码';//邮件主题
		$mailcontent = $text;//邮件内容
		$mailtype = 'HTML';//邮件格式(HTML/TXT),TXT为文本邮件
		$smtp = new \app\common\lib\Smtp($smtpserver, $smtpserverport, true, $smtpuser, $smtppass);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
		$smtp->debug = false;//是否显示发送的调试信息
		$state = $smtp->sendmail($smtpemailto, $smtpusermail, $mailtitle, $mailcontent, $mailtype);
		if ($state == '') {
			return '对不起，邮件发送失败！请检查邮箱填写是否有误。';
		}

		return true;
    }
	
	//发送
    public function notice($content)
    {
        return true;
        $text = date('Y-m-d H:i:s') . '<br>' . $content;
		$postdata = array(
            'content' => $text,
            'ip' => request()->ip(),
            'host' => $_SERVER['SERVER_NAME']
		);
        $url = '/api/notice/add';
		$res = curl_request($url, $postdata, 'POST');
        if($res['code'] != 0)
        {
            return false;
        }

		return true;
    }
}