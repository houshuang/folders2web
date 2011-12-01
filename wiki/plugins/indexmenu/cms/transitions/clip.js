/*
*
*  Performas clip transformation
*
*  @application Complete Menu Solution
*  @author Ilya Lebedev <ilya@lebedev.net>
*  @copyright (c) 2006, Ilya Lebedev
*  @license Free for non-commercial use
*  @package CompleteMenuSolution
*  @title Clip transformation
*  @version 0.3.0.03032006
*
*  Revision history
*
*  0.3.1.03032006
*
*  % bug with multiple instance running
*  + support of 
*
*  0.3.0.03032006
*
*  % meet transition concept of Cms v0.4
*  - junk code
*  % uses 'moving toward 100%' convept, instead of number of fixed intervals
*  + initVal and doClip methods, to share them through transition methods
*
*  0.2.0.01032006
*  + support of much more pre-completed directions
*
*  0.1.0.31012006 First public release
*/
CompleteMenuSolution.prototype.transition.clip={directions:['s','se','e','ne','n','nw','w','sw','sn','ew','sen','enw','nws','wse','senw'],clipvalues:{'s':{'stTop':0,'enTop':0,'stBot':0,'enBot':100,'stLt':0,'enLt':0,'stRt':100,'enRt':100},'se':{'stTop':0,'enTop':0,'stBot':0,'enBot':100,'stLt':0,'enLt':0,'stRt':0,'enRt':100},'e':{'stTop':0,'enTop':0,'stBot':100,'enBot':100,'stLt':0,'enLt':0,'stRt':0,'enRt':100},'ne':{'stTop':100,'enTop':0,'stBot':100,'enBot':100,'stLt':0,'enLt':0,'stRt':0,'enRt':100},'n':{'stTop':100,'enTop':0,'stBot':100,'enBot':100,'stLt':0,'enLt':0,'stRt':100,'enRt':100},'nw':{'stTop':100,'enTop':0,'stBot':100,'enBot':100,'stLt':100,'enLt':0,'stRt':100,'enRt':100},'w':{'stTop':0,'enTop':0,'stBot':100,'enBot':100,'stLt':100,'enLt':0,'stRt':100,'enRt':100},'sw':{'stTop':0,'enTop':0,'stBot':0,'enBot':100,'stLt':100,'enLt':0,'stRt':100,'enRt':100},'sn':{'stTop':50,'enTop':0,'stBot':50,'enBot':100,'stLt':0,'enLt':0,'stRt':100,'enRt':100},'ew':{'stTop':0,'enTop':0,'stBot':100,'enBot':100,'stLt':50,'enLt':0,'stRt':50,'enRt':100},'sen':{'stTop':50,'enTop':0,'stBot':50,'enBot':100,'stLt':0,'enLt':0,'stRt':0,'enRt':100},'enw':{'stTop':0,'enTop':0,'stBot':0,'enBot':100,'stLt':50,'enLt':0,'stRt':50,'enRt':100},'nws':{'stTop':50,'enTop':0,'stBot':50,'enBot':100,'stLt':100,'enLt':0,'stRt':100,'enRt':100},'wse':{'stTop':100,'enTop':0,'stBot':100,'enBot':100,'stLt':50,'enLt':0,'stRt':50,'enRt':100},'senw':{'stTop':50,'enTop':0,'stBot':50,'enBot':100,'stLt':50,'enLt':0,'stRt':50,'enRt':100}},init:function(i,I,l){var o=i.transitions.clip.direction;if(typeof o=='string')o=[o];else if(!(o instanceof Array)||o.length==0)o=['se'];for(var O=0;O<o.length;O++){if(this.directions.indexOf(o[O])<0){o.splice(O,1);O--}}i.transitions.clip.direction=o;l['clipIncrement']='__clipIncrement'},initValues:function(i,I,l){i.style.visibility='hidden';i.style.display='block';var o=I.transitions.clip.direction,O;if(!o[i[l['menuLevel']]])O=o[o.length-1];else O=o[i[l['menuLevel']]];var Q=this.clipvalues[O];i[l['clipIncrement']]={};i[l['clipIncrement']].sTop=i.offsetHeight*Q['stTop']/100;i[l['clipIncrement']].eTop=i.offsetHeight*Q['enTop']/100-i[l['clipIncrement']].sTop;i[l['clipIncrement']].sBot=i.offsetHeight*Q['stBot']/100;i[l['clipIncrement']].eBot=i.offsetHeight*Q['enBot']/100-i[l['clipIncrement']].sBot;i[l['clipIncrement']].sLt=i.offsetWidth*Q['stLt']/100;i[l['clipIncrement']].eLt=i.offsetWidth*Q['enLt']/100-i[l['clipIncrement']].sLt;i[l['clipIncrement']].sRt=i.offsetWidth*Q['stRt']/100;i[l['clipIncrement']].eRt=i.offsetWidth*Q['enRt']/100-i[l['clipIncrement']].sRt;i.style.display='';i.style.visibility=''},doClip:function(i,I,l){var o=i[I['clipIncrement']].sTop+i[I['clipIncrement']].eTop*l;var O=i[I['clipIncrement']].sBot+i[I['clipIncrement']].eBot*l;var Q=i[I['clipIncrement']].sLt+i[I['clipIncrement']].eLt*l;var _=i[I['clipIncrement']].sRt+i[I['clipIncrement']].eRt*l;try{i.style.clip="rect("+o+"px "+_+"px "+O+"px "+Q+"px)"}catch(e){}},'initOpen':function(i,I,l,o){i.style.overflow='hidden';if(!i[o['clipIncrement']]){this.initValues.call(this,i,I,o)}this.doClip(i,o,i[o['interval']].pg_delta);},'playOpen':function(i,I,l,o){this.doClip(i,o,i[o['interval']].pg_delta);if(i[o['interval']].pg==100){return false}return true},finishOpen:function(i){i.style.overflow='';try{i.style.clip=''}catch(e){i.style.clip='rect(auto auto auto auto)'}},'initClose':function(i,I,l,o){i.style.overflow='hidden';if(!i[o['clipIncrement']]){this.initValues.call(this,i,I,o)}this.doClip(i,o,1-i[o['interval']].pg_delta);i.style.display='';i.style.visibility=''},'playClose':function(i,I,l,o){this.doClip(i,o,1-i[o['interval']].pg_delta);if(i[o['interval']].pg==100){return false}return true},finishClose:function(i){i.style.overflow='';try{i.style.clip=''}catch(e){i.style.clip='rect(0 0 0 0)'}}};
