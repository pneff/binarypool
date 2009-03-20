var binarypool = {};
binarypool.date = function() {
	return {
		replace_timestamps: function() {
			var spans = document.getElementsByTagName('span');
			for ( var i = 0; i < spans.length; i++ ) {
				var theDate = new Date(spans[i].innerHTML * 1000);
				spans[i].innerHTML = theDate.toGMTString();
			}
		}
	};
}();

window.onload = function() {
	binarypool.date.replace_timestamps();
}