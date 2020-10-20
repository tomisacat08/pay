webpackJsonp([4],{237:function(e,t,a){"use strict";function n(e){c||a(370)}Object.defineProperty(t,"__esModule",{value:!0});var r=a(279),i=a.n(r);for(var o in r)"default"!==o&&function(e){a.d(t,e,function(){return r[e]})}(o);var s=a(372),l=(a.n(s),a(4)),c=!1,m=n,d=Object(l.a)(i.a,s.render,s.staticRenderFns,!1,m,"data-v-42bcaa75",null);d.options.__file="src\\views\\team\\member.vue",t.default=d.exports},279:function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var n=a(32),r=function(e){return e&&e.__esModule?e:{default:e}}(n),i=function(e,t,a,n){return t("Button",{props:{type:"primary"},style:{margin:"0 5px"},on:{click:function(){e.memberItem.id=a.id,e.memberItem.member_id=a.id,e.memberSetting.show=!0,e.memberSetting.index=n}}},"补单")},o=function(e,t,a,n){return t("Button",{props:{type:"info"},style:{margin:"0 5px"},on:{click:function(){e.checkSetting.show=!0,e.checkShow.id=a.id,e.getCheckList()}}},"查看")},s=function(e,t,a,n){return t("Poptip",{props:{confirm:!0,title:"您确定要删除此二维码吗?",transfer:!0},on:{"on-ok":function(){r.default.get("Member/delQrcode",{params:{id:a.id}}).then(function(t){a.loading=!1,1===t.data.code?(e.checkData.splice(n,1),e.$Message.success(t.data.msg)):e.$Message.error(t.data.msg)})}}},[t("Button",{style:{margin:"0 5px"},props:{type:"error",placement:"top",loading:a.isDeleting}},"删除")])};t.default={name:"interface_group",data:function(){return{isshow:!0,columnsList:[{title:"ID",width:65,align:"center",key:"id"},{title:"会员编号",key:"uid",align:"center"},{title:"昵称",align:"center",key:"nickname"},{title:"手机号",align:"center",key:"mobile"},{title:"代理商ID",align:"center",key:"agent_mobile"},{title:"总余额",align:"center",key:"total_limit"},{title:"可用余额",align:"center",key:"usable_limit"},{title:"费率%",align:"center",key:"poundage_ratio"},{title:"昨日成单情况(成单/总单:成功率)",align:"center",key:"yesterdayRate"},{title:"今日成单情况(成单/总单:成功率)",align:"center",key:"todayRate"},{title:"总成单情况(成单/总单:成功率)",align:"center",key:"allRate"},{title:"创建时间",align:"center",key:"create_time"},{title:"接单状态",align:"center",key:"is_receipt"},{title:"通道状态",align:"center",key:"is_pass"},{title:"登录状态",align:"center",key:"status"},{title:"操作",align:"center",key:"handle",width:180,handle:["edit","query","delete","check"]}],tableData:[],tableShow:{currentPage:1,pageSize:10,listCount:0},searchConf:{type:"",keywords:"",agent_mobile:"",is_receipt:"",uid:""},modalSetting:{show:!1,loading:!1,index:0},memberSetting:{show:!1,loading:!1,index:0},memberItem:{member_id:"",merchant_uid:"",price:"",remark:"",channel:"",id:0},formItem:{nickname:"",password:"",poundage_ratio:"",agent_mobile:"",mobile:"",id:0},checkColumns:[{title:"序号",type:"index",width:65,align:"center"},{title:"图片地址",align:"center",key:"img",width:250},{title:"金额",align:"center",key:"money",width:250},{title:"创建时间",align:"center",key:"create_time"},{title:"操作",align:"center",key:"handle",width:175,handle:["check"]}],checkData:[],checkShow:{currentPage:1,pageSize:10,listCount:0,id:0},checkSetting:{show:!1,loading:!1,index:0},channelData:[],imgUrl:"",visible:!1,ruleValidate:{nickname:[{required:!0,message:"昵称不能为空",trigger:"blur"}],mobile:[{required:!0,message:"手机号码不能为空",trigger:"blur"}],agent_mobile:[{required:!0,message:"代理商手机号码不能为空",trigger:"blur"}]}}},created:function(){this.getChannelList(),this.init(),this.getList()},methods:{changePassWordType:function(){this.isshow=!this.isshow},handleView:function(e){this.imgUrl=e,this.visible=!0},init:function(){var e=this,t=this;this.columnsList.forEach(function(a){a.handle&&(a.render=function(e,a){var n=t.tableData[a.index];return e("div",[i(t,e,n,a.index),o(t,e,n,a.index)])}),"status"===a.key&&(a.render=function(e,a){var n=t.tableData[a.index];return e("i-switch",{attrs:{size:"large"},props:{"true-value":1,"false-value":2,value:Number(n.status)},on:{"on-change":function(e){r.default.get("Member/changeStatus",{params:{status:e,id:n.id}}).then(function(e){var a=e.data;1===a.code?t.$Message.success(a.msg):-14===a.code?(t.$store.commit("logout",t),t.$router.push({name:"login"})):(t.$Message.error(a.msg),t.getList()),t.cancel()})}}},[e("span",{slot:"open"},"启用"),e("span",{slot:"close"},"禁用")])}),"is_receipt"===a.key&&(a.render=function(e,a){return 1===t.tableData[a.index].is_receipt?e("Tag",{props:{color:"green"}},"接单中"):e("Tag",{props:{color:"red"}},"未接单")}),"is_pass"===a.key&&(a.render=function(e,a){var n=t.tableData[a.index];return e("i-switch",{attrs:{size:"large"},props:{"true-value":1,"false-value":2,value:Number(n.is_pass)},on:{"on-change":function(e){r.default.get("Member/changeReceipt",{params:{is_pass:e,id:n.id}}).then(function(e){var a=e.data;1===a.code?t.$Message.success(a.msg):-14===a.code?(t.$store.commit("logout",t),t.$router.push({name:"login"})):(t.$Message.error(a.msg),t.getList()),t.cancel()})}}},[e("span",{slot:"open"},"启用"),e("span",{slot:"close"},"禁用")])}),e.checkColumns.forEach(function(a){"handle"===a.key&&(a.render=function(e,a){var n=t.checkData[a.index];return e("div",[s(t,e,n,a.index)])}),"img"===a.key&&(a.render=function(a,n){return a("div",[a("img",{attrs:{src:t.checkData[n.index].img},style:{width:"80px",height:"80px",borderRadius:"2px",paddingTop:"5px"},on:{click:function(t){e.handleView(t.srcElement.currentSrc)}}})])})})})},alertAdd:function(){this.modalSetting.show=!0},submit:function(){var e=this,t=this;this.$refs.myForm.validate(function(a){if(a){var n="";t.modalSetting.loading=!0,n=0===e.formItem.id?"Member/add":"Member/edit",r.default.post(n,t.formItem).then(function(e){1===e.data.code?(t.$Message.success(e.data.msg),t.cancel()):(t.$Message.error(e.data.msg),t.modalSetting.loading=!1),t.getList()})}})},submitMember:function(){var e=this;this.$refs.myFormMember.validate(function(t){t&&(e.memberSetting.loading=!0,r.default.post("member/memberReplacement",e.memberItem).then(function(t){1===t.data.code?(e.$Message.success(t.data.msg),e.cancel()):(e.$Message.error(t.data.msg),e.memberSetting.loading=!1),e.getList()}))})},cancel:function(){this.modalSetting.show=!1,this.memberSetting.show=!1},changePage:function(e){this.tableShow.currentPage=e,this.getList()},changeSize:function(e){this.tableShow.pageSize=e,this.getList()},changeCheckPage:function(e){this.checkShow.currentPage=e,this.getCheckList()},changeCheckSize:function(e){this.CheckShow.pageSize=e,this.getCheckList()},search:function(){this.tableShow.currentPage=1,this.getList()},getCheckList:function(){var e=this;r.default.get("Member/checkQrcode",{params:{page:e.checkShow.currentPage,size:e.checkShow.pageSize,id:e.checkShow.id}}).then(function(t){var a=t.data;1===a.code?(e.checkData=a.data.list,e.checkShow.listCount=a.data.count):-14===a.code?(e.$store.commit("logout",e),e.$router.push({name:"login"})):e.$Message.error(a.msg)})},getList:function(){var e=this;r.default.get("Member/index",{params:{page:e.tableShow.currentPage,size:e.tableShow.pageSize,keywords:e.searchConf.keywords,agent_mobile:e.searchConf.agent_mobile,uid:e.searchConf.uid,is_receipt:e.searchConf.is_receipt}}).then(function(t){var a=t.data;1===a.code?(e.tableData=a.data.list,e.tableShow.listCount=a.data.count):-14===a.code?(e.$store.commit("logout",e),e.$router.push({name:"login"})):e.$Message.error(a.msg)})},getChannelList:function(){var e=this;r.default.get("Channel/getChannelList",{params:{}}).then(function(t){var a=t.data;1===a.code?e.channelData=a.data:e.$Message.error(a.msg)})},doCancel:function(e){e||(this.formItem.id=0,this.$refs.myForm.resetFields(),this.modalSetting.loading=!1,this.modalSetting.index=0,this.$refs.myFormMember.resetFields(),this.memberSetting.loading=!1,this.memberSetting.index=0)}}}},370:function(e,t,a){var n=a(371);"string"==typeof n&&(n=[[e.i,n,""]]),n.locals&&(e.exports=n.locals);var r=a(15).default;r("b2a6b4b8",n,!1,{})},371:function(e,t,a){t=e.exports=a(14)(!1),t.push([e.i,"\n.formContent .formPwd[data-v-42bcaa75] {\n  position: relative;\n}\n.formContent .formPwd .clean[data-v-42bcaa75] {\n  position: absolute;\n  top: 0;\n  right: 10px;\n}\n",""])},372:function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var n=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("div",[a("Row",[a("Col",{attrs:{span:"24"}},[a("Card",{staticStyle:{"margin-bottom":"10px"}},[a("Form",{attrs:{inline:""}},[a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Select",{staticStyle:{width:"100px"},attrs:{clearable:"",placeholder:"接单状态"},model:{value:e.searchConf.is_receipt,callback:function(t){e.$set(e.searchConf,"is_receipt",t)},expression:"searchConf.is_receipt"}},[a("Option",{attrs:{value:1}},[e._v("接单中")]),e._v(" "),a("Option",{attrs:{value:2}},[e._v("未接单")])],1)],1),e._v(" "),a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Input",{attrs:{placeholder:"手机号/昵称"},model:{value:e.searchConf.keywords,callback:function(t){e.$set(e.searchConf,"keywords",t)},expression:"searchConf.keywords"}})],1),e._v(" "),a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Input",{attrs:{placeholder:"码商手机号"},model:{value:e.searchConf.agent_mobile,callback:function(t){e.$set(e.searchConf,"agent_mobile",t)},expression:"searchConf.agent_mobile"}})],1),e._v(" "),a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Input",{attrs:{placeholder:"会员编号"},model:{value:e.searchConf.uid,callback:function(t){e.$set(e.searchConf,"uid",t)},expression:"searchConf.uid"}})],1),e._v(" "),a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Button",{attrs:{type:"primary"},on:{click:e.search}},[e._v("查询/刷新")])],1)],1)],1)],1)],1),e._v(" "),a("Row",[a("Col",{attrs:{span:"24"}},[a("Card",[a("div",[a("Table",{attrs:{columns:e.columnsList,data:e.tableData,border:"","disabled-hover":""}})],1),e._v(" "),a("div",{staticClass:"margin-top-15",staticStyle:{"text-align":"center"}},[a("Page",{attrs:{total:e.tableShow.listCount,current:e.tableShow.currentPage,"page-size":e.tableShow.pageSize,"show-elevator":"","show-sizer":"","show-total":""},on:{"on-change":e.changePage,"on-page-size-change":e.changeSize}})],1)])],1)],1),e._v(" "),a("Modal",{attrs:{width:"668",styles:{top:"30px"}},on:{"on-visible-change":e.doCancel},model:{value:e.modalSetting.show,callback:function(t){e.$set(e.modalSetting,"show",t)},expression:"modalSetting.show"}},[a("p",{staticStyle:{color:"#2d8cf0"},attrs:{slot:"header"},slot:"header"},[a("Icon",{attrs:{type:"md-information-circle"}}),e._v(" "),a("span",[e._v(e._s(e.formItem.id?"编辑":"新增")+"会员信息")])],1),e._v(" "),a("Form",{ref:"myForm",staticClass:"formContent",attrs:{rules:e.ruleValidate,model:e.formItem,"label-width":100}},[a("FormItem",{attrs:{label:"手机号",prop:"mobile"}},[a("Input",{attrs:{placeholder:"请输商户手机号"},model:{value:e.formItem.mobile,callback:function(t){e.$set(e.formItem,"mobile",t)},expression:"formItem.mobile"}})],1),e._v(" "),a("FormItem",{attrs:{label:"代理商手机号",prop:"agent_mobile"}},[a("Input",{attrs:{placeholder:"请输代理商手机号"},model:{value:e.formItem.agent_mobile,callback:function(t){e.$set(e.formItem,"agent_mobile",t)},expression:"formItem.agent_mobile"}})],1),e._v(" "),a("FormItem",{attrs:{label:"昵称",prop:"nickname"}},[a("Input",{attrs:{placeholder:"请输商户昵称"},model:{value:e.formItem.nickname,callback:function(t){e.$set(e.formItem,"nickname",t)},expression:"formItem.nickname"}})],1),e._v(" "),a("FormItem",{staticClass:"formPwd",attrs:{label:"密码",prop:"password"}},[e.isshow?a("Input",{attrs:{type:"password",placeholder:"请输商户密码"},model:{value:e.formItem.password,callback:function(t){e.$set(e.formItem,"password",t)},expression:"formItem.password"}}):a("Input",{attrs:{type:"text",placeholder:"请输商户密码"},model:{value:e.formItem.password,callback:function(t){e.$set(e.formItem,"password",t)},expression:"formItem.password"}}),e._v(" "),(e.isshow,a("div",{staticClass:"clean",on:{click:e.changePassWordType}},[a("Icon",{attrs:{type:"ios-eye-outline"}})],1))],1),e._v(" "),a("FormItem",{attrs:{label:"手续费比例",prop:"poundage_ratio"}},[a("Input",{attrs:{placeholder:"请输手续费比例"},model:{value:e.formItem.poundage_ratio,callback:function(t){e.$set(e.formItem,"poundage_ratio",t)},expression:"formItem.poundage_ratio"}})],1)],1),e._v(" "),a("div",{attrs:{slot:"footer"},slot:"footer"},[a("Button",{staticStyle:{"margin-right":"8px"},attrs:{type:"text"},on:{click:e.cancel}},[e._v("取消")]),e._v(" "),a("Button",{attrs:{type:"primary",loading:e.modalSetting.loading},on:{click:e.submit}},[e._v("确定")])],1)],1),e._v(" "),a("Modal",{attrs:{width:"668",styles:{top:"30px"}},on:{"on-visible-change":e.doCancel},model:{value:e.memberSetting.show,callback:function(t){e.$set(e.memberSetting,"show",t)},expression:"memberSetting.show"}},[a("p",{staticStyle:{color:"#2d8cf0"},attrs:{slot:"header"},slot:"header"},[a("Icon",{attrs:{type:"md-information-circle"}}),e._v(" "),a("span",[e._v("补单信息")])],1),e._v(" "),a("Form",{ref:"myFormMember",staticClass:"formContent",attrs:{rules:e.ruleValidate,model:e.memberItem,"label-width":100}},[a("FormItem",{attrs:{label:"商户编号",prop:"merchant_uid"}},[a("Input",{attrs:{placeholder:"请输商户编号"},model:{value:e.memberItem.merchant_uid,callback:function(t){e.$set(e.memberItem,"merchant_uid",t)},expression:"memberItem.merchant_uid"}})],1),e._v(" "),a("FormItem",{attrs:{label:"会员ID",prop:"member_id"}},[a("Input",{attrs:{placeholder:"请输会员ID",disabled:""},model:{value:e.memberItem.member_id,callback:function(t){e.$set(e.memberItem,"member_id",t)},expression:"memberItem.member_id"}})],1),e._v(" "),a("FormItem",{attrs:{label:"订单金额",prop:"price"}},[a("Input",{attrs:{placeholder:"请输订单金额"},model:{value:e.memberItem.price,callback:function(t){e.$set(e.memberItem,"price",t)},expression:"memberItem.price"}})],1),e._v(" "),a("FormItem",{attrs:{label:"渠道信息",prop:"channel"}},[a("i-select",{staticStyle:{width:"100px"},attrs:{clearable:"",placeholder:"通道编码"},model:{value:e.memberItem.channel,callback:function(t){e.$set(e.memberItem,"channel",t)},expression:"memberItem.channel"}},e._l(e.channelData,function(t,n){return a("Option",{key:n,attrs:{value:n}},[e._v(e._s(t.name))])}))],1),e._v(" "),a("FormItem",{attrs:{label:"备注",prop:"remark"}},[a("Input",{attrs:{type:"textarea",rows:4,placeholder:"请输备注"},model:{value:e.memberItem.remark,callback:function(t){e.$set(e.memberItem,"remark",t)},expression:"memberItem.remark"}})],1)],1),e._v(" "),a("div",{attrs:{slot:"footer"},slot:"footer"},[a("Button",{staticStyle:{"margin-right":"8px"},attrs:{type:"text"},on:{click:e.cancel}},[e._v("取消")]),e._v(" "),a("Button",{attrs:{type:"primary",loading:e.memberSetting.loading},on:{click:e.submitMember}},[e._v("确定")])],1)],1),e._v(" "),a("Modal",{attrs:{width:"80%",styles:{top:"30px"}},model:{value:e.checkSetting.show,callback:function(t){e.$set(e.checkSetting,"show",t)},expression:"checkSetting.show"}},[a("p",{staticStyle:{color:"#2d8cf0"},attrs:{slot:"header"},slot:"header"},[a("Icon",{attrs:{type:"md-information-circle"}}),e._v(" "),a("span",[e._v("查看详情")])],1),e._v(" "),a("div",[a("Table",{attrs:{columns:e.checkColumns,data:e.checkData,border:"","disabled-hover":""}})],1),e._v(" "),a("div",{staticClass:"margin-top-15",staticStyle:{"text-align":"center"}},[a("Page",{attrs:{total:e.checkShow.listCount,current:e.checkShow.currentPage,"page-size":e.checkShow.pageSize,"show-elevator":"","show-sizer":"","show-total":""},on:{"on-change":e.changeCheckPage,"on-page-size-change":e.changeCheckSize}})],1),e._v(" "),a("p",{attrs:{slot:"footer"},slot:"footer"})]),e._v(" "),a("Modal",{attrs:{title:"查看大图","class-name":"fl-image-modal"},model:{value:e.visible,callback:function(t){e.visible=t},expression:"visible"}},[e.visible?a("img",{staticStyle:{width:"100%"},attrs:{src:e.imgUrl}}):e._e()])],1)},r=[];n._withStripped=!0,t.render=n,t.staticRenderFns=r}});