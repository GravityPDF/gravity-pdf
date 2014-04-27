         jQuery(document).ready(function($) {
             $('.gf_form_action_has_submenu').hover(function() {
                 var l = jQuery(this).offset().left;
                 jQuery(this).find('.gf_submenu')
                     .toggle()
                     .offset({
                         left: l
                     });
             }, function() {
                 jQuery(this).find('.gf_submenu').hide();
             });
         });