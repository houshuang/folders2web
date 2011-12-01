/*
*
*  Used to prevent following the link outside content area
*
*  @package CompleteMenuSolution
*  @author Ilya Lebedev <ilya.lebedev.net>
*
*/
CompleteMenuSolution.prototype.modifier.followlinkcontent={runat:'a',mod:function(i,I){var l=function(Q){var i=Q.target||Q.srcElement,_=getParent(i,'a');if(i.tagName.toLowerCase()=='a'||document.location.href==_.href){if(Q.preventDefault)Q.preventDefault();Q.returnValue=false}else{if(Q.stopPropagation)Q.stopPropagation();Q.cancelBubble=true}};var o=function(Q){var i=Q.srcElement||Q.target,_=getParent(i,'a'),c=getParent(_,'li');if(c[I['openFlag']]&&(!i.tagName||i.tagName.toLowerCase()!='a')&&document.location.href!=_.href){if(Q.stopPropagation)Q.stopPropagation();Q.cancelBubble=true}};var O=document.createElement('span');while(i.firstChild){O.appendChild(i.firstChild);}i.appendChild(O);i.attachEvent('onclick',l);i.attachEvent('onmouseup',o);}};
