<?php

namespace app\fladmin\controller;

use think\Db;
use think\Validate;
use app\common\lib\ReturnData;
use app\common\lib\Helper;
use app\common\logic\ProjectLogic;

class Project extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function getLogic()
    {
        return new ProjectLogic();
    }

    public function index()
    {
        $where = array();
        if (!empty($_REQUEST["keyword"])) {
            $where['title'] = array('like', '%' . $_REQUEST['keyword'] . '%');
        }
        if (!empty($_REQUEST["type_id"]) && $_REQUEST["type_id"] > 0) {
            $where['type_id'] = $_REQUEST["type_id"];
        }
        $where['delete_time'] = 0; //0未删除
        if (isset($_REQUEST["status"])) {
            $where['status'] = $_REQUEST["status"]; //投资状态：0投资中，1已投满
        }

        $list = $this->getLogic()->getPaginate($where, ['status' => 'asc', 'listorder' => 'asc'], ['content']);

        $this->assign('page', $list->render());
        $this->assign('list', $list);
        //echo '<pre>';print_r($list);exit;

        //分类列表
        $project_type_list = model('ProjectType')->tree_to_list(model('ProjectType')->list_to_tree());
        $this->assign('project_type_list', $project_type_list);

        return $this->fetch();
    }

    public function add()
    {
        if (Helper::isPostRequest()) {
            //表单令牌验证
            $validate = new Validate([
                ['__token__', 'require|token', '非法提交|请不要重复提交表单'],
            ]);
            if (!$validate->check($_POST)) {
                $this->error($validate->getError());
            }

            $litpic = "";
            if (!empty($_POST["litpic"])) {
                $litpic = $_POST["litpic"];
            } else {
                $_POST['litpic'] = "";
            } //缩略图
            if (empty($_POST["description"])) {
                if (!empty($_POST["content"])) {
                    $_POST['description'] = cut_str($_POST["content"]);
                }
            } //description
            $content = "";
            if (!empty($_POST["content"])) {
                $content = $_POST["content"];
            }

            $update_time = time();
            if ($_POST['update_time']) {
                $update_time = strtotime($_POST['update_time']);
            } // 更新时间
            $_POST['add_time'] = $_POST['update_time'] = $update_time;
			$_POST['admin_id'] = $this->admin_info['id']; // 管理员发布者ID
			
            //关键词
            if (!empty($_POST["keywords"])) {
                $_POST['keywords'] = str_replace("，", ",", $_POST["keywords"]);
            } else {
                if (!empty($_POST["title"])) {
                    $title = $_POST["title"];
                    $title = str_replace("，", "", $title);
                    $title = str_replace(",", "", $title);
                    $_POST['keywords'] = get_participle($title); // 标题分词
                }
            }

            if (isset($_POST['keywords']) && !empty($_POST['keywords'])) {
                $_POST['keywords'] = mb_strcut($_POST['keywords'], 0, 60, 'UTF-8');
            }
            if (isset($_POST["dellink"]) && $_POST["dellink"] == 1 && !empty($content)) {
                $content = logic('Project')->replacelinks($content, array(http_host()));
            } //删除非站内链接
            $_POST['content'] = $content;

            // 提取第一个图片为缩略图
            if (isset($_POST["autolitpic"]) && $_POST["autolitpic"] && empty($litpic)) {
                $litpic = logic('Project')->getBodyFirstPic($content);
                if ($litpic) {
                    $_POST['litpic'] = $litpic;
                }
            }

            $res = $this->getLogic()->add($_POST);
            if ($res['code'] == ReturnData::SUCCESS) {
                $this->success($res['msg'], url('index'), '', 1);
            }

            $this->error($res['msg']);
        }

        //文章添加到哪个栏目下
        $this->assign('type_id', input('type_id/d', 0));

        //栏目列表
        $project_type_list = model('ProjectType')->tree_to_list(model('ProjectType')->list_to_tree());
        $this->assign('project_type_list', $project_type_list);

		//牧场列表
        $project_farm_list = model('ProjectFarm')->getAll();
        $this->assign('project_farm_list', $project_farm_list);

        return $this->fetch();
    }

    public function edit()
    {
        if (Helper::isPostRequest()) {
            $id = $where['id'] = $_POST['id'];
            unset($_POST['id']);

            $litpic = "";
            if (!empty($_POST["litpic"])) {
                $litpic = $_POST["litpic"];
            } else {
                $_POST['litpic'] = "";
            } //缩略图
            if (empty($_POST["description"])) {
                if (!empty($_POST["content"])) {
                    $_POST['description'] = cut_str($_POST["content"]);
                }
            } //description
            $content = "";
            if (!empty($_POST["content"])) {
                $content = $_POST["content"];
            }

            $update_time = time();
            if ($_POST['update_time']) {
                $update_time = $_POST['add_time'] = strtotime($_POST['update_time']);
            } // 更新时间
            $_POST['update_time'] = $update_time;

            //关键词
            if (!empty($_POST["keywords"])) {
                $_POST['keywords'] = str_replace("，", ",", $_POST["keywords"]);
            } else {
                if (!empty($_POST["title"])) {
                    $title = $_POST["title"];
                    $title = str_replace("，", "", $title);
                    $title = str_replace(",", "", $title);
                    $_POST['keywords'] = get_participle($title); // 标题分词
                }
            }

            if (isset($_POST['keywords']) && !empty($_POST['keywords'])) {
                $_POST['keywords'] = mb_strcut($_POST['keywords'], 0, 60, 'UTF-8');
            }
            if (isset($_POST["dellink"]) && $_POST["dellink"] == 1 && !empty($content)) {
                $content = logic('Project')->replacelinks($content, array(http_host()));
            } //删除非站内链接
            $_POST['content'] = $content;

            // 提取第一个图片为缩略图
            if (isset($_POST["autolitpic"]) && $_POST["autolitpic"] && empty($litpic)) {
                $litpic = logic('Project')->getBodyFirstPic($content);
                if ($litpic) {
                    $_POST['litpic'] = $litpic;
                }
            }

            $res = $this->getLogic()->edit($_POST, $where);
            if ($res['code'] == ReturnData::SUCCESS) {
                $this->success($res['msg'], url('index'), '', 1);
            }

            $this->error($res['msg']);
        }

        if (!checkIsNumber(input('id', null))) {
            $this->error('参数错误');
        }
        $where['id'] = input('id');
        $this->assign('id', $where['id']);

        $post = $this->getLogic()->getOne($where);
        $this->assign('post', $post);

        //栏目列表
        $project_type_list = model('ProjectType')->tree_to_list(model('ProjectType')->list_to_tree());
        $this->assign('project_type_list', $project_type_list);

		//牧场列表
        $project_farm_list = model('ProjectFarm')->getAll();
        $this->assign('project_farm_list', $project_farm_list);

        return $this->fetch();
    }

    //删除
    public function del()
    {
		$id = input('id', '');
        if (!$id) {
            $this->error('参数错误');
        }
        if (is_numeric($id)) {
            $where['id'] = input('id');
        } else {
			$where = "id in ($id)";
		}
		$res = model('Project')->edit(array('delete_time' => time()), $where);
        if (!$res) {
            $this->error('操作失败');
        }
		$this->success("$id ,删除成功", url('index'), '', 1);
    }

    //文章重复列表
    public function repetarc()
    {
        $this->assign('list', Db::query("select title,count(*) AS count from " . config('database.prefix') . "Project group by title HAVING count>1 order by count DESC"));

        return $this->fetch();
    }

    //文章推荐
    public function recommendarc()
    {
        if (!empty($_GET["id"])) {
            $id = $_GET["id"];
        } else {
            $this->error('参数错误', url('index'), '', 3);
        } //if(preg_match('/[0-9]*/',$id)){}else{exit;}

        $data['tuijian'] = 1;
        $res = model('Project')->edit($data, "id in ($id)");
        if ($res) {
            $this->success("$id ,推荐成功");
        }

        $this->error("$id ,推荐失败！请重新提交");
    }

    //文章是否存在
    public function projectexists()
    {
        $map = [];
        if (!empty($_GET["title"])) {
            $map['title'] = $_GET["title"];
        }

        if (!empty($_GET["id"])) {
            $map['id'] = array('NEQ', $_GET["id"]);
        }

        echo model('Project')->getCount($map);
    }
}