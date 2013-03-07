"use strict";

(function() {
    function initializeLogin() {
        $('.js-login').on( 'click', function() {
            // todo
        } );
    }

    function initializeEditor() {
        var view = $('.article-view');
        var edit = $('.article-edit');

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

        $('.js-edit-style').change(function() {
            var newStyle = $(this).find( ':selected' ).val();

            view.attr( 'class', 'article-view ' + newStyle );
            edit.attr( 'class', 'article-view ' + newStyle );
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
