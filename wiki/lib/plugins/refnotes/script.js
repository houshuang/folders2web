(function() {
    /* Based on http://htmlcoder.visions.ru/JavaScript/?11 */
    var cssComp = document.compatMode && (document.compatMode == "CSS1Compat");
    var canvas = document.getElementsByTagName(cssComp ? "html" : "body")[0];
    var floater = null;
    var floaterWidth = 0;
    var floaterHeight = 0;
    var tracking = false;
    var shown = false;
    var timer = null;

    var preview = {
        createFloater: function() {
            floater = document.createElement('div');
            floater.id = 'insitu__fn';
            floater.className = 'insitu-footnote JSpopup dokuwiki';
            floater.style.position = 'absolute';
            floater.style.left = '0px';
            floater.style.top = '-100px';

            // autoclose on mouseout - ignoring bubbled up events
            addEvent(floater, 'mouseout', function(event) {
                if (event.target != floater) {
                    event.stopPropagation();
                    return;
                }

                // check if the element was really left
                var offsetX = event.pageX ? event.pageX - findPosX(floater) : event.offsetX;
                var offsetY = event.pageY ? event.pageY - findPosY(floater) : event.offsetY;
                var msieDelta = event.pageX ? 0 : 1;
                var width = floater.offsetWidth - msieDelta;
                var height = floater.offsetHeight - msieDelta;

                if ((offsetX > 0) && (offsetX < width) && (offsetY > 0) && (offsetY < height)) {
                    // we're still inside boundaries
                    event.stopPropagation();
                    return;
                }

                preview.hide();
            });

            document.body.appendChild(floater);
        },

        getFloater: function() {
            if (!floater) {
                floater = $('insitu__fn');
                if (!floater) {
                    this.createFloater();
                }
            }

            return floater;
        },

        measureFloater: function() {
            floaterWidth = 0;
            floaterHeight = 0;
            var floater = this.getFloater();
            if (floater) {
                var width = window.event ? floater.clientWidth : floater.offsetWidth;
                var height = window.event ? floater.clientHeight : floater.offsetHeight;

                if (width && height) {
                    // add CSS padding
                    floaterWidth = 10 + width;
                    floaterHeight = 10 + height;
                }
            }
        },

        setNoteId: function(id) {
            var floater = this.getFloater();
            // locate the note span element
            var note = $(id + ':text');
            if (!floater || !note) {
                return false;
            }

            // get the note HTML
            var html = note.innerHTML;
            // prefix ids on any elements to ensure they remain unique
            html.replace(/\bid=\"(.*?)\"/gi, 'id="refnotes-preview-$1');
            // now put the content into the floater
            floater.innerHTML = html;

            // display hidden tooltip so we can measure it's size
            floater.style.visibility = 'hidden';
            floater.style.display = '';
            floater.style.left = '0px';
            floater.style.top = '0px';

            this.measureFloater();
            if (floaterWidth && ((floaterWidth / canvas.clientWidth) > 0.45)) {
                // simulate max-width in IE
                floater.style.width = '40%';
                this.measureFloater();
            }

            return true;
        },

        show: function() {
            var floater = this.getFloater();
            if (floater) {
                floater.style.visibility = 'visible';
                floater.style.display = '';
            }

            shown = true;
        },

        hide: function() {
            var floater = this.getFloater();
            if (floater) {
                floater.style.display = 'none';
                floater.style.width = '';
            }

            floaterWidth = 0;
            floaterHeight = 0;
            shown = false;
        },

        move: function(x, y, dx, dy) {
            var floater = this.getFloater();
            if (!floater) {
                return;
            }

            var windowWidth = canvas.clientWidth + canvas.scrollLeft;
            var windowHeight = canvas.clientHeight + canvas.scrollTop;

            if (!floaterWidth || !floaterHeight) {
                this.measureFloater();
            }

            x += dx;
            if ((x + floaterWidth) > windowWidth) {
                x -= dx + 2 + floaterWidth;
            }

            y += dy;
            if ((y + floaterHeight) > windowHeight ) {
                y -= dy + 2 + floaterHeight;
            }

            floater.style.left = x + 'px';
            floater.style.top = y + 'px';
        }
    };

    function getNoteId(event) {
        return event.target.href.replace(/^.*?#([\w:]+)$/gi, '$1');
    }

    function getEventX(event) {
        return event.pageX ? event.pageX : event.offsetX;
    }

    function getEventY(event) {
        return event.pageY ? event.pageY : event.offsetY;
    }

    plugin_refnotes = {
        popup: {
            show: function(event) {
                plugin_refnotes.tooltip.hide(event);
                if (!preview.setNoteId(getNoteId(event))) {
                    return;
                }
                // position the floater and make it visible
                preview.move(getEventX(event), getEventY(event), 2, 2);
                preview.show();
            }
        },

        tooltip: {
            show: function(event) {
                plugin_refnotes.tooltip.hide(event);
                if (!preview.setNoteId(getNoteId(event))) {
                    return;
                }
                // start tooltip timeout
                timer = setTimeout(function(){ preview.show(); }, 500);
                tracking = true;
            },

            hide: function(event) {
                if (tracking) {
                    clearTimeout(timer);
                    tracking = false;
                }
                preview.hide();
            },

            track: function(event) {
                if (tracking) {
                    preview.move(getEventX(event), getEventY(event), 10, 12);
                }
            }
        }
    };
})();

addInitEvent(function(){
    var elems = getElementsByClass('refnotes-ref note-popup', null, 'a');
    for (var i = 0; i < elems.length; i++) {
        addEvent(elems[i], 'mouseover', plugin_refnotes.popup.show);
    }

    elems = getElementsByClass('refnotes-ref note-tooltip', null, 'a');
    for (var i = 0; i < elems.length; i++) {
        addEvent(elems[i], 'mouseover', plugin_refnotes.tooltip.show);
        addEvent(elems[i], 'mouseout', plugin_refnotes.tooltip.hide);
    }

    addEvent(document, 'mousemove', plugin_refnotes.tooltip.track);
    addEvent(window, 'scroll', plugin_refnotes.tooltip.hide);
});
