<?php

namespace app\index\controller;

use think\Db;
use think\Log;
use think\Request;
use think\Session;
use app\common\lib\ReturnData;
use app\common\lib\Helper;

class Caiji extends Base
{
    // 瑞业资本https://www.lingze37.com
    public function ruiye()
    {
        $car_list = db('caiji_nbfys')->order('id desc')->limit(50)->select();
		
		if (!$car_list) {
			exit('没有数据');
		}
        /* $car_list = db('project')->order('id desc')->limit(500)->select();
        foreach ($car_list as $k => $v) {
            if(!file_exists($_SERVER['DOCUMENT_ROOT'] . $v['cover'])){
                db('project')->where(['id'=>$v['id']])->update(['cover'=>'']);
            }
        }
		exit; */
		foreach ($car_list as $k => $v) {
			//图片处理
			preg_match_all('/<img src="(.+?)">/is', $v['cover'], $matches);
			$cover = $matches[1][0];
            $data['title'] = $v['title'];
            $data['content'] = $v['content'];
            $data['type_id'] = 1;
            $data['keywords'] = get_participle($v['title']);
            $data['scale'] = $v['scale'];
            $data['daily_interest'] = $v['daily_interest'];
            $data['term'] = $v['term'];
            $data['guarantee_agency'] = $v['guarantee_agency'];
            $data['min_buy_money'] = $v['min_buy_money'];
            $data['progress'] = $v['progress'];
            $data['cover'] = '/uploads/2019/02/' . $cover;
            $data['status'] = 1;
            $data['add_time'] = time();
            $data['admin_id'] = 1;
            
            $res = db('project')->insert($data);
            if (!$res) {
                echo "error-" . $v['title'] . "-<br>";
                continue;
            }
            echo "success-" . $v['title'] . "<br>";
            db('caiji_nbfys')->where(['id' => $v['id']])->delete();
		}
    }
    
