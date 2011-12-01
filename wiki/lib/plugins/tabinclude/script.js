function plugin_ti_class(){
    this.response = null;
    this.sack = null;
}
var pl_ti = new plugin_ti_class();
pl_ti.sack = new sack(DOKU_BASE + 'lib/plugins/tabinclude/ajax.php');
pl_ti.sack.AjaxFailedAlert = '';
pl_ti.sack.encodeURIString = false;

function pl_ti_onTabClicked(page){
  if(page){
    document.getElementById('ti_content').style.visibility='hidden';
    document.getElementById('ti_loading').style.display='block';
    pl_ti.sack.onCompletion = plugin_ti_refreshcontent;
    pl_ti.sack.URLString = '';
    pl_ti.sack.runAJAX('call=content&page='+encodeURI(page));
  }
}

function plugin_ti_refreshcontent(){
  data = this.response;
  document.getElementById('ti_content').innerHTML = data;
  document.getElementById('ti_loading').style.display='none';
  document.getElementById('ti_content').style.visibility='visible';
}

function plugin_ti_showinitialpage(){
  pageObj = document.getElementById('ti_initpage');
  if(pageObj){
    page = pageObj.value;
    document.getElementById("ti_content").style.visibility='hidden';
    document.getElementById("ti_loading").style.display='block';
    pl_ti.sack.onCompletion = plugin_ti_refreshcontent;
    pl_ti.sack.URLString = '';
    pl_ti.sack.runAJAX('call=content&page='+encodeURI(page));
  }
}
addInitEvent(plugin_ti_showinitialpage);
