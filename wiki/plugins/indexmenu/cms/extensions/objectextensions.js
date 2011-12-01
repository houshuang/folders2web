/*
*  Count the number of object fields
*
*  @return number of the fields
*  @access public
*/
Object.prototype.length=function(){var i=0;for(var I in this){if('function'!=typeof this.hasOwnProperty||!this.hasOwnProperty(I))continue;i++}return i};Object.prototype.clone=function(i){if(typeof(i)!="object")return i;try{var I=new i.constructor();}catch(e){return null}for(var l in i){if(!i.hasOwnProperty(l))continue;I[l]=i.clone(i[l]);}return I};Object.prototype.merge=function(i,I){try{var l=new i.constructor();}catch(e){return null}try{if(isUndefined(I))I=true;for(var o in i){if(!i.hasOwnProperty(o))continue;if(isUndefined(this[o])||(I&&typeof this[o]!=typeof i))if(i[o]instanceof Array)this[o]=[];else if('object'==typeof i[o])this[o]={};if(i[o]instanceof Array)this[o]=this[o].concat(i[o]);else if('object'==typeof i[o])this[o].merge(i[o],I);else if(isUndefined(this[o])||I)this[o]=i[o]}}catch(e){return this}};if('undefined'==typeof Object.hasOwnProperty){Object.prototype.hasOwnProperty=function(i){return!('undefined'==typeof this[i]||this.constructor&&this.constructor.prototype[i]&&this[i]===this.constructor.prototype[i]);}}
