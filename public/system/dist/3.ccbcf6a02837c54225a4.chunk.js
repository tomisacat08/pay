webpackJsonp([3],{242:function(t,e,a){"use strict";function n(t){c||a(385)}Object.defineProperty(e,"__esModule",{value:!0});var i=a(284),s=a.n(i);for(var o in i)"default"!==o&&function(t){a.d(e,t,function(){return i[t]})}(o);var r=a(388),l=(a.n(r),a(4)),c=!1,d=n,m=Object(l.a)(s.a,r.render,r.staticRenderFns,!1,d,"data-v-74dec41a",null);m.options.__file="src\\views\\merchant\\merchantdetails.vue",e.default=m.exports},284:function(t,e,a){"use strict";function n(t){return t&&t.__esModule?t:{default:t}}Object.defineProperty(e,"__esModule",{value:!0});var i=a(91),s=(n(i),a(32)),o=n(s),r=function(t,e,a,n){return e("Button",{props:{type:"primary"},style:{margin:"0 5px"},on:{click:function(){t.formItem.id=a.id,t.showMsg.no_pay=a.money,t.changeMoney=a.money,t.showMsg.withdraw_sn=a.withdraw_sn,t.showMsg.merchant_uid=a.merchant_uid,t.formItem.tableInfo="",t.changeAgentData=[],t.modalSetting.show=!0,t.modalSetting.index=n}}},"手动分配")},l=function(t,e,a,n){return e("Poptip",{props:{confirm:!0,title:"您确定要自动转账吗?",transfer:!0},on:{"on-ok":function(){o.default.get("MerchantWithdrawAudit/autoAllot",{params:{id:a.id}}).then(function(e){a.loading=!1,1==e.data.code?(t.$Message.success(e.data.msg),t.getList()):t.$Message.error(e.data.msg)})}}},[e("Button",{style:{margin:"0 5px"},props:{type:"success",placement:"top",loading:a.isDeleting}},"自动分配")])},c=function(t,e,a,n){return e("Button",{props:{type:"info"},style:{margin:"0 5px"},on:{click:function(){t.memberSetting.show=!0,t.memberShow.id=a.id,t.getMemberList()}}},"查看")},d=function(t,e,a,n){return e("Button",{props:{type:"error"},style:{margin:"0 5px"},on:{click:function(){t.nopassItem.id=a.id,t.nopassItem.remark=a.remark,""==t.nopassItem.remark?(t.nopassSetting.show=!0,t.nopassSetting.index=n):t.$Message.error("请勿重复提交")}}},"驳回")};e.default={name:"interface_group",data:function(){return{uploadUrl:"",uploadHeader:{},cityList:[],changeAgentData:[],changeMoney:"",beforeChangeMoney:"",changeAgentList:[],columnsList:[{title:"提现编号",align:"center",key:"id"},{title:"商户编号",align:"center",key:"merchant_uid"},{title:"提现金额",align:"center",key:"money"},{title:"开户信息",align:"center",key:"bank_card"},{title:"创建时间",align:"center",key:"create_time"},{title:"备注",align:"center",key:"remark"},{title:"状态",align:"center",key:"type"},{title:"操作",align:"center",key:"handle",width:300,handle:["edit","autochange","query","delete"]}],tableData:[],tableShow:{currentPage:1,pageSize:10,listCount:0},searchConf:{merchant_uid:"",withdraw_sn:"",type:""},modalSetting:{show:!1,loading:!1,index:0},nopassSetting:{show:!1,loading:!1,index:0},nopassItem:{remark:"",id:0},formItem:{tableInfo:"",id:0},memberColumns1:[{title:"序号",type:"index",width:65,align:"center"},{title:"代理商",align:"center",key:"agent_id"},{title:"付款人",align:"center",key:"type"},{title:"支付凭证",align:"center",key:"pic"},{title:"结算金额",align:"center",key:"money"},{title:"创建时间",align:"center",key:"create_time"},{title:"支付时间",align:"center",key:"pay_time"},{title:"支付状态",align:"center",key:"status"}],memberColumns:[{title:"序号",type:"index",width:65,align:"center"},{title:"代理商",align:"center",key:"agent_id"},{title:"结算单号",align:"center",key:"settlement_sn"},{title:"结算金额",align:"center",key:"settlement_money"},{title:"付款人",align:"center",key:"type"},{title:"创建时间",align:"center",key:"create_time"},{title:"支付状态",align:"center",key:"status"}],memberData:[],memberData1:[],memberShow:{currentPage:1,pageSize:10,listCount:0,id:0},showMsg:{no_pay:"",merchant_uid:"",withdraw_sn:""},memberSetting:{show:!1,loading:!1,index:0},imgUrl:"",visible:!1,ruleValidate:{}}},created:function(){this.init(),this.getList(),this.changeInfo()},methods:{handleAgent:function(t){var e=this;e.changeAgentData=[],t&&t.forEach(function(t){var a=JSON.parse(t.split(","));e.changeAgentData.push(a);for(var n=e.changeAgentData.push(a),i={},s=[],o=0;o<n;o++)i[e.changeAgentData[o].id]||(s.push(e.changeAgentData[o]),i[e.changeAgentData[o].id]=!0);e.changeAgentData=s})},handleView:function(t){this.imgUrl=t,this.visible=!0},init:function(){var t=this,e=this;e.changeAgentList=[{title:"ID",align:"center",key:"uid",width:80},{title:"代理商名称",align:"center",key:"nickname",width:150},{title:"未结算总额",align:"center",key:"settlement_money",width:150},{title:"输入金额",align:"center",key:"value",render:function(t,a){return t("Input",{props:{type:"text",value:e.changeAgentData[a.index].value},on:{"on-blur":function(t){if(t.target.value>=0&&t.target.value<=Number(a.row.settlement_money)){e.changeAgentData[a.index].value=t.target.value;var n=0;n=""===e.beforeChangeMoney?0:e.beforeChangeMoney,e.changeMoney=e.changeMoney+n-Number(t.target.value)}else e.$Message.error("您输入的金额不正确，请重新输入")},"on-focus":function(t){t.target.value>=0&&t.target.value<=Number(a.row.settlement_money)&&(e.beforeChangeMoney=Number(t.target.value))}}})}}],this.columnsList.forEach(function(n){n.handle&&(n.render=function(t,a){var n=e.tableData[a.index];return 1===n.status?t("div",[r(e,t,n,a.index),l(e,t,n,a.index),d(e,t,n,a.index)]):t("div",[c(e,t,n,a.index),d(e,t,n,a.index)])}),t.memberColumns1.forEach(function(n){"pic"===n.key&&(n.render=function(n,i){var s=e.memberData1[i.index];return n("div",[n("img",{attrs:{src:s.pic?s.pic:a(387)},style:{width:"80px",height:"80px",borderRadius:"2px",paddingTop:"5px"},on:{click:function(e){t.handleView(e.srcElement.currentSrc)}}})])})})})},submit:function(){var t=this;t.modalSetting.loading=!0,o.default.post("MerchantWithdrawAudit/manualAllot",{dataList:t.changeAgentData,id:t.formItem.id}).then(function(e){1===e.data.code?(t.$Message.success(e.data.msg),t.cancel()):(t.$Message.error(e.data.msg),t.modalSetting.loading=!1),t.getList()})},submitnopass:function(){var t=this;this.$refs.myForm1.validate(function(e){e&&(t.nopassSetting.loading=!0,o.default.get("MerchantWithdrawAudit/notPass",{params:{remark:t.nopassItem.remark,id:t.nopassItem.id}}).then(function(e){1===e.data.code?(t.$Message.success(e.data.msg),t.cancel()):(t.$Message.error(e.data.msg),t.nopassSetting.loading=!1),t.getList()}))})},cancel:function(){this.modalSetting.show=!1,this.nopassSetting.show=!1},changePage:function(t){this.tableShow.currentPage=t,this.getList()},changeSize:function(t){this.tableShow.pageSize=t,this.getList()},changeMemberPage:function(t){this.memberShow.currentPage=t,this.getMemberList()},changeMemberSize:function(t){this.memberShow.pageSize=t,this.getMemberList()},search:function(){this.tableShow.currentPage=1,this.getList()},getMemberList:function(){var t=this;o.default.get("MerchantWithdrawAudit/viewDetails",{params:{page:t.memberShow.currentPage,size:t.memberShow.pageSize,id:t.memberShow.id}}).then(function(e){var a=e.data;1===a.code?(t.memberData=a.data.listWithdraw,t.memberData1=a.data.list,t.memberShow.listCount=a.data.count):-14===a.code?(t.$store.commit("logout",t),t.$router.push({name:"login"})):t.$Message.error(a.msg)})},getList:function(){var t=this;o.default.get("MerchantWithdrawAudit/index",{params:{page:t.tableShow.currentPage,size:t.tableShow.pageSize,withdraw_sn:t.searchConf.withdraw_sn,merchant_uid:t.searchConf.merchant_uid,type:t.searchConf.type}}).then(function(e){var a=e.data;1===a.code?(t.tableData=a.data.list,t.tableShow.listCount=a.data.count):-14===a.code?(t.$store.commit("logout",t),t.$router.push({name:"login"})):t.$Message.error(a.msg)})},changeInfo:function(){var t=this;o.default.get("MerchantWithdrawAudit/manualInfo").then(function(e){var a=e.data;1===a.code?t.cityList=a.data:-14===a.code?(t.$store.commit("logout",t),t.$router.push({name:"login"})):t.$Message.error(a.msg)})},doCancel:function(t){t||(this.$refs.myForm1.resetFields(),this.modalSetting.loading=!1,this.modalSetting.index=0,this.$refs.myForm1.resetFields(),this.nopassSetting.loading=!1,this.nopassSetting.index=0)}}}},385:function(t,e,a){var n=a(386);"string"==typeof n&&(n=[[t.i,n,""]]),n.locals&&(t.exports=n.locals);var i=a(15).default;i("f4f6bb06",n,!1,{})},386:function(t,e,a){e=t.exports=a(14)(!1),e.push([t.i,"\n.api-box[data-v-74dec41a] {\n  max-height: 300px;\n  overflow: auto;\n  border: 1px solid #dddee1;\n  border-radius: 5px;\n  padding: 10px;\n}\n.demo-upload-list[data-v-74dec41a] {\n  display: inline-block;\n  width: 60px;\n  height: 60px;\n  text-align: center;\n  line-height: 60px;\n  border: 1px solid transparent;\n  border-radius: 4px;\n  overflow: hidden;\n  background: #fff;\n  position: relative;\n  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);\n  margin-right: 4px;\n}\n.demo-upload-list img[data-v-74dec41a] {\n  width: 100%;\n  height: 100%;\n}\n.demo-upload-list-cover[data-v-74dec41a] {\n  display: none;\n  position: absolute;\n  top: 0;\n  bottom: 0;\n  left: 0;\n  right: 0;\n  background: rgba(0, 0, 0, 0.6);\n}\n.demo-upload-list:hover .demo-upload-list-cover[data-v-74dec41a] {\n  display: block;\n}\n.demo-upload-list-cover i[data-v-74dec41a] {\n  color: #fff;\n  font-size: 20px;\n  cursor: pointer;\n  margin: 0 2px;\n}\n.titleTips[data-v-74dec41a] {\n  width: 100%;\n}\n.titleTips .info[data-v-74dec41a] {\n  color: #666;\n  font-size: 14px;\n}\n.titleTips .info .infoList[data-v-74dec41a] {\n  padding: 0 10px;\n}\n.titleTips .info .infoList .status[data-v-74dec41a] {\n  color: #2d8cf0;\n  font-size: 18px;\n  padding: 0 2px;\n}\n",""])},387:function(t,e,a){t.exports=a.p+"33d7315a39c1294f6b06844e2f3b084d.png"},388:function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.staticRenderFns=e.render=void 0;var n=a(33),i=function(t){return t&&t.__esModule?t:{default:t}}(n),s=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",[a("Row",[a("Col",{attrs:{span:"24"}},[a("Card",{staticStyle:{"margin-bottom":"10px"}},[a("Form",{attrs:{inline:""}},[a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Select",{staticStyle:{width:"100px"},attrs:{clearable:"",placeholder:"请选择状态"},model:{value:t.searchConf.type,callback:function(e){t.$set(t.searchConf,"type",e)},expression:"searchConf.type"}},[a("Option",{attrs:{value:1}},[t._v("申请中")]),t._v(" "),a("Option",{attrs:{value:2}},[t._v("打款中")]),t._v(" "),a("Option",{attrs:{value:3}},[t._v("结算成功")]),t._v(" "),a("Option",{attrs:{value:4}},[t._v("拒绝申请")])],1)],1),t._v(" "),a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Input",{attrs:{placeholder:"请输入提现编号"},model:{value:t.searchConf.withdraw_sn,callback:function(e){t.$set(t.searchConf,"withdraw_sn",e)},expression:"searchConf.withdraw_sn"}})],1),t._v(" "),a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Input",{attrs:{placeholder:"请输入商户编号"},model:{value:t.searchConf.merchant_uid,callback:function(e){t.$set(t.searchConf,"merchant_uid",e)},expression:"searchConf.merchant_uid"}})],1),t._v(" "),a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Button",{attrs:{type:"primary"},on:{click:t.search}},[t._v("查询/刷新")])],1)],1)],1)],1)],1),t._v(" "),a("Row",[a("Col",{attrs:{span:"24"}},[a("Card",[a("div",[a("Table",{attrs:{columns:t.columnsList,data:t.tableData,border:"","disabled-hover":""}})],1),t._v(" "),a("div",{staticClass:"margin-top-15",staticStyle:{"text-align":"center"}},[a("Page",{attrs:{total:t.tableShow.listCount,current:t.tableShow.currentPage,"page-size":t.tableShow.pageSize,"show-elevator":"","show-sizer":"","show-total":""},on:{"on-change":t.changePage,"on-page-size-change":t.changeSize}})],1)])],1)],1),t._v(" "),a("Modal",{attrs:{width:"668",styles:{top:"30px"}},on:{"on-visible-change":t.doCancel},model:{value:t.modalSetting.show,callback:function(e){t.$set(t.modalSetting,"show",e)},expression:"modalSetting.show"}},[a("p",{staticStyle:{color:"#2d8cf0"},attrs:{slot:"header"},slot:"header"},[a("Icon",{attrs:{type:"md-information-circle"}}),t._v(" "),a("span",[t._v("提现方式")])],1),t._v(" "),a("Form",{ref:"myForm",attrs:{rules:t.ruleValidate,model:t.formItem,"label-width":80}},[a("Col",{style:{marginBottom:"20px"},attrs:{md:24,lg:24}},[a("Card",[a("Row",{staticClass:"user-infor",attrs:{type:"flex"}},[a("Col",{attrs:{span:"24"}},[a("Row",{attrs:{"class-name":"made-child-con-middle",type:"flex",align:"middle"}},[a("div",{staticClass:"titleTips"},[a("p",{staticClass:"info"},[a("span",{staticClass:"infoList"},[t._v("提现编号："),a("span",{staticClass:"status"},[t._v(t._s(t.showMsg.withdraw_sn))])]),t._v(" "),a("span",{staticClass:"infoList"},[t._v("商户编号："),a("span",{staticClass:"status"},[t._v(t._s(t.showMsg.merchant_uid))])]),a("br"),t._v(" "),a("span",{staticClass:"infoList"},[t._v("结算金额："),a("span",{staticClass:"status"},[t._v(t._s(t.showMsg.no_pay))]),t._v("元")]),t._v(" "),a("span",{staticClass:"infoList"},[t._v("剩余可提现金额："),a("span",{staticClass:"status"},[t._v(t._s(t.changeMoney))]),t._v("元")])])])])],1)],1)],1)],1),t._v(" "),a("FormItem",{attrs:{label:"所选代理商",prop:"tableInfo"}},[a("i-select",{attrs:{placeholder:"请选代理商",multiple:""},on:{"on-change":t.handleAgent},model:{value:t.formItem.tableInfo,callback:function(e){t.$set(t.formItem,"tableInfo",e)},expression:"formItem.tableInfo"}},t._l(t.cityList,function(e){return a("Option",{key:e.id,attrs:{value:(0,i.default)(e)}},[t._v(t._s(e.nickname))])})),t._v(" "),a("div",{staticClass:"margin-top-15"},[a("Table",{attrs:{columns:t.changeAgentList,data:t.changeAgentData,border:"","disabled-hover":""}})],1)],1)],1),t._v(" "),a("div",{attrs:{slot:"footer"},slot:"footer"},[a("Button",{staticStyle:{"margin-right":"8px"},attrs:{type:"text"},on:{click:t.cancel}},[t._v("取消")]),t._v(" "),a("Button",{attrs:{type:"primary",loading:t.modalSetting.loading},on:{click:t.submit}},[t._v("确定")])],1)],1),t._v(" "),a("Modal",{attrs:{width:"668",styles:{top:"30px"}},on:{"on-visible-change":t.doCancel},model:{value:t.nopassSetting.show,callback:function(e){t.$set(t.nopassSetting,"show",e)},expression:"nopassSetting.show"}},[a("p",{staticStyle:{color:"#2d8cf0"},attrs:{slot:"header"},slot:"header"},[a("Icon",{attrs:{type:"md-information-circle"}}),t._v(" "),a("span",[t._v("驳回原因")])],1),t._v(" "),a("Form",{ref:"myForm1",attrs:{rules:t.ruleValidate,model:t.nopassItem,"label-width":80}},[a("FormItem",{attrs:{label:"驳回描述",prop:"remark"}},[a("Input",{attrs:{type:"textarea",autosize:{maxRows:10,minRows:4},placeholder:"请输入驳回描述"},model:{value:t.nopassItem.remark,callback:function(e){t.$set(t.nopassItem,"remark",e)},expression:"nopassItem.remark"}})],1)],1),t._v(" "),a("div",{attrs:{slot:"footer"},slot:"footer"},[a("Button",{staticStyle:{"margin-right":"8px"},attrs:{type:"text"},on:{click:t.cancel}},[t._v("取消")]),t._v(" "),a("Button",{attrs:{type:"primary",loading:t.nopassSetting.loading},on:{click:t.submitnopass}},[t._v("确定")])],1)],1),t._v(" "),a("Modal",{attrs:{width:"80%",styles:{top:"30px"}},model:{value:t.memberSetting.show,callback:function(e){t.$set(t.memberSetting,"show",e)},expression:"memberSetting.show"}},[a("p",{staticStyle:{color:"#2d8cf0"},attrs:{slot:"header"},slot:"header"},[a("Icon",{attrs:{type:"md-information-circle"}}),t._v(" "),a("span",[t._v("提现信息")])],1),t._v(" "),a("Tabs",{attrs:{type:"card"}},[a("Tab-pane",{attrs:{label:"平台的分配"}},[a("Table",{attrs:{columns:t.memberColumns,data:t.memberData,border:"","disabled-hover":""}})],1),t._v(" "),a("Tab-pane",{attrs:{label:"代理商的分配"}},[a("Table",{attrs:{columns:t.memberColumns1,data:t.memberData1,border:"","disabled-hover":""}})],1)],1),t._v(" "),a("div",{staticClass:"margin-top-15",staticStyle:{"text-align":"center"}},[a("Page",{attrs:{total:t.memberShow.listCount,current:t.memberShow.currentPage,"page-size":t.memberShow.pageSize,"show-elevator":"","show-sizer":"","show-total":""},on:{"on-change":t.changeMemberPage,"on-page-size-change":t.changeMemberSize}})],1),t._v(" "),a("p",{attrs:{slot:"footer"},slot:"footer"})],1),t._v(" "),a("Modal",{attrs:{title:"查看大图","class-name":"fl-image-modal"},model:{value:t.visible,callback:function(e){t.visible=e},expression:"visible"}},[t.visible?a("img",{staticStyle:{width:"100%"},attrs:{src:t.imgUrl}}):t._e()])],1)},o=[];s._withStripped=!0,e.render=s,e.staticRenderFns=o}});