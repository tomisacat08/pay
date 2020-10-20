<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\admin\controller;
use app\api\swoole\SwooleClientService;
use app\util\Tools;
use app\util\ReturnCode;
use app\model\Notice as NoticeModel;

class Notice extends Base{

    /**
     * 公告列表
     * @return array
     * @author
     */
    public function index(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $listObj = (new NoticeModel())->order('create_time DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();

        $listInfo = $listObj['data'];
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total'],
        ]);
    }

    /**
     * 新增公告
     * @return array
     * @author
     */
    public function add(){
        $postData = $this->request->post();
        $postData['create_time']= time();
        $res = NoticeModel::create($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }

        //swoole_websocket_server推送
        $client = new SwooleClientService();
        $params = [
            'msg'=>$postData['content'],
        ];

        $package = $client->package('pushMsg',$params);

        $client->push($package);

        return $this->buildSuccess([]);
    }
    /**
     * 公告编辑
     * @author
     * @return array
     */
    public function edit() {
        $postData = $this->request->post();
        $res = NoticeModel::update($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }
    /**
     * 删除公告
     * @return array
     * @author
     */
    public function del() {
        $id = $this->request->get('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        NoticeModel::destroy($id);
        NoticeModel::destroy(['id' => $id]);

        return $this->buildSuccess([]);

    }
    /**
     * 公告显示开关
     * @return array
     * @author
     */
    public function changeStatus() {
        $id = $this->request->get('id');
        $type = $this->request->get('type');
        $status = $this->request->get('status');
        $data['id'] = $id;
        if($type == 1){//代理
            $data['agent_status'] = $status;
        }elseif($type == 2){//会员
            $data['member_status'] = $status;
        }elseif($type == 3){//商户
            $data['merchant_status'] = $status;
        }
        $res = NoticeModel::update($data);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }
    /**
     * 公告置顶开关
     * @return array
     * @author
     */
    public function isTop() {
        $id = $this->request->get('id');
        $status = $this->request->get('is_top');
        $res = NoticeModel::update([
            'id'         => $id,
            'is_top'     => $status,
        ]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }

}