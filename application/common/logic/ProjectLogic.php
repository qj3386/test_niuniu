<?php

namespace app\common\logic;

use think\Loader;
use app\common\lib\ReturnData;
use app\common\model\Project;

class ProjectLogic extends BaseLogic
{
    protected function initialize()
    {
        parent::initialize();
    }

    public function getModel()
    {
        return new Project();
    }

    public function getValidate()
    {
        return Loader::validate('Project');
    }

    //列表
    public function getList($where = array(), $order = '', $field = '*', $offset = '', $limit = '')
    {
        $res = $this->getModel()->getList($where, $order, $field, $offset, $limit);

        if ($res['count'] > 0) {
            foreach ($res['list'] as $k => $v) {
                $res['list'][$k] = $res['list'][$k]->append(['dividend_mode_text', 'type_name_text', 'status_text'])->toArray();
            }
        }

        return $res;
    }

    //分页html
    public function getPaginate($where = array(), $order = '', $field = '*', $limit = '')
    {
        $res = $this->getModel()->getPaginate($where, $order, $field, $limit);

        $res = $res->each(function ($item, $key) {
            $item = $item->append(['dividend_mode_text', 'type_name_text', 'status_text'])->toArray();
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
                $res[$k] = $res[$k]->append(['dividend_mode_text', 'type_name_text', 'status_text'])->toArray();
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

        $res = $res->append(['dividend_mode_text', 'type_name_text', 'status_text', 'project_farm'])->toArray();
		
        $this->getModel()->getDb()->where($where)->setInc('click', 1);

        return $res;
    }

    //添加
    public function add($data = array(), $type = 0)
    {
        if (empty($data)) {
            return ReturnData::create(ReturnData::PARAMS_ERROR);
        }

		//标题最多150个字符
		if (isset($data['title']) && !empty($data['title'])) {
			$data['title'] = mb_strcut($data['title'],0,150,'UTF-8');
        }
		//SEO标题最多150个字符
		if (isset($data['seotitle']) && !empty($data['seotitle'])) {
			$data['seotitle'] = mb_strcut($data['seotitle'],0,150,'UTF-8');
        }
		//关键词最多60个字符
		if (isset($data['keywords']) && !empty($data['keywords'])) {
			$data['keywords'] = mb_strcut($data['keywords'],0,60,'UTF-8');
        }
		//描述最多240个字符
		if (isset($data['description']) && !empty($data['description'])) {
			$data['description'] = mb_strcut($data['description'],0,240,'UTF-8');
        }
		//卖点最多150个字符
		if (isset($data['sell_point']) && !empty($data['sell_point'])) {
			$data['sell_point'] = mb_strcut($data['sell_point'],0,150,'UTF-8');
        }
		//担保机构最多150个字符
		if (isset($data['guarantee_agency']) && !empty($data['guarantee_agency'])) {
			$data['guarantee_agency'] = mb_strcut($data['guarantee_agency'],0,150,'UTF-8');
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

        $res = $this->getModel()->add($data, $type);
        if (!$res) {
            return ReturnData::create(ReturnData::FAIL);
        }

        return ReturnData::create(ReturnData::SUCCESS, $res);
    }

    //修改
    public function edit($data, $where = array())
    {
        if (empty($data)) {
            return ReturnData::create(ReturnData::SUCCESS);
        }

		//标题最多150个字符
		if (isset($data['title']) && !empty($data['title'])) {
			$data['title'] = mb_strcut($data['title'],0,150,'UTF-8');
        }
		//SEO标题最多150个字符
		if (isset($data['seotitle']) && !empty($data['seotitle'])) {
			$data['seotitle'] = mb_strcut($data['seotitle'],0,150,'UTF-8');
        }
		//关键词最多60个字符
		if (isset($data['keywords']) && !empty($data['keywords'])) {
			$data['keywords'] = mb_strcut($data['keywords'],0,60,'UTF-8');
        }
		//描述最多240个字符
		if (isset($data['description']) && !empty($data['description'])) {
			$data['description'] = mb_strcut($data['description'],0,240,'UTF-8');
        }
		//卖点最多150个字符
		if (isset($data['sell_point']) && !empty($data['sell_point'])) {
			$data['sell_point'] = mb_strcut($data['sell_point'],0,150,'UTF-8');
        }
		//担保机构最多150个字符
		if (isset($data['guarantee_agency']) && !empty($data['guarantee_agency'])) {
			$data['guarantee_agency'] = mb_strcut($data['guarantee_agency'],0,150,'UTF-8');
        }
        //更新时间
        if (!(isset($data['update_time']) && !empty($data['update_time']))) {
            $data['update_time'] = time();
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

        $res = $this->getModel()->edit(array('delete_time' => time()), $where);
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

    // 获取文章内容的第一张图片，并缩略图保存
    public function getBodyFirstPic($content)
    {
        $res = '';
        $imagepath = $this->getfirstpic($content);
        if ($imagepath) {
            // 获取文章内容的第一张图片
            $imagepath = '.' . $imagepath;

            // 获取后缀名
            preg_match_all("/\/(.+)\.(gif|jpg|jpeg|bmp|png)$/iU", $imagepath, $out, PREG_PATTERN_ORDER);

            $saveimage = './uploads/' . date('Y/m', time()) . '/' . basename($imagepath, '.' . $out[2][0]) . '-lp.' . $out[2][0];

            // 生成缩略图
            $image = \think\Image::open($imagepath);
            // 按照原图的比例生成一个最大为240*180的缩略图
            $image->thumb(sysconfig('CMS_IMGWIDTH'), sysconfig('CMS_IMGHEIGHT'))->save($saveimage);

            // 缩略图路径
            $res = '/uploads/' . date('Y/m', time()) . '/' . basename($imagepath, '.' . $out[2][0]) . '-lp.' . $out[2][0];
        }

        return $res;
    }

    // 按二维数组的某一字段长度排序
    public function arrStringLenSort($arr, $field)
    {
        $res = [];

        foreach ($arr as $key => $value) {
            $arr[$key]['len'] = strlen($value[$field]);
        }

        $len_arr = array_column($arr, 'len');
        array_multisort($len_arr, SORT_ASC, $arr);

        return $arr;
    }

    /**
     * 为文章内容添加内链, 排除alt title <a></a>直接的字符替换
     *
     * @param string $body
     * @return string
     */
    public function replaceKeyword($body)
    {
        //暂时屏蔽超链接
        $body = preg_replace("#(<a(.*))(>)(.*)(<)(\/a>)#isU", '\\1-]-\\4-[-\\6', $body);
        $body = preg_replace_callback("/title=\"(.*)\"/isU", function ($matches) {
            return 'title="' . urlencode($matches[1]) . '"';
        }, $body);
        $body = preg_replace_callback("/alt=\"(.*)\"/isU", function ($matches) {
            return 'alt="' . urlencode($matches[1]) . '"';
        }, $body);

        $posts = cache("keyword_list");
        if (!$posts) {
            $posts = db("keyword")->select();
            cache("keyword_list", $posts, 2592000);
        }
        if (!$posts) {
            return $body;
        }
        $body = str_replace('\"', '"', $body);

        $posts = $this->arrStringLenSort($posts, 'name');

        foreach ($posts as $key => $value) {
            $body = preg_replace("#" . preg_quote($value['name']) . "#isU", '<a href="' . $value['url'] . '"><u>' . $value['name'] . '</u></a>', $body, 1);
        }

        //恢复超链接
        $body = preg_replace("#(<a(.*))-\]-(.*)-\[-(\/a>)#isU", '\\1>\\3<\\4', $body);
        $body = preg_replace_callback("/title=\"(.*)\"/isU", function ($matches) {
            return 'title="' . urldecode($matches[1]) . '"';
        }, $body);
        $body = preg_replace_callback("/alt=\"(.*)\"/isU", function ($matches) {
            return 'alt="' . urldecode($matches[1]) . '"';
        }, $body);
        return $body;
    }

    /**
     * 删除非站内链接
     *
     * @access public
     * @param  string $body 内容
     * @param  array $allow_urls 允许的超链接
     * @return string
     */
    public function replacelinks($body, $allow_urls = array())
    {
        $host_rule = join('|', $allow_urls);
        $host_rule = preg_replace("#[\n\r]#", '', $host_rule);
        $host_rule = str_replace('.', "\\.", $host_rule);
        $host_rule = str_replace('/', "\\/", $host_rule);
        $arr = '';

        preg_match_all("#<a([^>]*)>(.*)<\/a>#iU", $body, $arr);

        if (is_array($arr[0])) {
            $rparr = array();
            $tgarr = array();

            foreach ($arr[0] as $i => $v) {
                if ($host_rule != '' && preg_match('#' . $host_rule . '#i', $arr[1][$i])) {
                    continue;
                } else {
                    $rparr[] = $v;
                    $tgarr[] = $arr[2][$i];
                }
            }

            if (!empty($rparr)) {
                $body = str_replace($rparr, $tgarr, $body);
            }
        }
        $arr = $rparr = $tgarr = '';
        return $body;
    }

    /**
     * 获取文本中首张图片地址
     * @param  [type] $content
     * @return [type]
     */
    public function getfirstpic($content)
    {
        if (preg_match_all("/(src)=([\"|']?)([^ \"'>]+\.(gif|jpg|jpeg|bmp|png))\\2/i", $content, $matches)) {
            $file = $_SERVER['DOCUMENT_ROOT'] . $matches[3][0];

            if (file_exists($file)) {
                return $matches[3][0];
            }
        }

        return false;
    }

}