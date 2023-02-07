/*
 * Based on MIT licensed https://github.com/agraddy/wp-base-cpt
 */
(function() {

	function clickCopy() {
        console.log('clickCopy');
        var output = $(this).attr('data-numq-copy');
        var holder = document.createElement('textarea');
        holder.value = output;         
        document.body.appendChild(holder);
        holder.select();               
        holder.setSelectionRange(0, 99999); /*For mobile devices*/
        document.execCommand('Copy');
        holder.remove();
		return false;
	}

	function init() {
		$(document).on('click', '[data-numq-copy]', clickCopy);
	}

	jQuery(document).ready(function(jquery) {
		$ = jquery;
		init();
	});
})();
