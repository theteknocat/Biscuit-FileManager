<div id="bfm-header"<?php if (!empty($tiny_mce)) { ?> class="tiny-mce-dialog"<?php } ?>>
	<?php
	$admin_buttons = array(
		array(
			'href' => '#help',
			'label' => 'Help',
			'classname' => 'ui-button ui-button-icon-only',
			'id' => 'bfm-help-button'
		),
		array(
			'href' => $FileManager->url().'?view_mode=icon',
			'label' => 'Icon View',
			'classname' => 'ui-button ui-button-icon-only '.(($view_mode == 'icon') ? 'active' : ''),
			'id' => 'bfm-icon-view-button'
		),
		array(
			'href' => $FileManager->url().'?view_mode=list',
			'label' => 'List View',
			'classname' => 'ui-button ui-button-icon-only '.(($view_mode == 'list') ? 'active' : ''),
			'id' => 'bfm-list-view-button'
		),
		array(
			'href' => $FileManager->url(),
			'label' => 'Refresh',
			'classname' => 'ui-button ui-button-icon-only',
			'id' => 'bfm-refresh-button'
		)
	);
	print $Navigation->render_admin_bar($FileManager,null,array(
		'bar_title' => $Biscuit->Page->title(),
		'has_new_button' => false,
		'custom_buttons' => $admin_buttons
	));
	$class = $view_mode;
	if ($FileManager->user_can_upload()) {
		$class .= ' with-upload';
	}
	?>
