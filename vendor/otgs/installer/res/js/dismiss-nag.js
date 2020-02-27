var otgs_wp_installer_dismiss_nag = {

	init: function () {
		jQuery('.installer-dismiss-nag').click(otgs_wp_installer_dismiss_nag.dismiss_nag);
	},

	dismiss_nag: function () {
		var element = jQuery(this);

		jQuery('.button-primary').attr('disabled', true);
		jQuery('.button-secondary').attr('disabled', true);
		var data = {
			action: 'installer_dismiss_nag',
			repository: element.data('repository'),
			noticeId: element.data('notice')
		};

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: data,
			success:
				function () {
					element.closest('.otgs-is-dismissible').remove();
				}
		});

		return false;
	}
};

jQuery(document).ready(otgs_wp_installer_dismiss_nag.init);
