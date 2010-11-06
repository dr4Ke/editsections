/**
 * Highlight the section when hovering over the appropriate section edit button
 *
 * @author Christophe Drevet <christophe.drevet@gmail.com>
 */
addInitEvent(function(){
    var class_regexp = new RegExp('level([1-5])');
    var btns = getElementsByClass('btn_secedit',document,'form');
    for(var i=0; i<btns.length; i++){
        // Remove existing mouseover events
        var btnhdls = btns[i].events['mouseover'];
        for(btnhdl in btnhdls){
            removeEvent(btns[i],'mouseover',btnhdls[btnhdl]);
        }
        addEvent(btns[i],'mouseover',function(e){
            var tgt = e.target;
            if(tgt.form) tgt = tgt.form;
            // reach the DIV 'secedit' from its child form
            tgt = tgt.parentNode.nextSibling;
            tgtlvl = '0';
            // walk in all the nodes
            while(tgt != null){
                // search for all 'level?' DIVs
                if((tgt.tagName == 'DIV')&&(class_regexp.test(tgt.className) == true)){
                    if(JSINFO['es_order_type'] == '0'){
                        // flat
                        tgt.className += ' section_highlight';
                        break;
                    } else {
                        // nested
                        if(tgtlvl == '0'){
                            // We get the starting level
                            tgtlvl = class_regexp.exec(tgt.className)[1];
                        } else {
                            // Break the loop if the level is lower than the starting level
                            if(class_regexp.exec(tgt.className)[1] <= tgtlvl) break;
                        }
                        tgt.className += ' section_highlight';
                    }
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
