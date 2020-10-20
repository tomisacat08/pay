<?php
namespace app\admin\controller;

use app\api\swoole\PayService;
use app\model\AgentBucket;
use app\model\Merchant;
use app\model\Agent;
use app\model\MerchantBucket;
use app\util\lock\Lock;
use app\util\ReturnCode;
use app\model\Bucket as BucketModel;
use think\Db;


class Bucket extends Base {
	public function index() {
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $name = $this->request->get('name', '');
        $merchantUid = $this->request->get('merchantUid', '');
        $agentMobile = $this->request->get('agentMobile', '');

        $model = new BucketModel();
        if($name){
            $model->where('name',$name);
        }
        if($merchantUid){
            $merchantInfo = Merchant::where('uid',$merchantUid)->find();
            if(empty($merchantInfo) || empty($merchantInfo->bucket_id)){
                return $this->buildSuccess([
                    'list'  => [],
                    'count' => 0
                ]);
            }
            $model->where('id',$merchantInfo->bucket_id);
        }

        if($agentMobile){
            $agentInfo = Agent::where('mobile',$agentMobile)->find();
            if(empty($agentInfo) || empty($agentInfo->bucket_id)){
                return $this->buildSuccess([
                    'list'  => [],
                    'count' => 0
                ]);
            }

            $model->where('id',$agentInfo->bucket_id);
        }



        $listObj = $model->order('id', 'DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();

        foreach($listObj['data'] as &$item){
            $memberNum = PayService::getQrcodeListCount( $item[ 'id']);
            $item['memberNum'] = $memberNum;
        }

        return $this->buildSuccess([
            'list'  => $listObj['data'],
            'count' => $listObj['total']
        ]);
	}
    /**
     * 新增配置
     * @return array
     */
    public function add(){
        $params = $this->request->post();
        $postData['name'] = $params['name'];
        $postData['channel'] = $params['channel'];
        $postData['desc'] = $params['desc'];
        $postData['create_time'] = $postData['update_time'] = time();
        $res = BucketModel::create($postData);
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
        $params = $this->request->post();
        $model = BucketModel::find($params['id']);
        $model->name = $params['name'];
        $model->channel = $params['channel'];
        $model->desc = $params['desc'];
        $model->update_time = time();
        $res = $model->save();
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }

    /**
     * 获取商户列表
     * @return array
     * @throws \think\exception\DbException
     */
    public function getMerchantList() {
        $bucketId = $this->request->get('bucket_id',0);
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);

        $bucketInfo = \app\model\Bucket::find($bucketId);

        $listObj = Merchant::field('id,uid,nickname,mobile')
                           ->where('parent_id',0)
                           ->order('id', 'DESC')
                           ->paginate($limit, false, ['page' => $start])
                           ->toArray();


        //仅查相同通道编码的桶 ,互斥
        $usedBucketList = MerchantBucket::with(['bucketInfo'])->where('channel',$bucketInfo->channel)->select();

        $usedMerchantIdList = [];
        $inBucketArr = [];
        foreach($usedBucketList as $usedBucketInfo){
            $usedMerchantIdList[$usedBucketInfo->merchant_id] = $usedBucketInfo;

            if($usedBucketInfo->bucket_id == $bucketId){
                $inBucketArr[$usedBucketInfo->merchant_id] = $usedBucketInfo;
            }
        }

        foreach($listObj['data'] as &$item){
            $inBucket = 2;
            if( $inBucketArr && array_key_exists($item['id'],$inBucketArr) ){
                $inBucket = 1;
                unset($inBucketArr[$item['id']]);
            }
            $item['inBucket'] = $inBucket;

            $bucketInfo = '';
            if( $usedMerchantIdList && array_key_exists($item['id'],$usedMerchantIdList) ){
                $currentBucketInfo = $usedMerchantIdList[$item['id']]->bucket_info;
                $bucketInfo = $currentBucketInfo->name. '-'. $currentBucketInfo->desc;
                unset($usedMerchantIdList[$item['id']]);
            }
            $item['bucketInfo'] = $bucketInfo;
        }

        return $this->buildSuccess([
            'list'  => $listObj['data'],
            'count' => $listObj['total']
        ]);
    }

    /**
     * 获取代理列表
     * @return array
     * @throws \think\exception\DbException
     */
    public function getAgentList() {
        $bucketId = $this->request->get('bucket_id',0);
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);

        $bucketInfo = \app\model\Bucket::find($bucketId);

