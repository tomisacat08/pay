
# 商户接入文档



###  公共部分说明:
> POST请求说明
* POST,请设置请求头 Content-Type: application/json 或者 multipart/form-data 或者 Content-Type:application/x-www-form-urlencoded;

>  签名生成规则
#### 注意: 返回格式code,msg,data为约定json数据结构,异步通知回调地址签名,只签data内的数据(除sign)
*  1.排序:将除sign外所有参数 根据键名ASCII从小到大排序 (非必传参数键值为空可不加入签名)
*  2.拼接字符串 : 键名1=键值1&键名2=键值2... &apikey=商户apikey
*  3.生成待加密字符串 : 转换大写(拼接字符串)
*  4.md5加密获得sign:  md5(待加密字符串)


### 下单网关

> 地址: {{HOST}}/payapi/Index/order


| 参数          |   POST        | 是否必填|说明
| ------------- |:-------------:|:-------------:|:-------------:
| merchant_order_uid            | int     |  必填  |    商户UID
| merchant_order_sn             | string     |  必填  |    商品订单号
| merchant_order_money        | decimal(11,2) | 必填  |支付金额 (单位：元)
| merchant_order_channel        | string | 必填  |支付渠道 ,见下表
| merchant_order_date          | datetime   |   必填  | 提交时间 例: 2016-12-26 18:18:18
| merchant_order_sign          | string     |  必填  |    MD5签名(见公共签名规则)
| merchant_order_callbak_confirm_duein | string     |  必填  | 回调网关,收款成功回调通知地址,具体用法看接口文档说明

| 返回          |     类型      |说明
| ------------- |:-------------:|:-------------:
| url           | string        | 支付页面-跳转地址

| 支付渠道          |     编码      |说明
| ------------- |:-------------:|:-------------:
| 支付宝扫码 |      alipay_qrcode   | 可用,详细联系客服
| 支付宝转账 |      alipay_account   | 可用,详细联系客服
| 支付宝转卡 |      alipay_card   | 可用,详细联系客服
| 微信扫码 |      wechat_qrcode   | 维护中
| 微信转账 |      wechat_account   | 维护中
| 微信转卡 |      wechat_card   | 维护中

* 返回

```json

{
    "code": 200,
    "msg": "提交成功",
    "data": {
        "url": "http://pay.demo.com/payapi/Index/index?id=UCJerYaI7WJkL7_ra93W5q2&token=LLTw3KuvH7A24AqQXrfuX1auzyIr9l4uFfL7gf2aLiHOFg3BFdLLU9c1K-vTY4_"
    }
}
```


### 查询网关

> 地址: {{HOST}}/payapi/Index/select


| 参数          |   POST        | 是否必填|说明
| ------------- |:-------------:|:-------------:|:-------------:
| merchant_order_uid            | int     |  必填  |    商户UID
| merchant_order_sn             | string     |  必填  |    商户订单号
| merchant_order_sign          | string     |  必填  |    MD5签名(见公共签名规则)

| 返回          |     类型      |说明
| ------------- |:-------------:|:-------------:
| pay_status           | int        | 1:已收款  2:未收款 

* 返回

```json

{
    "code": "200",
    "msg": "订单创建失败,支付页面未开启或开启已超时",
    "data": {
        "pay_status": "2"
    }
}
```


---
# 商家设置异步通知回调地址说明:


### 回调IP:(请求IP白名单)
{{IP}}

### 收款成功回调
    merchant_order_callbak_confirm_duein
系统收款后,我们会向设置的回调地址发送数据
> 参数json

| 返回          | POST|说明
| ------------- |:-------------:|:-------------:
| code          | int           |状态码:200确认收款成功
| msg           | string        |提示信息: 确认收款成功 
|order_id       | int           |平台订单ID
|order_money    | float         |实际支付金额
|merchant_order_sn| string      |商家订单号
|sign           | string        |签名md5(规则见公共说明)


