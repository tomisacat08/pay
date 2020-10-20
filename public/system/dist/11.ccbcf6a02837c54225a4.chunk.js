webpackJsonp([11],{234:function(t,e,n){"use strict";function a(t){l||n(356)}Object.defineProperty(e,"__esModule",{value:!0});var o=n(275),i=n.n(o);for(var r in o)"default"!==r&&function(t){n.d(e,t,function(){return o[t]})}(r);var s=n(358),c=(n.n(s),n(4)),l=!1,g=a,h=Object(c.a)(i.a,s.render,s.staticRenderFns,!1,g,"data-v-178311b3",null);h.options.__file="src\\views\\system\\bucket.vue",e.default=h.exports},275:function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var a=n(32),o=function(t){return t&&t.__esModule?t:{default:t}}(a),i=function(t,e,n,a){return e("Button",{props:{type:"primary"},style:{margin:"0 5px"},on:{click:function(){t.formItem.id=n.id,t.formItem.name=n.name,t.formItem.channel=n.channel,t.formItem.desc=n.desc,t.modalSetting.show=!0,t.modalSetting.index=a}}},"编辑")},r=function(t,e,n,a){return e("Button",{props:{type:"primary"},style:{margin:"0 5px"},on:{click:function(){t.merchantSetting.show=!0,t.merchantShow.id=n.id,t.getMerchantList()}}},"商户列表")},s=function(t,e,n,a){return e("Button",{props:{type:"primary"},style:{margin:"0 5px"},on:{click:function(){t.agentSetting.show=!0,t.agentShow.id=n.id,t.getAgentList()}}},"代理列表")};e.default={name:"interface_group",data:function(){return{columnsList:[{title:"序号",type:"index",width:65,align:"center"},{title:"桶名称",align:"center",key:"name"},{title:"支付渠道编码",align:"center",key:"channel"},{title:"桶描述",align:"center",key:"desc"},{title:"桶接单人数",align:"center",key:"memberNum"},{title:"创建时间",align:"center",key:"create_time"},{title:"桶商户成员",align:"center",key:"merchant",handle:["merchant"]},{title:"桶代理成员",align:"center",key:"agent",handle:["agent"]},{title:"操作",align:"center",key:"handle",width:180,handle:["edit"]}],merchantColumns:[{title:"序号",type:"index",width:65,align:"center"},{title:"商户账号",align:"center",key:"mobile"},{title:"昵称",align:"center",key:"nickname"},{title:"商户编号",align:"center",key:"uid"},{title:"所属桶",align:"center",key:"bucketInfo"},{title:"桶状态",align:"center",key:"inBucket",width:100}],agentColumns:[{title:"序号",type:"index",width:65,align:"center"},{title:"代理账号",align:"center",key:"mobile"},{title:"昵称",align:"center",key:"nickname"},{title:"所属桶",align:"center",key:"bucketInfo"},{title:"桶状态",align:"center",key:"inBucket",width:100}],tableData:[],merchantData:[],agentData:[],channelData:[],tableShow:{currentPage:1,pageSize:10,listCount:0},merchantShow:{currentPage:1,pageSize:10,listCount:0,id:0},agentShow:{currentPage:1,pageSize:10,listCount:0,id:0},searchConf:{name:"",merchantUid:"",agentMobile:""},modalSetting:{show:!1,loading:!1,index:0},merchantSetting:{show:!1,loading:!1,index:0},agentSetting:{show:!1,loading:!1,index:0},formItem:{name:"",channel:"",desc:"",id:0},ruleValidate:{name:[{required:!0,message:"桶名称不能为空",trigger:"blur"}],channel:[{required:!0,message:"桶描述不能为空",trigger:"blur"}],desc:[{required:!0,message:"桶描述不能为空",trigger:"blur"}]}}},created:function(){this.getChannelList(),this.init(),this.getList()},methods:{init:function(){var t=this,e=this;this.columnsList.forEach(function(n){n.handle&&(n.render=function(t,n){var a=e.tableData[n.index];return t("div",[i(e,t,a,n.index)])}),"merchant"===n.key&&(n.render=function(t,n){var a=e.tableData[n.index];return t("div",[r(e,t,a,n.index)])}),"agent"===n.key&&(n.render=function(t,n){var a=e.tableData[n.index];return t("div",[s(e,t,a,n.index)])}),t.merchantColumns.forEach(function(t){"inBucket"===t.key&&(t.render=function(t,n){var a=e.merchantData[n.index],i=e.merchantShow.id;return t("i-switch",{attrs:{size:"large"},props:{"true-value":1,"false-value":2,value:Number(a.inBucket)},on:{"on-change":function(t){o.default.post("Bucket/changeMerchantBucket",{status:t,merchant_id:a.id,bucket_id:i}).then(function(t){var n=t.data;1===n.code?(e.$Message.success(n.msg),e.getMerchantList()):-14===n.code?(e.$store.commit("logout",e),e.$router.push({name:"login"})):(e.$Message.error(n.msg),e.getMerchantList()),e.cancel()})}}},[t("span",{slot:"open"},"开启"),t("span",{slot:"close"},"关闭")])})}),t.agentColumns.forEach(function(t){"inBucket"===t.key&&(t.render=function(t,n){var a=e.agentData[n.index],i=e.agentShow.id;return t("i-switch",{attrs:{size:"large"},props:{"true-value":1,"false-value":2,value:Number(a.inBucket)},on:{"on-change":function(t){o.default.post("Bucket/changeAgentBucket",{status:t,agent_id:a.id,bucket_id:i}).then(function(t){var n=t.data;1===n.code?(e.$Message.success(n.msg),e.getAgentList()):-14===n.code?(e.$store.commit("logout",e),e.$router.push({name:"login"})):(e.$Message.error(n.msg),e.getAgentList()),e.cancel()})}}},[t("span",{slot:"open"},"开启"),t("span",{slot:"close"},"关闭")])})})})},alertAdd:function(){this.modalSetting.show=!0},submit:function(){var t=this,e=this;this.$refs.myForm.validate(function(n){if(n){e.modalSetting.loading=!0;var a="";a=0===t.formItem.id?"Bucket/add":"Bucket/edit",o.default.post(a,e.formItem).then(function(t){1===t.data.code?(e.$Message.success(t.data.msg),e.getList(),e.cancel()):(e.$Message.error(t.data.msg),e.modalSetting.loading=!1)})}})},cancel:function(){this.modalSetting.show=!1},changePage:function(t){this.tableShow.currentPage=t,this.getList()},changeSize:function(t){this.tableShow.pageSize=t,this.getList()},changeMerchantPage:function(t){this.merchantShow.currentPage=t,this.getMerchantList()},changeAgentPage:function(t){this.agentShow.currentPage=t,this.getAgentList()},changeAgentSize:function(t){this.agentShow.pageSize=t,this.getAgentList()},changeMerchantSize:function(t){this.merchantShow.pageSize=t,this.getMerchantList()},search:function(){this.tableShow.currentPage=1,this.getList()},getList:function(){var t=this;o.default.get("Bucket/index",{params:{page:t.tableShow.currentPage,size:t.tableShow.pageSize,name:t.searchConf.name,merchantUid:t.searchConf.merchantUid,agentMobile:t.searchConf.agentMobile}}).then(function(e){var n=e.data;1===n.code?(t.tableData=n.data.list,t.tableShow.listCount=n.data.count):-14===n.code?(t.$store.commit("logout",t),t.$router.push({name:"login"})):t.$Message.error(n.msg)})},getMerchantList:function(){var t=this;o.default.get("Bucket/getMerchantList",{params:{page:t.merchantShow.currentPage,size:t.merchantShow.pageSize,bucket_id:t.merchantShow.id}}).then(function(e){var n=e.data;1===n.code?(t.merchantData=n.data.list,t.merchantShow.listCount=n.data.count):-14===n.code?(t.$store.commit("logout",t),t.$router.push({name:"login"})):t.$Message.error(n.msg)})},getAgentList:function(){var t=this;o.default.get("Bucket/getAgentList",{params:{page:t.agentShow.currentPage,size:t.agentShow.pageSize,bucket_id:t.agentShow.id}}).then(function(e){var n=e.data;1===n.code?(t.agentData=n.data.list,t.agentShow.listCount=n.data.count):-14===n.code?(t.$store.commit("logout",t),t.$router.push({name:"login"})):t.$Message.error(n.msg)})},getChannelList:function(){var t=this;o.default.get("Channel/getChannelList",{params:{}}).then(function(e){var n=e.data;1===n.code?t.channelData=n.data:t.$Message.error(n.msg)})},doCancel:function(t){t||(this.formItem.id=0,this.$refs.myForm.resetFields(),this.modalSetting.loading=!1,this.modalSetting.index=0)}}}},356:function(t,e,n){var a=n(357);"string"==typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);var o=n(15).default;o("96f111b2",a,!1,{})},357:function(t,e,n){e=t.exports=n(14)(!1),e.push([t.i,"\n.formContent .formPwd[data-v-178311b3] {\n  position: relative;\n}\n.formContent .formPwd .clean[data-v-178311b3] {\n  position: absolute;\n  top: 0;\n  right: 10px;\n}\n",""])},358:function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var a=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("div",[n("Row",[n("Col",{attrs:{span:"24"}},[n("Card",{staticStyle:{"margin-bottom":"10px"}},[n("Form",{attrs:{inline:""}},[n("FormItem",{staticStyle:{"margin-bottom":"0"}},[n("Input",{attrs:{placeholder:"桶名称"},model:{value:t.searchConf.name,callback:function(e){t.$set(t.searchConf,"name","string"==typeof e?e.trim():e)},expression:"searchConf.name"}})],1),t._v(" "),n("FormItem",{staticStyle:{"margin-bottom":"0"}},[n("Input",{attrs:{placeholder:"代理账号"},model:{value:t.searchConf.agentMobile,callback:function(e){t.$set(t.searchConf,"agentMobile","string"==typeof e?e.trim():e)},expression:"searchConf.agentMobile"}})],1),t._v(" "),n("FormItem",{staticStyle:{"margin-bottom":"0"}},[n("Input",{attrs:{placeholder:"商户编号"},model:{value:t.searchConf.merchantUid,callback:function(e){t.$set(t.searchConf,"merchantUid","string"==typeof e?e.trim():e)},expression:"searchConf.merchantUid"}})],1),t._v(" "),n("FormItem",{staticStyle:{"margin-bottom":"0"}},[n("Button",{attrs:{type:"primary"},on:{click:t.search}},[t._v("查询/刷新")])],1)],1)],1)],1)],1),t._v(" "),n("Row",[n("Col",{attrs:{span:"24"}},[n("Card",[n("p",{staticStyle:{height:"32px"},attrs:{slot:"title"},slot:"title"},[n("Button",{attrs:{type:"primary",icon:"md-add"},on:{click:t.alertAdd}},[t._v("新增桶")])],1),t._v(" "),n("div",[n("Table",{attrs:{columns:t.columnsList,data:t.tableData,border:"","disabled-hover":""}})],1),t._v(" "),n("div",{staticClass:"margin-top-15",staticStyle:{"text-align":"center"}},[n("Page",{attrs:{total:t.tableShow.listCount,current:t.tableShow.currentPage,"page-size":t.tableShow.pageSize,"show-elevator":"","show-sizer":"","show-total":""},on:{"on-change":t.changePage,"on-page-size-change":t.changeSize}})],1)])],1)],1),t._v(" "),n("Modal",{attrs:{width:"668",styles:{top:"30px"}},on:{"on-visible-change":t.doCancel},model:{value:t.modalSetting.show,callback:function(e){t.$set(t.modalSetting,"show",e)},expression:"modalSetting.show"}},[n("p",{staticStyle:{color:"#2d8cf0"},attrs:{slot:"header"},slot:"header"},[n("Icon",{attrs:{type:"md-information-circle"}}),t._v(" "),n("span",[t._v(t._s(t.formItem.id?"编辑":"新增")+"桶信息")])],1),t._v(" "),n("Form",{ref:"myForm",staticClass:"formContent",attrs:{rules:t.ruleValidate,model:t.formItem,"label-width":100}},[n("FormItem",{attrs:{label:"桶名称",prop:"name"}},[n("Input",{attrs:{placeholder:"请输入桶名称"},model:{value:t.formItem.name,callback:function(e){t.$set(t.formItem,"name",e)},expression:"formItem.name"}})],1),t._v(" "),n("Form-item",{attrs:{label:"支付渠道",prop:"channel_id"}},[n("i-select",{attrs:{placeholder:"请选择支付渠道"},model:{value:t.formItem.channel,callback:function(e){t.$set(t.formItem,"channel",e)},expression:"formItem.channel"}},t._l(t.channelData,function(e,a){return n("Option",{key:a,attrs:{value:a}},[t._v(t._s(e.name))])}))],1),t._v(" "),n("FormItem",{attrs:{label:"桶描述",prop:"desc"}},[n("Input",{attrs:{autosize:{maxRows:10,minRows:4},type:"textarea",placeholder:"请输入桶描述"},model:{value:t.formItem.desc,callback:function(e){t.$set(t.formItem,"desc",e)},expression:"formItem.desc"}})],1)],1),t._v(" "),n("div",{attrs:{slot:"footer"},slot:"footer"},[n("Button",{staticStyle:{"margin-right":"8px"},attrs:{type:"text"},on:{click:t.cancel}},[t._v("取消")]),t._v(" "),n("Button",{attrs:{type:"primary",loading:t.modalSetting.loading},on:{click:t.submit}},[t._v("确定")])],1)],1),t._v(" "),n("Modal",{attrs:{width:"998",styles:{top:"30px"}},model:{value:t.merchantSetting.show,callback:function(e){t.$set(t.merchantSetting,"show",e)},expression:"merchantSetting.show"}},[n("p",{staticStyle:{color:"#2d8cf0"},attrs:{slot:"header"},slot:"header"},[n("Icon",{attrs:{type:"md-information-circle"}}),t._v(" "),n("span",[t._v("商户列表")])],1),t._v(" "),n("div",[n("Table",{attrs:{columns:t.merchantColumns,data:t.merchantData,border:"","disabled-hover":""}})],1),t._v(" "),n("div",{staticClass:"margin-top-15",staticStyle:{"text-align":"center"}},[n("Page",{attrs:{total:t.merchantShow.listCount,current:t.merchantShow.currentPage,"page-size":t.merchantShow.pageSize,"show-elevator":"","show-sizer":"","show-total":""},on:{"on-change":t.changeMerchantPage,"on-page-size-change":t.changeMerchantSize}})],1),t._v(" "),n("p",{attrs:{slot:"footer"},slot:"footer"})]),t._v(" "),n("Modal",{attrs:{width:"998",styles:{top:"30px"}},model:{value:t.agentSetting.show,callback:function(e){t.$set(t.agentSetting,"show",e)},expression:"agentSetting.show"}},[n("p",{staticStyle:{color:"#2d8cf0"},attrs:{slot:"header"},slot:"header"},[n("Icon",{attrs:{type:"md-information-circle"}}),t._v(" "),n("span",[t._v("代理列表")])],1),t._v(" "),n("div",[n("Table",{attrs:{columns:t.agentColumns,data:t.agentData,border:"","disabled-hover":""}})],1),t._v(" "),n("div",{staticClass:"margin-top-15",staticStyle:{"text-align":"center"}},[n("Page",{attrs:{total:t.agentShow.listCount,current:t.agentShow.currentPage,"page-size":t.agentShow.pageSize,"show-elevator":"","show-sizer":"","show-total":""},on:{"on-change":t.changeAgentPage,"on-page-size-change":t.changeAgentSize}})],1),t._v(" "),n("p",{attrs:{slot:"footer"},slot:"footer"})])],1)},o=[];a._withStripped=!0,e.render=a,e.staticRenderFns=o}});