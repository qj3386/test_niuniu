<?php

namespace app\mobile\controller;

use think\Db;
use think\Log;
use think\Request;
use think\Session;
use app\common\lib\ReturnData;
use app\common\lib\Helper;

/**
 * 公共-首页
 */
class Index extends Common
{
    /**
     * 首页
     * @param string $modelname 模块名与数据库表名对应
     * @param array $map 查询条件
     * @param string $orderby 查询排序
     * @param string $field 要返回数据的字段
     * @param int $listRows 每页数量，默认10条
     *
     * @return 格式化后输出的数据。内容格式为：
     *     - "code"                 (string)：代码
     *     - "info"                 (string)：信息提示
     *
     *     - "result" array
     *
     *     - "img_list"             (array) ：图片队列，默认8张
     *     - "img_title"            (string)：车图名称
     *     - "img_url"              (string)：车图片url地址
     *     - "car_name"             (string)：车名称
     */
    public function index()
    {
        $uri = $_SERVER["REQUEST_URI"]; //获取当前url的参数

        //分享到首页，把推荐码invite_code存下来
        if (isset($_REQUEST['invite_code']) && !empty($_REQUEST['invite_code'])) {
            session('mobile_user_invite_code', $_REQUEST['invite_code']);
        }

        //获取所有分类
		$project_type = logic('ProjectType')->getAll(['delete_time'=>0], 'listorder asc', 'id,name');
		if ($project_type) {
			foreach ($project_type as $k => $v) {
				$project_type[$k]['project_list'] = logic('Project')->getAll(['type_id'=>$v['id'], 'delete_time'=>0, 'status'=>0]);
			}
		}
		$assign_data['project_type'] = $project_type;

        //轮播图
        $slide_list = cache("mobile_index_index_slide_list");
        if (!$slide_list) {
            $where_slide['status'] = 0;
            $slide_list = logic('Slide')->getAll($where_slide, 'listorder asc', '*', 5);
            cache("index_index_index_slide_list", $slide_list, 3600 * 24 * 30); //1天
        }
        $assign_data['slide_list'] = $slide_list;
        //dd($assign_data);
        $this->assign($assign_data);
        return $this->fetch();
    }

    //利息计算器
    public function calculator()
    {
        $assign_data['money'] = input('money', ''); //投资金额
        $assign_data['term'] = input('term', '');
        $assign_data['daily_interest'] = input('daily_interest', '');
        $assign_data['dividend_mode'] = input('dividend_mode', '');
        $this->assign($assign_data);
        return $this->fetch();
    }

    //利息计算
    public function interest_calculation()
    {
        $data['money'] = input('money'); //投资金额
        $data['term'] = input('term');
        $data['daily_interest'] = input('daily_interest');
        $data['dividend_mode'] = input('dividend_mode');
        $res = $this->interest_calculation_method($data);
        Util::echo_json(ReturnData::create(ReturnData::SUCCESS, $res));
    }

    //利息计算方法
    public function interest_calculation_method($data)
    {
        $res = [];
        $time = time();
        if (isset($data['add_time'])) {
            $time = $data['add_time'];
        }
        $money = $data['money']; //投资金额
        $term = $data['term'];
        $daily_interest = $data['daily_interest'];
        $dividend_mode = $data['dividend_mode'];

        $expire_time = $time + $term * 86400; //到期时间
        //还款方式，0到期还本还息，1每日返息到期返本，7每周返息到期返本，10000每日复利到期返本
        if ($dividend_mode == 0) {
            $temp['collection_date'] = $expire_time; //收款日期
            $temp['collection_date'] = date('m-d', $temp['collection_date']);
            $temp['interest'] = round(($daily_interest * $term * $money) / 100, 2); //利息
            $temp['amount_received'] = $money + $temp['interest']; //收款金额
            $temp['recover_principal'] = $money; //收回本金
            $temp['remaining_principal'] = 0; //剩余本金
            $res[] = $temp;
        } elseif ($dividend_mode == 10000) {
            $temp['collection_date'] = $expire_time; //收款日期
            $temp['collection_date'] = date('m-d', $temp['collection_date']);
            $temp['amount_received'] = round(pow((1 + $daily_interest * 0.01), $term) * $money, 2); //收款金额
            $temp['interest'] = $temp['amount_received'] - $money; //复利利息
            $temp['recover_principal'] = $money; //收回本金
            $temp['remaining_principal'] = 0; //剩余本金
            $res[] = $temp;
        } else {
            $total_period = ceil($term / $dividend_mode); //总期数
            $total_period_decrement = $total_period - 1;
            for ($x = 1; $x <= $total_period_decrement; $x++) {
                $temp = [];
                $temp['collection_date'] = $time + $x * $dividend_mode * 86400; //收款日期
                $temp['collection_date'] = date('m-d', $temp['collection_date']);
                $temp['interest'] = round(($daily_interest * $dividend_mode * $money) / 100, 2); //利息
                $temp['amount_received'] = $temp['interest']; //收款金额
                $temp['recover_principal'] = 0; //收回本金
                $temp['remaining_principal'] = $money; //剩余本金
                $res[] = $temp;
            }

            //不规律的最后一期另算
            $temp = [];
            $temp['collection_date'] = $expire_time; //收款日期
            $temp['collection_date'] = date('m-d', $temp['collection_date']);
            $temp['interest'] = round(($daily_interest * ($term - $total_period_decrement * $dividend_mode) * $money) / 100, 2); //利息
            $temp['amount_received'] = $money + $temp['interest']; //收款金额
            $temp['recover_principal'] = $money; //收回本金
            $temp['remaining_principal'] = 0; //剩余本金
            $res[] = $temp;
        }
        return $res;
    }