> 发送

```
{
"code":"200",
"msg":"确认收款成功",
    "data":{
        "order_id":250,
        "order_money":500.00,
        "merchant_order_sn":"P20190513100515556",
        "sign": 69523304cc9033cad4b09c46e3bea95e
    }
}
```

> 期望返回:json字符串,success,SUCCESS 如不按期望返回,会重复发送通知

```
{
    "code": "200",
    "msg": "提交成功"
}
或者 
SUCCESS
或者
success
```


### 查询余额

> 地址: {{HOST}}/payapi/Withdraw/getBalance


| 参数          |   POST        | 是否必填|说明
| ------------- |:-------------:|:-------------:|:-------------:
| uid            | int     |  必填  |    商户UID
| sign          | string     |  必填  |    MD5签名(见公共签名规则)

| 返回          |     类型      |说明
| ------------- |:-------------:|:-------------:
| json           | string        | 

* 返回

```json
{
    "code": 1,
    "msg": "查询成功!",
    "data": {
        "balance": "100000.00",
        "date": "2019-11-13 15:43:30"
    }
}
```


### 下发API

> 地址: {{HOST}}/payapi/Withdraw/withdrawAudit


| 参数          |   POST        | 是否必填|说明
| ------------- |:-------------:|:-------------:|:-------------:
| uid            | int     |  必填  |    商户UID
| sn             | string     |  必填  |    下发单号
| money        | decimal(11,2) | 必填  |  下发金额 (单位：元)
| callback      | string     |  必填  | 回调通知地址 url http://xxx.xxx.com
| remark      | string     |  选填  | 下发备注
| bank_name      | string     |  必填  | 银行名称
| bank_address      | string     |  必填  | 开户行地址
| card      | string     |  必填  | 银行卡号
| name      | string     |  必填  | 开户名
| sign          | string     |  必填  |    MD5签名(见公共签名规则)

| 返回          |     类型      |说明
| ------------- |:-------------:|:-------------:
| json           | string        | 

* 返回

```json

{
    "code": 1,
    "msg": "提交成功"
}
```

### 下发打款成功回调
    callback
下发成功后,我们会向设置的回调地址发送数据
> 参数json

| 返回          | POST|说明
| ------------- |:-------------:|:-------------:
| code          | int           |状态码:200确认收款成功
| msg           | string        |提示信息: 确认收款成功 
|money          | float         |下发金额
|sn             | string        |商家下发订单号
|sign           | string        |签名md5(规则见公共说明)


> 发送

```
{
"code":"200",
"msg":"打款成功!",
    "data":{
        "money":50000.00,
        "sn":"P20190513100515556",
        "sign": 69523304cc9033cad4b09c46e3bea95e
    }
}
```

> 期望返回:json字符串,success,SUCCESS 如不按期望返回,会重复发送通知

```
{
    "code": "200",
    "msg": "提交成功"
}
或者 
SUCCESS
或者
success
```


