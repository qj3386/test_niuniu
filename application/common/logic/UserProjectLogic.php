<?php

namespace app\common\logic;

use think\Db;
use think\Loader;
use app\common\lib\ReturnData;
use app\common\model\UserProject;

class UserProjectLogic extends BaseLogic
{
    protected function initialize()
    {
        parent::initialize();
    }

    public function getModel()
    {
        return new UserProject();
    }

    public function getValidate()
    {
        return Loader::validate('UserProject');
    }

    //列表
    public function getList($where = array(), $order = '', $field = '*', $offset = '', $limit = '')
    {
        $res = $this->getModel()->getList($where, $order, $field, $offset, $limit);

        if ($res['count'] > 0) {
            foreach ($res['list'] as $k => $v) {
                $res['list'][$k] = $res['list'][$k]->append(['status_text', 'dividend_mode_text'])->toArray();
            }
        }

        return $res;
    }

    //分页html
    public function getPaginate($where = array(), $order = '', $field = '*', $limit = '')
    {
        $res = $this->getModel()->getPaginate($where, $order, $field, $limit);

        $res = $res->each(function ($item, $key) {
            //$item = $this->getDataView($item);
            $item = $item->append(['status_text', 'dividend_mode_text'])->toArray();
            return $item;
        });

        return $res;
    }

    //全部列表
    public function getAll($where = array(), $order = '', $field = '*', $limit = '')
    {
        $res = $this->getModel()->getAll($where, $order, $field, $limit);

        if ($res) {
            foreach ($res as $k => $v) {
                //$res[$k] = $this->getDataView($v);
                $res[$k] = $res[$k]->append(['status_text', 'dividend_mode_text'])->toArray();
            }
        }

        return $res;
    }

    //详情
    public function getOne($where = array(), $field = '*')
    {
        $res = $this->getModel()->getOne($where, $field);
        if (!$res) {
            return false;
        }

        $res = $res->append(['status_text', 'dividend_mode_text'])->toArray();

        return $res;
    }

