<?php
/**
 * 权限相关配置
 * @since   2018-02-06
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\agent\controller;


use app\agent\model\AgentAuthGroup;
use app\agent\model\AgentAuthGroupAccess;
use app\agent\model\AgentAuthRule;
use app\agent\model\AgentMenu;
use app\util\ReturnCode;
use app\util\Tools;
use think\Db;
use think\Exception;

class Auth extends Base
{

    /**
     * 获取权限组列表
     * @return array
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function index()
    {

        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $keywords = $this->request->get('keywords', '');
        $status = $this->request->get('status', '');

        $where['agent_id'] = $this->agent_id;
        $where['name'] = ['like', "%{$keywords}%"];
        if ($status === '1' || $status === '0') {
            $where['status'] = $status;
        }
        $listObj = (new AgentAuthGroup())->where($where)->order('id DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();

        return $this->buildSuccess([
            'list' => $listObj['data'],
            'count' => $listObj['total']
        ]);
    }

    /**
     * 添加编辑子账号 可选分组
     * 获取全部已开放的可选组
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGroups()
    {
        $listInfo = (new AgentAuthGroup())->where(['status' => 1,'agent_id'=>$this->agent_id])->order('id', 'DESC')->select();
        $count = count($listInfo);
        $listInfo = Tools::buildArrFromObj($listInfo);

        return $this->buildSuccess([
            'list' => $listInfo,
            'count' => $count
        ]);
    }

    /**
     * 添加编辑权限组 可选权限列表
     * 获取组所在权限列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function getRuleList()
    {
        $groupId = $this->request->get('groupId', 0);

        $list = (new AgentMenu)->where([])->order('sort', 'ASC')->select();
        $list = Tools::buildArrFromObj($list);
        $list = listToTree($list);

        $rules = [];
        if ($groupId) {
            $rules = (new AgentAuthRule())->where(['groupId' => $groupId])->select();
            $rules = Tools::buildArrFromObj($rules);
            $rules = array_column($rules, 'url');
        }
        $newList = $this->buildList($list, $rules);

        return $this->buildSuccess([
            'list' => $newList
        ]);
    }

    /**
     * 新增组
     * @return array
     * @throws \Exception
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function add()
    {
        $rules = [];
        $postData = $this->request->post();
        if ($postData['rules']) {
            $rules = $postData['rules'];
            $rules = array_filter($rules);
        }
        $postData['agent_id'] = $this->agent_id;
        unset($postData['rules']);
        $res = AgentAuthGroup::create($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            if ($rules) {
                $insertData = [];
                foreach ($rules as $value) {
                    if ($value) {
                        $insertData[] = [
                            'groupId' => $res->id,
                            'url' => $value
                        ];
                    }
                }
                (new AgentAuthRule())->saveAll($insertData);
            }

            return $this->buildSuccess([]);
        }
    }

    /**
     * 权限组状态编辑
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function changeStatus()
    {
        $id = $this->request->get('id');
        $status = $this->request->get('status');
        $res = AgentAuthGroup::update([
            'id' => $id,
            'status' => $status
        ]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }

    /**
     * 编辑权限组
     * @return array
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function edit()
    {
        $postData = $this->request->post();
        $pass = $postData;
//        if ($postData['rules']) {
            $this->editRule();
//        }

        unset($postData['rules']);
        $res = AgentAuthGroup::update($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([$pass]);
        }
    }

    /**
     * 删除组
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function del()
    {
        $id = $this->request->get('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }

        $groupAccess = (new AgentAuthGroupAccess())->where('groupId','eq',$id)->select();
        if(empty($groupAccess)){//该分组下没有账号才可以删除
            Db::startTrans();
            try{
                AgentAuthGroup::destroy($id);
                AgentAuthRule::destroy(['groupId' => $id]);
                Db::commit();
                return $this->buildSuccess([]);
            }catch(\Exception $e){
                Db::rollback();
                return $this->buildFailed(ReturnCode::DELETE_FAILED,'删除失败');
            }
        }else{
            return $this->buildFailed(ReturnCode::DELETE_FAILED,'该分组下有账户无法删除');
        }

        //多选权限组 23,25 删除方式
        /*$listInfo = (new AgentAuthGroupAccess())->where(['groupId' => ['like', "%{$id}%"]])->select();
        if ($listInfo) {
            foreach ($listInfo as $value) {
                $valueArr = $value->toArray();
                $oldGroupArr = explode(',', $valueArr['groupId']);
                $key = array_search($id, $oldGroupArr);
                if ($key !== false) {
                    unset($oldGroupArr[$key]);
                }
                $newData = implode(',', $oldGroupArr);
                $value->groupId = $newData;
                $value->save();
            }
        }

        AgentAuthGroup::destroy($id);
        AgentAuthRule::destroy(['groupId' => $id]);

        return $this->buildSuccess([]);*/
    }

    /**
     * 从指定组中删除指定用户
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function delMember()
    {
        $gid = $this->request->get('gid', 0);
        $uid = $this->request->get('uid', 0);
        if (!$gid || !$uid) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        $oldInfo = AgentAuthGroupAccess::get(['uid' => $uid])->toArray();
        $oldGroupArr = explode(',', $oldInfo['groupId']);
        $key = array_search($gid, $oldGroupArr);
        if ($key !== false) {
            unset($oldGroupArr[$key]);
        }
        $newData = implode(',', $oldGroupArr);
        $res = AgentAuthGroupAccess::update([
            'groupId' => $newData
        ], [
            'uid' => $uid
        ]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }

    /**
     * 构建适用前端的权限数据
     * @param $list
     * @param $rules
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    private function buildList($list, $rules)
    {
        $newList = [];
        foreach ($list as $key => $value) {
            $newList[$key]['title'] = $value['name'];
            $newList[$key]['key'] = $value['url'];
            if (isset($value['_child'])) {
                $newList[$key]['expand'] = true;
                $newList[$key]['children'] = $this->buildList($value['_child'], $rules);
            } else {
                if (in_array($value['url'], $rules)) {
                    $newList[$key]['checked'] = true;
                }
            }
        }

        return $newList;
    }

    /**
     * 编辑权限细节
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    private function editRule()
    {
        $postData = $this->request->post();
        $needAdd = [];
        $has = (new AgentAuthRule())->where(['groupId' => $postData['id']])->select();
        $has = Tools::buildArrFromObj($has);
        $hasRule = array_column($has, 'url');
        $needDel = array_flip($hasRule);
        foreach ($postData['rules'] as $key => $value) {
            if (!empty($value)) {
                if (!in_array($value, $hasRule)) {
                    $data['url'] = $value;
                    $data['groupId'] = $postData['id'];
                    $needAdd[] = $data;
                } else {
                    unset($needDel[$value]);
                }
            }
        }
        if (count($needAdd)) {
            (new AgentAuthRule())->saveAll($needAdd);
        }
        if (count($needDel)) {
            $urlArr = array_keys($needDel);
            AgentAuthRule::destroy([
                'groupId' => $postData['id'],
                'url' => ['in', $urlArr]
            ]);
        }
    }

}
