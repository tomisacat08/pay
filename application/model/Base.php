<?php
/**
 * 模型基类
 * @since   2017/07/25 创建
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\model;


use think\Model;

class Base extends Model {
    protected $autoWriteTimestamp = 'Y-m-d H:i:s';
    protected $field = true;

    /*protected $controlleName = NULL;//当前访问的控制器名称
    protected $actionName = NULL;//当前方位的操作名称
    //protected $table = '';

    function initialize(){
        parent::initialize();
        //$this->table(request()->controller());
        //$this->name = request()->controller();
        $this->table = 'admin';
    }

    function __construct(){
        parent::__construct();
        $this->table = 'admin';
    }*/
    function getList($where = '', $order = '') {
        $list = $this->where($where)->order($order)->select();
        if ($list) {
            $data = [];
            foreach ($list as $k => $v) {
                $data[] = $v->toArray();
            }
            return $data;
        } else {
            return false;
        }
    }

    /**
     * 获取列表（分页）
     * @param string $where
     * @param string $order
     * @return mixed
     */
    function getListToPage($where = '', $order = '') {
        $list = $this->where($where)->order($order)->paginate(null, false, ['query' => input('get.')]);
        if (!$list->isEmpty()) {
            $data = [];
            foreach ($list as $k => $v) {
                $data['list'][] = $v->toArray();
            }
            $data['total'] = $list->total();
            $data['page'] = $list->render();
            return $data;
        } else {
            return false;
        }
    }

    function getListToPageHome($where = '', $order = '', $path = '') {
        $list = $this->where($where)->order($order)->paginate(null, false, ['query' => input('get.'), 'path' => $path]);
        if (!$list->isEmpty()) {
            $data = [];
            foreach ($list as $k => $v) {
                $data['list'][] = $v->toArray();
            }
            $data['total'] = $list->total();
            $data['page'] = $list->render();
            return $data;
        } else {
            return false;
        }
    }

    /**
     * 获取列表并排序为树状目录
     */
    function getListToTree($where = '', $order = '') {
        $list = $this->where($where)->order($order)->select();
        if ($list) {
            $data = [];
            foreach ($list as $k => $v) {
                $data['list'][] = $v->toArray();
            }
            return $data;
        } else {
            return false;
        }
    }

    function getListToNode() {
    }

    /**
     * 根据id获取记录
     * @param $id
     * @return array|bool
     */
    function getRowById($id) {
        if (!$id) {
            return false;
        }
        $info = $this->where(['id' => $id])->find();
        if ($info) {
            $data = $info->toArray();
        } else {
            $data = $info;
        }
        return $data;
    }

    function getRowInfo($where = '') {
        $info = $this->where($where)->find();
        if ($info) {
            $data = $info->toArray();
        } else {
            $data = $info;
        }
        return $data;
    }

    /**
     * 更新记录
     * @param array $data
     * @param string $scene
     * @return bool
     */
    function updateRow($data = [], $scene = '') {
        if (!is_array($data)) {
            return false;
        } else {
            $data = request()->input($data);
        }
        $id = input('post.id');
        $controllerName = request()->controller();
        /*数据验证场景*/
        if ($scene == '') {
            $scene = $id ? $controllerName . '.editor' : $controllerName . '.create';
        }
        if ($id) {
            return $this->validate($scene)->save($data, $data['id']);
        } else {
            return $this->validate($scene)->save($data);
        }
    }

    /**
     * 更新带有排序和层级的字段
     * @return bool
     */
    function updateRowForSortAndLevel() {
        $id = input('post.id');
        $pid = input('post.pid', 0);
        $data = $_POST;
        if (empty($id)) {
            $sort = $this->where(['pid' => $pid])->max('sort');
            $data['sort'] = $sort + 1;
            if ($pid == 0) {
                $data['level'] = 1;
            } else {
                $tmp = $this->where('id=' . $pid)->field('level')->find();
                $data['level'] = $tmp['level'] + 1;
            }
        } else {
            $tmp = $this->where('id=' . $pid)->find();
            $data['level'] = $tmp['level'] + 1;
        }
        $res = $this->updateRow($data);
        if (false !== $res) {
            return true;
        } else {
            return false;
        }
    }

    function updateRowForSort() {
        $id = input('post.id');
        $data = $_POST;
        if (empty($id)) {
            $sort = $this->max('sort');
            $data['sort'] = $sort + 1;
        }
        $res = $this->updateRow($data);
        if (false !== $res) {
            return true;
        } else {
            return false;
        }
    }
}