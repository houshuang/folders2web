/*
*
*  Used to set group of checkboxes in the list
*
*  @package CompleteMenuSolution
*  @author Ilya Lebedev <ilya.lebedev.net>
*
*/
CompleteMenuSolution.prototype.modifier.groupcheckbox={runat:'a',mod:function(i,I){var l=function(e){var i=e.srcElement||e.target;if(!i.tagName||i.tagName.toLowerCase()!='input'||i.type.toLowerCase()!='checkbox')return;var v=O(i[I['parentNode']][I['parentNode']][I['submenu']],true);i.checked=!i.checked;if(!v){i.checked=true}else if(i.checked==false&&v){O(i[I['parentNode']][I['parentNode']][I['submenu']],false);}Q(i[I['parentNode']][I['parentNode']][I['parentNode']][I['parentNode']],i.checked);if(e.stopPropagation)e.stopPropagation();e.cancelBubble=true};var o=function(e){var i=e.srcElement||e.target;if(i.tagName&&i.tagName.toLowerCase()=='input'){if(e.preventDefault)e.preventDefault();e.returnValue=false}};var O=function(i,e){var v=true;if(i){if(i&&i.tagName.toLowerCase()=='ul'){for(var c=0,V=i[I['submenu']].length;c<V;c++){v&=O(i[I['submenu']][c],e);}}else{v=i[I['activator']].__groupCheckbox.checked==true;i[I['activator']].__groupCheckbox.checked=e;v&=O(i[I['submenu']],e);}}return v};var Q=function(i,e){var v=true;while(i){if(i[I['submenu']][I['submenu']]){for(var c=0,V=i[I['submenu']][I['submenu']].length;c<V;c++){v&=i[I['submenu']][I['submenu']][c][I['activator']].__groupCheckbox.checked==e}}if(!e||v){i[I['activator']].__groupCheckbox.checked=e}else{return}i=i[I['parentNode']][I['parentNode']]}};var _=i.getElementsByTagName('input');for(var c=0,C=_.length;c<C;c++){if(_[c].type.toLowerCase()=='checkbox'){_[c][I['parentNode']]=i;i.attachEvent('onmouseup',l);i.attachEvent('onclick',o);i.__groupCheckbox=_[c]}}}};
