/*
*  $Id: ajaxum.js 96 2007-02-13 18:12:53Z wingedfox $
*  $HeadURL: https://svn.debugger.ru/repos/CompleteMenuSolution/tags/v0.5.12/modifiers/ajaxum.js $
*
*  Ajaxum is the small modifier to bring Ajax-driven dynamic menus to CMS.
*
*
*  @package CompleteMenuSolution
*  @author Ilya Lebedev <ilya.lebedev.net>
*  @lastmodifier $Author: wingedfox $
*  @title Ajaxum
*  @version $Rev: 96 $
*/
CompleteMenuSolution.prototype.modifier.ajaxum={runat:'ul',menuOptions:{ajaxum:{'fetcher':null,'error':{'callback':"Error: no callback method specified",'resource':"Error: no resource specified",'fetch':"Error: could not fetch data from the server"},'message':{'loading':"Loading..."}}},init:function(i,I,l){i.handlers.onOpen.push([this,this.openhandler]);l.ajaxumTarget='__ajaxumTarget'},mod:function(i,I,l,o){if((!i.firstChild||i.firstChild.nodeType!=8)&&!i.title)i[I.ajaxumTarget]=null;else{i[I.ajaxumTarget]=i.title||i.firstChild.nodeValue.replace(/(^\s*)|(\s*$)/g,"");i.innerHTML="";i.title=""}},openhandler:function(el,keys,css,mo){el=el[keys.submenu];if(el.childNodes.length&&!el[keys.ajaxumTarget])return;var showmsg=function(i,I){el.appendChild(document.createElementExt('span',{'class':i,'child':[document.createTextNode(I)]}));};var callback=function(i){el.innerHTML="";if(!i.state){showmsg('ajaxum_error',i.response?"Server Error: "+i.response:mo.ajaxum.error.fetch);return}el.innerHTML=i.response;el[keys.ajaxumTarget]=null;var I=getParent(el,keys.isRoot,true);if(I)I[keys['cmsSelf']].reinitSubmenu(el[keys.parentNode]);};if(!el[keys.ajaxumTarget]){if(null==el[keys.ajaxumTarget]){el.innerHTML="";showmsg('ajaxum_error',mo.ajaxum.error.resource);}return}el.innerHTML="";if(!mo.ajaxum||!(mo.ajaxum.fetcher instanceof Function)){showmsg('ajaxum_error',mo.ajaxum.error.callback);return}try{showmsg('ajaxum_loading',mo.ajaxum.message.loading);mo.ajaxum.fetcher(el[keys.ajaxumTarget],callback);}catch(e){showmsg('ajaxum_error',mo.ajaxum.error.fetch);return}}};
