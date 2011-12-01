/*
*  Does emulate of .call method for IE5
*
*  @return mixed execution result
*  @access public
*/
if('undefined'==typeof Function.call)Function.prototype.call=function(){var context,oldprop,s=[],i=1,aL=arguments.length;if(arguments.length==0)context=window;else context=arguments[0];oldprop=context.______________tmp______________;context.______________tmp______________=this;for(;i<aL;i++)s[s.length]='arguments['+i+']';var ret=eval("context.______________tmp______________("+s.join(",")+")");context.______________tmp______________=oldprop;return ret};if('undefined'==typeof Function.apply)Function.prototype.apply=function(){var context,oldprop,s=[],i=0,aL=arguments[1].length;if(arguments.length==0)context=window;else context=arguments[0];oldprop=context.______________tmp______________;context.______________tmp______________=this;for(;i<aL;i++)s[s.length]='arguments[1]['+i+']';var ret=eval("context.______________tmp______________("+s.join(",")+")");context.______________tmp______________=oldprop;return ret};
