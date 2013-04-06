var FileManagerActivate = {
	base_url: null,
	tiny_mce: function(field_name, url, type, win) {
		// Function for Tiny MCE integration
		window.tinyMCE.activeEditor.windowManager.open({
			file : this.base_url+'?tiny_mce=1',
			title : __('bfm_window_title'),
			width : 950, 
			height : 600,
			resizable : "yes",
			scrollbars : "yes",
			inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
			close_previous : "no"
		},{
			window : win,
			input : field_name
		});
		return false;
	},
	standalone: function(target_el_id, folder) {
		url = this.base_url;
		if (target_el_id != undefined) {
			url += '?target_el_id='+target_el_id;
		}
		if (folder != undefined) {
			if (target_el_id != undefined) {
				url += '&';
			} else {
				url += '?';
			}
			url += 'folder='+folder;
		}
		newwindow = window.open(url,'biscuit-file-manager','width=965,height=615,scrollbars=yes,resizable=yes');
		if (window.focus) {
			newwindow.focus();
		}
		return false;
	}
}
