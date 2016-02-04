jQuery(document).ready(function($) {
	$('#subsminform').submit(function(event) {
		event.preventDefault();

		$.post(
			subsmin.ajaxurl,
			{
				action: 'subsmin_subscribe',
				email: $('#subsminemail').val()
			}
		).done(function(data) {
			$('#subsminform').hide();
			$('.subsmin-completion-message').show();
		});
	});
});
