"use strict";

(function() {
    function initializeLogin() {
        $('.js-login').on( 'click', function() {
            // todo
        } );
    }

    var Line = function( content, lines ) {
        this.text = content;

        var numSpaces = 0,
            headerCount = 0,
            starCount = 0,
            numCount = 0,
            isSpace = true,
            seenText = false;

        for ( var i = 0; i < content.length; i++ ) {
            var c = content.charAt(i);

            if ( c === ' ' ) {
                if ( isSpace ) {
                    numSpaces++;

                    if ( numSpaces >= 4 ) {
                        break;
                    }
                }
            } else if ( isSpace ) {
                isSpace = false;

                if ( c === '#' ) {
                    headerCount = 1;
                } else if ( c === '*' ) {
                    starCount = 1;
                } else if ( c === '-' ) {
                    numCount = 1;
                } else {
                    seenText = true;
                }
            } else if ( ! seenText ) {
                if ( c === '#' ) {
                    headerCount++;
                } else if ( c === '*' ) {
                    starCount++;
                } else if ( c === '-' ) {
                    numCount++;
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        this.isTextFlag = seenText
        this.headerCount = Math.min( 5, headerCount );
        this.starCount = starCount;
        this.numCount = numCount;
        this.isCodeFlag = ( numSpaces >= 4 );
    }

    Line.prototype.isHeader = function() {
        return this.headerCount > 0 ;
    }

    Line.prototype.isCode = function() {
        return this.isCodeFlag;
    }

    Line.prototype.isHeader = function() {
        return this.headerCount > 0;
    }

    Line.prototype.isList = function() {
        return this.isNumList() || this.isStarList();
    }

    Line.prototype.isNumList = function() {
        return this.numCount > 0;
    }

    Line.prototype.isStarList = function() {
        return this.starCount > 0;
    }

    Line.prototype.toString = function() {
        var klass =
                ( this.headerCount > 0  ) ? ('style-h' + this.headerCount)    :
                ( this.isCodeFlag       ) ?  'style-code'                     :
                ( this.numCount > 0     ) ?  'style-list.num'                 :
                ( this.starCount > 0    ) ?  'style-list.star'                :
                                             ''                               ;

        return '<div class="editor-line ' + klass + '">' + this.text + '</div>';
    }

    var highlight = function( text ) {
        var strs  = text.split( /(?:\n\r)|(?:\r\n)|\r|\n/ );
        var lines = new Array( strs.length );

        for ( var i = 0; i < strs.length; i++ ) {
            lines[i] = new Line( strs[i], lines );
        }

        return lines;
    }

    var Editor = function( dom ) {
        this.dom = dom;
        this.lines = highlight( this.dom.textContent );

        this.dom.innerHTML = this.lines.join( "\n" );
        console.log( this.lines.join("\n") );
    }

    Editor.prototype.update = function( startLine, endLine ) {

    }

    Editor.updateLines = function( lines, srcI, srcEnd, dest ) {
        for ( var i = srcI; i < srcEnd; i++ ) {
            var line = src;
        }
    }

    function initializeEditor() {
        var view = $('.article-view');
        var edit = $('.article-edit');

        var editor = new Editor( $('.js-edit').get(0) );

        var editFullscreen = $('.article-fullscreen');
        var editWindow = $('.article-window');

        $('.js-edit-fullscreen').click(function() {
            if ( editFullscreen.is(':visible') ) {
                edit.appendTo( editWindow );
                view.appendTo( editWindow );

                editFullscreen.hide();
            } else {
                edit.appendTo( editFullscreen );
                view.appendTo( editFullscreen );

                editFullscreen.show();
            }
        });

        $('.js-edit-number').change(function() {
            if ( $(this).is(':checked') ) {
                view.addClass('numbered');
            } else {
                view.removeClass( 'numbered' );
            }
        });

        var stylable = $('.article-style');

        $('.js-edit-style').change(function() {
            var newStyle = $(this).find( ':selected' ).val();

            stylable.attr( 'class', 'article-style ' + newStyle );
        });

        $('.js-edit-save').click(function() {
            // todo
        });
    }

    $(function() {
        initializeLogin();
        initializeEditor();
    });
})();
