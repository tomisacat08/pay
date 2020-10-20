<?php
/**
 * @since   2017-11-02
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\model;


class MerchantWithdrawAudit extends Base
{
    protected $name = 'merchant_withdraw_audit';
    protected $pk = 'id';
    public function audit_status($type){
        $arr = [1=>'申请中',2=>'打款中',3=>'结算成功',4=>'驳回申请'];
        return $arr[$type];
    }
}
