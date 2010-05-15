/**
 * Highlight the section when hovering over the appropriate section edit button
 *
 * @author Christophe Drevet <christophe.drevet@gmail.com>
 */
addInitEvent(function(){ 
    var break_classes = new RegExp('secedit');
    var level_regexp = new RegExp('level([1-5])');
    var elt_regexp = new RegExp('DIV|H[1-5]');
    var btns = getElementsByClass('btn_secedit',document,'form');
    for(var i=0; i<btns.length; i++){
        // Remove existing mouseover events to cancel dokuwiki's script.js same events
        var btnhdls = btns[i].events['mouseover'];
        for(btnhdl in btnhdls){
            removeEvent(btns[i],'mouseover',btnhdls[btnhdl]);
        }
        addEvent(btns[i],'mouseover',function(e){
            var tgt = e.target;
            if(tgt.form) tgt = tgt.form;
            // reach the DIV 'secedit' from its child form and step forward
            tgt = tgt.parentNode.nextSibling;
            tgtlvl = '0';
            // walk in all the nodes (within the wikipage DIV)
            while(tgt != null){
                // search for all highlight capable elements
                if(elt_regexp.test(tgt.tagName) == true){
                    if(JSINFO['es_order_type'] == '0'){
                        // flat
                        if(break_classes.test(tgt.className)) break;
                        tgt.className += ' section_highlight';
                    } else {
                        // nested
                        if(tgtlvl == '0'){
                            // We get the starting level
                            tgtlvl = level_regexp.exec(tgt.className)[1];
                        } else {
                            // Break the loop if the level is lower than the starting level
                            if(level_regexp.exec(tgt.className)[1] <= tgtlvl) break;
                        }
                        tgt.className += ' section_highlight';
                    }
                }
                tgt = tgt.nextSibling;
            }

        // Remove existing mouseout events to cancel dokuwiki's script.js same events
        var btnhdls = btns[i].events['mouseout'];
        for(btnhdl in btnhdls){
            removeEvent(btns[i],'mouseout',btnhdls[btnhdl]);
        }
        addEvent(btns[i],'mouseout',function(e){
            var secs = getElementsByClass('section_highlight');
            for(var j=0; j<secs.length; j++){
                secs[j].className = secs[j].className.replace(/ ?section_highlight/,'');
            }
            var secs = getElementsByClass('section_highlight');
        });
    }
});
