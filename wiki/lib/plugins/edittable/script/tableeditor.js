/**
 * Add neat functions to the table editor
 *
 * This function adds a toolbar and column/row handles to the table editor.
 *
 * The source code has the following sections:
 * - helper functions
 * - element prototype enhancers (pimps); the core functionality related to the
 *   table, rows and cells
 * - toolbar definition; the button actions
 * - drag ’n’ drop handler
 *
 * @author Adrian Lang <lang@cosmocode.de>
 */

function map(arr, func) {
    for (var index in arr) {
        if (arr.hasOwnProperty(index)) {
            arr[index] = func(arr[index]);
        }
    }
    return arr;
}

addInitEvent(function () {
    var table = getElementsByClass('edit', document, 'table')[0];
    if (!table) {
        // There is no table editor.
        return;
    }
    initSizeCtl('size__ctl','edit__wrap');
    var tbody = table.getElementsByTagName('tbody')[0];
    prependChild(table, document.createElement('thead'));

    // The currently selected table field
    var cur_field = null;

    function setCurrentField(newcur) {
        var t = getType.call(newcur);
        if (t < TYPE__CELL) {
            return false;
        }
        if (newcur._parent) {
            newcur = newcur._parent;
        }
        cur_field = newcur;
        lastChildElement.call(cur_field).focus();
        if ($('table__cur_field')) {
            $('table__cur_field').id = '';
        }
        lastChildElement.call(cur_field).id = 'table__cur_field';
        linkwiz.textArea = lastChildElement.call(cur_field);
        for (var i = 0 ; i < setCurrentField._handlers.length ; ++i) {
            setCurrentField._handlers[i].call(cur_field);
        }
    }
    setCurrentField._handlers = [];

    /**
     * General helper functions
     *
     * These functions allow to navigate through DOM trees without text nodes.
     */
    function previousElement() {
        var node = this.previousSibling;
        while (node && !assertType.call(node, TYPE__ELEMENT)) {
            node = node.previousSibling;
        }
        return node;
    }

    function nextElement() {
        var node = this.nextSibling;
        while (node && !assertType.call(node, TYPE__ELEMENT)) {
            node = node.nextSibling;
        }
        return node;
    }

    function firstChildElement() {
        var node = this.firstChild;
        return (node && !assertType.call(node, TYPE__ELEMENT)) ?
               nextElement.call(node) : node;
    }

    function lastChildElement() {
        var node = this.lastChild;
        return (node && !assertType.call(node, TYPE__ELEMENT)) ?
               previousElement.call(node) : node;
    }

    /**
     * Table related helper functions
     */

    // Internal helper function used in findColumn and findRow
    function findTableElement(target, col, prep, coord_pos, coord_size) {
        for (var i = 0 ; i < col.length; ++i) {
            var startelem = prep.call(col[i]);
            var c_val = 0;
            var c_elem = startelem;
            do {
                c_val += c_elem[coord_pos];
                c_elem = c_elem.offsetParent;
            } while (c_elem);
            if (target >= c_val && c_val >= target - startelem[coord_size]) {
                return i;
            }
        }
        return -1;
    }

    /**
     * Get the column number of a certain x value
     *
     * x should include scrollLeft.
     */
    function findColumn(x) {
        return findTableElement(x, table.tHead.rows[0].cells,
                                function () { return this; },
                                'offsetLeft',
                                'offsetWidth');
    }

    /**
     * Get the row number of a certain y value
     *
     * y should include scrollHeight.
     */
    function findRow(y) {
        return findTableElement(y, table.rows,
                                firstChildElement,
                                'offsetTop',
                                'offsetHeight');
    }

    /**
     * Get the number of columns this has
     *
     * upto specifies the cell up to which should be counted.
     */
    function countCols(upto) {
        var count = 0;
        var node = firstChildElement.call(this);
        while (node && node !== upto) {
            ++count;
            node = nextElement.call(node);
        }
        return count;
    }

    /**
     * Get the cell in this row at position “column”
     */
    function getCell(column) {
        var _ret = null;
        if (this.forEveryCell) {
            this.forEveryCell(function () {
                if (this.getPos()[1] === column) {
                    _ret = this;
                }
            });
        }
        if (_ret === null) {
            for (var i = 0, cnt = -1 ; i < this.childNodes.length ; ++i) {
                if (assertType.call(this.childNodes[i], TYPE__ELEMENT) &&
                    ++cnt === column) {
                    _ret = this.childNodes[i];
                    break;
                }
            }
        }
        return _ret;
    }

    /**
     * Get the cell below this
     */
    function getCellBelow() {
        var row = nextElement.call(this.parentNode);
        return row ? getCell.call(row, this.getPos()[1]) : null;
    }

    /**
     * Get the cell above this
     */
    function getCellAbove() {
        var row = previousElement.call(this.parentNode);
        return row ? getCell.call(row, this.getPos()[1]) : null;
    }

    var TYPE__NODE    = 0;
    var TYPE__ELEMENT = 1;
    var TYPE__CELL    = 2;
    var TYPE__FIELD   = 3;

    function getType() {
        if (!this.tagName) {
            return TYPE__NODE;
        }
        if (this._parent) {
            return TYPE__CELL;
        }
        if (this.getInpObj || (this.tagName.match(/t[dh]/i) &&
                               !hasClass(this, 'handle'))) {
            return TYPE__FIELD;
        }
        return TYPE__ELEMENT;
    }

    function assertType(type) {
        return getType.call(this) >= type;
    }

    function moveNextRows(update) {
        var nextrow = nextElement.call(this);
        // Update pos information in rows after the new one.
        while (nextrow) {
            nextrow.move(update(nextrow.getPos()));
            nextrow = nextElement.call(nextrow);
        }
    }

    function splitHandleColumns(target_col, mv_delta, match_func) {
        table.forEveryRow(function () {
            var match = null;
            this.forEveryCell(function () {
                var pos = this.getPos();
                if (pos[1] === target_col) {
                    match = this;
                }
                if (pos[1] >= target_col) {
                    pos[1] += mv_delta;
                    this.setPos(pos);
                }
            });
            match_func(this, match);
        });
    }

    function addColumn(target_col) {
        splitHandleColumns(target_col, 1,
                           function (row, ins) {
                               var newnode = null;
                               if  (ins && ins._parent) {
                                   // TODO: Abstraction fail
                                   var root = ins._parent ? ins._parent : ins;
                                   var before = previousElement.call(ins);
                                   if (before && before._parent === root) {
                                       root.setVal('colspan', root.getVal('colspan') + 1);
                                       var pos = previousElement.call(ins).getPos();
                                       newnode = getNewPlaceholder([row.getPos(), pos[1] + 1], root);
                                   }
                               }
                               if (newnode === null) {
                                   // FIXME: Inheritance
                                   newnode = getNewCell(null, {'pos': [row.getPos(), target_col], 'text': '',
                                                               'colspan': 1, 'rowspan': 1});
                               }
                               row.insertBefore(newnode, ins);
                            });
        addHandle.call(table.tHead.rows[0], 'col', null);
    }

    function deleteColumn (target_col) {
        splitHandleColumns(target_col, -1,
                           function (row, rm) {
                               assert(rm.removeFromSpan([rm, rm]) !== false);
                           });
        table.tHead.rows[0].removeChild(lastChildElement.call(table.tHead.rows[0]));
    }

    /**
     * Create a new cell based on a template cell
     *
     * The cell consists in a td or th object, hidden inputs and text input.
     */
    function getNewCell(template, changes) {
        var params;
        if (template) {
            params = {'tag': template.getVal('tag'),
                      'align': template.getVal('align'),
                      'colspan': template.getVal('colspan'),
                      'rowspan': template.getVal('rowspan'),
                      'pos': template.getPos(),
                      'text': template.getVal('text')};
        } else {
            params = {'tag': 'td', 'align': 'left', 'colspan': 1,
                      'rowspan': 1, 'pos': [0, 0], 'text': ''};
        }

        for (var index in changes) {
            params[index] = changes[index];
        }

        var cell = document.createElement(params.tag);
        cell.className = 'col' + params.pos[1];
        cell.colSpan = params.colspan;
        cell.rowSpan = params.rowspan;
        var basename = 'table[' + params.pos[0] + '][' + params.pos[1] + ']';
        for (var val in params) {
            if (val === 'pos' || val === 'text') continue;
            cell.innerHTML += '<input type="hidden" value="' + params[val] +
                              '" name="' + basename + '[' + val + ']" />';
        }
        cell.innerHTML += '<input name="' + basename + '[text]" value="' +
                          params.text + '" ' + 'class="' + params.align +
                          'align" />';
        pimp.call(cell);
        cell._placeholders = [];
        return cell;
    }

    /**
     * Create a new placeholder div
     */
    function getNewPlaceholder(pos, partof) {
        var elem = document.createElement('div');
        elem.style.display = 'none';
        elem._parent = partof;

        elem.setPos = function (n_pos) {
            this.getPos = function () {
                return n_pos;
            };
        };

        elem.setPos(pos);

        elem.nextCell = function () {
            var nextcell = this;
            do {
                nextcell = nextElement.call(nextcell);
            } while (nextcell && nextcell.tagName === 'DIV');
            return nextcell;
        };

        elem.getVal = function (val) {
            return '';
        };

        elem.removeFromSpan = function (pos, newobj) {
            return this._parent.removeFromSpan(pos, newobj);
        };

        elem.checkRemoveSpan = function (pos) {
            return this._parent.checkRemoveSpan(pos);
        };

        partof._placeholders.push(elem);
        return elem;
    }

    /** PIMPS **/

    /**
     * Table
     */
    table.forEveryRow = function (func) {
        for (var r = 0 ; r < tbody.rows.length ; ++r) {
            func.call(tbody.rows[r]);
        }
    };

    table.forEveryCell = function (func) {
        this.forEveryRow(function () { this.forEveryCell(func); });
    };

    /**
     * Rows
     */
    function pimpRow() {
        this.forEveryCell = function (func) {
            for (var c = 0 ; c < this.childNodes.length ; ++c) {
                var elem = this.childNodes[c];
                if (assertType.call(elem, TYPE__CELL)) {
                    func.call(elem);
                }
            }
        };

        // map 0-based index classes to 1-based indizes
        this.getPos = function () {
            return parseInt(this.className.match(/row(\d+)/)[1], 10) + 1;
        };
        this.setPos = function (nupos) {
            this.className = 'row' + (nupos - 1);
        };

        this.move = function (nupos) {
            this.setPos(nupos);
            this.forEveryCell(function () {
                this.setPos([nupos, this.getPos()[1]]);
            });
        };

        this.remove = function () {
            while (this.hasChildNodes()) {
                var c = this.firstChild;
                if (assertType.call(c, TYPE__CELL)) {
                    assert(c.removeFromSpan([c, '*']) !== false, 'failed removing');
                } else {
                    this.removeChild(c);
                }
            }

            // Update pos information in rows after the new one.
            moveNextRows.call(this, function (i) {return i - 1;});

            // Remove row.
            this.parentNode.removeChild(this);
        };

        this.addRow = function (offset) {
            var newrow = document.createElement('tr');
            pimpRow.call(newrow);
            newrow.setPos(this.getPos() + offset);

            // Insert new cells.
            this.forEveryCell(function () {
                var root = this._parent ? this._parent : this;
                var newnode = null;
                var pos = root.getPos();
                pos[0] += offset;
                if  (offset > 0) {
                    // Do not try to continue spannings if adding above row
                    var below = getCellBelow.call(this);
                    if ( below && root === below._parent) {
                        // TODO: Abstraction fail
                        root.setVal('rowspan', root.getVal('rowspan') + 1);
                        newnode = getNewPlaceholder(pos, root);
                    }
                }
                if (newnode === null) {
                    // FIXME new row should have the same colspans
                    newnode = getNewCell(root, {'pos': pos, 'text': '',
                                                'colspan': 1, 'rowspan': 1});
                }
                newrow.appendChild(newnode);
            });
            addHandle.call(newrow, 'row', newrow.firstChild);

            // Insert row.
            var nextrow = this;
            var mv = (offset > 0) ? [-1, nextElement]
                                  : [+1, previousElement];
            while (offset != 0) {
                nextrow = mv[1].call(nextrow);
                offset += mv[0];
            }
            this.parentNode.insertBefore(newrow, nextrow);

            // Update pos information in rows after the new one.
            moveNextRows.call(newrow, function (i) {return i + 1;});
            return newrow;
        };
    }

    table.forEveryRow(function () { pimpRow.call(this); });

    /**
     * Cells
     */
    // Attaches focus handlers and methods to a cell.
    function pimp() {
        addEvent(lastChildElement.call(this), 'focus', function() { return setCurrentField(this.parentNode); });

        this.nextCell = function () {
            var nextcell = this;
            do {
                nextcell = nextElement.call(nextcell);
            } while (nextcell && !assertType.call(nextcell, TYPE__FIELD) &&
                     nextcell._parent === this);
            return nextcell;
        };

        this.getInpObj = function (name) {
            var tname = lastChildElement.call(this).name.replace('text', name);
            var inputs = this.getElementsByTagName('input');
            for (var i = 0 ; i < inputs.length ; ++i) {
                if (inputs[i].name === tname) {
                    return inputs[i];
                }
            }
        };

        this.getVal = function (name) {
            var val = this.getInpObj(name).value;
            if (name === 'colspan' || name === 'rowspan') {
                val = parseInt(val, 10);
            }
            return val;
        };

        this.setVal = function (name, nuval) {
            this.getInpObj(name).value = nuval;
            if (name === 'rowspan') {
                this.rowSpan = nuval;
            } else if (name === 'colspan') {
                this.colSpan = nuval;
            }
        };

        this.setTag = function (nuval) {
            var nuparent = getNewCell(this, {'tag': nuval});
            nuparent._placeholders = this._placeholders;
            for (var p in this._placeholders) {
                this._placeholders[p]._parent = nuparent;
            }
            this.parentNode.replaceChild(nuparent, this);
            setCurrentField(nuparent);
        };

        this.setAlign = function (nualign) {
            this.setVal('align', nualign);
            var aligns = ['left', 'right', 'center'];
            var obj = lastChildElement.call(this);
            for (var align in aligns) {
                updateClass(obj, aligns[align] + 'align', nualign === aligns[align]);
            }
        };

        /**
         * Update position information
         */
        this.setPos = function (pos) {
            var match = /table\[\d+\]\[\d+\]\[(\w+)\]/;
            var newname = 'table[' + pos[0] + '][' + pos[1] + '][$1]';
            for (var i = 0 ; i < this.childNodes.length ; ++i) {
                this.childNodes[i].name = this.childNodes[i].name
                                          .replace(match, newname);
            }
            this.className = this.className.replace(/(.*)col\d+(.*)/,
                                                    '$1col' + pos[1] + '$2');
        };

        /**
         * Get position information
         */
        this.getPos = function () {
            return map(lastChildElement.call(this).name
                   .match(/table\[(\d+)\]\[(\d+)\]/).slice(1),
                   function (v) { return parseInt(v, 10); });
        };

        this.getBottom = function () {
            return this.getLast(getCellBelow);
        };

        this.getRight = function () {
            return this.getLast(nextElement);
        };

        this.getLast = function (func) {
            var node = this;
            var nextnode = func.call(node);
            while (nextnode && nextnode._parent === this) {
                node = nextnode;
                nextnode = func.call(node);
            }
            return node;
        };

        this.checkRemoveSpan = function (pos, killcheck) {
            if (this.getVal('rowspan') === 1) pos[0] = '*';
            if (this.getVal('colspan') === 1) pos[1] = '*';
            if (pos[0] !== '*' && pos[1] !== '*') {
                return false;
            }
            if (killcheck) {
                // The removed potion is to be deleted, not to be inserted
                // into another span, hence we can remove from the middle of a
                // span as well.
                return pos;
            }

            if (pos[0] !== '*') {
                var coord = pos[0].getPos()[0] - this.getPos()[0];
                if (coord !== 0 && coord !== this.getVal('rowspan') - 1) {
                    // Do not remove elements from the middle of a span
                    return false;
                }
            }
            if (pos[1] !== '*') {
                var coord = pos[1].getPos()[1] - this.getPos()[1];
                if (coord !== 0 && coord !== this.getVal('colspan') - 1) {
                    // Do not remove elements from the middle of a span
                    return false;
                }
            }
            return pos;
        };

        /**
         * Remove cells from a (span) field
         *
         * @param array pos [x, y] where x, y <- ['*', element]
         * @param function template_func Function to create replacements
         *
         * @return mixed false on error, true on success, string if cell
         *               is completely removed and contained text
         */
        this.removeFromSpan = function (pos, template_func) {
            pos = this.checkRemoveSpan(pos, true);
            if (pos === false) return false;

            function handle(elem) {
                if (template_func) {
                    elem.parentNode.replaceChild(template_func(elem), elem);
                } else {
                    elem.parentNode.removeChild(elem);
                }
            }

            if (pos[0] === '*' && pos[1] === '*') {
                for (var pholder in this._placeholders) {
                    handle(this._placeholders[pholder]);
                }
                handle(this);
                return this.getVal('text');
            }

            var ops = (pos[0] === '*') ?
                        {'span': 'colspan', 'index': 1,
                         'getnext': nextElement} :
                        {'span': 'rowspan', 'index': 0,
                         'getnext': getCellBelow};
            var spanval = this.getVal(ops.span);
            if (spanval > 1) {
                this.setVal(ops.span, spanval - 1);
            }

            pos[ops.index] = pos[ops.index].getPos()[ops.index];

            if (this.getPos()[ops.index] === pos[ops.index]) {
                // The main node is to be deleted, so move it to a safe place.
                var oldplaceholder = ops.getnext.call(this);
                var pholder_insertpoint = this.nextSibling;
                var pholder_pnode = this.parentNode;
                var c_pos = this.getPos();
                this.setPos(oldplaceholder.getPos());
                oldplaceholder.setPos(c_pos);
                if (pholder_insertpoint !== oldplaceholder) {
                    oldplaceholder.parentNode.replaceChild(this, oldplaceholder);
                    pholder_pnode.insertBefore(oldplaceholder, pholder_insertpoint);
                }
            }

            var newp = [];
            for (var pholder in this._placeholders) {
                var placeholder = this._placeholders[pholder];
                var c_pos = placeholder.getPos();
                if (c_pos[ops.index] === pos[ops.index]) {
                    handle(placeholder);
                } else {
                    // not a target
                    newp.push(placeholder);
                }
            }
            this._placeholders = newp;
            return true;
        };

        this.addToSpan = function (pos, check) {
            var ops = (pos[0] === '*') ?
                      {'span': 'colspan', 'index': 1, 'getnext': getCellBelow,
                       'ospan': 'rowspan'} :
                      {'span': 'rowspan', 'index': 0, 'getnext': nextElement,
                       'ospan': 'colspan'};

            var span = this.getVal(ops.ospan);
            var node = pos[ops.index];
            for (var n = 0 ; n < span ; ++n) {
                if (node === null ||
                    node.checkRemoveSpan([node, node]) === false) {
                    return false;
                }
                node = ops.getnext.call(node);
            }

            if (check) return true;

            var spanval = this.getVal(ops.span);
            this.setVal(ops.span, spanval + 1);

            var node = pos[ops.index];
            var _this = this;
            function spawnPlaceholder (placeholder) {
                    return getNewPlaceholder(placeholder.getPos(), _this);
            }
            for (var n = 0 ; n < span ; ++n) {
                var nnode = ops.getnext.call(node);

                var ret = node.removeFromSpan([node, node], spawnPlaceholder);
                if (ret !== false && ret !== true && ret !== '') {
                    this.setVal('text', this.getVal('text') + ' ' + ret);
                }
                node = nnode;
            }
        };
    }

    // Attach focus handlers and methods to every cell.
    table.forEveryCell(pimp);

    // Insert rowspan and colspan placeholder.
    table.forEveryCell(function () {
        if (!assertType.call(this, TYPE__FIELD)) return;
        this._placeholders = [];
        var colspan = this.getVal('colspan');
        var pos = this.getPos();
        while (colspan-- > 1) {
            this.parentNode.insertBefore(getNewPlaceholder([pos[0],
                                                            pos[1] + colspan],
                                                           this),
                                         nextElement.call(this));
        }
        var rowspan = this.getVal('rowspan');
        var insertpoint = getCellBelow.call(this);
        var placeholder = this;
        for (var c = 1; c < rowspan; ++c) {
            var trow = insertpoint ? insertpoint.parentNode :
                       nextElement.call(placeholder.parentNode);
            placeholder = getNewPlaceholder([pos[0] + c, pos[1]], this);
            trow.insertBefore(placeholder, insertpoint);

            // Move subsequent cell names
            var ncell = insertpoint;
            while (ncell) {
                var m_pos = ncell.getPos();
                ncell.setPos([m_pos[0], m_pos[1] + this.getVal('colspan')]);
                ncell = nextElement.call(ncell);
            }

            insertpoint = getCellBelow.call(placeholder);
        }
    });

    /**
     * Toolbar
     */
    function prepareButton(button, click_handler, update_handler) {
        // Click handler
        addEvent(button, 'click', function () {
            var nextcur = cur_field ? click_handler() :
                          tbody.rows[0].cells[1];
            if (!nextcur) {
                nextcur = cur_field;
            }
            setCurrentField(nextcur);
        });

        // Update the button’s state
        button.update = function () {
            if (!cur_field) return;
            var state = update_handler.call(this);
            updateClass(this, 'selected', state[0]);
            updateClass(this, 'disabled', state[1]);
            this.disabled = state[1];
        };
        setCurrentField._handlers.push(function () {button.update.call(button); });
        return button;
    }

    // window scope needed for these
    window.addBtnActionToggletag = function (button) {
        return prepareButton(button,
        function () {
            cur_field.setTag(cur_field.getVal('tag') === 'th' ? 'td' : 'th');
        }, function () {
            return [cur_field.getVal('tag') === 'th', false];
        });
    };

    window.addBtnActionVal = function (button, arr) {
        var ucase = arr.prop.charAt(0).toUpperCase() + arr.prop.substring(1);
        return prepareButton(button,
        function () {
            cur_field['set' + ucase](arr.val);
        }, function () {
            return [cur_field.getVal(arr.prop) === arr.val, false];
        });
    };

    window.addBtnActionSpan = function (button, arr) {
        var target = ['*', '*'];
        var ops = arr.target === 'col' ?
                  {index: 1, next: [nextElement, 'getRight']} :
                  {index: 0, next: [getCellBelow, 'getBottom']};

        if (arr.ops === '+') {
            return prepareButton(button,
            function () {
                target[ops.index] = ops.next[0].call(cur_field[ops.next[1]]());
                cur_field.addToSpan(target);
            }, function () {
                target[ops.index] = ops.next[0].call(cur_field[ops.next[1]]());
                return [false, !cur_field.addToSpan(target, true)];
            });
        } else {
            return prepareButton(button,
            function () {
                target[ops.index] = cur_field[ops.next[1]]();
                assert(cur_field.removeFromSpan(target, function (placeholder) {
                        return getNewCell(placeholder._parent, {'text': '', 'colspan': 1, 'rowspan': 1, 'pos': placeholder.getPos()});
                       }) === true);
            }, function () {
                return [false, cur_field.getVal(arr.target +  'span') === 1];
            });
        }
    };

    window.addBtnActionStructure = function (button, arr) {
        var click_handler = null, update = null;
        var ops = arr.target === 'row' ?
                {next: getCellBelow, prev: getCellAbove} :
                {next: nextElement, prev: previousElement};

        if (arr.ops === '+') {
            update = function () {return [false, false];};
        } else {
            var getNextcur = function () {
                var nextcur = ops.prev.call(cur_field);
                if (!assertType.call(nextcur, TYPE__CELL)) {
                    nextcur = ops.next.call(cur_field);
                }
                return (nextcur && nextcur._parent) ? nextcur._parent : nextcur;
            };
            update = function () {
                return [false, !assertType.call(getNextcur(), TYPE__CELL)];
            };
        }

        if (arr.ops === '+' && arr.target === 'row') {
            click_handler = function () {
                var rowspan = cur_field.getVal('rowspan');

                var newrow = cur_field.parentNode.addRow(rowspan);

                return getCell.call(newrow, cur_field.getPos()[1]);
            };
        } else if (arr.ops === '-' && arr.target === 'row') {
            click_handler = function () {
                if (!confirm(LANG.plugins.edittable.confirmdeleterow)) return;
                var nextcur = getNextcur();
                assert(nextcur !== null, 'Cannot find next cur_field, the button should have been disabled');
                cur_field.parentNode.remove();
                return nextcur;
            };
        } else if (arr.ops === '+' && arr.target === 'col') {
            click_handler = function () {
                addColumn(cur_field.getPos()[1] + cur_field.getVal('colspan'));
                return cur_field.nextCell();
            };
        } else {
            click_handler = function () {
                if (!confirm(LANG.plugins.edittable.confirmdeletecol)) return;
                var nextcur = getNextcur();
                assert(nextcur !== null, 'Cannot find next cur_field, the button should have been disabled');

                deleteColumn(cur_field.getPos()[1] +
                          cur_field.getVal('colspan') - 1);

                return nextcur;
            };
        }
        return prepareButton(button, click_handler, update);
    };
    var table_toolbar = document.createElement('div');
    table_toolbar.id = 'tool__bar_table';
    $('tool__bar').parentNode.insertBefore(table_toolbar, $('tool__bar'));
    initToolbar('tool__bar_table', 'dw__editform', window.table_toolbar);

    setCurrentField(tbody.rows[0].cells[0]);
    initToolbar('tool__bar', 'table__cur_field', window.toolbar, false);

    /**
     * Drag ’n’ drop
     */
    drag_marker = document.createElement('span');
    drag_marker.id = 'table__dragmarker';
    drag_marker.set = function(target, topright, bottomright, bottomleft) {
        while (drag_marker.hasChildNodes()) {
            drag_marker.removeChild(drag_marker.lastChild);
        }
        function add(cssclass) {
            img = document.createElement('img');
            img.className = cssclass;
            img.src = DOKU_BASE + 'lib/plugins/edittable/images/' + cssclass
                    + '.png';
            drag_marker.appendChild(img);
        }
        if (topright) add('dragmarker_topright');
        if (bottomright) add('dragmarker_bottomright');
        if (bottomleft) add('dragmarker_bottomleft');
        prependChild(target, drag_marker);
    };

    function checkSpans(obj, func) {
        // If there is (row|col)span on (row|col) move, die.
        var _break = false;
        if (hasClass(obj, 'rowhandle')) {
            obj.parentNode.forEveryCell(function () {
                if (func(this, 'row')) {
                    _break = true;
                }
            });
        } else if (hasClass(obj, 'colhandle')) {
            var pos = countCols.call(obj.parentNode, obj) - 1;
            for (var i = 0 ; i < tbody.rows.length ; ++i) {
                var elem = tbody.rows[i].childNodes[pos];
                while (elem && (!elem.getPos || elem.getPos()[1] !== pos)) {
                    elem = nextElement.call(elem);
                }
                if (elem && func(elem, 'col')) {
                    _break = true;
                }
            }
        }
        return !_break;
    }

    function TableEditorDrag () {
        this.start = function (e) {
            if (e.currentTarget !== e.target) {
                // Do not handle mousedowns on the drop down button
                return false;
            }

            if (!checkSpans(e.target, function (node, tgt) {
                 return (node._parent ? node._parent : node)[tgt + 'Span'] > 1;
            })) {
                return false;
            }
            document.body.style.cursor = 'move';
            var rowhandle = hasClass(e.target, 'rowhandle');
            drag_marker._src_pos = rowhandle ? e.target.parentNode.getPos() : findColumn(e.pageX);
            drag_marker.set(e.target,
                            rowhandle && drag_marker._src_pos !== 1,
                            (rowhandle ? e.target.parentNode : e.target).nextSibling,
                            !rowhandle && drag_marker._src_pos !== 1);

            return drag.start.call(this, e);
        };

        this.drag = function (e) {
            var target = null;

            // Move marker
            var rowhandle = hasClass(this.obj, 'rowhandle');
            if (rowhandle) {
                var pos = findRow(e.pageY);
                if (pos > 0) {
                    target = table.rows[pos].cells[0];
                }
            } else {
                var pos = findColumn(e.pageX);
                if (pos > 0) {
                    target = table.tHead.rows[0].cells[pos];
                }
            }

            if (target && checkSpans(target, function (node, tgt) {
                    var root = node._parent ? node._parent : node;
                    var other = (tgt === 'row' ? getCellBelow : nextElement).call(node);
                    return (other && root === other._parent);
                })) {
                drag_marker.set(target,
                                rowhandle && drag_marker._src_pos >= pos && (drag_marker._src_pos !== 1 || pos !== 1),
                                drag_marker._src_pos < pos || (drag_marker._src_pos === pos && (rowhandle ? table.rows.length :
                                                                           table.tHead.rows[0].cells.length) > pos + 1),
                                !rowhandle && drag_marker._src_pos >= pos && (drag_marker._src_pos !== 1 || pos !== 1));
            }
            return false;
        };

        this.stop = function(){
            var target = drag_marker.parentNode;
            var src = this.obj;

            if (!target) return;

            target.removeChild(drag_marker);

            // Are we moving a row or a column?
            if (hasClass(src, 'rowhandle')) {
                var ins = target.parentNode;
                if (drag_marker._src_pos > ins.getPos()) {
                    ins = previousElement.call(ins);
                }
                ins = ins ? nextElement.call(ins) : tbody.rows[0];

                // Move row HTML element.
                src.parentNode.parentNode.insertBefore(src.parentNode, ins);

                // Rebuild pos information after move.
                for (var r = 0 ; r < tbody.rows.length ; ++r) {
                    tbody.rows[r].move(r + 1);
                }

                setCurrentField(src.parentNode.cells[1]);
            } else {
                var from = countCols.call(src.parentNode, src) - 1;
                var to = countCols.call(target.parentNode, target);
                if (from >= to) to--;

                for (var i = 0 ; i < tbody.rows.length ; ++i) {
                    var obj = null;
                    var ins = null;
                    var diffs = [];
                    tbody.rows[i].forEveryCell(function () {
                        var pos = this.getPos();
                        if (ins === null && pos[1] === to) {
                            ins = this;
                        }
                        if (obj === null && pos[1] === from) {
                            obj = this;
                        } else if (ins === null ^ obj === null) {
                            diffs.push([this, (ins === null)]);
                        }
                    });
                    if (obj === ins) continue;
                    for (var n in diffs) {
                        var pos = diffs[n][0].getPos();
                        pos[1] += diffs[n][1] ? -1 : 1;
                        diffs[n][0].setPos(pos);
                    }
                    obj.setPos([obj.getPos()[0], to - (to > from ? 1 : 0)]);
                    tbody.rows[i].insertBefore(obj, ins);
                }
                setCurrentField(obj);
                target.parentNode.insertBefore(src, target.nextSibling);
            }

            drag.stop.call(this);
            document.body.style.cursor = '';
        };
    }

    TableEditorDrag.prototype = drag;

    function updateHandleState(handle) {
        updateClass(handle, 'disabledhandle',
                    !checkSpans(handle, function (node, tgt) {
             return (node._parent ? node._parent : node)[tgt + 'Span'] > 1;
        }));
    }

    var handles_done = false;
    // Add handles to rows and columns.
    function addHandle(text, before) {
        var handle = document.createElement('TD');
        handle.innerHTML = '&nbsp;';
        handle.className = 'handle ' + text + 'handle';

        (new TableEditorDrag()).attach(handle);
        var dropdown = document.createElement('span');
        dropdown.className = 'handle_dropdown';
        var dropbuttn = document.createElement('img');
        dropbuttn.src = DOKU_BASE + 'lib/plugins/edittable/images/dropdown.png';

        dropdown.appendChild(dropbuttn);
        var dropcontent = document.createElement('div');
        dropcontent.style.display = 'none';

        dropcontent.appendChild(document.createElement('ul'));
        if (text === 'row') {
            var items = [['minus',
                          function () {
                              if (tbody.rows.length === 1) {
                                  alert(LANG.plugins.edittable.last_row);
                                  return;
                              }
                              handle.parentNode.remove();
                          }],
                         ['plus_before',
                          function () { handle.parentNode.addRow(0); }],
                         ['plus',
                          function () { handle.parentNode.addRow(1); }]];
        } else {
            var items = [['minus',
                          function () {
                              if (countCols.call(handle.parentNode, null) === 2) {
                                  alert(LANG.plugins.edittable.last_column);
                                  return;
                              }
                              deleteColumn(countCols.call(handle.parentNode, handle) - 1);
                          }],
                         ['plus_before', function () {
                              addColumn(countCols.call(handle.parentNode, handle) - 1);
                          }],
                         ['plus', function () {
                              addColumn(countCols.call(handle.parentNode, handle));
                          }]];
        }
        for (var item = 0 ; item < items.length ; ++item) {
            var b = document.createElement('li');
            b.appendChild(document.createElement('a'));
            b.firstChild.innerHTML = LANG.plugins.edittable['struct_' + text + '_' + items[item][0]];
            addEvent(b.firstChild, 'click', bind(function (c) {
                             dropcontent.style.display = 'none'; c(); }, items[item][1]));
            dropcontent.firstChild.appendChild(b);
        }
        dropdown.appendChild(dropcontent);
        handle.appendChild(dropdown);

        this.insertBefore(handle, before);
        if (handles_done) updateHandleState(handle);
    }

    addEvent(document.body, 'mousedown', function (e) {
        // Check if we are in a dropdown
        var tgt = e.target;
        while (tgt && !hasClass(tgt, 'handle_dropdown')) {
            tgt = tgt.parentNode;
        }

        var show = false;
        if (tgt && hasClass(tgt, 'handle_dropdown') &&
            (tgt.lastChild.style.display === 'none' || e.target !== tgt.firstChild)) {
            // Show after hiding all dropdowns if click on dropdown icon or link
            // in the dropdown
            show = true;
        }

        // Hide all dropdowns
        var dropdowns = getElementsByClass('handle_dropdown', $('dw__editform'), 'span');
        for (var drop = 0 ; drop < dropdowns.length ; ++drop) {
            dropdowns[drop].lastChild.style.display = 'none';
        }

        if (show) {
            tgt.lastChild.style.display = '';
        }
    });

    var newrow = document.createElement('TR');
    newrow.className = 'handles';
    table.tHead.appendChild(newrow);
    for (var i = countCols.call(tbody.rows[0], null) ; i > 0 ; --i) {
        addHandle.call(newrow, 'col', newrow.firstChild);
    }
    var nullhandle = document.createElement('TD');
    nullhandle.className = 'handle nullhandle';
    prependChild(newrow, nullhandle);

    for (var r = 0 ; r < tbody.rows.length ; ++r) {
        addHandle.call(tbody.rows[r], 'row', tbody.rows[r].firstChild);
    }
    handles_done = true;

    function updateHandlesState () {
        var handles = getElementsByClass('handle', table, 'td');
        for (var handle = 0 ; handle < handles.length ; ++handle) {
            updateHandleState(handles[handle]);
        }
    }

    var buttons = $('tool__bar').getElementsByTagName('button');
    for (var i = 0 ; i < buttons.length ; ++i) {
        addEvent(buttons[i], 'click', updateHandlesState);
    }

    updateHandlesState();

    setCurrentField._handlers.push(function () {
        var handles = getElementsByClass('handle', table, 'td');
        for (var handle = 0 ; handle < handles.length ; ++handle) {
            removeClass(handles[handle], 'curhandle');
        }
        var rowhandle = firstChildElement.call(this.parentNode);
        if (assertType.call(rowhandle, TYPE__ELEMENT)) {
            addClass(rowhandle, 'curhandle');
        }
        var colhandle = getCell.call(table.tHead.rows[0], countCols.call(this.parentNode, this));
        if (assertType.call(colhandle, TYPE__ELEMENT)) {
            addClass(colhandle, 'curhandle');
        }
    });

    // Fix lock timer

    locktimer.init = function(timeout,msg,draft){
        // init values
        locktimer.timeout  = timeout*1000;
        locktimer.msg      = msg;
        locktimer.draft    = draft;
        locktimer.lasttime = new Date();

        if(!$('dw__editform')) return;
        locktimer.pageid = $('dw__editform').elements.id.value;
        if(!locktimer.pageid) return;

        // init ajax component
        locktimer.sack = new sack(DOKU_BASE + 'lib/exe/ajax.php');
        locktimer.sack.AjaxFailedAlert = '';
        locktimer.sack.encodeURIString = false;
        locktimer.sack.onCompletion = locktimer.refreshed;

        // register refresh event
        addEvent($('dw__editform'),'keypress',function(){locktimer.refresh();});
        addEvent($('tool__bar'),'keypress',function(){locktimer.refresh();});

        // start timer
        locktimer.reset();
    };
});

