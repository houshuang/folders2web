/*
*  This library appends to DOM interface IE-proprietary extensions
*
*  @version 1.1
*  @title DOMExtensions
*  @author Ilya Lebedev (ilya@lebedev.net), (c) 2004-2005
*  @license GNU LGPL
*
*  This library is free software; you can redistribute it and/or
*  modify it under the terms of the GNU Lesser General Public
*  License as published by the Free Software Foundation; either
*  version 2.1 of the License, or (at your option) any later version.
*  See http://www.gnu.org/copyleft/lesser.html
*/
if(Node&&!Node.prototype){var node=document.createTextNode('');var Node=node.constructor}if(window.Node){Node.prototype.removeNode=function(i){var I=this;if(Boolean(i))return this.parentNode.removeChild(I);else{var l=document.createRange();l.selectNodeContents(I);return this.parentNode.replaceChild(l.extractContents(),I);}};Node.prototype.swapNode=function(i){var I=this;n=I.cloneNode(true);nt=i.cloneNode(true);I.parentNode.insertBefore(nt,I);I.removeNode(true);i.parentNode.insertBefore(n,i);i.removeNode(true);};if(!Node.prototype.attachEvent)Node.prototype.attachEvent=function(i,I,l){var o=this;return o.addEventListener(i.substr(2),I,false);};if(!Node.prototype.fireEvent)Node.prototype.fireEvent=function(i){var I={resize:['HTMLEvents',1,0],scroll:['HTMLEvents',1,0],focusin:['HTMLEvents',0,0],focusout:['HTMLEvents',0,0],gainselection:['HTMLEvents',1,0],loseselection:['HTMLEvents',1,0],activate:['HTMLEvents',1,1],load:['HTMLEvents',0,0],unload:['HTMLEvents',0,0],abort:['HTMLEvents',1,0],error:['HTMLEvents',1,0],select:['HTMLEvents',1,0],change:['HTMLEvents',1,0],submit:['HTMLEvents',1,1],reset:['HTMLEvents',1,0],focus:['HTMLEvents',0,0],blur:['HTMLEvents',0,0],click:['MouseEvents',1,1],mousedown:['MouseEvents',1,1],mouseup:['MouseEvents',1,1],mouseover:['MouseEvents',1,1],mousemove:['MouseEvents',1,0],mouseout:['MouseEvents',1,0],keypress:['KeyEvents',1,1],keydown:['KeyEvents',1,1],keyup:['KeyEvents',1,1],DOMSubtreeModified:['MutationEvents',1,0],DOMNodeInserted:['MutationEvents',1,0],DOMNodeRemoved:['MutationEvents',1,0],DOMNodeRemovedFromDocument:['MutationEvents',0,0],DOMNodeInsertedIntoDocument:['MutationEvents',0,0],DOMAttrModified:['MutationEvents',1,0],DOMCharacterDataModified:['MutationEvents',1,0]};var l=this;i=i.substr(2);if(!I[i])return false;var o=document.createEvent(I[i][0]);o.initEvent(i,I[i][1],I[i][2]);return l.dispatchEvent(o);}}if(!window.attachEvent){window.attachEvent=function(i,I,l){var o=this;if(o.addEventListener)o.addEventListener(i.substr(2),I,false);else o[i]=I}}
