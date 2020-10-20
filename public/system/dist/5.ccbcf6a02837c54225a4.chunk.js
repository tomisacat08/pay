webpackJsonp([5],{238:function(e,t,a){"use strict";function o(e){m||a(373)}Object.defineProperty(t,"__esModule",{value:!0});var r=a(280),n=a.n(r);for(var i in r)"default"!==i&&function(e){a.d(t,e,function(){return r[e]})}(i);var s=a(375),l=(a.n(s),a(4)),m=!1,c=o,d=Object(l.a)(n.a,s.render,s.staticRenderFns,!1,c,"data-v-b0ee670c",null);d.options.__file="src\\views\\team\\agent.vue",t.default=d.exports},280:function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var o=a(32),r=function(e){return e&&e.__esModule?e:{default:e}}(o),n=function(e,t,a,o){return t("Button",{props:{type:"primary"},style:{margin:"0 5px"},on:{click:function(){r.default.get("Agent/getAgentInfo",{params:{id:a.id}}).then(function(t){var r=t.data;if(1===r.code){var n=r.data;e.is_disable=!0,e.formItem.total_limit=n.total_limit,e.formItem.usable_limit=n.usable_limit,e.formItem.poundage_ratio=n.poundage_ratio,e.formItem.nickname=a.nickname,e.formItem.password="",e.formItem.pay_password="",e.formItem.account_holder=a.account_holder,e.formItem.mobile=a.mobile,e.formItem.id=a.id,e.modalSetting.show=!0,e.modalSetting.index=o}else-14===r.code?(e.$store.commit("logout",e),e.$router.push({name:"login"})):e.$Message.error(r.msg)})}}},"编辑")},i=function(e,t,a,o){return t("Button",{props:{type:"primary"},style:{margin:"0 0px"},on:{click:function(){e.memberSetting.show=!0,e.memberShow.id=a.id,e.getMemberList()}}},"查看")};t.default={name:"agent_index",data:function(){return{uploadUrl:"",is_disable:!1,isshow:!0,columnsList:[{title:"ID",width:65,align:"center",key:"id"},{title:"代理编号",key:"uid",align:"center"},{title:"昵称",align:"center",key:"nickname"},{title:"手机号",align:"center",key:"mobile"},{title:"额度",align:"center",key:"usable_limit"},{title:"未结算总额",align:"center",key:"settlement_money"},{title:"总业绩",align:"center",key:"total_per_money"},{title:"昨日成单情况(成单/总单:成功率)",align:"center",key:"yesterdayRate"},{title:"今日成单情况(成单/总单:成功率)",align:"center",key:"todayRate"},{title:"总成单情况(成单/总单:成功率)",align:"center",key:"allRate"},{title:"创建时间",align:"center",key:"create_time"},{title:"谷歌验证",align:"center",key:"used_google_code",width:100},{title:"会员列表",align:"center",key:"member",handle:["member"]},{title:"通道状态",align:"center",key:"type"},{title:"登录状态",align:"center",key:"status"},{title:"操作",align:"center",key:"handle",width:80,handle:["edit"]}],memberColumns:[{title:"序号",type:"index",width:65,align:"center"},{title:"用户账号",align:"center",key:"mobile"},{title:"昵称",align:"center",key:"nickname"},{title:"总额度",align:"center",key:"total_limit"},{title:"可用额度",align:"center",key:"usable_limit"},{title:"手续费比例(%)",align:"center",key:"poundage_ratio",width:120},{title:"通道状态",align:"center",key:"is_pass"},{title:"登录状态",align:"center",key:"status"},{title:"接单状态",align:"center",key:"is_receipt"}],tableData:[],memberData:[],tableShow:{currentPage:1,pageSize:10,listCount:0},memberShow:{currentPage:1,pageSize:10,listCount:0,id:0},searchConf:{type:"",keywords:"",uid:""},modalSetting:{show:!1,loading:!1,index:0},memberSetting:{show:!1,loading:!1,index:0},formItem:{nickname:"",password:"",total_limit:"",usable_limit:"",pay_password:"123456",account_holder:"",poundage_ratio:"",mobile:"",id:0},ruleValidate:{nickname:[{required:!0,message:"昵称不能为空",trigger:"blur"}],mobile:[{required:!0,message:"手机号码不能为空",trigger:"blur"}]}}},created:function(){this.formItem.account_holder=JSON.parse(sessionStorage.getItem("userInfo")).username,this.init(),this.getList()},methods:{changePassWordType:function(){this.isshow=!this.isshow},init:function(){var e=this,t=this;this.columnsList.forEach(function(a){a.handle&&(a.render=function(e,a){var o=t.tableData[a.index];return e("div",[n(t,e,o,a.index)])}),"member"===a.key&&(a.render=function(e,a){var o=t.tableData[a.index];return e("div",[i(t,e,o,a.index)])}),"status"===a.key&&(a.render=function(e,a){var o=t.tableData[a.index];return e("i-switch",{attrs:{size:"large"},props:{"true-value":1,"false-value":2,value:Number(o.status)},on:{"on-change":function(e){r.default.get("Agent/changeStatus",{params:{status:e,id:o.id}}).then(function(e){var a=e.data;1===a.code?t.$Message.success(a.msg):-14===a.code?(t.$store.commit("logout",t),t.$router.push({name:"login"})):(t.$Message.error(a.msg),t.getList()),t.cancel()})}}},[e("span",{slot:"open"},"启用"),e("span",{slot:"close"},"禁用")])}),"type"===a.key&&(a.render=function(e,a){var o=t.tableData[a.index];return e("i-switch",{attrs:{size:"large"},props:{"true-value":1,"false-value":2,value:Number(o.type)},on:{"on-change":function(e){r.default.get("Agent/changeType",{params:{type:e,id:o.id}}).then(function(e){var a=e.data;1===a.code?t.$Message.success(a.msg):-14===a.code?(t.$store.commit("logout",t),t.$router.push({name:"login"})):(t.$Message.error(a.msg),t.getList()),t.cancel()})}}},[e("span",{slot:"open"},"启用"),e("span",{slot:"close"},"禁用")])}),"used_google_code"===a.key&&(a.render=function(e,a){return 1===t.tableData[a.index].used_google_code?e("Tag",{props:{color:"green"}},"已启用"):e("Tag",{props:{color:"red"}},"未启用")}),e.memberColumns.forEach(function(e){"is_pass"===e.key&&(e.render=function(e,a){return 1===t.memberData[a.index].is_pass?e("Tag",{props:{color:"green"}},"启用"):e("Tag",{props:{color:"red"}},"禁用")}),"status"===e.key&&(e.render=function(e,a){return 1===t.memberData[a.index].status?e("Tag",{props:{color:"green"}},"启用"):e("Tag",{props:{color:"red"}},"禁用")}),"is_receipt"===e.key&&(e.render=function(e,a){return 1===t.memberData[a.index].is_receipt?e("Tag",{props:{color:"green"}},"接单中"):e("Tag",{props:{color:"red"}},"未接单")})})})},alertAdd:function(){this.modalSetting.show=!0,this.is_disable=!1,this.formItem.id=0},submit:function(){var e=this,t=this;this.$refs.myForm.validate(function(a){if(a){var o="";t.modalSetting.loading=!0,o=0===e.formItem.id?"Agent/add":"Agent/edit",r.default.post(o,t.formItem).then(function(e){1===e.data.code?(t.$Message.success(e.data.msg),t.cancel()):(t.$Message.error(e.data.msg),t.modalSetting.loading=!1),t.getList()})}})},cancel:function(){this.modalSetting.show=!1},changePage:function(e){this.tableShow.currentPage=e,this.getList()},changeSize:function(e){this.tableShow.pageSize=e,this.getList()},changeMemberPage:function(e){this.memberShow.currentPage=e,this.getMemberList()},changeMemberSize:function(e){this.memberShow.pageSize=e,this.getMemberList()},search:function(){this.tableShow.currentPage=1,this.getList()},getList:function(){var e=this;r.default.get("Agent/index",{params:{page:e.tableShow.currentPage,size:e.tableShow.pageSize,type:e.searchConf.type,uid:e.searchConf.uid,keywords:e.searchConf.keywords}}).then(function(t){var a=t.data;1===a.code?(e.tableData=a.data.list,e.tableShow.listCount=a.data.count):-14===a.code?(e.$store.commit("logout",e),e.$router.push({name:"login"})):e.$Message.error(a.msg)})},getMemberList:function(){var e=this;r.default.get("Agent/member_index",{params:{page:e.memberShow.currentPage,size:e.memberShow.pageSize,id:e.memberShow.id}}).then(function(t){var a=t.data;1===a.code?(e.memberData=a.data.list,e.memberShow.listCount=a.data.count):-14===a.code?(e.$store.commit("logout",e),e.$router.push({name:"login"})):e.$Message.error(a.msg)})},doCancel:function(e){e||(this.formItem.id=0,this.$refs.myForm.resetFields(),this.modalSetting.loading=!1,this.modalSetting.index=0)}}}},373:function(e,t,a){var o=a(374);"string"==typeof o&&(o=[[e.i,o,""]]),o.locals&&(e.exports=o.locals);var r=a(15).default;r("6094a42a",o,!1,{})},374:function(e,t,a){t=e.exports=a(14)(!1),t.push([e.i,"\n.formContent .formPwd[data-v-b0ee670c] {\n  position: relative;\n}\n.formContent .formPwd .clean[data-v-b0ee670c] {\n  position: absolute;\n  top: 0;\n  right: 10px;\n}\n",""])},375:function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var o=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("div",[a("Row",[a("Col",{attrs:{span:"24"}},[a("Card",{staticStyle:{"margin-bottom":"10px"}},[a("Form",{attrs:{inline:""}},[a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Select",{staticStyle:{width:"100px"},attrs:{clearable:"",placeholder:"通道状态"},model:{value:e.searchConf.type,callback:function(t){e.$set(e.searchConf,"type",t)},expression:"searchConf.type"}},[a("Option",{attrs:{value:1}},[e._v("启用")]),e._v(" "),a("Option",{attrs:{value:2}},[e._v("禁用")])],1)],1),e._v(" "),a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Input",{attrs:{placeholder:"手机号/昵称"},model:{value:e.searchConf.keywords,callback:function(t){e.$set(e.searchConf,"keywords",t)},expression:"searchConf.keywords"}})],1),e._v(" "),a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Input",{attrs:{placeholder:"代理商编号"},model:{value:e.searchConf.uid,callback:function(t){e.$set(e.searchConf,"uid",t)},expression:"searchConf.uid"}})],1),e._v(" "),a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Button",{attrs:{type:"primary"},on:{click:e.search}},[e._v("查询/刷新")])],1)],1)],1)],1)],1),e._v(" "),a("Row",[a("Col",{attrs:{span:"24"}},[a("Card",[a("p",{staticStyle:{height:"32px"},attrs:{slot:"title"},slot:"title"},[a("Button",{attrs:{type:"primary",icon:"md-add"},on:{click:e.alertAdd}},[e._v("新增")])],1),e._v(" "),a("div",[a("Table",{attrs:{columns:e.columnsList,data:e.tableData,border:"","disabled-hover":""}})],1),e._v(" "),a("div",{staticClass:"margin-top-15",staticStyle:{"text-align":"center"}},[a("Page",{attrs:{total:e.tableShow.listCount,current:e.tableShow.currentPage,"page-size":e.tableShow.pageSize,"show-elevator":"","show-sizer":"","show-total":""},on:{"on-change":e.changePage,"on-page-size-change":e.changeSize}})],1)])],1)],1),e._v(" "),a("Modal",{attrs:{width:"668",styles:{top:"30px"}},on:{"on-visible-change":e.doCancel},model:{value:e.modalSetting.show,callback:function(t){e.$set(e.modalSetting,"show",t)},expression:"modalSetting.show"}},[a("p",{staticStyle:{color:"#2d8cf0"},attrs:{slot:"header"},slot:"header"},[a("Icon",{attrs:{type:"md-information-circle"}}),e._v(" "),a("span",[e._v(e._s(e.formItem.id?"编辑":"新增")+"代理信息")])],1),e._v(" "),a("Form",{ref:"myForm",staticClass:"formContent",attrs:{rules:e.ruleValidate,model:e.formItem,"label-width":100}},[a("FormItem",{attrs:{label:"手机号",prop:"mobile"}},[e.is_disable?a("Input",{attrs:{placeholder:"请输手机号",readonly:"readonly",disabled:""},model:{value:e.formItem.mobile,callback:function(t){e.$set(e.formItem,"mobile",t)},expression:"formItem.mobile"}}):a("Input",{attrs:{placeholder:"请输手机号"},model:{value:e.formItem.mobile,callback:function(t){e.$set(e.formItem,"mobile",t)},expression:"formItem.mobile"}})],1),e._v(" "),a("FormItem",{attrs:{label:"昵称",prop:"nickname"}},[a("Input",{attrs:{placeholder:"请输昵称"},model:{value:e.formItem.nickname,callback:function(t){e.$set(e.formItem,"nickname",t)},expression:"formItem.nickname"}})],1),e._v(" "),a("FormItem",{staticClass:"formPwd",attrs:{label:"密码",prop:"password"}},[e.isshow?a("Input",{attrs:{type:"password",placeholder:"请输密码"},model:{value:e.formItem.password,callback:function(t){e.$set(e.formItem,"password",t)},expression:"formItem.password"}}):a("Input",{attrs:{type:"text",placeholder:"请输密码"},model:{value:e.formItem.password,callback:function(t){e.$set(e.formItem,"password",t)},expression:"formItem.password"}}),e._v(" "),e.isshow?a("div",{staticClass:"clean",on:{click:e.changePassWordType}},[a("Icon",{attrs:{type:"ios-eye-online"}})],1):a("div",{staticClass:"clean",on:{click:e.changePassWordType}},[a("Icon",{attrs:{type:"ios-eye-outline"}})],1)],1),e._v(" "),a("FormItem",{attrs:{label:"支付密码",prop:"pay_password"}},[a("Input",{attrs:{type:"text",placeholder:"请输支付密码"},model:{value:e.formItem.pay_password,callback:function(t){e.$set(e.formItem,"pay_password",t)},expression:"formItem.pay_password"}})],1),e._v(" "),a("FormItem",{attrs:{label:"总额度",prop:"total_limit"}},[a("Input",{attrs:{placeholder:"请输可用总额度"},model:{value:e.formItem.total_limit,callback:function(t){e.$set(e.formItem,"total_limit",t)},expression:"formItem.total_limit"}})],1),e._v(" "),a("FormItem",{attrs:{label:"可用额度",prop:"usable_limit"}},[a("Input",{attrs:{readonly:"readonly",disabled:""},model:{value:e.formItem.usable_limit,callback:function(t){e.$set(e.formItem,"usable_limit",t)},expression:"formItem.usable_limit"}})],1),e._v(" "),a("FormItem",{attrs:{label:"手续费比例",prop:"poundage_ratio"}},[a("Input",{attrs:{placeholder:"请输手续费比例（%）"},model:{value:e.formItem.poundage_ratio,callback:function(t){e.$set(e.formItem,"poundage_ratio",t)},expression:"formItem.poundage_ratio"}})],1),e._v(" "),a("FormItem",{attrs:{label:"开户人",prop:"account_holder"}},[a("Input",{attrs:{placeholder:"请输开户人",readonly:""},model:{value:e.formItem.account_holder,callback:function(t){e.$set(e.formItem,"account_holder",t)},expression:"formItem.account_holder"}})],1)],1),e._v(" "),a("div",{attrs:{slot:"footer"},slot:"footer"},[a("Button",{staticStyle:{"margin-right":"8px"},attrs:{type:"text"},on:{click:e.cancel}},[e._v("取消")]),e._v(" "),a("Button",{attrs:{type:"primary",loading:e.modalSetting.loading},on:{click:e.submit}},[e._v("确定")])],1)],1),e._v(" "),a("Modal",{attrs:{width:"998",styles:{top:"30px"}},model:{value:e.memberSetting.show,callback:function(t){e.$set(e.memberSetting,"show",t)},expression:"memberSetting.show"}},[a("p",{staticStyle:{color:"#2d8cf0"},attrs:{slot:"header"},slot:"header"},[a("Icon",{attrs:{type:"md-information-circle"}}),e._v(" "),a("span",[e._v("成员列表")])],1),e._v(" "),a("div",[a("Table",{attrs:{columns:e.memberColumns,data:e.memberData,border:"","disabled-hover":""}})],1),e._v(" "),a("div",{staticClass:"margin-top-15",staticStyle:{"text-align":"center"}},[a("Page",{attrs:{total:e.memberShow.listCount,current:e.memberShow.currentPage,"page-size":e.memberShow.pageSize,"show-elevator":"","show-sizer":"","show-total":""},on:{"on-change":e.changeMemberPage,"on-page-size-change":e.changeMemberSize}})],1),e._v(" "),a("p",{attrs:{slot:"footer"},slot:"footer"})])],1)},r=[];o._withStripped=!0,t.render=o,t.staticRenderFns=r}});