function assert(cond, desc) {
    if (!cond) {
        throw (desc ? desc : 'Assertion failed ') + 'in ' + arguments.callee.caller;
    }
}

/**
 * Functions for handling classes
 *
 * @author Benutzer:D <http://de.wikipedia.org/wiki/Benutzer:D/monobook/api.js>
 */

function classNameRE(className) {
    return new RegExp("(^|\\s+)" + className + "(\\s+|$)");
}

/** returns an Array of the classes of an element */
function getClasses(element) {
    return element.className.split(/\s+/);
}

/** returns whether an element has a class */
function hasClass(element, className) {
    if (!element.className) return false;
    var re  = classNameRE(className);
    return re.test(element.className);
    // return (" " + element.className + " ").indexOf(" " + className + " ") !== -1;
}

/** adds a class to an element */
function addClass(element, className) {
    if (hasClass(element, className))  return;
    var old = element.className ? element.className : "";
    element.className = (old + " " + className).match(/^\s*(.+)\s*$/)[1];
}

/** removes a class to an element */
function removeClass(element, className) {
    var re  = classNameRE(className);
    var old = element.className ? element.className : "";
    element.className = old.replace(re, " ");
}

/** replaces a class in an element with another */
function replaceClass(element, oldClassName, newClassName) {
    this.removeClass(element, oldClassName);
    this.addClass(element, newClassName);
}

/** sets or unsets a class on an element */
function updateClass(element, className, active) {
    var has = hasClass(element, className);
    if (has === active) return;
    if (active) addClass(element, className);
    else        removeClass(element, className);
}
