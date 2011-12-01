/*
*
*  Fixes blending for IE
*
*  @package CompleteMenuSolution
*  @author Ilya Lebedev <ilya.lebedev.net>
*
*/
CompleteMenuSolution.prototype.modifier.blendiebugfix={runat:'ul',mod:function(i,I,l,o){var O=document.createElementExt('div',{'class':'blendIeBugfix'});i[I['parentNode']].replaceChild(O,i);O.appendChild(i);}};
