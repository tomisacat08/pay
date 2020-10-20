<?php
namespace app\admin\controller;
use app\util\ReturnCode;
use app\model\Config as ConfigModel;


class Config extends Base {
	public function index() {
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);

        $listObj = (new ConfigModel())->order('id', 'DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();
        return $this->buildSuccess([
            'list'  => $listObj['data'],
            'count' => $listObj['total']
        ]);
	}
    /**
     * 新增配置
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function add(){
        $postData = $this->request->post();
        $res = ConfigModel::create($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }
    /**
     * 配置编辑
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     * @return array
     */
    public function edit() {
        $postData = $this->request->post();
        $res = ConfigModel::update($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }
    /**
     * 删除配置
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function del() {
        $id = $this->request->get('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        ConfigModel::destroy($id);
        ConfigModel::destroy(['id' => $id]);

        return $this->buildSuccess([]);

    }
}