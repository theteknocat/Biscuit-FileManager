<div id="help-tabs">
	<ul>
		<li><a href="#help-fm-basics">Basics</a></li>
		<li><a href="#help-file-details">Viewing File Details</a></li>
		<li><a href="#help-file-selecting">Selecting a File to Use</a></li>
		<li><a href="#help-uploading">Uploading</a></li>
	</ul>
	<div id="help-fm-basics">
		<p>The Biscuit File Manager works similarly to Windows Explorer or Mac OS Finder. This cheat sheet covers the basics and is also available via the question mark button in the top right-hand corner of the file manager window.</p>
		<p>Note that depending on your permission level, some functions may not be available.</p>
		<h4>Buttons/Controls</h4>
		<ul>
			<li><img src="/framework/modules/file_manager/images/help/button-refresh.png" alt="Refresh button" style="vertical-align: middle;"> Refresh the file list</li>
			<li><img src="/framework/modules/file_manager/images/help/button-list-view.png" alt="List view button" style="vertical-align: middle;"> Switch to List view</li>
			<li><img src="/framework/modules/file_manager/images/help/button-icon-view.png" alt="Icon view button" style="vertical-align: middle;"> Switch to icon view</li>
			<li><img src="/framework/modules/file_manager/images/help/icon-size-slider.png" alt="Icon size slider" style="vertical-align: middle;"><br>In icon view only, drag this to change icon size</li>
			<li><img src="/framework/modules/file_manager/images/help/button-new-folder.png" alt="New folder button" style="vertical-align: middle;"> Create new folder</li>
			<li><img src="/framework/modules/file_manager/images/help/button-delete.png" alt="Delete button" style="vertical-align: middle;"> Delete selected items, appears when one or more items are selected</li>
			<li><img src="/framework/modules/file_manager/images/help/button-rename.png" alt="Rename button" style="vertical-align: middle;"> Rename selected item, appears when only ONE item is selected</li>
		</ul>
		<h4>Mouse Functions</h4>
		<ul>
			<li>Single-click on a folder in the left column to open it</li>
			<li>Single-click an item in the file list or icon view to select it</li>
			<li>Single-click an item in the file list or icon view a second time to rename it - but not fast enough to be a double-click</li>
			<li>Double-click an item in the file list or icon view to open/view it</li>
			<li>Hold down the CTRL key (Windows PC) or Option/Alt key (Mac) when clicking on items to select more than one</li>
			<li>Hold down SHIFT and click on items to select a range - selects everything between the first and last selected items</li>
			<li>Drag selected items in the file list onto a folder to move them. Hold down the CTRL key (Windows PC) or Option/Alt key (Mac) while dragging to copy instead of move</li>
			<li>In icon view, click and empty area and start dragging to lasso items. To select more files after making one selection, hold the CTRL key (Windows PC) or CMD/Apple key (Mac) before making a new selection and your first selection will be retained</li>
		</ul>
		<h4>Keyboard Shortcuts</h4>
		<ul>
			<li><strong>CTRL+SHIFT+A</strong> - Select all</li>
			<li><strong>CTRL+SHIFT+L</strong> - List view</li>
			<li><strong>CTRL+SHIFT+I</strong> - Icon view</li>
			<li><strong>CTRL+SHIFT+N</strong> - New folder</li>
			<li><strong>CTRL+SHIFT+R</strong> - Rename selected item (only works when one item is selected)</li>
			<li><strong>Delete or Backspace</strong> - Delete selected items</li>
			<li><strong>Escape</strong> - De-select all selected items</li>
		</ul>
	</div>
	<div id="help-file-details">
		<h4>Standard Detail View</h4>
		<p>When you double-click on any file, a dialog will open to display details about the file. The detail view for most types of files looks like this:</p>
		<p><img src="/framework/modules/file_manager/images/help/file-detail-view-standard.gif" alt="Standard detail view"></p>
		<p>You can see the basic file information (name, kind, size, date) along with an open/download link. There is also a text field you can click in to select and copy the full URL that you can email to somebody to download the file.</p>
		<h4>Image Detail View</h4>
		<p>The detail view for image files offers some additional information and looks like this:</p>
		<p><img src="/framework/modules/file_manager/images/help/file-detail-view-image.gif" alt="Image detail view"></p>
		<p>As you can see, in addition to the basics you see the image dimensions as well as a list of other available size variations. You can click on any of the variation links to open/download them.</p>
	</div>
	<div id="help-file-selecting">
		<p>One the main purposes of the file manager is to select files you have uploaded to use in your website content. As such, you will most often access the file manager via a file selector field such as:</p>
		<ul>
			<li><img src="/framework/modules/file_manager/images/help/file-select-field1.gif" style="vertical-align: middle" alt="Standard file selector"><br>
				Seen in standard Biscuit editor forms, for example the banner add insertion form in the Banner Ad module.</li>
			<li><img src="/framework/modules/file_manager/images/help/file-select-field2.gif" style="vertical-align: middle" alt="Rich text editor file selector"><br>
				Seen in the image and link insertion dialogs in the Tiny MCE rich text editor.</li>
		</ul>
		<p>Whenever you access the file manager from one of these fields it opens in file selection mode, which causes the file detail view to provide select buttons:</p>
		<p><img src="/framework/modules/file_manager/images/help/file-detail-view-image-select.gif" alt="Image detail view with select buttons"></p>
		<p>As you can see in this example of the image detail view, you can select either the standard size or any of the variations. If you clicked on any other type of file, there would be only one select button.</p>
		<p>When you click the select button, the file manager will close and the file selector field you accessed it from will be populated with the file URL.</p>
	</div>
	<div id="help-uploading">
		<p>You can upload files from your computer into the folder currently being viewed at any time by using the upload function at the top of file list or icon display.</p>
		<h4>Flash Uploader</h4>
		<p>If you have Flash version 9.0.24 or newer installed, you will have access to the Flash uploader:</p>
		<p><img src="/framework/modules/file_manager/images/help/flash-uploader.gif" alt="Flash Uploader"></p>
		<p>The Flash uploader allows you to select multiple files at once, if desired, works in the background and shows progress as files are being uploaded. The upload progress bar will appear to the right of the file selector widget and looks like this:</p>
		<p><img src="/framework/modules/file_manager/images/help/upload-progress.gif" alt="Upload progress"></p>
		<p>You can click the X icon to the right of the bar to stop the upload at any time. Note that closing the file manager or quitting your web browser will also stop the upload. During upload, you can change folders, browse other files and perform all other file manager functions. You cannot, however, add more files to an existing upload. When uploading is complete, the window will darken and show a twirly throbber image while it refreshes the files. If you are in the middle of viewing file details, the refresh will occur in the background and not interfere with your file detail view.</p>
		<h4>Standard Uploader</h4>
		<p>If you do not have Flash version 9.0.24 or newer installed, you will see a standard file upload field:</p>
		<p><img src="/framework/modules/file_manager/images/help/standard-uploader.gif" alt="Standard file upload field"></p>
		<p>This will allow you to upload one file at a time in the standard manner. This means you must wait until the selected file has been uploaded and the page reloads before you can do anything else. Google Chrome will show the file upload progress at the bottom of the window, but other browsers may not indicate any sort of progress.</p>
		<h4>Maximum Allowed File Size</h4>
		<p>This value will vary depending on the server that hosts your site and the way your web developer has configured it for you. Contact your developer if the maximum size is too small for the kind of files you need to upload.</p>
	</div>
</div>