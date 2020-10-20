<?php
/**
 * @since   2017-11-02
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\model;

use think\Request;

class AgentBucket extends Base
{
    protected $name = 'agent_bucket';
    protected $pk = 'id';


    public function bucketInfo()
    {
        return $this->belongsTo('Bucket','bucket_id','id');
    }

    public function agentInfo()
    {
        return $this->belongsTo('Agent','agent_id','id');
    }
}
