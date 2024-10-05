(function ($) {
    'use strict';
    // :: PreventDefault a Click
    $("a[href='#']").on('click', function ($) {
        $.preventDefault();
    });


})(jQuery);