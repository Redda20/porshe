if(typeof console=="undefined"||!console.log){console={log:function(){return false}}}jQuery(document).ready(function(){var c=jQuery;c(document).bind("ajaxComplete",function(f,g){if(g.status&&g.status==401){document.location=EE.BASE+"&"+g.responseText}});EE.create_searchbox=(function(){function g(i,k,j){i.setAttribute("type","search");c(i).attr({autosave:j,results:"10",placeholder:k})}function f(i,l){var k=c(i),j=k.css("color");k.focus(function(){k.css("color",j);(k.val()==l&&k.val(""))}).blur(function(){if(k.val()==""||k.val==l){k.val(l).css("color","#888")}}).trigger("blur")}var h=(parseInt(navigator.productSub)>=20020000&&navigator.vendor.indexOf("Apple Computer")!=-1)?g:f;return function(l,k,j){var i=document.getElementById(l);(i&&h(i,k,j))}})();EE.create_searchbox("cp_search_keywords","Search","ee_cp_search");EE.create_searchbox("template_keywords","Search Templates","ee_template_search");c('a[rel="external"]').click(function(){window.open(this.href);return false});function a(){var h=EE.SESS_TIMEOUT-60000,g=EE.XID_TIMEOUT-60000,f=(h<g)?h:g;setTimeout(i,f);function i(){var j='<form><div id="logOutWarning" style="text-align:center"><p>'+EE.lang.session_expiring+'</p><label for="username">'+EE.lang.username+'</label>: <input type="text" id="log_backin_username" name="username" value="" style="width:100px" size="35" dir="ltr" id="username" maxlength="32"  />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label for="password">'+EE.lang.password+'</label>: <input id="log_backin_password" type="password" name="password" value="" style="width:100px" size="32" dir="ltr" id="password" maxlength="32"  /> <input type="submit" id="submit" name="submit" value="'+EE.lang.login+'" class="submit" /><span id="logInSpinner"></span></div></form>';c.ee_notice(j,{type:"custom",open:true,close_on_click:false});logOutWarning=c("#logOutWarning");logOutWarning.find("#log_backin_username").focus();logOutWarning.find("input#submit").click(function(){var n=logOutWarning.find("input#log_backin_username").val(),l=logOutWarning.find("input#log_backin_password").val(),k=c(this),m=logOutWarning.find("span#logInSpinner");k.hide();m.html('<img src="'+EE.PATH_CP_GBL_IMG+'indicator.gif" />');c.ajax({type:"POST",dataType:"json",url:EE.BASE+"&C=login&M=authenticate&is_ajax=true",data:{username:n,password:l,XID:EE.XID},success:function(o){if(o.messageType=="success"){c("input[name='XID']").val(o.xid);logOutWarning.slideUp("fast");c.ee_notice(o.message,{type:"custom",open:true});EE.XID=o.xid;setTimeout(i,f)}else{if(o.messageType=="failure"){logOutWarning.before('<div id="loginCheckFailure">'+o.message+"</div>");m.hide("fast");k.css("display","inline")}}}});return false})}}a();function e(){var g={revealSidebarLink:"77%",hideSidebarLink:"100%"},i=c("#mainContent"),j=c("#sidebarContent"),h=i.height(),f=j.height();if(EE.CP_SIDEBAR_STATE=="off"){i.css("width","100%");c("#revealSidebarLink").css("display","block");c("#hideSidebarLink").hide();j.show();f=j.height();j.hide()}else{j.hide();h=i.height(),j.show()}var k=f>h?f:h;c("#revealSidebarLink, #hideSidebarLink").click(function(){var n=c(this),l=n.siblings("a"),m=(this.id=="revealSidebarLink");c("#sideBar").css({position:"absolute","float":"",right:"0"});n.hide();l.css("display","block");j.slideToggle();i.animate({width:g[this.id],height:m?k:h},function(){c("#sideBar").css({position:"","float":"right",})});return false})}e();if(EE.flashdata!==undefined){var d=c(".notice");types={success:"message_success",notice:"message",error:"message_failure"},show_notices=[];for(type in types){if(EE.flashdata.hasOwnProperty(types[type])){if(type=="error"){notice=d.filter(".failure").slice(0,1)}else{if(type=="success"){notice=d.filter(".success").slice(0,1)}else{notice=d.slice(0,1)}}if(EE.flashdata[types[type]]==notice.html()){show_notices.push({message:EE.flashdata[types[type]],type:type});notice.remove()}}}if(show_notices.length){c.ee_notice(show_notices)}}EE.notepad=(function(){var j=c("#notePad"),h=c("#notepad_form"),m=c("#sidebar_notepad_edit_desc"),g=c("#notePadTextEdit"),i=c("#notePadControls"),l=c("#notePadText").removeClass("js_show"),f=l.text(),k=g.val();return{init:function(){if(k){l.html(k.replace(/</ig,"&lt;").replace(/>/ig,"&gt;").replace(/\n/ig,"<br />"))}j.click(EE.notepad.show);i.find("a.cancel").click(EE.notepad.hide);h.submit(EE.notepad.submit);i.find("input.submit").click(EE.notepad.submit);g.autoResize()},submit:function(){k=c.trim(g.val());var n=k.replace(/</ig,"&lt;").replace(/>/ig,"&gt;").replace(/\n/ig,"<br />");g.attr("readonly","readonly").css("opacity",0.5);i.find("#notePadSaveIndicator").show();c.post(h.attr("action"),{notepad:k,XID:EE.XID},function(o){l.html(n||f).show();g.attr("readonly","").css("opacity",1).hide();i.hide().find("#notePadSaveIndicator").hide()},"json");return false},show:function(){if(i.is(":visible")){return false}var n="";if(l.hide().text()!=f){n=l.html().replace(/<br>/ig,"\n").replace(/&lt;/ig,"<").replace(/&gt;/ig,">")}i.show();g.val(n).show().height(0).focus().trigger("keypress")},hide:function(){l.show();g.hide();i.hide();return false}}})();EE.notepad.init();c("#accessoryTabs li a").click(function(){var f=c(this).parent("li"),g=c("#"+this.className);if(f.hasClass("current")){g.hide();f.removeClass("current")}else{if(f.siblings().hasClass("current")){g.show().siblings(":not(#accessoryTabs)").hide();f.siblings().removeClass("current")}else{g.slideDown()}f.addClass("current")}return false});function b(){var g=c("#search"),f=g.clone(),h=c("#cp_search_form").find(".searchButton");submit_handler=function(){var i=c(this).attr("action"),j={cp_search_keywords:c("#cp_search_keywords").attr("value")};c.ajax({url:i+"&ajax=y",data:j,beforeSend:function(){h.toggle()},success:function(k){h.toggle();g=g.replaceWith(f);f.html(k);c("#cp_reset_search").click(function(){f=f.replaceWith(g);c("#cp_search_form").submit(submit_handler);c("#cp_search_keywords").select();return false})},dataType:"html"});return false};c("#cp_search_form").submit(submit_handler)}b();c("h4","#quickLinks").click(function(){window.location.href=EE.BASE+"&C=myaccount&M=quicklinks"}).add("#notePad").hover(function(){c(".sidebar_hover_desc",this).show()},function(){c(".sidebar_hover_desc",this).hide()}).css("cursor","pointer");c("#activeUser").one("mouseover",function(){var k=c('<div id="logOutConfirm">'+EE.lang.logout_confirm+" </div>"),g=30,i=g,m;function l(){c.ajax({url:EE.BASE+"&C=login&M=logout",async:(!c.browser.safari)});window.location=EE.BASE+"&C=login&M=logout"}function h(){if(g<1){return setTimeout(l,0)}else{if(g==i){c(window).bind("unload.logout",l)}}k.dialog("option","title",EE.lang.logout+" ("+(g--||"...")+")");m=setTimeout(h,1000)}function f(){clearTimeout(m);c(window).unbind("unload.logout");g=i}var j={};j.Cancel=function(){c(this).dialog("close")};j[EE.lang.logout]=l;k.dialog({autoOpen:false,resizable:false,modal:true,title:EE.lang.logout,position:"center",minHeight:"0px",buttons:j,beforeclose:f});c("a.logOutButton",this).click(function(){c("#logOutConfirm").dialog("open");c(".ui-dialog-buttonpane button:eq(2)").focus();h();return false})});c(".js_show").show()});