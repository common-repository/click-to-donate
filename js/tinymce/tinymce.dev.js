(function() {
    var pName = 'ClickToDonate';
    
    var DOM = tinymce.DOM;
    tinymce.create('tinymce.plugins.'+pName, {
        
        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         * This call is done before the editor instance has finished it's initialization so use the onInit event
         * of the editor instance to intercept that event.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init : function(ed, url) {
            var disabled = true;

            // Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
            ed.addCommand('mce_'+pName, function() {
                if ( disabled )
                    return;
                ed.windowManager.open({
                    id : pName+"Links",
                    width : 480,
                    height : "auto",
                    wpDialog : true,
                    title : ed.getLang(pName+'.title')
                }, {
                    plugin_url : url // Plugin absolute URL
                });
            });

            // Register example button
            ed.addButton(pName, {
                title : ed.getLang(pName+'.button'),
                cmd : 'mce_'+pName/*,
                image : url + '/../../images/icon.gif'*/
            });

            //ed.addShortcut('alt+shift+a', ed.getLang('advanced.link_desc'), 'mce_'+pName);

            ed.onNodeChange.add(function(ed, cm, n, co, e) {
                disabled = (co && n.nodeName != 'A');
                
                /* Verify if the selected element or their parents are of tag name p and toggle the button accordingly */
                function o(p){
                    var s,n=e.parents,t=p;
                    if(typeof(p)=="string"){
                        t=function(v){
                            return v.nodeName==p
                        }
                    }
                    for(s=0;s<n.length;s++){
                        if(t(n[s])){
                            return n[s]
                        }
                    }
                }
                C=o("A");
                if(G=cm.get(pName)){
                    if(!C||!C.name){
                        /* search for our anchor in the link */
                        var match = /^#ctd\-\d+$/i.test(ed.dom.getAttrib(C, 'href', ''));
                        G.setDisabled(!C&&co);
                        G.setActive(!!C&&match)
                    }
                }
            });
        },
        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo : function() {
            return this.ed.getParam(pName, false);
        }
    });

    tinymce.PluginManager.add(pName, eval('tinymce.plugins.'+pName));
})();