/*
*  $Id: activeontop.js 51 2006-07-25 22:11:49Z wingedfox $
*  $HeadURL: https://svn.debugger.ru/repos/CompleteMenuSolution/tags/v0.5.12/modifiers/activeontop.js $
*
*  Activeontop modifier adjusts 
*
*
*  @package CompleteMenuSolution
*  @author Ilya Lebedev <ilya.lebedev.net>
*  @lastmodifier $Author: wingedfox $
*  @title Ajaxum
*  @version $Rev: 51 $
*/
CompleteMenuSolution.prototype.modifier.activeontop={runat:'root',maxZIndex:0,mod:function(i,I){var l=this;var o=function(O){var i=O.srcElement||O.target;l.maxZIndex++;while(i&&(!i[I['parentNode']]||i[I['isRoot']]))i=i.parentNode;if(!i)return;while(i[I['parentNode']]){if(i[I['isFolder']]){i.style.zIndex=l.maxZIndex}i=i[I['parentNode']]}if(i[I['isRoot']])i.style.zIndex=l.maxZIndex};i.attachEvent('onmouseover',o);}};