    /**
     * 添加一条记录
     * @param int $data ['user_id'] 用户id
     * @param int $data ['project_id'] 项目id
     * @param float $data ['money'] 金额
     * @return array
     */
    public function add($data = array(), $type = 0)
    {
        if (empty($data)) {
            return ReturnData::create(ReturnData::PARAMS_ERROR);
        }

        //添加时间、更新时间
        $time = time();
        if (!(isset($data['add_time']) && !empty($data['add_time']))) {
            $data['add_time'] = $time;
        }
        if (!(isset($data['update_time']) && !empty($data['update_time']))) {
            $data['update_time'] = $time;
        }

        $check = $this->getValidate()->scene('add')->check($data);
        if (!$check) {
            return ReturnData::create(ReturnData::PARAMS_ERROR, null, $this->getValidate()->getError());
        }

        $project = model('Project')->getOne(['id' => $data['project_id'], 'delete_time' => 0, 'stock' => ['>', 0]]);
        if (!$project) {
            return ReturnData::create(ReturnData::FAIL, null, '已认领完');
        }

		$sale = intval($data['money']);
		//判断库存
		if ($data['money'] > $project['stock']) {
            return ReturnData::create(ReturnData::FAIL, null, '认领数量太多啦' . $project['min_buy_money']);
        }

		/* if ($data['money'] < $project['min_buy_money']) {
            return ReturnData::create(ReturnData::FAIL, null, '投资金额不能小于起投金额' . $project['min_buy_money']);
        } */
		if ($project['max_buy_money'] != 0) {
            if ($data['money'] > $project['max_buy_money']) {
				return ReturnData::create(ReturnData::FAIL, null, '已达最大认领份数' . $project['max_buy_money']);
			}
        }
        if ($project['status'] == 1) {
            return ReturnData::create(ReturnData::FAIL, null, '已认领完');
        }
        //判断用户是否复投
        if ($project['is_repeat'] == 1) {
            if ($this->getModel()->getOne(['user_id' => $data['user_id'], 'project_id' => $data['project_id']])) {
                return ReturnData::create(ReturnData::FAIL, null, '限认领一只');
            }
        }

        $user = model('User')->getOne(['id' => $data['user_id']]);
        if (!$user) {
            return ReturnData::create(ReturnData::PARAMS_ERROR, null, '用户不存在');
        }
		
		$money = $data['money'] * $project['min_buy_money'];
        //判断用户余额是否足够
        if ($money > $user['money']) {
            return ReturnData::create(ReturnData::FAIL, null, '余额不足');
        }

		$hike = model('UserRank')->getValue(['rank' => $user['user_rank']], 'hike');
		if (!$hike) {
			$hike = 0;
		}
        // 启动事务
        Db::startTrans();

        $data['title'] = $project['title'];
        $data['dividend_mode'] = $project['dividend_mode'];
        $data['daily_interest'] = $project['daily_interest'];
        $data['term'] = $project['term'];
        $data['hike'] = $hike;
		$data['money'] = $money;
        $user_project_id = $this->getModel()->add($data, $type);
        if (!$user_project_id) {
            // 回滚事务
            Db::rollback();
            return ReturnData::create(ReturnData::FAIL);
        }

        //生成收益记录列表-start
        $expire_time = $time + $project['term'] * 86400; //到期时间
        //款方式，0到期还本还息，1每日返息到期返本，7每周返息到期返本，10000每日复利到期返本返息
        if ($project['dividend_mode'] == 0) {
            $user_project_income_data['user_project_id'] = $user_project_id;
            $user_project_income_data['user_id'] = $data['user_id'];
            $user_project_income_data['project_id'] = $data['project_id'];
            $user_project_income_data['title'] = $project['title'];
            $user_project_income_data['is_last'] = 1;
            $user_project_income_data['money'] = round((($project['daily_interest'] + $hike) * $project['term'] * $money) / 100, 2); //利息
            $user_project_income_data['add_time'] = $expire_time; //应支付利息时间
            $res = model('UserProjectIncome')->add($user_project_income_data, 0);
            if (!$res) {
                // 回滚事务
                Db::rollback();
                return ReturnData::create(ReturnData::FAIL);
            }
        } elseif ($project['dividend_mode'] == 10000) {
            $user_project_income_data['user_project_id'] = $user_project_id;
            $user_project_income_data['user_id'] = $data['user_id'];
            $user_project_income_data['project_id'] = $data['project_id'];
            $user_project_income_data['title'] = $project['title'];
            $user_project_income_data['is_last'] = 1;
            $user_project_income_data['money'] = (round(pow((1 + ($project['daily_interest'] + $hike) * 0.01), $project['term']) * $money, 2) - $money); //复利总金额
            $user_project_income_data['add_time'] = $expire_time; //应支付利息时间
            $res = model('UserProjectIncome')->add($user_project_income_data, 0);
            if (!$res) {
                // 回滚事务
                Db::rollback();
                return ReturnData::create(ReturnData::FAIL);
            }
        } else {
            $user_project_income_data = [];
            $total_period = ceil($project['term'] / $project['dividend_mode']); //总期数
            $total_period_decrement = $total_period - 1;
            for ($x = 1; $x <= $total_period_decrement; $x++) {
                $temp = [];
                $temp['user_project_id'] = $user_project_id;
                $temp['user_id'] = $data['user_id'];
                $temp['project_id'] = $data['project_id'];
                $temp['title'] = $project['title'];
                $temp['is_last'] = 0;
                $temp['money'] = round((($project['daily_interest'] + $hike) * $project['dividend_mode'] * $money) / 100, 2); //利息
                $temp['add_time'] = $time + $x * $project['dividend_mode'] * 86400; //应支付利息时间
                $user_project_income_data[] = $temp;
            }

            //不规律的最后一期另算
            $temp = [];
            $temp['user_project_id'] = $user_project_id;
            $temp['user_id'] = $data['user_id'];
            $temp['project_id'] = $data['project_id'];
            $temp['title'] = $project['title'];
            $temp['is_last'] = 1;
            $temp['money'] = round((($project['daily_interest'] + $hike) * ($project['term'] - $total_period_decrement * $project['dividend_mode']) * $money) / 100, 2); //利息
            $temp['add_time'] = $expire_time; //应支付利息时间
            $user_project_income_data[] = $temp;
            $res = model('UserProjectIncome')->add($user_project_income_data, 2);
            if (!$res) {
                // 回滚事务
                Db::rollback();
                return ReturnData::create(ReturnData::FAIL);
            }
        }
        //生成收益记录列表-end

        //扣除用户余额
        $user_money_data['user_id'] = $data['user_id'];
        $user_money_data['type'] = 1; // 0增加,1减少
        $user_money_data['money'] = $money;
        $user_money_data['desc'] = '认领-' . $project['title'];
        $res = logic('UserMoney')->add($user_money_data);
        if ($res['code'] != ReturnData::SUCCESS) {
            Db::rollback();
            return ReturnData::create(ReturnData::FAIL);
        }

        //项目销量+1，库存-1
        model('Project')->edit(['sale' => ($project['sale'] + $sale), 'stock' => ($project['stock'] - $sale)], ['id' => $data['project_id']]);
        //增加用户累计消费金额
        model('User')->setIncrement(array('id' => $data['user_id']), 'consumption_money', $money);
        //修改用户等级
        logic('User')->user_rank_calculation($data['user_id']);

        //分销计算
        logic('User')->dividend_calculation($data['user_id'], $money, ['distribution_yiji'=>$project['distribution_yiji'], 'distribution_erji'=>$project['distribution_erji']]);

        // 提交事务
        Db::commit();
        return ReturnData::create(ReturnData::SUCCESS, $res);
    }