</div>
<div id="bfm-content">
	<div id="folder-list-outer">
		<div class="content-inner">
			<?php echo $FileManager->render_folder_hierarchy(); ?>
			<div class="clearance"></div>
		</div>
	</div>
	<div id="file-list-outer" class="<?php echo $class; ?>">
		<div class="content-inner">
			<div id="bfm-breadcrumbs"><?php echo $FileManager->render_breadcrumbs(); ?></div>
			<?php
			if ($FileManager->user_can_upload()) {
			?>
			<div id="upload-bar">
				<form name="upload-form" id="file-upload-form" action="<?php echo $FileManager->url('upload') ?>" method="post" accept-charset="UTF-8" enctype="multipart/form-data">
					<?php echo RequestTokens::render_token_field(); ?>
					<label for="bfm-file-field"><?php echo __('Upload:'); ?></label><input id="bfm-file-field" type="file" name="bfm_upload" class="file"><input id="upload-submit-button" type="submit" class="SubmitButton" name="" value="<?php echo __('Start Upload'); ?>">
					<span class="instructions"><?php echo sprintf(__('Select any type of file up to %s in size.'), FileUpload::max_size(true));
					if (!$Biscuit->module_exists('Uploadify')) {
						echo " ".__('Ask your system administrator to install the Uploadify module for better uploading.');
					}
					?></span>
					<div id="uploadify-progress">
						<div id="uploadify-current-file"></div>
						<div id="uploadify-progress-bar"></div>
						<div id="uploadify-progress-info"></div>
						<a id="uploadify-cancel-all" class="ui-dialog-titlebar-close ui-corner-all" href="#cancel-upload" title="<?php echo __('Cancel'); ?>"><span class="ui-icon ui-icon-closethick"><?php echo __('Cancel'); ?></span></a>
					</div>
				</form>
			</div>
			<?php
			}
			if ($sort_dir == 'asc') {
				$icon_dir = 'n';
			} else {
				$icon_dir = 's';
			}
			?>
			<table id="sorting" cellspacing="0" cellpadding="0" border="0">
				<tr>
					<th class="sort-column"><a data-sort-by="name" class="sort-column<?php if ($sort_by == 'name') { ?> sort-active sort-<?php echo $sort_dir; } ?> sort-by-name" href="#sort-by-name"><?php echo __('Name'); ?><span<?php if ($sort_by != 'name') { ?> style="display: none;"<?php } ?> class="ui-icon ui-icon-triangle-1-<?php echo $icon_dir; ?>"></span></a></th>
					<th class="sort-column last" style="text-align: right; width: 170px;"><a data-sort-by="last_modified" class="sort-column<?php if ($sort_by == 'last_modified') { ?> sort-active sort-<?php echo $sort_dir; } ?> sort-by-last_modified" href="#sort-by-date"><?php echo __('Last Modified'); ?><span<?php if ($sort_by != 'last_modified') { ?> style="display: none;"<?php } ?> class="ui-icon ui-icon-triangle-1-<?php echo $icon_dir; ?>"></span></a></th>
				</tr>
			</table>
			<div id="file-list-content">
				<?php
				if (empty($files['/'.$active_path])) {
					?><p class="none-found"><?php echo __('Empty'); ?></p><?php
				} else {
					if ($view_mode == 'list') {
						?>
				<table id="file-list" cellspacing="0" cellpadding="0" border="0">
						<?php
					} else {
						?>
				<div id="file-list-selectable-area">
						<?php
					}
					foreach ($files['/'.$active_path] as $index => $file_info) {
						if ($file_info['kind'] == 'folder') {
							if (!empty($files[$file_info['path']])) {
								$file_title = $file_info['name'].' ('.sprintf(__('%s items'), count($files[$file_info['path']])).')';
							} else {
								$file_title = $file_info['name'].' ('.__('Empty').')';
							}
						} else {
							$file_title = $file_info['name'].' ('.$file_info['filesize'].' '.$file_info['kind'].')';
						}
						if ($file_info['is_dir']) {
							$type = 'folder';
						} else {
							$type = 'file';
						}
						if ($view_mode == 'list') {
							?>
					<tr class="file-info <?php echo $type; ?> <?php echo $Navigation->tiger_stripe('file-list'); ?>" data-type="<?php echo $type; ?>" data-parent-path="<?php echo $file_info['parent_path'] ?>" data-file-index="<?php echo $index; ?>" data-path="<?php echo $file_info['path']; ?>" data-last-modified="<?php echo $file_info['last_modified']; ?>" title="<?php echo $file_title; ?>">
						<td class="filename" style="background-image: url(/framework/modules/file_manager/images/icons/16/<?php echo $file_info['icon']; ?>);"><?php echo $file_info['name']; ?></td>
						<td class="file-date" style="width: 150px; text-align: right;"><?php echo $file_info['last_modified']; ?></td>
					</tr>
							<?php
						} else {
							$extra_class = '';
							if ($file_info['is_file'] && !empty($file_info['thumb_path'])) {
								$extra_class = 'thumbnail';
								$icon_path = $file_info['thumb_path'];
							} else {
								$icon_path = '/framework/modules/file_manager/images/icons/scalable/'.$file_info['icon'];
							}
							?>
					<div class="icon <?php echo $extra_class; ?>" style="width: <?php echo $icon_size+30; ?>px; height: <?php echo $icon_size+70; ?>px;">
						<a class="file-info <?php echo $type; ?>" data-type="<?php echo $type; ?>" data-parent-path="<?php echo $file_info['parent_path'] ?>" data-file-index="<?php echo $index; ?>" data-path="<?php echo $file_info['path']; ?>" data-last-modified="<?php echo $file_info['last_modified']; ?>" href="#view-file-info" title="<?php echo $file_title; ?>">
							<span class="icon-image" style="width: <?php echo $icon_size; ?>px; height: <?php echo $icon_size; ?>px;"><img class="file-icon" src="<?php echo $icon_path; ?>"></span>
							<span class="filename"><?php echo $file_info['name']; ?></span>
						</a>
					</div>
							<?php
						}
					}
					if ($view_mode == 'list') {
						?>
				</table>
						<?php
					} else {
						?>
					<div class="clearance"></div>
				</div>
						<?php
					}
				}
				?>
			</div>
			<div id="folder-admin-buttons" class="controls">
				<?php
				if ($FileManager->user_can_new_folder()) {
					?>
				<a id="bfm-new-folder" class="ui-button-icons-only" href="#new-folder"><?php echo __('New Folder'); ?></a>
					<?php
				}
				if ($FileManager->user_can_rename()) {
					?>
				<a id="bfm-rename-file" class="ui-button-icons-only" href="#rename-file" style="display: none;"><?php echo __('Rename'); ?></a>
					<?php
				}
				if ($FileManager->user_can_delete()) {
					?>
				<a id="trash-button" class="ui-button-icon-only" href="#delete" style="display: none;"><?php echo __('Delete Files/Folders'); ?></a>
					<?php
				}
				?>
			</div>
			<div id="icon-sizer"<?php if ($view_mode == 'list') { ?> style="display: none;"<?php } ?>>
				<div id="icon-slider"></div><?php echo __('Icon Size'); ?>:&nbsp;&nbsp;
			</div>
		</div>
	</div>
