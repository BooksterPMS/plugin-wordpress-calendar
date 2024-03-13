let booksterACWDomReady = function(callback) {
	document.readyState === "interactive" || document.readyState === "complete" ? callback() : document.addEventListener("DOMContentLoaded", callback);
};

booksterACWDomReady(() => {
	let bookster_shortcode = document.getElementById('bookster-acw-shortcode'),
	shortcodes = document.querySelectorAll('.bookster-acw-shortcodes');

	if(bookster_shortcode != null) {
		bookster_shortcode.addEventListener('click', booksterACWToClipboard(bookster_shortcode));
		bookster_shortcode.addEventListener('focus', booksterACWToClipboard(bookster_shortcode));
	}

	if(shortcodes != null) {
		shortcodes.forEach(item => {
			item.addEventListener('click', booksterACWToClipboard(item));
			item.addEventListener('focus', booksterACWToClipboard(item));
		});
	}

	function booksterACWToClipboard(item){ 
		navigator.clipboard.writeText(item.value).then(
			() => {
				alert('Shortcode copied to clipboard');
			}
		);
	}
});

