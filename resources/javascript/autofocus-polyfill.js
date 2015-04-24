(function ($) {
 
    $(function () {
        if (!supportsInputAttribute('autofocus')) {
            $('[autofocus=]:first').focus(); // http://bugs.jquery.com/ticket/5637
        }
    });
 
    // detect support for input attirbute
    function supportsInputAttribute (attr) {
        var input = document.createElement('input');
        return attr in input;
    }
 
})(jQuery);