### PHP版对接DEMO
```php
class demo{

    public $merchant = ['uid'=>'商户UID','apikey'=>'商户秘钥'];
       
    public function getSign($data,$salt)
    {
        //获取商户apikey
        if(empty($salt)){
            abort(500,'获取商户秘钥失败!');
        }

        ksort( $data );
        $str = '';
        foreach($data as $key=>$value){
            $str .= $key.'='.$value.'&';
        }
        $md5str = strtoupper($str . "apikey=" . $salt);
        //签名验证，查询数据是否被篡改
        return md5($md5str);
    }


    public function curl_post_json($url, array $params = array(), $timeout = 200,$header = [])
    {

        $data_string = json_encode($params);

        $originHeader = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ];

        $header = array_merge($originHeader,$header);

        $ch = curl_init();//初始化
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
    }

    public function curl_post($url, array $params = array(), $timeout)
    {
        $ch = curl_init();//初始化
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return ($data);
    }


    public function addOrder()
    {
        //必填
        $postData['merchant_order_uid'] = $this->merchant;//订单金额
        $postData['merchant_order_money'] = 25;//订单金额
        $postData['merchant_order_sn'] = '订单号';//商户订单号
        $postData['merchant_order_channel'] = 'alipay_qrcode';//渠道编号
        $postData['merchant_order_date'] = date('Y-m-d H:i:s',time());//下单时间
        $postData['merchant_order_callbak_confirm_duein'] = 'http:\\www.确认收款回调.com';
        
        //非必填项,  无需求可不设置
        $postData['merchant_order_callbak_redirect'] = 'http:\\www.确认收款后页面跳转地址.com';
        $postData['merchant_order_name'] = '测试订单';
        $postData['merchant_order_count'] = 1;
        $postData['merchant_order_desc'] = '测试订单简介';
        $postData['merchant_order_callbak_confirm_create'] = 'http:\\www.下单成功通知回调.com';

        
        //签名验证，查询数据是否被篡改
        $apikey = $this->merchant['apikey'];
        
        $sign = $this->getSign($postData,$apikey);

        //必填
        $postData["merchant_order_sign"] = $sign;//md5签名
  

        $host = '服务器地址.com';
        $createOrder = '网关地址';

        
        $url = $host.$createOrder;
        $data = $this->curl_post_json($url,$postData,200);

        //显示获得的数据
        return response($data);
    }
    
    
    //接单回调
    public function callbak()
    {
        $post = $_POST;
        $data = $post['data'];
        $sign = $data['sign'];
        unset($data['sign']);
        $checkSign = $this->getSign($data,$this->merchant['apikey']);
        
        if($sign != $checkSign){
            $return = ['code'=>500,'msg'=>'failed','data'=>['params'=>'签名错误!']];
            echo json_encode($return);
            return false;
        }
        
        $return = ['code'=>200,'msg'=>'success','data'=>[]];
        //签名通过, 处理自己逻辑
        try{
            
        }catch(Exception $e){
            $return = ['code'=>500,'msg'=>'failed','data'=>['params'=>'{任意参数,供查问题}']];
            echo json_encode($return);
            return false;
        }
        
       
        //返回
        $return = ['code'=>200,'msg'=>'success','data'=>[]];
        echo json_encode($return);
    }
    
}
```