</div>
<div id="bfm-help-content">
	<p>The Biscuit File Manager works similarly to Windows Explorer or Mac OS Finder. Here's a cheat sheet to help you remember some of the shortcuts.</p>
	<h4>Buttons/Controls</h4>
	<ul>
		<li><img src="/framework/modules/file_manager/images/help/button-refresh.png" alt="<?php echo __('Refresh button'); ?>" style="vertical-align: middle;"> Refresh the file list</li>
		<li><img src="/framework/modules/file_manager/images/help/button-list-view.png" alt="<?php echo __('List view button'); ?>" style="vertical-align: middle;"> Switch to List view</li>
		<li><img src="/framework/modules/file_manager/images/help/button-icon-view.png" alt="<?php echo __('Icon view button'); ?>" style="vertical-align: middle;"> Switch to icon view</li>
		<li><img src="/framework/modules/file_manager/images/help/icon-size-slider.png" alt="<?php echo __('Icon size slider'); ?>" style="vertical-align: middle;"><br>In icon view only, drag this to change icon size</li>
		<?php
		if ($FileManager->user_can_new_folder()) {
			?>
		<li><img src="/framework/modules/file_manager/images/help/button-new-folder.png" alt="<?php echo __('New folder button'); ?>" style="vertical-align: middle;"> Create new folder</li>
			<?php
		}
		if ($FileManager->user_can_delete()) {
			?>
		<li><img src="/framework/modules/file_manager/images/help/button-delete.png" alt="<?php echo __('Delete button'); ?>" style="vertical-align: middle;"> Delete selected items, appears when one or more items are selected</li>
			<?php
		}
		if ($FileManager->user_can_rename()) {
			?>
		<li><img src="/framework/modules/file_manager/images/help/button-rename.png" alt="<?php echo __('Rename button'); ?>" style="vertical-align: middle;"> Rename selected item, appears when only ONE item is selected</li>
			<?php
		}
		?>
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
<script type="text/javascript">
	$(document).ready(function() {
		FileManager.files = <?php echo Crumbs::to_json($files); ?>;
		FileManager.base_url = '<?php echo $FileManager->url(); ?>';
		FileManager.base_upload_path = '<?php echo $FileManager->base_upload_path(); ?>';
		FileManager.active_path = '<?php echo $active_path; ?>';
		FileManager.icon_size = <?php echo $icon_size; ?>;
		FileManager.view_mode = '<?php echo $view_mode; ?>';
		FileManager.can_delete = <?php echo ($FileManager->user_can_delete() ? 'true' : 'false'); ?>;
		FileManager.can_upload = <?php echo ($FileManager->user_can_upload() ? 'true' : 'false'); ?>;
		FileManager.can_rename = <?php echo ($FileManager->user_can_rename() ? 'true' : 'false'); ?>;
		FileManager.can_copy_move = <?php echo ($FileManager->user_can_copy_move() ? 'true' : 'false'); ?>;
		FileManager.sort.sort_by = '<?php echo $sort_by; ?>';
		FileManager.sort.sort_dir = '<?php echo $sort_dir; ?>';
		<?php
		if (!empty($tiny_mce)) {
			?>

		FileManager.tiny_mce = true;
		FileManager.is_selector_dialog = true;
			<?php
		} else if (!empty($target_el_id)) {
			?>

		FileManager.target_el_id = '<?php echo $target_el_id; ?>';
		FileManager.is_selector_dialog = true;
			<?php
		}
		?>
		FileManager.uploadify_module_installed = <?php echo ($Biscuit->module_exists('Uploadify')) ? 'true' : 'false'; ?>;
		<?php
		if ($Biscuit->module_exists('Uploadify')) {
			?>
		FileManager.uploadify.session_id = '<?php echo Session::id(); ?>';
		UploadifyHelpers.max_file_size = <?php echo FileUpload::max_size(); ?>;
			<?php
		}
		?>
		FileManager.init();
	});
</script>
<script id="file-rename-template" type="text/x-jquery-tmpl">
	<form id="rename-file-form" name="rename-file-form" action="${form_action}" accept-charset="UTF-8" method="post">
		<p>
			<label for="attr_new_name">*<?php echo __('Name'); ?>:</label><input id="attr_new_name" class="text" type="text" name="new_name" value="${original_filename}">
			<span class="instructions"><?php echo __('May contain only letters, numbers, hyphens and underscores'); ?></span>
		</p>
		<div class="controls" style="display: none;"><input class="SubmitButton" type="submit" value="<?php echo __('Rename'); ?>"></div>
	</form>