    // 宁波鄞州泛洋盛二手车经纪有限公司http://www.nbfys.com
    public function nbfys()
    {
        $car_list = db('caiji_nbfys')->order('id asc')->limit(5)->select();
		
		if (!$car_list) {
			exit('没有数据');
		}
		
		foreach ($car_list as $k => $v) {
			$id = $v['id'];
			//获取最新一条车源
			$zuixin_car = db('car')->order('add_time desc')->find();
			$time = $zuixin_car['add_time'] + 3000 + rand(100,1000);
			
			//车辆图片处理
			preg_match_all('/<img src="(.+?)" class="vt"/is', $v['car_img'], $matches);
			$car_img_temp = $matches[1];
			$car_img = [];
			if (!$car_img_temp) {
                db('caiji_nbfys')->where(['id' => $id])->delete();
				continue;
			}
			$i = 0;
			foreach ($car_img_temp as $row) {
				$new_img_path = '/uploads/' . date('Y/m', $time);
				$oldname = $_SERVER['DOCUMENT_ROOT'] . '/uploads/caiji_uploads/' . $row;
				$newname = date('YmdHis', $time + $i) . rand(1000, 9999) . '.' . pathinfo($oldname, PATHINFO_EXTENSION);
				//创建文件夹
				if(!file_exists($_SERVER['DOCUMENT_ROOT'] . $new_img_path))
				{
					Helper::createDir($_SERVER['DOCUMENT_ROOT'] . $new_img_path);
				}
				
				copy($_SERVER['DOCUMENT_ROOT'] . '/uploads/caiji_uploads/' . $row, $_SERVER['DOCUMENT_ROOT'] . $new_img_path . '/' . $newname);
				//unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/caiji_uploads/' . $row);
				
				$car_img[] = $new_img_path . '/' . $newname;
				$i = $i + 2;
			}
			
			//车辆数据处理
            $price = $v["price"];
			$v["price"] = $v["price"]*10000;
            $mileage = $v["mileage"];
			$v["mileage"] = $v["mileage"]*10000;
			if ($v["mileage"] > 30000) { $v["vehicle_class"] = 15; } //二手车
			if ($v["mileage"] > 1000 && $v["mileage"] <= 10000) { $v["vehicle_class"] = 9; } //准新车
			
			$v["title"] = trim($v["title"]);
			$content = trim($v["content"]);
			$v["content"] = '<p>' . $content . '</p>';
			$v["description"] = $v["sell_point"] = $content;
			$title = $v["title"];
			$title = str_replace("，", "", $title);
			$title = str_replace(",", "", $title);
			$v['keywords'] = get_participle($title); // 标题分词
			if (strpos($title, '国六') !== false || strpos($title, '国6') !== false || strpos($title, 'VI') !== false) { $v["effluent_standard"] = 6; }
			
			$v['shop_id'] = 6; // 店铺ID
			$v['click'] = rand(200, 500);
			$v['add_time'] = $v['update_time'] = $time; // 更新时间
			//品牌
			$v['brand_id'] = 36; // 丰田ID
			$v['series_id'] = 430; // 凯美瑞ID
			//省市区
			$v['province_id'] = 15; // 浙江ID
			$v['city_id'] = 1158; // 宁波ID
			$v['district_id'] = 46343; // 鄞州区ID
			
			$v['effluent_standard'] = 5; // 国五排放
			$v['transfer_num'] = 0; // 过户次数
			$v['use_character'] = 1; // 使用性质
            $registrer_date = $v['registrer_date'];
			if ($v['registrer_date']) { $v['registrer_date'] = strtotime($v['registrer_date'] . '-01'); } else { $v['registrer_date'] = 0; } // 上牌时间
			$v['contact'] = '张小姐'; // 联系人
			$v['phone'] = '13095912093'; // 联系电话
			
            if ($v['type_id'] > 0) { } else { $v['type_id'] = 1; }
            if ($v['displacement'] > 0) { } else { $v['displacement'] = 1.5; }
            if ($v['color'] > 0) { } else { $v['color'] = 1; }
            if ($v['gearbox_type'] > 0) { } else { $v['gearbox_type'] = 1; }
            
			//车辆首图
			$v['litpic'] = $car_img[0];
			unset($v['id']);
			
            //供求信息
            if ($v["mileage"] > 50000) {
                $v["type"] = 1;
                $v['click'] = rand(200, 500);
                $miaoshu = '颜色：' . model('Car')->getColorTextAttr('', ['color'=>$v['color']]) . ' 里程：' . floatval($mileage) . '万公里 排量：' . number_format($v['displacement'], 1);
                if ($registrer_date) { $miaoshu = $miaoshu . ' 上牌日期：' . $registrer_date; }
                if ($price > 0) { $miaoshu = $miaoshu . ' 价格：' . $price . '万元'; }
                
                $v["content"] = $v["content"] . "<p><br/></p><p>{$miaoshu}</p>";
                
                foreach ($car_img as $key => $value) {
                    $v["content"] = $v["content"] . '<p><br/></p><p><img src="' . $value . '" alt="' . $v["title"] . '"/></p>';
				}
                
                $res = logic('SupplyDemandInfo')->add($v);
                if ($res['code'] != ReturnData::SUCCESS) {
                    echo "error-$title-" . $res['msg'] . "<br>";
                    db('caiji_nbfys')->where(['id' => $id])->delete();
                    continue;
                }
                
                db('caiji_nbfys')->where(['id' => $id])->delete();
                echo "success-$title-" . $res['msg'] . "<br>";
            } else {
                $res = logic('Car')->add($v);
                if ($res['code'] != ReturnData::SUCCESS) {
                    echo "error-$title-" . $res['msg'] . "<br>";
                    db('caiji_nbfys')->where(['id' => $id])->delete();
                    continue;
                }

                $i = 0;
                if (isset($car_img)) {
                    $tmp = [];
                    foreach ($car_img as $key => $value) {
                        $tmp[] = ['url' => $value, 'car_id' => $res['data'], 'add_time' => ($time + $i)];
                        $i = $i + 2;
                    }

                    db('car_img')->insertAll($tmp);
                }

                db('caiji_nbfys')->where(['id' => $id])->delete();
                echo "success-$title-" . $res['msg'] . "<br>";
			}

			sleep(1);
		}
    }

}