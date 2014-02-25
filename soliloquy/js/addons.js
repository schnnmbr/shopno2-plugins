jQuery(document).ready(function(b){b("#soliloquy-addon-area").on("click.refreshInstallAddon",".soliloquy-addon-action-button",function(f){var c=b(this);var d=b("#soliloquy-addon-area").find(".soliloquy-addon-action-button");b.each(d,function(g,e){if(c==e){return true}a(e)})});b("#soliloquy-addon-area").on("click.activateAddon",".soliloquy-activate-addon",function(i){i.preventDefault();b(".soliloquy-addon-error").remove();b(this).text(soliloquy_addon.activating);b(this).after('<span class="soliloquy-waiting"><img class="soliloquy-spinner" src="'+soliloquy_addon.spinner+'" width="16px" height="16px" style="margin-left: 6px; vertical-align: middle;" /></span>');var c=b(this);var h=b(this).attr("rel");var d=b(this).parent().parent();var g=b(this).parent().parent().find(".addon-status");var f={url:ajaxurl,type:"post",async:true,cache:false,dataType:"json",data:{action:"soliloquy_activate_addon",nonce:soliloquy_addon.activate_nonce,plugin:h},success:function(e){if(e&&true!==e){b(d).slideDown("normal",function(){b(this).after('<div class="soliloquy-addon-error"><strong>'+e.error+"</strong></div>");b(".soliloquy-waiting").remove();b(".soliloquy-addon-error").delay(3000).slideUp()});return}b(c).text(soliloquy_addon.deactivate).removeClass("soliloquy-activate-addon").addClass("soliloquy-deactivate-addon");b(g).text(soliloquy_addon.active);b(d).removeClass("soliloquy-addon-inactive").addClass("soliloquy-addon-active");b(".soliloquy-waiting").remove()},error:function(k,l,j){b(".soliloquy-waiting").remove();return}};b.ajax(f)});b("#soliloquy-addon-area").on("click.deactivateAddon",".soliloquy-deactivate-addon",function(i){i.preventDefault();b(".soliloquy-addon-error").remove();b(this).text(soliloquy_addon.deactivating);b(this).after('<span class="soliloquy-waiting"><img class="soliloquy-spinner" src="'+soliloquy_addon.spinner+'" width="16px" height="16px" style="margin-left: 6px; vertical-align: middle;" /></span>');var c=b(this);var h=b(this).attr("rel");var d=b(this).parent().parent();var g=b(this).parent().parent().find(".addon-status");var f={url:ajaxurl,type:"post",async:true,cache:false,dataType:"json",data:{action:"soliloquy_deactivate_addon",nonce:soliloquy_addon.deactivate_nonce,plugin:h},success:function(e){if(e&&true!==e){b(d).slideDown("normal",function(){b(this).after('<div class="soliloquy-addon-error"><strong>'+e.error+"</strong></div>");b(".soliloquy-waiting").remove();b(".soliloquy-addon-error").delay(3000).slideUp()});return}b(c).text(soliloquy_addon.activate).removeClass("soliloquy-deactivate-addon").addClass("soliloquy-activate-addon");b(g).text(soliloquy_addon.inactive);b(d).removeClass("soliloquy-addon-active").addClass("soliloquy-addon-inactive");b(".soliloquy-waiting").remove()},error:function(k,l,j){b(".soliloquy-waiting").remove();return}};b.ajax(f)});b("#soliloquy-addon-area").on("click.installAddon",".soliloquy-install-addon",function(j){j.preventDefault();b(".soliloquy-addon-error").remove();b(this).text(soliloquy_addon.installing);b(this).after('<span class="soliloquy-waiting"><img class="soliloquy-spinner" src="'+soliloquy_addon.spinner+'" width="16px" height="16px" style="margin-left: 6px; vertical-align: middle;" /></span>');var c=b(this);var h=b(this).attr("rel");var d=b(this).parent().parent();var g=b(this).parent().parent().find(".addon-status");var i=soliloquy_addon.pagehook.split("soliloquy_page_");var f={url:ajaxurl,type:"post",async:true,cache:false,dataType:"json",data:{action:"soliloquy_install_addon",nonce:soliloquy_addon.install_nonce,plugin:h,hook:i[1]},success:function(e){if(e.error){b(d).slideDown("normal",function(){b(this).after('<div class="soliloquy-addon-error"><strong>'+e.error+"</strong></div>");b(c).text(soliloquy_addon.install);b(".soliloquy-waiting").remove();b(".soliloquy-addon-error").delay(4000).slideUp()});return}if(e.form){b(d).slideDown("normal",function(){b(this).after('<div class="soliloquy-addon-error">'+e.form+"</div>");b(".soliloquy-waiting").remove()});b(c).attr("disabled",true);b("#soliloquy-addon-area").on("click.installCredsAddon","#upgrade",function(p){p.preventDefault();b(".soliloquy-waiting").remove();b(this).val(soliloquy_addon.installing);b(this).after('<span class="soliloquy-waiting"><img class="soliloquy-spinner" src="'+soliloquy_addon.spinner+'" width="16px" height="16px" style="margin-left: 6px; vertical-align: text-bottom;" /></span>');var l=b(this).parent().parent().find("#hostname").val();var q=b(this).parent().parent().find("#username").val();var m=b(this).parent().parent().find("#password").val();var o=b(this);var k=b(this).parent().parent().parent().parent();var n={url:ajaxurl,type:"post",async:true,cache:false,dataType:"json",data:{action:"soliloquy_install_addon",nonce:soliloquy_addon.install_nonce,plugin:h,hook:i[1],hostname:l,username:q,password:m},success:function(r){if(r.error){b(d).slideDown("normal",function(){b(c).after('<div class="soliloquy-addon-error"><strong>'+r.error+"</strong></div>");b(c).text(soliloquy_addon.install);b(".soliloquy-waiting").remove();b(".soliloquy-addon-error").delay(4000).slideUp()});return}if(r.form){b(".soliloquy-waiting").remove();b(".soliloquy-inline-error").remove();b(o).val(soliloquy_addon.proceed);b(o).after('<span class="soliloquy-inline-error">'+soliloquy_addon.connect_error+"</span>");return}b(k).remove();b(c).show();b(c).text(soliloquy_addon.activate).removeClass("soliloquy-install-addon").addClass("soliloquy-activate-addon");b(c).attr("rel",r.plugin);b(c).removeAttr("disabled");b(g).text(soliloquy_addon.inactive);b(d).removeClass("soliloquy-addon-not-installed").addClass("soliloquy-addon-inactive");b(".soliloquy-waiting").remove()},error:function(s,t,r){b(".soliloquy-waiting").remove();return}};b.ajax(n)});return}b(c).text(soliloquy_addon.activate).removeClass("soliloquy-install-addon").addClass("soliloquy-activate-addon");b(c).attr("rel",e.plugin);b(g).text(soliloquy_addon.inactive);b(d).removeClass("soliloquy-addon-not-installed").addClass("soliloquy-addon-inactive");b(".soliloquy-waiting").remove()},error:function(l,m,k){b(".soliloquy-waiting").remove();return}};b.ajax(f)});function a(c){if(b(c).attr("disabled")){b(c).removeAttr("disabled")}if(b(c).parent().parent().hasClass("soliloquy-addon-not-installed")){b(c).text(soliloquy_addon.install)}}});