/**
 * Highlight the section when hovering over the appropriate section edit button
 *
 * @author Christophe Drevet <christophe.drevet@gmail.com>
 */
addInitEvent(function(){
    // detect header and its level
    var class_regexp = new RegExp('H([1-5])');
    var btns = getElementsByClass('btn_secedit',document,'form');
    for(var i=0; i<btns.length; i++){
        // Remove existing mouseover events
        var btnhdls = btns[i].events['mouseover'];
        for(btnhdl in btnhdls){
            removeEvent(btns[i],'mouseover',btnhdls[btnhdl]);
        }
        addEvent(btns[i],'mouseover',function(e){
            var tgt = e.target.form.parentNode;
            tgtlvl = '0';
            // walk in all the nodes
            while(tgt != null){
                if(typeof tgt.className !== 'undefined') {
                    //(class_regexp.test(tgt.className) == true)){
                    if(tgtlvl === '0') {
                        if (class_regexp.test(tgt.tagName) == true){
                            // We get the starting level
                            tgtlvl = class_regexp.exec(tgt.tagName)[1];
                        }
                    } else {
                        if(JSINFO['es_order_type'] == 'flat'){
                            // flat : stop at the next header
                            if (class_regexp.test(tgt.tagName) == true) {
                                break;
                            }
                        } else {
                            // nested
                            if(tgtlvl !== '0'){
                                // Break the loop if the level is lower than the starting level
                                if((class_regexp.test(tgt.tagName) == true)&&(class_regexp.exec(tgt.tagName)[1] <= tgtlvl)) {
                                    break;
                                }
                            }
                        }
                    }
                    // highlight this element
                    tgt.className += ' section_highlight';
                }
                tgt = tgt.nextSibling;
            }
        });

        addEvent(btns[i],'mouseout',function(e){
            var secs = getElementsByClass('section_highlight');
            for(var j=0; j<secs.length; j++){
                secs[j].className = secs[j].className.replace(/ ?section_highlight/,'');
            }
            var secs = getElementsByClass('section_highlight');
        });
    }
});
