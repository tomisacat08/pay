<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\admin\controller;

use app\model\MemberImages;
use app\util\ReturnCode;

class Image extends Base{

    /**
     * 图片列表
     * @return array
     * @author
     */
    public function index(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $type = $this->request->get('type','');
        $daterange = $this->request->get('daterange/a','');
        $where = [];
        if($type){
            $where['type'] = $type;
        }
        if($daterange){
            $listObj = (new MemberImages())->where($where)->order('create_time desc')
                ->whereTime('create_time','between',[strtotime($daterange[0]),strtotime($daterange[1])])
                ->paginate($limit, false, ['page' => $start])->toArray();
        }else{
            $listObj = (new MemberImages())->where($where)->order('create_time desc')
                ->paginate($limit, false, ['page' => $start])->toArray();
        }
        $listInfo = $listObj['data'];
        foreach($listInfo as $key => $val){
            $member = db('member')->field('mobile,nickname')->where(['id'=>$listInfo[$key]['member_id']])->find();
            $listInfo[$key]['member_id'] = $member['mobile'].'-'.$member['nickname'];
            $listInfo[$key]['wechat_id'] = db('member_wechat')->where(['id'=>$listInfo[$key]['wechat_id']])->value('title');
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total'],
        ]);
    }
    /**
     * 图片删除
     * @return array
     * @author
     */
    public function del(){
        $id = $this->request->post('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        $info = (new MemberImages())->field('id,img,system_img,type')->where(['id'=>$id])->find();
        if(file_exists('.'.$info['img'])){
            if(unlink('.'.$info['img'])){
                $curfile = '.'.pathinfo($info['img'])['dirname'];
                if(count(scandir($curfile))==2){
                    rmdir($curfile);
                }
            }
        }
        if(file_exists('.'.$info['system_img']) && $info['type']==1){
            if(unlink('.'.$info['system_img'])){
                $curfiles = '.'.pathinfo($info['system_img'])['dirname'];
                if(count(scandir($curfiles))==2){
                    rmdir($curfiles);
                }
            }
        }
        MemberImages::destroy(['id' => $id]);
        return $this->buildSuccess([]);
    }
    /**
     * 选择日期删除
     * @return array
     * @author
     */
    public function chooseDel(){
        $daterange = $this->request->post('daterange/a','');
        $type = $this->request->post('type','');
        if(!$daterange){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '请选择删除的日期');
        }
        if($type){
            $where['type'] = $type;
        }
        $where['is_auto_qrcode'] = 0;
        $data = (new MemberImages())
            ->field('id,img,system_img,type')
            ->where($where)
            ->order('create_time asc')
            ->whereTime('create_time','between',[strtotime($daterange[0]),strtotime($daterange[1])])
            ->select();
        $img_num = 0;
        $system_num = 0;
        foreach ($data as $key =>$val){
            if(file_exists('.'.$val['img'])){
                if(unlink('.'.$val['img'])){
                    $curfile = '.'.pathinfo($val['img'])['dirname'];
                    if(count(scandir($curfile))==2){
                        rmdir($curfile);
                    }
                    $img_num++;
                }
            }

            if(file_exists('.'.$val['system_img']) && $val['type']==1){
                if(unlink('.'.$val['system_img'])){
                    $curfiles = '.'.pathinfo($val['system_img'])['dirname'];
                    if(count(scandir($curfiles))==2){
                        rmdir($curfiles);
                    }
                    $system_num++;
                }
            }
            MemberImages::destroy(['id' => $val['id']]);
        }
        return $this->buildSuccess([]);

    }
}
