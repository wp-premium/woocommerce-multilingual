function styleInlineStatusesLikeParent() {
	jQuery('.js-otgs-plugin-tr').each(function () {
		if (jQuery(this).prev().addClass('update').hasClass('active')) {
			jQuery(this).addClass('active');
		}
	})
}

jQuery(document).ready(styleInlineStatusesLikeParent);