</script>
<script id="new-folder-template" type="text/x-jquery-tmpl">
	<form id="new-folder-form" name="new-folder-form" action="${form_action}" accept-charset="UTF-8" method="post">
		<p>
			<label for="attr_folder_name">*<?php echo __('Folder Name'); ?>:</label><input id="attr_folder_name" class="text" type="text" name="folder_name" value="">
			<span class="instructions"><?php echo __('May contain only letters, numbers, hyphens and underscores'); ?></span>
		</p>
		<div class="controls" style="display: none;"><input class="SubmitButton" type="submit" value="Rename"></div>
	</form>
</script>
<script id="file-info-template" type="text/x-jquery-tmpl">
	<div id="file-info-content">
		<div id="file-details">
			<div id="image-thumb">
				<img src="${thumbnail_source}" alt="${file_data.name}">
			</div>
			<div id="file-info">
				<div id="file-specs">
					<p>
						<strong><?php echo __('Name'); ?>:</strong> ${file_data.name}<br>
						<strong><?php echo __('Kind'); ?>:</strong> ${file_data.kind}<br>
						<strong><?php echo __('Size'); ?>:</strong> ${file_data.size}<br>
						<strong><?php echo __('Last Modified'); ?>:</strong> ${file_data.last_modified}<br>
						<a target="_blank" href="${file_data.path}"><strong><?php echo __('Download/Open'); ?></strong>{{if file_data.width != '' && file_data.height != ''}} (${file_data.width} x ${file_data.height}){{/if}}</a>
					</p>
					{{if selectable}}
						<a class="ui-button file-info-select-button" href="${file_data.path}" target="_blank"><?php echo __('Select'); ?></a><div class="clearance"></div>
					{{/if}}
					<p style="margin: 10px 0 0;">
						<?php echo __('Full URL (for copy/paste)'); ?>:<br>
						<input id="file-full-url-input" class="text" type="text" readonly="readonly" value="${file_data.web_link}">
					</p>
				</div>
				{{if has_variants}}
					<h4><?php echo __('Other Variations'); ?></h4>
					<div id="file-variants">
					{{each variants}}
						<div class="variant-item">
							<a href="${this.path}" target="_blank" title="<?php echo __('Preview in a new window'); ?>">${this.name}</a>
							{{if selectable}}
								<a class="ui-button file-info-select-button" href="${this.path}"><?php echo __('Select'); ?></a><div class="clearance"></div>
							{{/if}}
						</div>
					{{/each}}
					</div>
				{{/if}}
			</div>
			<div class="clearance"></div>
		</div>
	</div>
</script>
<script id="file-list-template" type="text/x-jquery-tmpl">
	<table id="file-list" width="100%" cellspacing="0" cellpadding="0" border="0">
		{{each files}}
			<tr class="file-info ${this.type} ${this.stripe_class}" title="this.file_title" data-last-modified="${this.last_modified}" data-path="${this.path}" data-file-index="${this.file_index}" data-parent-path="${this.parent_path}" data-type="${this.type}">
				<td class="filename" style="background-image: url(${this.icon_path});">${this.name}</td>
				<td class="file-date" style="width: 150px; text-align: right;">${this.last_modified}</td>
			</tr>
		{{/each}}
	</table>
</script>
<script id="file-icon-template" type="text/x-jquery-tmpl">
	<div id="file-list-selectable-area">
		{{each files}}
			<div class="icon ${this.extra_class}" style="width: ${outer_width}px; height: ${outer_height}px;">
				<a class="file-info ${this.type}" data-type="${this.type}" data-parent-path="${this.parent_path}" data-file-index="${this.file_index}" data-path="${this.path}" data-last-modified="${this.last_modified}" href="#view-file-info" title="${this.file_title}">
					<span class="icon-image" style="width: ${icon_size}px; height: ${icon_size}px;"><img class="file-icon" src="${this.icon_path}"></span>
					<span class="filename">${this.name}</span>
				</a>
			</div>
		{{/each}}
		<div class="clearance"></div>
	</div>
</script>