        $listObj = Agent::field('id,uid,nickname,mobile')
                        ->where('parent_id',0)
                        ->order('id', 'DESC')
                        ->paginate($limit, false, ['page' => $start])
                        ->toArray();


        //仅查相同通道编码的桶 ,互斥
        $usedBucketList = AgentBucket::with(['bucketInfo'])->where('channel',$bucketInfo->channel)->select();

        $usedAgentIdList = [];
        $inBucketArr = [];
        foreach($usedBucketList as $usedBucketInfo){
            $usedAgentIdList[$usedBucketInfo->agent_id] = $usedBucketInfo;

            if($usedBucketInfo->bucket_id == $bucketId){
                $inBucketArr[$usedBucketInfo->agent_id] = $usedBucketInfo;
            }
        }

        foreach($listObj['data'] as &$item){
            $inBucket = 2;
            if( $inBucketArr && array_key_exists($item['id'],$inBucketArr) ){
                $inBucket = 1;
                unset($inBucketArr[$item['id']]);
            }
            $item['inBucket'] = $inBucket;

            $bucketInfo = '';
            if( $usedAgentIdList && array_key_exists($item['id'],$usedAgentIdList) ){
                $currentBucketInfo = $usedAgentIdList[$item['id']]->bucket_info;
                $bucketInfo = $currentBucketInfo->name. '-'. $currentBucketInfo->desc;
                unset($usedAgentIdList[$item['id']]);
            }
            $item['bucketInfo'] = $bucketInfo;
        }

        return $this->buildSuccess([
            'list'  => $listObj['data'],
            'count' => $listObj['total']
        ]);
    }

    /**
     * 变更商家通道状态
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changeMerchantBucket() {
        $merchantId = $this->request->post('merchant_id');
        $bucketId = $this->request->post('bucket_id');
        $status = $this->request->post('status' ,1);
        $bucketInfo = \app\model\Bucket::find($bucketId);

        $lock = new Lock('redis');

        $lockKey = 'changeMerchantBucket:'.$merchantId.'_'.$bucketId;
        $lock->get($lockKey,15);

        Db::startTrans();
        try{
            //开启
            if($status == 1){
                MerchantBucket::where('merchant_id',$merchantId)->where('channel',$bucketInfo->channel)->delete();

                $now = time();
                $createData = [
                    'merchant_id' => $merchantId,
                    'bucket_id' => $bucketId,
                    'channel' => $bucketInfo->channel,
                    'create_time' => $now,
                    'update_time' => $now
                ];
                MerchantBucket::create($createData);

                Db::commit();
                $lock->release($lockKey);
                return $this->buildSuccess([]);
            }

            // 关闭
            $del = MerchantBucket::where('merchant_id',$merchantId)->where('bucket_id',$bucketId)->delete();
            if($del === false){
                abort(500,'操作失败');
            }

            Db::commit();
            $lock->release($lockKey);
            return $this->buildSuccess([]);

        }catch(\Exception $e){
            $lock->release($lockKey);
            Db::rollback();
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }
    }
    /**
     * 变更代理通道状态
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changeAgentBucket() {
        $agent = $this->request->post('agent_id');
        $bucketId = $this->request->post('bucket_id');
        $status = $this->request->post('status' ,1);
        $bucketInfo = \app\model\Bucket::find($bucketId);

        $lock = new Lock('redis');

        $lockKey = 'changeAgentBucket:'.$agent.'_'.$bucketId;
        $lock->get($lockKey,15);

        Db::startTrans();
        try{
            //开启
            if($status == 1){
                AgentBucket::where('agent_id',$agent)->where('channel',$bucketInfo->channel)->delete();

                $now = time();
                $createData = [
                    'agent_id' => $agent,
                    'bucket_id' => $bucketId,
                    'channel' => $bucketInfo->channel,
                    'create_time' => $now,
                    'update_time' => $now
                ];
                AgentBucket::create($createData);

                Db::commit();
                $lock->release($lockKey);
                return $this->buildSuccess([]);
            }

            // 关闭
            $del = AgentBucket::where('agent_id',$agent)->where('bucket_id',$bucketId)->delete();
            if($del === false){
                abort(500,'操作失败');
            }

            Db::commit();
            $lock->release($lockKey);
            return $this->buildSuccess([]);

        }catch(\Exception $e){
            $lock->release($lockKey);
            Db::rollback();
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $e->getMessage());
        }
    }

}