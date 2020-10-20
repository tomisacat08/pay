webpackJsonp([8],{223:function(e,t,a){"use strict";function o(e){c||a(260)}Object.defineProperty(t,"__esModule",{value:!0});var r=a(233),n=a.n(r);for(var i in r)"default"!==i&&function(e){a.d(t,e,function(){return r[e]})}(i);var s=a(262),l=(a.n(s),a(4)),c=!1,d=o,u=Object(l.a)(n.a,s.render,s.staticRenderFns,!1,d,"data-v-77f1bcd2",null);u.options.__file="src\\views\\account\\groups.vue",t.default=u.exports},233:function(e,t,a){"use strict";function o(e){return e&&e.__esModule?e:{default:e}}Object.defineProperty(t,"__esModule",{value:!0});var r=a(91),n=o(r),i=a(32),s=o(i),l=function(e,t,a,o){return t("Button",{props:{type:"primary"},style:{margin:"0 5px"},on:{click:function(){e.formItem.id=a.id,e.formItem.title=a.title,e.formItem.desc=a.desc,e.modalSetting.show=!0,e.modalSetting.index=o}}},"编辑")},c=function(e,t,a,o){return t("Poptip",{props:{confirm:!0,title:"您确定要删除此分组么?",transfer:!0},on:{"on-ok":function(){s.default.get("Groups/del",{params:{id:a.id}}).then(function(t){a.loading=!1,1===t.data.code?(e.tableData.splice(o,1),e.$Message.success(t.data.msg)):e.$Message.error(t.data.msg)})}}},[t("Button",{style:{margin:"0 5px"},props:{type:"error",placement:"top",loading:a.isDeleting}},"删除")])},d=function(e,t,a,o){return t("Button",{props:{type:"primary"},style:{margin:"0 5px"},on:{click:function(){e.memberSetting.show=!0,e.qrcodeShow.user_id=a.user_id,e.qrcodeShow.id=a.id,e.getQrcodeList()}}},"组成员")},u=function(e,t,a,o){return t("Poptip",{props:{confirm:!0,title:"您确定要删除这条数据吗? ",transfer:!0},on:{"on-ok":function(){s.default.post("Member/delMember",{id:e.qrcodeShow.id,user_id:e.qrcodeShow.user_id}).then(function(t){a.loading=!1,1===t.data.code?(e.qrcodeData.splice(o,1),e.$Message.success(t.data.msg)):e.$Message.error(t.data.msg)})}}},[t("Button",{style:{margin:"0 5px"},props:{type:"error",placement:"top",loading:a.isDeleting}},"删除")])};t.default={name:"interface_group",data:function(){return{uploadUrl:"",uploadHeader:{},isshow:!0,columnsList:[{title:"序号",type:"index",width:65,align:"center"},{title:"分组名称",key:"title",align:"center"},{title:"分组描述",align:"center",key:"desc"},{title:"创建时间",align:"center",key:"create_time"},{title:"分组成员",align:"center",key:"member",width:116,handle:["member"]},{title:"分组状态",align:"center",key:"status",width:100},{title:"操作",align:"center",key:"handle",width:180,handle:["edit","delete"]}],qrcodeColumns:[{title:"序号",type:"index",width:65,align:"center"},{title:"用户账号",align:"center",key:"mobile"},{title:"昵称",align:"center",key:"nickname"},{title:"会员",align:"center",key:"type"},{title:"创建时间",align:"center",key:"create_time"},{title:"接单状态",align:"center",key:"is_receipt",width:100},{title:"登陆状态",align:"center",key:"status",width:100},{title:"是否为组长",align:"center",key:"is_leader",width:100}],tableData:[],qrcodeData:[],tableShow:{currentPage:1,pageSize:10,listCount:0},qrcodeShow:{currentPage:1,pageSize:10,listCount:0,user_id:0,id:0},searchConf:{title:""},modalSetting:{show:!1,loading:!1,index:0},memberSetting:{show:!1,loading:!1,index:0},formItem:{title:"",desc:"",id:0},ruleValidate:{title:[{required:!0,message:"分组不能为空",trigger:"blur"}],desc:[{required:!0,message:"分组描述不能为空",trigger:"blur"}]}}},created:function(){this.init(),this.getList()},methods:{changePassWordType:function(){this.isshow=!this.isshow},init:function(){var e=this,t=this;this.uploadUrl=n.default.baseUrl+"Index/upload",this.uploadHeader={ApiAuth:sessionStorage.getItem("apiAuth")},this.columnsList.forEach(function(a){a.handle&&(a.render=function(e,a){var o=t.tableData[a.index];return e("div",[l(t,e,o,a.index),c(t,e,o,a.index)])}),"member"===a.key&&(a.render=function(e,a){var o=t.tableData[a.index];return e("div",[d(t,e,o,a.index)])}),"status"===a.key&&(a.render=function(e,a){var o=t.tableData[a.index];return e("i-switch",{attrs:{size:"large"},props:{"true-value":1,"false-value":0,value:Number(o.status)},on:{"on-change":function(e){s.default.post("Groups/changeStatus",{status:e,id:o.id}).then(function(e){var a=e.data;1===a.code?(t.$Message.success(a.msg),t.getList()):-14===a.code?(t.$store.commit("logout",t),t.$router.push({name:"login"})):(t.$Message.error(a.msg),t.getList()),t.cancel()})}}},[e("span",{slot:"open"},"启用"),e("span",{slot:"close"},"禁用")])}),e.qrcodeColumns.forEach(function(e){"handle"===e.key&&(e.render=function(e,a){var o=t.qrcodeData[a.index];return e("div",[u(t,e,o,a.index)])}),"status"===e.key&&(e.render=function(e,a){return 1===t.qrcodeData[a.index].status?e("Tag",{props:{color:"green"}},"启用"):e("Tag",{props:{color:"red"}},"禁用")}),"is_leader"===e.key&&(e.render=function(e,a){var o=t.qrcodeData[a.index];return e("i-switch",{attrs:{size:"large"},props:{"true-value":1,"false-value":2,value:Number(o.is_leader)},on:{"on-change":function(e){s.default.get("Member/changeStatus",{params:{is_leader:e,id:o.id}}).then(function(e){var a=e.data;1==a.code?t.$Message.success(a.msg):-14==a.code?(t.$store.commit("logout",t),t.$router.push({name:"login"})):(t.$Message.error(a.msg),t.getMemberList()),t.cancel()})}}},[e("span",{slot:"open"},"启用"),e("span",{slot:"close"},"禁用")])}),"is_receipt"===e.key&&(e.render=function(e,a){return 1==t.qrcodeData[a.index].status?e("Tag",{props:{color:"green"}},"启用"):e("Tag",{props:{color:"red"}},"禁用")})})})},alertAdd:function(){this.modalSetting.show=!0},submit:function(){var e=this,t=this;this.$refs.myForm.validate(function(a){if(a){t.modalSetting.loading=!0;var o="";o=0===e.formItem.id?"Groups/add":"Groups/edit",s.default.post(o,t.formItem).then(function(e){1===e.data.code?(t.$Message.success(e.data.msg),t.getList(),t.cancel()):(t.$Message.error(e.data.msg),t.modalSetting.loading=!1)})}})},cancel:function(){this.modalSetting.show=!1},changePage:function(e){this.tableShow.currentPage=e,this.getList()},changeSize:function(e){this.tableShow.pageSize=e,this.getList()},changeMemberPage:function(e){this.qrcodeShow.currentPage=e,this.getMemberList()},changeMemberSize:function(e){this.qrcodeShow.pageSize=e,this.getMemberList()},search:function(){this.tableShow.currentPage=1,this.getList()},getList:function(){var e=this;s.default.get("Groups/index",{params:{page:e.tableShow.currentPage,size:e.tableShow.pageSize,title:e.searchConf.title}}).then(function(t){var a=t.data;1===a.code?(e.tableData=a.data.list,e.tableShow.listCount=a.data.count):-14===a.code?(e.$store.commit("logout",e),e.$router.push({name:"login"})):e.$Message.error(a.msg)})},getMemberList:function(){var e=this;s.default.get("Qrcode/index",{params:{page:e.qrcodeShow.currentPage,size:e.qrcodeShow.pageSize,group_id:e.qrcodeShow.id}}).then(function(t){var a=t.data;1===a.code?(e.qrcodeData=a.data.list,e.qrcodeShow.listCount=a.data.count):-14===a.code?(e.$store.commit("logout",e),e.$router.push({name:"login"})):e.$Message.error(a.msg)})},doCancel:function(e){e||(this.formItem.id=0,this.$refs.myForm.resetFields(),this.modalSetting.loading=!1,this.modalSetting.index=0)}}}},260:function(e,t,a){var o=a(261);"string"==typeof o&&(o=[[e.i,o,""]]),o.locals&&(e.exports=o.locals);var r=a(15).default;r("66cb64b9",o,!1,{})},261:function(e,t,a){t=e.exports=a(14)(!1),t.push([e.i,"\n.formContent .formPwd[data-v-77f1bcd2] {\n  position: relative;\n}\n.formContent .formPwd .clean[data-v-77f1bcd2] {\n  position: absolute;\n  top: 0;\n  right: 10px;\n}\n",""])},262:function(e,t,a){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var o=function(){var e=this,t=e.$createElement,a=e._self._c||t;return a("div",[a("Row",[a("Col",{attrs:{span:"24"}},[a("Card",{staticStyle:{"margin-bottom":"10px"}},[a("Form",{attrs:{inline:""}},[a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Input",{attrs:{placeholder:"分组名称"},model:{value:e.searchConf.title,callback:function(t){e.$set(e.searchConf,"title","string"==typeof t?t.trim():t)},expression:"searchConf.title"}})],1),e._v(" "),a("FormItem",{staticStyle:{"margin-bottom":"0"}},[a("Button",{attrs:{type:"primary"},on:{click:e.search}},[e._v("查询/刷新")])],1)],1)],1)],1)],1),e._v(" "),a("Row",[a("Col",{attrs:{span:"24"}},[a("Card",[a("p",{staticStyle:{height:"32px"},attrs:{slot:"title"},slot:"title"},[a("Button",{attrs:{type:"primary",icon:"md-add"},on:{click:e.alertAdd}},[e._v("新增组")])],1),e._v(" "),a("div",[a("Table",{attrs:{columns:e.columnsList,data:e.tableData,border:"","disabled-hover":""}})],1),e._v(" "),a("div",{staticClass:"margin-top-15",staticStyle:{"text-align":"center"}},[a("Page",{attrs:{total:e.tableShow.listCount,current:e.tableShow.currentPage,"page-size":e.tableShow.pageSize,"show-elevator":"","show-sizer":"","show-total":""},on:{"on-change":e.changePage,"on-page-size-change":e.changeSize}})],1)])],1)],1),e._v(" "),a("Modal",{attrs:{width:"668",styles:{top:"30px"}},on:{"on-visible-change":e.doCancel},model:{value:e.modalSetting.show,callback:function(t){e.$set(e.modalSetting,"show",t)},expression:"modalSetting.show"}},[a("p",{staticStyle:{color:"#2d8cf0"},attrs:{slot:"header"},slot:"header"},[a("Icon",{attrs:{type:"md-information-circle"}}),e._v(" "),a("span",[e._v(e._s(e.formItem.id?"编辑":"新增")+"分组信息")])],1),e._v(" "),a("Form",{ref:"myForm",staticClass:"formContent",attrs:{rules:e.ruleValidate,model:e.formItem,"label-width":100}},[a("FormItem",{attrs:{label:"分组名称",prop:"title"}},[a("Input",{attrs:{placeholder:"请输入分组名称"},model:{value:e.formItem.title,callback:function(t){e.$set(e.formItem,"title",t)},expression:"formItem.title"}})],1),e._v(" "),a("FormItem",{attrs:{label:"分组描述",prop:"desc"}},[a("Input",{attrs:{autosize:{maxRows:10,minRows:4},type:"textarea",placeholder:"请输入分组描述"},model:{value:e.formItem.desc,callback:function(t){e.$set(e.formItem,"desc",t)},expression:"formItem.desc"}})],1)],1),e._v(" "),a("div",{attrs:{slot:"footer"},slot:"footer"},[a("Button",{staticStyle:{"margin-right":"8px"},attrs:{type:"text"},on:{click:e.cancel}},[e._v("取消")]),e._v(" "),a("Button",{attrs:{type:"primary",loading:e.modalSetting.loading},on:{click:e.submit}},[e._v("确定")])],1)],1),e._v(" "),a("Modal",{attrs:{width:"998",styles:{top:"30px"}},model:{value:e.memberSetting.show,callback:function(t){e.$set(e.memberSetting,"show",t)},expression:"memberSetting.show"}},[a("p",{staticStyle:{color:"#2d8cf0"},attrs:{slot:"header"},slot:"header"},[a("Icon",{attrs:{type:"md-information-circle"}}),e._v(" "),a("span",[e._v("成员列表")])],1),e._v(" "),a("div",[a("Table",{attrs:{columns:e.qrcodeColumns,data:e.qrcodeData,border:"","disabled-hover":""}})],1),e._v(" "),a("div",{staticClass:"margin-top-15",staticStyle:{"text-align":"center"}},[a("Page",{attrs:{total:e.qrcodeShow.listCount,current:e.qrcodeShow.currentPage,"page-size":e.qrcodeShow.pageSize,"show-elevator":"","show-sizer":"","show-total":""},on:{"on-change":e.changeMemberPage,"on-page-size-change":e.changeMemberSize}})],1),e._v(" "),a("p",{attrs:{slot:"footer"},slot:"footer"})])],1)},r=[];o._withStripped=!0,t.render=o,t.staticRenderFns=r}});