###java版对接Demo
```java
package com.demo;

import com.alibaba.fastjson.JSON;
import com.alibaba.fastjson.JSONObject;

import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.*;

/**
 * 统一下单demo
 */
public class PayOrderDemo {
    static final String merchantOrderUid = "商户ID";
    static final String merchantOrderChannel = "支付渠道";
    static final String key = "私钥key";
    static final String payUrl = "请求地址";
    static final String merchantOrderCallbakConfirmDuein = "回调地址，本地环境测试,可到ngrok.cc网站注册";

    public static void main(String[] args) {
        payOrderTest();

    }

    // 统一下单
    static String payOrderTest() {
        JSONObject paramMap = new JSONObject();
        paramMap.put("merchant_order_uid", merchantOrderUid);               // 商户ID
		paramMap.put("merchant_order_sn", "商户订单号,唯一ID");     		// 商户订单号
        paramMap.put("merchant_order_money", 100);                       	// 币种, cny-人民币，单位：元
        paramMap.put("merchant_order_channel", merchantOrderChannel);   	// 支付渠道
        paramMap.put("merchant_order_date", date2Str(new Date(),"yyyy-MM-dd HH:mm:ss")); // 提交时间：格式：yyyy-MM-dd HH:mm:ss
		paramMap.put("merchant_order_callbak_confirm_duein", merchantOrderCallbakConfirmDuein);    // 回调URL
     
        String signParam = getSortParam(paramMap);
        signParam = signParam + "apikey=" + key;
        signParam = signParam.toUpperCase(); // 转大写
		String signValue = MD5Util.string2MD5(signParam);	
        paramMap.put("sign", signValue);                              // 签名
        String reqData = genUrlParams(paramMap);
        System.out.println("请求支付中心下单接口,请求数据:" + reqData);
        String result = HttpClient.Post(payUrl + "?" + reqData);
        System.out.println("请求支付中心下单接口,响应数据:" + result);
        JSONObject resObj = JSONObject.parseObject(result);
        Integer code = resObj.getInteger("code");
        if(200 == code) {// 下单成功
             resObj = resObj.getJSONObject("data");
             String url = resObj.getString("url");     // 支付URL
        }else {
			//下单失败
		}
        return retMap.get("payOrderId")+"";
    }




	/**
     * 参数排序
     * @param map
     * @return
     */
	public static String getSortParam(Map<String,Object> map){
		ArrayList<String> list = new ArrayList<String>();
		for(Map.Entry<String,Object> entry:map.entrySet()){
			if(null != entry.getValue() && !"".equals(entry.getValue())){
				if(entry.getValue() instanceof JSONObject) {
					list.add(entry.getKey() + "=" + getSortJson((JSONObject) entry.getValue()) + "&");
				}else {
					list.add(entry.getKey() + "=" + entry.getValue() + "&");
				}
			}
		}
		int size = list.size();
		String [] arrayToSort = list.toArray(new String[size]);
		Arrays.sort(arrayToSort, String.CASE_INSENSITIVE_ORDER);
		StringBuilder sb = new StringBuilder();
		for(int i = 0; i < size; i ++) {
			sb.append(arrayToSort[i]);
		}
		String result = sb.toString();
		return result;
	}

    /**
     *
     * @param obj
     * @return
     */
    private static String getSortJson(JSONObject obj){
        SortedMap map = new TreeMap();
        Set<String> keySet = obj.keySet();
        Iterator<String> it = keySet.iterator();
        while (it.hasNext()) {
            String key = it.next().toString();
            Object vlaue = obj.get(key);
            map.put(key, vlaue);
        }
        return JSONObject.toJSONString(map);
    }

    /**
     * MD5
     * @param value
     * @param charset
     * @return
     */
    public static String md5(String value, String charset) {
        MessageDigest md = null;
        try {
            byte[] data = value.getBytes(charset);
            md = MessageDigest.getInstance("MD5");
            byte[] digestData = md.digest(data);
            return toHex(digestData);
        } catch (NoSuchAlgorithmException e) {
            e.printStackTrace();
            return null;
        } catch (UnsupportedEncodingException e) {
            e.printStackTrace();
            return null;
        }
    }

    public static String toHex(byte input[]) {
        if (input == null)
            return null;
        StringBuffer output = new StringBuffer(input.length * 2);
        for (int i = 0; i < input.length; i++) {
            int current = input[i] & 0xff;
            if (current < 16)
                output.append("0");
            output.append(Integer.toString(current, 16));
        }

        return output.toString();
    }

    public static String genUrlParams(Map<String, Object> paraMap) {
        if(paraMap == null || paraMap.isEmpty()) return "";
        StringBuffer urlParam = new StringBuffer();
        Set<String> keySet = paraMap.keySet();
        int i = 0;
        for(String key:keySet) {
            urlParam.append(key).append("=");
            if(paraMap.get(key) instanceof String) {
                urlParam.append(URLEncoder.encode((String) paraMap.get(key)));
            }else {
                urlParam.append(paraMap.get(key));
            }
            if(++i == keySet.size()) break;
            urlParam.append("&");
        }
        return urlParam.toString();
    }
	
	/**
	 * 时间转换成 Date 类型
	 *
	 * @param date
	 * @param format
	 * @return
	 */
	public static String date2Str(Date date, String format) {
		SimpleDateFormat sdf = new SimpleDateFormat(format);
		if (date != null) {
			return sdf.format(date);
		}
		return "";
	}
}
```