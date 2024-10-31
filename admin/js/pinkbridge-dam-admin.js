(function( $ ) {
	'use strict';

	jQuery(document).ready(function() {
		//Authentication page show and hide password
		jQuery('.toggle-password').click(function() {
			jQuery(this).toggleClass("show");
			var passwordInput = jQuery(this).closest(".form-field").find('input');
			if (passwordInput.attr('type') === 'password') {
				passwordInput.attr('type', 'text');
			} else {
				passwordInput.attr('type', 'password');
			}
		});
		// copy source code 
		jQuery(".copy-code").click(function (event) {
            event.preventDefault();
			var comp = jQuery(this);
            var shortcodeText = jQuery(this).prev(".shortcode").text();

            copyToClipboard(shortcodeText);
			jQuery(this).find('.custom-tooltip').css({
				'visibility': 'visible',
				'opacity': 1
			});
			var $this = jQuery(this);
			setTimeout( function() {
				$this.find('.custom-tooltip').css({
					'visibility': 'hidden',
					'opacity': 0
				});
			}, 1000);
        });

		jQuery('.pinkbridge-formular-outer .pinkbridge-formular-section').on('click', '#ptc_send_data', function(){
			if(jQuery('body #dam_overlay').length <= 0){
				jQuery('body').append('<div id="dam_overlay"></div>');
			}
			jQuery.ajax({
				url: customMedia.ajaxurl,
				type: 'POST',
				data:{ 
					action: 'ptc_send_data',
				},
				success: function( result ){
					jQuery("#dam_overlay").fadeOut();
					jQuery("#dam_overlay").remove();
					alert(result);
		
				}
			});
		});
	});
	function copyToClipboard(text) {
		var $temp = jQuery("<input>").appendTo("body");
		$temp.val(text).select();
		document.execCommand("copy");
		$temp.remove();	
	}
	
})( jQuery );