/**
 * Javascript for tagindex management
 *
 * @author Gina Haeussge, Michael Klier <dokuwiki@chimeric.de>
 * @author Andreas Gohr <andi@splitbrain.org>
 */

/**
 * Class to hold some values
 */


function plugin_tagindex_class(){
    this.id = null;
    this.page = null;
    this.range = null;
    this.sack = null;
    this.done = 1;
}
var pl_si = new plugin_tagindex_class();
pl_si.sack = new sack(DOKU_BASE + 'lib/plugins/quickedit/ajax.php');
pl_si.sack.AjaxFailedAlert = '';
pl_si.sack.encodeURIString = false;

/**
 * Gives textual feedback
 */

function plugin_quickedit_return()
{
	data = this.response;
	if (data == 0)
	{
		document.getElementById("load"+pl_si.id).style.display = 'none';
		document.getElementById("quickedit_start"+pl_si.id).style.display = 'block';
		document.getElementById("old").value='0';
		return;
	}
	else
	{
		if(data.match(/[\n]/g))
		{
			var nbRetourChariot = data.match(/[\n]/g).length;
		}
		else
		{
			var nbRetourChariot = 0;
		}
		document.getElementById("quickedit_textbox"+pl_si.id).value = data;
		document.getElementById("quickedit_textbox"+pl_si.id).rows = nbRetourChariot;	
		document.getElementById("load"+pl_si.id).style.display = 'none';
		document.getElementById("quickedit_start"+pl_si.id).style.display= 'none';
		document.getElementById("quickedit_stop"+pl_si.id).style.display = 'block';
		
	}
}

function quickedit_save(id, range, page)
{
	pl_si.sack.onCompletion = quickedit_save_cb;
    pl_si.sack.URLString = '';
    pl_si.id=id;
    pl_si.page=page;
    minor=document.getElementById("minoredit"+id).value;
	text=document.getElementById("quickedit_textbox"+id).value;
	sub=document.getElementById("editsummary"+id).value;
	pl_si.sack.runAJAX('call=save_page&range='+range+'&page='+page+'&text='+text.replace(/&/g, '%26')+'&sub='+sub.replace(/&/g, '%26')+'&minor='+minor);
}

function quickedit_save_cb()
{
	document.getElementById("quickedit_start"+pl_si.id).style.display = 'block';
	document.getElementById("quickedit_stop"+pl_si.id).style.display = 'none';
	document.location.href="doku.php?id="+pl_si.page;
	document.getElementById("old").value = '0';
}

function plugin_quickedit_ok()
{
	data = this.response;
	if (data == 0)
		return;
	else
		{
			pl_si.sack.onCompletion = plugin_quickedit_return;
			pl_si.sack.URLString = '';
			pl_si.sack.runAJAX('call=get_text&range='+pl_si.range+'&page='+pl_si.page);
			document.getElementById("quickedit_start"+pl_si.id).style.display = 'none';
			document.getElementById("load"+pl_si.id).style.display = 'block';
		}
}

function plugin_quickedit_go(id,range,page, adm){
	if(document.getElementById("old").value == '0'){
		document.getElementById("old").value = id;
		pl_si.sack.onCompletion = plugin_quickedit_ok;
		pl_si.sack.URLString = '';
		pl_si.range = range;
		pl_si.id=id;
		pl_si.page=page;
		pl_si.sack.runAJAX('call=get_auth');
//    document.getElementById("quickedit_start"+id).style.display = 'none';
//    document.getElementById("load"+id).style.display = 'block';
	}
}
	
function quickedit_cancel(id, range, page)
{
	document.getElementById("quickedit_start"+id).style.display = 'block';
	document.getElementById("quickedit_stop"+id).style.display = 'none';
	document.getElementById("old").value='0';
}


//Setup VIM: ex: et ts=4 enc=utf-8 :