    //关于我们
    public function about()
    {
        return $this->fetch();
    }

    //投资协议
    public function agreement()
    {
        if (!checkIsNumber(input('id', null))) {
            Helper::http404();
        }
        $id = input('id');
        $res = logic('UserProject')->getOne(array('id' => $id));
        if (empty($res)) {
            Helper::http404();
        }
        $assign_data['post'] = $res;
        $assign_data['login_info'] = model('User')->getOne(['id' => $res['user_id']]);
        //dd($assign_data['post']);
        $this->assign($assign_data);
        return $this->fetch();
    }

	//养牛赚钱
    public function yangniuzhuanqian()
    {
        return $this->fetch();
    }

    //XML地图
    public function sitemap()
    {
        //最新文章
        $where['delete_time'] = 0;
        $where['status'] = 0;
        $where['add_time'] = ['<', time()];
        $list = logic('Article')->getAll($where, 'update_time desc', ['content'], 100);
        $this->assign('list', $list);

        return $this->fetch();
    }

    //404页面
    public function notfound()
    {
        return $this->fetch();
    }

    public function test()
    {
        //echo '<pre>';print_r(request());exit;
        //echo (dirname('/images/uiui/1.jpg'));
        //echo '<pre>';
        //$str='<p><img border="0" src="./images/1.jpg" alt=""/></p>';

        //echo getfirstpic($str);
        //$imagepath='.'.getfirstpic($str);
        //$image = new \Think\Image();
        //$image->open($imagepath);
        // 按照原图的比例生成一个最大为240*180的缩略图并保存为thumb.jpg
        //$image->thumb(CMS_IMGWIDTH, CMS_IMGHEIGHT)->save('./images/1thumb.jpg');

        return $this->fetch();
    }

    /**
     * 获取验证码图片
     * @param int $type 0字母+数字，1纯数字，2字母
     * @param int $length 位数
     * @param int $width 验证码图片宽度
     * @param int $height 验证码图片高度
     */
    public function get_verifycode_image()
    {
        $config = [
            // 验证码字体大小
            'fontSize' => 16,
            // 是否添加杂点
            'useNoise' => input('use_noise', false),
            // 是否画混淆曲线
            'useCurve' => input('use_curve', false),
            // 验证码位数
            'length' => input('length', 4),
            // 验证码图片宽度，设置为0为自动计算
            'imageW' => input('width', 0),
            // 验证码图片高度，设置为0为自动计算
            'imageH' => input('height', 0),
        ];
        $captcha = new \think\captcha\Captcha($config);
        $captcha->codeSet = '0123456789';
        return $captcha->entry();
    }

    /**
     * 获取验证码图片
     * @param int $type 0字母+数字，1纯数字，2字母
     * @param int $length 位数
     * @param int $width 验证码图片宽度
     * @param int $height 验证码图片高度
     */
    public function verifycode($type = 1, $length = 4, $width = 80, $height = 30)
    {
        $img = imagecreate($width, $height);

        $red = imagecolorallocate($img, 255, 0, 0);
        $white = imagecolorallocate($img, 255, 255, 255);

        // \Session::flash('captcha_math', $num1 + $num2); // 一次性使用
        // \Cookie::queue('captcha_math', $num1 + $num2, 10); // 10 分钟
        // \Cookie::queue('captcha_math', null , -1);     // 销毁
        $gray = imagecolorallocate($img, 118, 151, 199);
        $black = imagecolorallocate($img, mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));

        // 画背景
        imagefilledrectangle($img, 0, 0, $width, $height, $white);

        // 在画布上随机生成大量点，起干扰作用;
        for ($i = 0; $i < 30; $i++) {
            imagesetpixel($img, rand(0, $width), rand(0, $height), $gray);
        }

        //设置干扰线
        for ($i = 0; $i < 3; $i++) {
            $linecolor = imagecolorallocate($img, mt_rand(50, 200), mt_rand(50, 200), mt_rand(50, 200));
            imageline($img, mt_rand(1, 99), mt_rand(1, 29), mt_rand(1, 99), mt_rand(1, 29), $linecolor);
        }

        $content = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        if ($type == 1) {
            $content = "0123456789";
        } else if ($type == 2) {
            $content = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        }
        //创建一个变量存储产生的验证码数据，便于用户提交核对
        $captcha = "";
        for ($i = 0; $i < $length; $i++) {
            // 字体大小
            $fontsize = 10;
            // 字体颜色
            $fontcolor = imagecolorallocate($img, mt_rand(0, 120), mt_rand(0, 120), mt_rand(0, 120));
            // 设置字体内容
            $fontcontent = substr($content, mt_rand(0, strlen($content)), 1);
            $captcha .= $fontcontent;
            // 显示的坐标
            $x = $i * (($width * 3 / 4) / $length) + mt_rand(5, 10);
            $y = mt_rand(5, ($height / 2));
            // 填充内容到画布中
            imagestring($img, $fontsize, $x, $y, $fontcontent, $fontcolor);
        }
        session('verifyimg', $captcha);

        header("Content-type: image/png");
        imagepng($img);
        imagedestroy($img);
        die;
    }

	//PHP实现下载图片
	public function download()
    {
		//获取要下载的文件名
		$filename = $_GET['filename'];
		//设置头信息
		header('Content-Disposition:attachment;filename=' . basename($filename));
		header('Content-Length:' . filesize($filename));
		//读取文件并写入到输出缓冲
		readfile($filename);
	}
}