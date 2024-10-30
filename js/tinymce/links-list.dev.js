var ClickToDonateLinks;

(function($){
    var inputs = {}, rivers = {}, ed, River, Query;

    ClickToDonateLinks = {
        timeToTriggerRiver: 150,
        minRiverAJAXDuration: 200,
        riverBottomThreshold: 5,
        keySensitivity: 100,
        lastSearch: '',
        textarea: '',

        init : function() {
            inputs.dialog = $('#ClickToDonateLinks');
            inputs.submit = $('#ClickToDonateLinks-submit');
            // URL
            inputs.url = $('#ctd-url-field');
            inputs.nonce = $('#_ajax_ctd_links_nonce');
            inputs.name = $('#ctd-campaign-name-field');
            // Secondary options
            inputs.title = $('#ctd-link-title-field');
            // Advanced Options
            inputs.openInNewTab = $('#ctd-link-target-checkbox');
            inputs.search = $('#ctd-search-field');
            // Build Rivers
            rivers.search = new River( $('#ctd-search-results') );
            rivers.recent = new River( $('#ctd-most-recent-results') );
            rivers.elements = $('.ctd-query-results', inputs.dialog);

            // Bind event handlers
            inputs.dialog.keydown( ClickToDonateLinks.keydown );
            inputs.dialog.keyup( ClickToDonateLinks.keyup );
            inputs.submit.click( function(e){
                e.preventDefault();
                ClickToDonateLinks.update();
            });
            $('#ctd-links-cancel').click( function(e){
                e.preventDefault();
                ClickToDonateLinks.close();
            });
            $('#ctd-internal-toggle').click( ClickToDonateLinks.toggleInternalLinking );

            rivers.elements.bind('river-select', ClickToDonateLinks.updateFields );

            inputs.search.keyup( ClickToDonateLinks.searchInternalLinks );

            inputs.dialog.bind('wpdialogrefresh', ClickToDonateLinks.refresh);
            inputs.dialog.bind('wpdialogbeforeopen', ClickToDonateLinks.beforeOpen);
            inputs.dialog.bind('wpdialogclose', ClickToDonateLinks.onClose);
        },

        beforeOpen : function() {
            ClickToDonateLinks.range = null;
            $('#ctd-campaign-name-field-wrapper').hide();

            if ( ! ClickToDonateLinks.isMCE() && document.selection ) {
                ClickToDonateLinks.textarea.focus();
                ClickToDonateLinks.range = document.selection.createRange();
            }
        },

        open : function() {
            if ( !wpActiveEditor )
                return;

            this.textarea = $('#'+wpActiveEditor).get(0);

            // Initialize the dialog if necessary (html mode).
            if ( ! inputs.dialog.data('wpdialog') ) {
                inputs.dialog.wpdialog({
                    title: ctdLinksListL10n.title,
                    width: 480,
                    height: 'auto',
                    modal: true,
                    dialogClass: 'wp-dialog',
                    zIndex: 300000
                });
            }

            inputs.dialog.wpdialog('open');
        },

        isMCE : function() {
            return tinyMCEPopup && ( ed = tinyMCEPopup.editor ) && ! ed.isHidden();
        },

        refresh : function() {
            // Refresh rivers (clear links, check visibility)
            rivers.search.refresh();
            rivers.recent.refresh();

            if ( ClickToDonateLinks.isMCE() )
                ClickToDonateLinks.mceRefresh();
            else
                ClickToDonateLinks.setDefaultValues();

            // Focus the URL field and highlight its contents.
            //     If this is moved above the selection changes,
            //     IE will show a flashing cursor over the dialog.
            inputs.url.focus()[0].select();
            // Load the most recent results if this is the first time opening the panel.
            if ( ! rivers.recent.ul.children().length )
                rivers.recent.ajax();
        },

        mceRefresh : function() {
            var e, r, nameQuery;
            ed = tinyMCEPopup.editor;

            tinyMCEPopup.restoreSelection();

            // If link exists, select proper values.
            if ( e = ed.dom.getParent(ed.selection.getNode(), 'A') ) {
                // Set URL and description.
                inputs.url.val( ed.dom.getAttrib(e, 'href') );
                inputs.title.val( ed.dom.getAttrib(e, 'title') );
                
                // Get the current campaign link
                r = ed.dom.getAttrib(e, 'href').match(/^#ctd\-(\d+)$/i);
                if(r && r[1]){
                    inputs.name.html(ctdLinksListL10n.loadingCampaign.replace("{0}", r[1]));
                    $('#ctd-campaign-name-field-wrapper').show();
                    
                    nameQuery = new Query("#ctd-"+r[1]);
                    
                    nameQuery.ajax(function( results, params ) {
                        if(results){
                            inputs.name.html(r[1]);
                            $.each( results, function() {
                                if(this['ID']==r[1]){
                                    inputs.name.html("<a href='"+this['permalink']+"' target='_blank'>"+this['title']+"</a>");
                                }
                            });
                        }
                    });
                }else{
                    inputs.name.html("");
                    $('#ctd-internal-toggle').trigger('click');
                    $('#ctd-campaign-name-field-wrapper').hide();
                }
                
                // Set open in new tab.
                if ( "_blank" == ed.dom.getAttrib(e, 'target') )
                        inputs.openInNewTab.prop('checked', true);
                // Update save prompt.
                inputs.submit.val( ctdLinksListL10n.update );

            // If there's no link, set the default values.
            } else {
                ClickToDonateLinks.setDefaultValues();
            }

            tinyMCEPopup.storeSelection();
        },

        close : function() {
            if ( ClickToDonateLinks.isMCE() )
                tinyMCEPopup.close();
            else
                inputs.dialog.wpdialog('close');
        },

        onClose: function() {
            if ( ! ClickToDonateLinks.isMCE() ) {
                ClickToDonateLinks.textarea.focus();
                if ( ClickToDonateLinks.range ) {
                    ClickToDonateLinks.range.moveToBookmark( ClickToDonateLinks.range.getBookmark() );
                    ClickToDonateLinks.range.select();
                }
            }
        },

        getAttrs : function() {
            return {
                href : inputs.url.val(),
                title : inputs.title.val(),
                target : inputs.openInNewTab.prop('checked') ? '_blank' : ''
            };
        },

        update : function() {
            if ( ClickToDonateLinks.isMCE() )
                ClickToDonateLinks.mceUpdate();
            else
                ClickToDonateLinks.htmlUpdate();
        },

        htmlUpdate : function() {
            var attrs, html, start, end, cursor,
                textarea = ClickToDonateLinks.textarea;

            if ( ! textarea )
                return;

            attrs = ClickToDonateLinks.getAttrs();

            // If there's no href, return.
            if ( ! attrs.href || attrs.href == 'http://' || attrs.href == '#' )
                return;

            // Build HTML
            html = '<a href="' + attrs.href + '"';

            if ( attrs.title )
                html += ' title="' + attrs.title + '"';
            if ( attrs.target )
                html += ' target="' + attrs.target + '"';

            html += '>';

            // Insert HTML
            // W3C
            if ( typeof textarea.selectionStart !== 'undefined' ) {
                start       = textarea.selectionStart;
                end         = textarea.selectionEnd;
                selection   = textarea.value.substring( start, end );
                html        = html + selection + '</a>';
                cursor      = start + html.length;

                // If no next is ctd-selected, place the cursor inside the closing tag.
                if ( start == end )
                    cursor -= '</a>'.length;

                textarea.value = textarea.value.substring( 0, start )
                                + html
                                + textarea.value.substring( end, textarea.value.length );

                // Update cursor position
                textarea.selectionStart = textarea.selectionEnd = cursor;

            // IE
            // Note: If no text is ctd-selected, IE will not place the cursor
            //       inside the closing tag.
            } else if ( document.selection && ClickToDonateLinks.range ) {
                textarea.focus();
                ClickToDonateLinks.range.text = html + ClickToDonateLinks.range.text + '</a>';
                ClickToDonateLinks.range.moveToBookmark( ClickToDonateLinks.range.getBookmark() );
                ClickToDonateLinks.range.select();

                ClickToDonateLinks.range = null;
            }

            ClickToDonateLinks.close();
            textarea.focus();
        },

        mceUpdate : function() {
            var ed = tinyMCEPopup.editor,
                    attrs = ClickToDonateLinks.getAttrs(),
                    e, b;

            tinyMCEPopup.restoreSelection();
            e = ed.dom.getParent(ed.selection.getNode(), 'A');

            // If the values are empty, unlink and return
            if ( ! attrs.href || attrs.href == 'http://' || attrs.href == '#' ) {
                if ( e ) {
                    tinyMCEPopup.execCommand("mceBeginUndoLevel");
                    b = ed.selection.getBookmark();
                    ed.dom.remove(e, 1);
                    ed.selection.moveToBookmark(b);
                    tinyMCEPopup.execCommand("mceEndUndoLevel");
                    ClickToDonateLinks.close();
                }
                return;
            }

            tinyMCEPopup.execCommand("mceBeginUndoLevel");

            if (e == null) {
                ed.getDoc().execCommand("unlink", false, null);
                tinyMCEPopup.execCommand("CreateLink", false, "#mce_temp_url#", {skip_undo : 1});

                tinymce.each(ed.dom.select("a"), function(n) {
                    if (ed.dom.getAttrib(n, 'href') == '#mce_temp_url#') {
                        e = n;
                        ed.dom.setAttribs(e, attrs);
                    }
                });

                // Sometimes WebKit lets a user create a link where
                // they shouldn't be able to. In this case, CreateLink
                // injects "#mce_temp_url#" into their content. Fix it.
                if ( $(e).text() == '#mce_temp_url#' ) {
                    ed.dom.remove(e);
                    e = null;
                }
            } else {
                ed.dom.setAttribs(e, attrs);
            }

            // Don't move caret if selection was image
            if ( e && (e.childNodes.length != 1 || e.firstChild.nodeName != 'IMG') ) {
                ed.focus();
                ed.selection.select(e);
                ed.selection.collapse(0);
                tinyMCEPopup.storeSelection();
            }

            tinyMCEPopup.execCommand("mceEndUndoLevel");
            ClickToDonateLinks.close();
        },

        updateFields : function( e, li, originalEvent ) {
            inputs.url.val( li.children('.item-link').val() );
            inputs.title.val( li.hasClass('no-title') ? '' : li.children('.item-title').text() );
            inputs.name.html("<a href='"+li.children('.item-permalink').val()+"' target='_blank'>"+inputs.title.val()+"</a>");
            $('#ctd-campaign-name-field-wrapper').show();
            if ( originalEvent && originalEvent.type == "click" )
                inputs.url.focus();
        },
        setDefaultValues : function() {
            // Set URL and description to defaults.
            // Leave the new tab setting as-is.
            inputs.url.val('#');
            inputs.title.val('');

            // Update save prompt.
            inputs.submit.val( ctdLinksListL10n.save );
        },

        searchInternalLinks : function() {
            var t = $(this), waiting, search = t.val();

            if ( search.length > 1 ) {
                rivers.recent.hide();
                rivers.search.show();

                // Don't search if the keypress didn't change the title.
                if ( ClickToDonateLinks.lastSearch == search )
                    return;

                ClickToDonateLinks.lastSearch = search;
                waiting = t.siblings('img.waiting').show();

                rivers.search.change( search );
                rivers.search.ajax( function(){ waiting.hide(); });
            } else {
                rivers.search.hide();
                rivers.recent.show();
            }
        },

        next : function() {
            rivers.search.next();
            rivers.recent.next();
        },
        prev : function() {
            rivers.search.prev();
            rivers.recent.prev();
        },

        keydown : function( event ) {
            var fn, key = $.ui.keyCode;

            switch( event.which ) {
                case key.UP:
                    fn = 'prev';
                case key.DOWN:
                    fn = fn || 'next';
                    clearInterval( ClickToDonateLinks.keyInterval );
                    ClickToDonateLinks[ fn ]();
                    ClickToDonateLinks.keyInterval = setInterval( ClickToDonateLinks[ fn ], ClickToDonateLinks.keySensitivity );
                    break;
                default:
                    return;
            }
            event.preventDefault();
        },
        keyup: function( event ) {
            var key = $.ui.keyCode;

            switch( event.which ) {
                case key.ESCAPE:
                    event.stopImmediatePropagation();
                    if ( ! $(document).triggerHandler( 'wp_CloseOnEscape', [{ event: event, what: 'ClickToDonate', cb: ClickToDonateLinks.close }] ) )
                        ClickToDonateLinks.close();

                    return false;
                    break;
                case key.UP:
                case key.DOWN:
                    clearInterval( ClickToDonateLinks.keyInterval );
                    break;
                default:
                    return;
            }
            event.preventDefault();
        },

        delayedCallback : function( func, delay ) {
            var timeoutTriggered, funcTriggered, funcArgs, funcContext;

            if ( ! delay )
                return func;

            setTimeout( function() {
                if ( funcTriggered )
                    return func.apply( funcContext, funcArgs );
                // Otherwise, wait.
                timeoutTriggered = true;
            }, delay);

            return function() {
                if ( timeoutTriggered )
                    return func.apply( this, arguments );
                // Otherwise, wait.
                funcArgs = arguments;
                funcContext = this;
                funcTriggered = true;
            };
        },

        toggleInternalLinking : function( event ) {
            var panel = $('#ctd-search-panel'),
                    widget = inputs.dialog.wpdialog('widget'),
                    // We're about to toggle visibility; it's currently the opposite
                    visible = !panel.is(':visible'),
                    win = $(window);

            $(this).toggleClass('ctd-toggle-arrow-active', visible);

            inputs.dialog.height('auto');
            panel.slideToggle( 300, function() {
                setUserSetting('ctd-links', visible ? '1' : '0');
                inputs[ visible ? 'search' : 'title' ].focus();

                // Move the box if the box is now expanded, was opened in a collapsed state,
                // and if it needs to be moved. (Judged by bottom not being positive or
                // bottom being smaller than top.)
                var scroll = win.scrollTop(),
                    top = widget.offset().top,
                    bottom = top + widget.outerHeight(),
                    diff = bottom - win.height();

                if ( diff > scroll ) {
                    widget.animate({'top': diff < top ?  top - diff : scroll }, 200);
                }
            });
            event.preventDefault();
        }
    }

    River = function( element, search ) {
        var self = this;
        this.element = element;
        this.ul = element.children('ul');
        this.waiting = element.find('.ctd-river-waiting');

        this.change( search );
        this.refresh();

        element.scroll( function(){ self.maybeLoad(); });
        element.delegate('li', 'click', function(e){ self.select( $(this), e ); });
    };

    $.extend( River.prototype, {
        refresh: function() {
            this.deselect();
            this.visible = this.element.is(':visible');
        },
        show: function() {
            if ( ! this.visible ) {
                this.deselect();
                this.element.show();
                this.visible = true;
            }
        },
        hide: function() {
            this.element.hide();
            this.visible = false;
        },
        // Selects a list item and triggers the river-select event.
        select: function( li, event ) {
            var liHeight, elHeight, liTop, elTop;

            if ( li.hasClass('ctd-unselectable') || li == this.ctd_selected )
                return;

            this.deselect();
            this.ctd_selected = li.addClass('ctd-selected');
            // Make sure the element is visible
            liHeight = li.outerHeight();
            elHeight = this.element.height();
            liTop = li.position().top;
            elTop = this.element.scrollTop();

            if ( liTop < 0 ) // Make first visible element
                this.element.scrollTop( elTop + liTop );
            else if ( liTop + liHeight > elHeight ) // Make last visible element
                this.element.scrollTop( elTop + liTop - elHeight + liHeight );

            // Trigger the river-select event
            this.element.trigger('river-select', [ li, event, this ]);
        },
        deselect: function() {
            if ( this.ctd_selected )
                this.ctd_selected.removeClass('ctd-selected');
            this.ctd_selected = false;
        },
        prev: function() {
            if ( ! this.visible )
                return;

            var to;
            if ( this.ctd_selected ) {
                to = this.ctd_selected.prev('li');
                if ( to.length )
                    this.select( to );
            }
        },
        next: function() {
            if ( ! this.visible )
                return;

            var to = this.ctd_selected ? this.ctd_selected.next('li') : $('li:not(.ctd-unselectable):first', this.element);
            if ( to.length )
                this.select( to );
        },
        ajax: function( callback ) {
            var self = this, 
            delay = this.query.page == 1 ? 0 : ClickToDonateLinks.minRiverAJAXDuration, 
            response = ClickToDonateLinks.delayedCallback( function( results, params ) {
                self.process( results, params );
                if ( callback )
                    callback( results, params );
            }, delay );

            this.query.ajax( response );
        },
        change: function( search ) {
            if ( this.query && this._search == search )
                return;

            this._search = search;
            this.query = new Query( search );
            this.element.scrollTop(0);
        },
        process: function( results, params ) {
            var list = '', alt = true, classes = '', firstPage = params.page == 1;

            if ( !results ) {
                if ( firstPage ) {
                    list += '<li class="ctd-unselectable"><span class="item-title"><em>'
                    + ctdLinksListL10n.noMatchesFound
                    + '</em></span></li>';
                }
            } else {
                $.each( results, function() {
                    classes = alt ? 'alternate' : '';
                    classes += this['title'] ? '' : ' no-title';
                    list += classes ? '<li class="' + classes + '">' : '<li>';
                    list += '<input type="hidden" class="item-permalink" value="' + this['permalink'] + '" />';
                    list += '<input type="hidden" class="item-link" value="#ctd-' + this['ID'] + '" />';
                    list += '<span class="item-title">';
                    list += this['title'] ? this['title'] : ctdLinksListL10n.noTitle;
                    list += '</span><span class="item-info">' + this['info'] + '</span></li>';
                    alt = ! alt;
                });
            }

            this.ul[ firstPage ? 'html' : 'append' ]( list );
        },
        maybeLoad: function() {
            var self = this,
                el = this.element,
                bottom = el.scrollTop() + el.height();

            if ( ! this.query.ready() || bottom < this.ul.height() - ClickToDonateLinks.riverBottomThreshold )
                return;

            setTimeout(function() {
                var newTop = el.scrollTop(), newBottom = newTop + el.height();

                if ( ! self.query.ready() || newBottom < self.ul.height() - ClickToDonateLinks.riverBottomThreshold )
                    return;

                self.waiting.show();
                el.scrollTop( newTop + self.waiting.outerHeight() );

                self.ajax( function() { self.waiting.hide(); });
            }, ClickToDonateLinks.timeToTriggerRiver );
        }
    });

    Query = function( search ) {
        this.page = 1;
        this.allLoaded = false;
        this.querying = false;
        this.search = search;
    };

    $.extend( Query.prototype, {
        ready: function() {
            return !( this.querying || this.allLoaded );
        },
        ajax: function( callback ) {
            var self = this,
                query = {
                    action : 'ctd_get_links',
                    page : this.page,
                    '_ajax_ctd_links_nonce' : inputs.nonce.val()
                };

            if ( this.search )
                query.search = this.search;

            this.querying = true;

            $.post( ajaxurl, query, function(r) {
                self.page++;
                self.querying = false;
                self.allLoaded = !r;
                callback( r, query );
            }, "json" );
        }
    });

    $(document).ready( ClickToDonateLinks.init );
})(jQuery);