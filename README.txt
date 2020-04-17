


服务器域名
短信宝
阿里云三要素

icon图标
印章
服务协议
注册协议
充值提现二维码
轮播图
公告
注册/签到金额
app下载地址


短信格式
【范例】您的验证码是1234，有效期30分钟，若非本人操作请忽略此消息。

mysql自增ID起始值修改
alter table fl_user AUTO_INCREMENT=100000;


数据库
truncate table fl_user;
alter table fl_user AUTO_INCREMENT=110000;
truncate table fl_user_log;
truncate table fl_user_message;
alter table fl_user_message AUTO_INCREMENT=110000;
truncate table fl_user_money;
alter table fl_user_money AUTO_INCREMENT=110000;
truncate table fl_user_point;
truncate table fl_user_project;
alter table fl_user_project AUTO_INCREMENT=110000;
truncate table fl_user_project_income;
alter table fl_user_project_income AUTO_INCREMENT=110000;
truncate table fl_user_recharge;
alter table fl_user_recharge_income AUTO_INCREMENT=110000;
truncate table fl_user_withdraw;
alter table fl_user_withdraw AUTO_INCREMENT=110000;
truncate table fl_verify_code;
truncate table fl_sms_log;
truncate table fl_feedback;
truncate table fl_admin_log;

UPDATE fl_sysconfig SET value = 0 WHERE varname = 'CMS_CUMULATIVE_RECHARGE';
UPDATE fl_sysconfig SET value = 0 WHERE varname = 'CMS_CUMULATIVE_WITHDRAWAL';

CentOS下防御或减轻DDoS攻击方法
https://www.cnblogs.com/EasonJim/p/8325661.html
https://blog.csdn.net/sunguoqiang1213/article/details/78349690

Centos7防火墙关闭和启用iptables操作
https://www.cnblogs.com/wntd/p/11668287.html

ModSecurity
http://www.modsecurity.cn/practice/post/11.html


安装单元测试扩展
php composer.phar config -g repo.packagist composer https://packagist.phpcomposer.com
php composer.phar update topthink/think-testing

进行单元测试
php think unit

通常在ThinkPHP中进行单元测试需要遵守以下的规范：
1.测试类保存在tests目录下
2.针对某个控制器的测试类命名规则为xxxTest.php，比如针对Index控制器进行测试的话，则测试的命名为：IndexTest.php
3.测试类通常继承自TestCase，命名空间通常为tests。
4.针对某个操作的测试通常命名为testxxx，比如针对Index控制器下的index操作，其测试方法命名为：testIndex，并且需要为公有方法(public)。
5.建议：当对同一个操场进行多种测试的时候，测试方法的命名可以在尾部递增数字，然后使用注释进行说明，而不用去想具体的测试范围所对应的名字。比如testIndex1，testIndex2.





