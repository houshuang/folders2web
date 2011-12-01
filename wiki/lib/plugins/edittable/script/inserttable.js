function addBtnActionInsertTable(btn, param, edid) {
    addEvent(btn, 'click', function () {
        var editform = $('dw__editform');
        var ed = $(edid);

        function addField(name, val) {
            var pos_field = document.createElement('textarea');
            pos_field.name = 'edittable__new[' + name + ']';
            pos_field.value = val;
            pos_field.style.display = 'none';
            editform.appendChild(pos_field);
        }

        var sel = getSelection(ed);
        addField('pre', ed.value.substr(0, sel.start));
        addField('text', ed.value.substr(sel.start, sel.end - sel.start));
        addField('suf', ed.value.substr(sel.end));

        ed.parentNode.removeChild(ed);

        // Fake POST
        var editbutton = document.createElement('input');
        editbutton.name = 'do[edit]';
        editbutton.type = 'submit';
        editbutton.style.display = 'none';
        editform.appendChild(editbutton);
        // Prevent warning
        textChanged = false;
        editbutton.click();
    });
    return true;
}