    //修改
    public function edit($data, $where = array())
    {
        if (empty($data)) {
            return ReturnData::create(ReturnData::SUCCESS);
        }

        //更新时间
        if (!(isset($data['update_time']) && !empty($data['update_time']))) {
            $data['update_time'] = time();
        }

        $check = $this->getValidate()->scene('edit')->check($data);
        if (!$check) {
            return ReturnData::create(ReturnData::PARAMS_ERROR, null, $this->getValidate()->getError());
        }

        $record = $this->getModel()->getOne($where);
        if (!$record) {
            return ReturnData::create(ReturnData::RECORD_NOT_EXIST);
        }

        $res = $this->getModel()->edit($data, $where);
        if (!$res) {
            return ReturnData::create(ReturnData::FAIL);
        }

        return ReturnData::create(ReturnData::SUCCESS, $res);
    }

    //删除
    public function del($where)
    {
        if (empty($where)) {
            return ReturnData::create(ReturnData::PARAMS_ERROR);
        }

        $check = $this->getValidate()->scene('del')->check($where);
        if (!$check) {
            return ReturnData::create(ReturnData::PARAMS_ERROR, null, $this->getValidate()->getError());
        }

        $res = $this->getModel()->del($where);
        if (!$res) {
            return ReturnData::create(ReturnData::FAIL);
        }

        return ReturnData::create(ReturnData::SUCCESS, $res);
    }

    /**
     * 数据获取器
     * @param array $data 要转化的数据
     * @return array
     */
    private function getDataView($data = array())
    {
        return getDataAttr($this->getModel(), $data);
    }

    //用户项目收益计算
    public function revenue_calculation($user_id)
    {
        $time = time();
        //获取需要支付给用户收益的项目
        $where = [];
        $where['user_id'] = $user_id;
        $where['status'] = 0;
        $where['add_time'] = ['<', $time];
        $user_project_income = model('UserProjectIncome')->getAll($where);
        if (!$user_project_income) {
            return false;
        }

        foreach ($user_project_income as $k => $v) {
            $user = model('User')->getOne(['id' => $v['user_id']]);
            $project = model('Project')->getOne(['id' => $v['project_id']]);
            $user_project = model('UserProject')->getOne(['id' => $v['user_project_id']]);

            // 启动事务
            Db::startTrans();
            //增加用户余额
            $user_money_data['user_id'] = $user_id;
            $user_money_data['type'] = 0; // 0增加,1减少
            $user_money_data['money'] = $v['money'];
            $user_money_data['desc'] = '认领收益-' . $v['title'];
            $user_money_data['add_time'] = $v['add_time'];
            $res = logic('UserMoney')->add($user_money_data);
            if ($res['code'] != ReturnData::SUCCESS) {
                Db::rollback();
                continue;
            }

            $edit_user_project_income['update_time'] = $time;
            $edit_user_project_income['status'] = 1;
            $res_user_project_income = model('UserProjectIncome')->edit($edit_user_project_income, ['id' => $v['id']]);
            if (!$res_user_project_income) {
                Db::rollback();
                continue;
            }

            //增加用户累计收益
            model('User')->setIncrement(array('id' => $user_id), 'consumption_income', $v['money']);

            //最后一期
            if ($v['is_last'] == 1) {
                //修改用户投资项目的状态为已完结
                $edit_user_project['update_time'] = $time;
                $edit_user_project['status'] = 1;
                $res_user_project = model('UserProject')->edit($edit_user_project, ['id' => $v['user_project_id']]);
                if (!$res_user_project) {
                    Db::rollback();
                    continue;
                }
                //返本金给用户
                $user_money_data['user_id'] = $user_id;
                $user_money_data['type'] = 0; // 0增加,1减少
                $user_money_data['money'] = $user_project['money'];
                $user_money_data['desc'] = '返还本金-' . $v['title'];
                $user_money_data['add_time'] = $v['add_time'];
                $res = logic('UserMoney')->add($user_money_data);
                if ($res['code'] != ReturnData::SUCCESS) {
                    Db::rollback();
                    continue;
                }
            }

            // 提交事务
            Db::commit();
        }

        return true;
    }

}