/*
*
*  Check window borders and applies cpecific CSS classes
*  when needed
*
*  @package CompleteMenuSolution
*  @author Ilya Lebedev <ilya.lebedev.net>
*
*/
CompleteMenuSolution.prototype.transition.windowborderschecker={cssClasses:['FolderOverflowRight','FolderOverflowLeft','FolderOverflowBottom','FolderOverflowTop'],getOffset:function(i){var I=[i.offsetLeft,i.offsetTop];if(i.offsetParent){var l=this.getOffset(i.offsetParent);I[0]+=l[0];I[1]+=l[1]}return I},getWindowWH:function(){var i=0,I=0;if(typeof(window.innerWidth)=='number'){i=window.innerWidth;I=window.innerHeight}else if(document.documentElement&&(document.documentElement.clientWidth||document.documentElement.clientHeight)){i=document.documentElement.clientWidth;I=document.documentElement.clientHeight}else if(document.body&&(document.body.clientWidth||document.body.clientHeight)){i=document.body.clientWidth;I=document.body.clientHeight}return[i,I]},getScrollXY:function(){var i=0,I=0;if(typeof(window.pageYOffset)=='number'){I=window.pageYOffset;i=window.pageXOffset}else if(document.body&&(document.body.scrollLeft||document.body.scrollTop)){I=document.body.scrollTop;i=document.body.scrollLeft}else if(document.documentElement&&(document.documentElement.scrollLeft||document.documentElement.scrollTop)){I=document.documentElement.scrollTop;i=document.documentElement.scrollLeft}return[i,I]},'initOpen':function(i,I,l,o){var O=i.className.split(" ");var Q=this.cssClasses;for(var _=0,c=Q.length;_<c;_++){O.splice(O.indexOf(Q[_]),1);}var C=this.getOffset(i),e=this.getWindowWH(),v=this.getScrollXY();if(i.offsetWidth+C[0]>e[0]+v[0])O[O.length]=Q[0];if(C[0]<0)O[O.length]=Q[1];if(i.offsetHeight+C[1]>e[1]+v[1])O[O.length]=Q[2];if(C[1]<0)O[O.length]=Q[3];i.className=O.join(" ");},finishClose:function(i){var I=i.className.split(" ");for(var l=0,o=this.cssClasses.length;l<o;l++){I.splice(I.indexOf(this.cssClasses[l]),1);}i.className=I.join(" ");}};
