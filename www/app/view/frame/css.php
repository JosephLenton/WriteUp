<?php
    echo
            /* # CSS #
             * CSS : implied media="all"
             * these should be minified together at some point
             */
            css( "/css/reset.css"            ),
            css( "/css/site.css"             ),
            
            // these items should always go at the end of the head
            css( "/css/browser_specific.css" ),
            
            // noscript css
            '<noscript>',
                css( "/css/noscript.css" ),
            '</noscript>'
    ;
