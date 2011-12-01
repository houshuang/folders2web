function doku_ajax(call, params) {
    var ajax = new sack(DOKU_BASE + 'lib/plugins/ajaxloader/ajax.php');
    if (!params) {
        params = {};
    } else if (params.tagName && params.tagName.toLowerCase() === 'form') {
        params = serialize_form(params);
    }
    if (call) {
        params.call = call;
    }
    var oldrunAJAX = ajax.runAJAX;

    /* Always apply given params before making a call. sack.runAJAX resets
       the param string. */
    ajax.runAJAX = function(more_params) {
        if (typeof more_params === 'object') {
            for (val in more_params) {
                if (more_params.hasOwnProperty(val)) {
                    params[val] = more_params[val];
                }
            }
            more_params = '';
        } else if (this.URLString.length > 0) {
            var strcache = this.URLString;
            this.URLString = '';
        }
        for (val in params) {
            if (params.hasOwnProperty(val)) {
                ajax.setVar(val, params[val]);
            }
        }
        if (typeof strcache !== undefined) {
            this.URLString += '&' + strcache;
        }
        return oldrunAJAX.call(this, more_params);
    };
    return ajax;
}

function serialize_form(form) {
    var data = {};
    var inps = form.elements;
    // FIXME: Perform more extensive form processing:
    //  http://www.w3.org/TR/html401/interact/forms.html#h-17.13
    for (var i = 0 ; i < inps.length ; ++i) {
        var name = inps[i].name || inps[i].id;
        if (!name) {
            continue;
        }
        data[name] = inps[i].value;
    }
    return data;
}
