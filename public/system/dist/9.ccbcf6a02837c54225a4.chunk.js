webpackJsonp([9],{226:function(t,a,n){"use strict";function e(t){m||n(332)}Object.defineProperty(a,"__esModule",{value:!0});var s=n(267),o=n.n(s);for(var r in s)"default"!==r&&function(t){n.d(a,t,function(){return s[t]})}(r);var i=n(334),l=(n.n(i),n(4)),m=!1,c=e,d=Object(l.a)(o.a,i.render,i.staticRenderFns,!1,c,null,null);d.options.__file="src\\views\\system\\homepage.vue",a.default=d.exports},267:function(t,a,n){"use strict";function e(t){return t&&t.__esModule?t:{default:t}}Object.defineProperty(a,"__esModule",{value:!0});var s=n(91),o=(e(s),n(32)),r=e(o);a.default={name:"home",data:function(){return{homeData:{},addData:[]}},computed:{headImgPath:function(){return n(92)}},created:function(){this.getAgentMsg()},methods:{getAgentMsg:function(){var t=this;r.default.post("index/index").then(function(a){var n=a.data;1==n.code?(t.homeData=n.data,t.addData=n.data.notice):-14===n.code?(t.$store.commit("logout",t),t.$router.push({name:"login"})):t.$Message.error(n.msg)})}}}},332:function(t,a,n){var e=n(333);"string"==typeof e&&(e=[[t.i,e,""]]),e.locals&&(t.exports=e.locals);var s=n(15).default;s("1c945c9a",e,!1,{})},333:function(t,a,n){a=t.exports=n(14)(!1),a.push([t.i,"\n.user-infor {\n  height: 135px;\n}\n.avator-img {\n  display: block;\n  width: 80%;\n  max-width: 100px;\n  height: auto;\n}\n.card-user-infor-name {\n  font-size: 2em;\n  color: #2d8cf0;\n}\n.card-title {\n  color: #abafbd;\n}\n.made-child-con-middle {\n  height: 100%;\n}\n.to-do-list-con {\n  height: 145px;\n  overflow: auto;\n}\n.to-do-item {\n  padding: 2px;\n}\n.infor-card-con {\n  height: 100px;\n}\n.infor-card-icon-con {\n  height: 100%;\n  color: white;\n  border-radius: 3px 0 0 3px;\n}\n.line-chart-con {\n  height: 350px;\n}\n.code-row-bg {\n  border-color: #F0F0F0;\n}\n.infor-card-count .infor-intro-text {\n  color: #333;\n  font-size: 16px;\n  font-weight: 500;\n}\n.notwrap {\n  display: inline-block;\n  padding-right: 20px;\n}\n.notwrap .status {\n  color: #2d8cf0;\n  font-size: 18px;\n}\n.msg1 {\n  font-size: 24px;\n  color: #2d8cf0;\n}\n.msg2 {\n  font-size: 16px;\n  padding-top: 5px;\n  color: #333;\n}\n.adTitle {\n  display: inline-block;\n}\n.adTitleFr {\n  float: right;\n  padding-right: 20px;\n}\n.title {\n  font-size: 15px;\n  font-weight: 700;\n  color: #333;\n  display: -webkit-inline-box;\n  display: -ms-inline-flexbox;\n  display: inline-flex;\n  -webkit-box-align: center;\n      -ms-flex-align: center;\n          align-items: center;\n}\n.margin-top-8 {\n  margin-top: 8px;\n}\n.margin-top-10 {\n  margin-top: 10px;\n}\n.margin-top-20 {\n  margin-top: 20px;\n}\n.margin-left-10 {\n  margin-left: 10px;\n}\n.margin-bottom-10 {\n  margin-bottom: 10px;\n}\n.margin-bottom-100 {\n  margin-bottom: 100px;\n}\n.margin-right-10 {\n  margin-right: 10px;\n}\n.padding-left-6 {\n  padding-left: 6px;\n}\n.padding-left-8 {\n  padding-left: 5px;\n}\n.padding-left-10 {\n  padding-left: 10px;\n}\n.padding-left-20 {\n  padding-left: 20px;\n}\n.height-100 {\n  height: 100%;\n}\n.height-120px {\n  height: 100px;\n}\n.height-200px {\n  height: 200px;\n}\n.height-492px {\n  height: 492px;\n}\n.height-460px {\n  height: 460px;\n}\n.line-gray {\n  height: 0;\n  border-bottom: 2px solid #dcdcdc;\n}\n.notwrap {\n  word-break: keep-all;\n  white-space: nowrap;\n  overflow: hidden;\n  text-overflow: ellipsis;\n}\n.padding-left-5 {\n  padding-left: 10px;\n}\n[v-cloak] {\n  display: none;\n}\n.titleTips {\n  width: 100%;\n}\n.titleTips .info {\n  color: #666;\n  font-size: 14px;\n}\n.titleTips .info .status {\n  color: #2d8cf0;\n  font-size: 18px;\n}\n",""])},334:function(t,a,n){"use strict";Object.defineProperty(a,"__esModule",{value:!0});var e=function(){var t=this,a=t.$createElement,n=t._self._c||a;return n("div",{staticClass:"home-main"},[n("Row",{attrs:{gutter:16}},[n("Col",{style:{marginBottom:"10px"},attrs:{md:24,lg:24}},[n("Card",{attrs:{bordered:!1}},[n("div",{staticClass:"title"},[t._v("重要提醒")]),t._v(" "),n("Row",{staticClass:"code-row-bg margin-top-8",attrs:{type:"flex",justify:"space-around"}},[n("i-col",{attrs:{span:"3",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.receipt_num?t.homeData.receipt_num:0))]),n("p",{staticClass:"msg2"},[t._v("排队码")])]),t._v(" "),n("i-col",{attrs:{span:"3",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.merchant_withdraw_num?t.homeData.merchant_withdraw_num:0))]),n("p",{staticClass:"msg2"},[t._v("未处理的商户提现")])]),t._v(" "),n("i-col",{attrs:{span:"3",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.balance?t.homeData.balance:0))]),n("p",{staticClass:"msg2"},[t._v("今日平台佣金")])])],1)],1)],1)],1),t._v(" "),n("Row",{attrs:{gutter:16}},[n("Col",{style:{marginBottom:"10px"},attrs:{md:24,lg:24}},[n("Card",{attrs:{bordered:!1}},[n("div",{staticClass:"title"},[t._v("用户信息")]),t._v(" "),n("Row",{staticClass:"code-row-bg margin-top-8",attrs:{type:"flex",justify:"space-around"}},[n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.merchant_num?t.homeData.merchant_num:0))]),n("p",{staticClass:"msg2"},[t._v("商家总个数")])]),t._v(" "),n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.agent_num?t.homeData.agent_num:0))]),n("p",{staticClass:"msg2"},[t._v("代理商总个数")])]),t._v(" "),n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.member_num?t.homeData.member_num:0))]),n("p",{staticClass:"msg2"},[t._v("会员总个数")])])],1)],1)],1)],1),t._v(" "),n("Row",{attrs:{gutter:16}},[n("Col",{style:{marginBottom:"10px"},attrs:{md:24,lg:24}},[n("Card",{attrs:{bordered:!1}},[n("div",{staticClass:"title"},[t._v("今日交易信息")]),t._v(" "),n("Row",{staticClass:"code-row-bg margin-top-8",attrs:{type:"flex",justify:"space-around"}},[n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.today_order_num?t.homeData.today_order_num:0))]),n("p",{staticClass:"msg2"},[t._v("今日派单数")])]),t._v(" "),n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.today_over_order_num?t.homeData.today_over_order_num:0))]),n("p",{staticClass:"msg2"},[t._v("今日成交单数")])]),t._v(" "),n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.today_order_money?t.homeData.today_order_money:0))]),n("p",{staticClass:"msg2"},[t._v("今日派单金额")])]),t._v(" "),n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.today_over_order_money?t.homeData.today_over_order_money:0))]),n("p",{staticClass:"msg2"},[t._v("今日成交金额")])])],1)],1)],1)],1),t._v(" "),n("Row",{attrs:{gutter:16}},[n("Col",{style:{marginBottom:"10px"},attrs:{md:24,lg:24}},[n("Card",{attrs:{bordered:!1}},[n("div",{staticClass:"title"},[t._v("昨天交易信息")]),t._v(" "),n("Row",{staticClass:"code-row-bg margin-top-8",attrs:{type:"flex",justify:"space-around"}},[n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.yesterday_order_num?t.homeData.yesterday_order_num:0))]),n("p",{staticClass:"msg2"},[t._v("昨日派单数")])]),t._v(" "),n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.yesterday_over_order_num?t.homeData.yesterday_over_order_num:0))]),n("p",{staticClass:"msg2"},[t._v("昨日成交单数")])]),t._v(" "),n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.yesterday_order_money?t.homeData.yesterday_order_money:0))]),n("p",{staticClass:"msg2"},[t._v("昨日派单金额")])]),t._v(" "),n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.yesterday_over_order_money?t.homeData.yesterday_over_order_money:0))]),n("p",{staticClass:"msg2"},[t._v("昨日交金额")])])],1)],1)],1)],1),t._v(" "),n("Row",{attrs:{gutter:16}},[n("Col",{style:{marginBottom:"10px"},attrs:{md:24,lg:24}},[n("Card",{attrs:{bordered:!1}},[n("div",{staticClass:"title"},[t._v("总交易信息")]),t._v(" "),n("Row",{staticClass:"code-row-bg margin-top-8",attrs:{type:"flex",justify:"space-around"}},[n("i-col",{attrs:{span:"3",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.order_num?t.homeData.order_num:0))]),n("p",{staticClass:"msg2"},[t._v("派单总单数")])]),t._v(" "),n("i-col",{attrs:{span:"3",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.over_order_num?t.homeData.over_order_num:0))]),n("p",{staticClass:"msg2"},[t._v("成交总单数")])]),t._v(" "),n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.order_money?t.homeData.order_money:0))]),n("p",{staticClass:"msg2"},[t._v("派单总金额")])]),t._v(" "),n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.over_order_money?t.homeData.over_order_money:0))]),n("p",{staticClass:"msg2"},[t._v("成交总金额")])]),t._v(" "),n("i-col",{attrs:{span:"4",align:"center"}},[n("p",{staticClass:"msg1"},[t._v(t._s(t.homeData.turnover_rate?t.homeData.turnover_rate:0))]),n("p",{staticClass:"msg2"},[t._v("总成交率")])])],1)],1)],1)],1)],1)},s=[];e._withStripped=!0,a.render=e,a.staticRenderFns=s}});