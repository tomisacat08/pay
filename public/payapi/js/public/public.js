function publicUrl(){url="/nnc.php";return url}function publicRedirectFirst(){redirectUrl="/eos.jsp";return redirectUrl}function getQueryStr(str,locString){var reg=new RegExp("(^|&)"+str+"=([^&]*)(&|$)");var r=window.location.search.substr(1).match(reg);if(r!=null){return unescape(r[2])}return""}function getQueryStrOne(locString){var arr=locString.split("?");var oM="";if(locString.indexOf("&")){oM=arr[1].split("&")[0].split("=")[1]}else{oM=arr[1].split("=")[1]}return oM}function xiaoshu(obj){obj.value=obj.value.replace(/[^\d.]/g,"");obj.value=obj.value.replace(/\.{2,}/g,".");obj.value=obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");obj.value=obj.value.replace(/^(\-)*(\d+)\.(\d\d).*$/,"$1$2.$3");if(obj.value.indexOf(".")<0&&obj.value!=""){obj.value=parseFloat(obj.value)}}function zhengshu(obj){if(obj.value.substr(0)=="0"){obj.value=obj.value.replace(/[^1-9]/g,"")}obj.value=obj.value.replace(/\D/g,"")};