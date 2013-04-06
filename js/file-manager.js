var FileManager = {
	is_selector_dialog: false, // Set to true tells it that selecting a file goes to a target element
	tiny_mce: false, // Set to true when using tiny MCE
	target_el_id: null, // For standalone file browse popup
	files: null,
	base_url: null,
	base_upload_path: null,
	active_path: null,
	view_mode: null,
	icon_size: null,
	file_info_dialog: null,
	can_delete: false,
	can_upload: false,
	can_rename: false,
	can_copy_move: false,
	currently_refreshing: false,
	currently_dragging_files: false,
	can_use_uploadify: false,
	uploadify_module_installed: false,
	rename_timeout: null,
	uploadify: {
		session_id: null,
		enabled: false,
		total_file_count: 0,
		total_file_size: 0,
		last_known_speed: 0
	},
	init: function() {
		this.init_folder_list();
		this.init_icon_size_slider();
		this.init_buttons();
		this.init_file_actions();
		this.init_upload_actions();
		this.init_drag_and_drop();
		this.init_keyboard_shortcuts();
		$('#file-list tr').live('mouseover',function() {
			$(this).addClass('hovered');
		});
		$('#file-list tr').live('mouseout',function() {
			$(this).removeClass('hovered');
		});
		$('#file-full-url-input').live('focus', function() {
			$(this).select();
		});
		this.init_drag_to_select();
		$('#bfm-header .controls a').show();
		$('#folder-admin-buttons').show();
		// Prevent anything in the header or content areas (except for input elements) from being selected. This is so when we drag things, in particular
		// dragging the box around icons via jquery selectable, it doesn't select text and such at the same time
		$('#bfm-header *, #bfm-content *:not(input)')
			.attr('unselectable', 'on')
			.each(function() {
				this.onselectstart = function() { return false; }
			})
			.css({
				'-moz-user-select': 'none',
				'-webkit-user-select': 'none',
				'user-select': 'none'
			});
	},
	init_folder_list: function() {
		$('dl#folder-list dd.inactive dl').css('display', 'none');
		$('dl#folder-list a.expander').live('click',function() {
			var dl_el = $(this).parent().find('dl:first');
			if (dl_el.length > 0) {
				var is_active = $(this).parent().hasClass('active');
				if (is_active) {
					$(this).removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-e');
					dl_el.slideUp('fast', function() {
						$(this).parent().removeClass('active').addClass('inactive');
					});
				} else {
					$(this).removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-s');
					dl_el.slideDown('fast', function() {
						$(this).parent().removeClass('inactive').addClass('active');
					});
				}
			}
			$(this).blur();
			return false;
		});
	},
	init_icon_size_slider: function() {
		$('#icon-slider').slider({
			min: 64,
			max: 256,
			step: 1,
			value: this.icon_size,
			slide: function(event, ui) {
				FileManager.icon_size = ui.value;
				$('#file-list-content .icon').css({
					'width': (ui.value+30)+'px',
					'height': (ui.value+70)+'px'
				});
				$('#file-list-content .icon .icon-image').css({
					'width': ui.value+'px',
					'height': ui.value+'px'
				});
			},
			stop: function(event, ui) {
				Biscuit.Cookie('BFMViewOptions',FileManager.view_mode+','+FileManager.icon_size+','+FileManager.sort.sort_by+','+FileManager.sort.sort_dir, {
					'expires': 365,
					'path': '/file-manager'
				});
			}
		});
	},
	init_buttons: function() {
		$('#bfm-new-folder').live('click', function() {
			FileManager.new_folder();
			return false;
		}).button({
			text: false,
			icons: {
				secondary: 'ui-icon-plus',
				primary: 'ui-icon-folder-collapsed'
			}
		});
		$('#bfm-help-button').live('click', function(event) {
			event.preventDefault();
			FileManager.display_help();
			return false;
		}).button({
			text: false,
			icons: {
				primary: 'ui-icon-help'
			}
		});
		$('#bfm-refresh-button').live('click', function(event) {
			event.preventDefault();
			FileManager.refresh_files();
			return false;
		}).button({
			text: false,
			icons: {
				primary: 'ui-icon-refresh'
			}
		});
		$('#bfm-icon-view-button').live('click', function(event) {
			event.preventDefault();
			if (!FileManager.currently_refreshing) {
				FileManager.set_view_mode('icon');
			}
			return false;
		}).button({
			text: false,
			icons: {
				primary: 'ui-icon-image'
			}
		});
		$('#bfm-list-view-button').live('click', function(event) {
			event.preventDefault();
			if (!FileManager.currently_refreshing) {
				FileManager.set_view_mode('list');
			}
			return false;
		}).button({
			text: false,
			icons: {
				primary: 'ui-icon-grip-solid-horizontal'
			}
		});
		$('#trash-button').live('click', function(event) {
			event.preventDefault();
			if (FileManager.can_delete) {
				FileManager.handle_delete();
			}
			return false;
		}).button({
			text: false,
			icons: {
				primary: 'ui-icon-trash'
			}
		});
		$('#bfm-rename-file').live('click', function(event) {
			event.preventDefault();
			if (FileManager.can_rename) {
				FileManager.rename_selected();
			}
			return false;
		}).button({
			text: false,
			icons: {
				primary: 'ui-icon-pencil'
			}
		});
		$('a.file-info-select-button').live('click', function(event) {
			event.preventDefault();
			FileManager.select_file($(this).attr('href'));
			return false;
		});
	},
	init_file_actions: function() {
		$('#file-list-content').click(function(event) {
			if ($(event.target).attr('id') == 'file-list-content' || $(event.target).attr('id') == 'file-list-selectable-area' || $(event.target).hasClass('icon')) {
				FileManager.deselect_all();
			}
		});
		$('#folder-list a.folder, #bfm-breadcrumbs a.folder').live('click', function(event) {
			event.preventDefault();
			FileManager.change_folder($(this).data('path'));
			return false;
		});
		$('#file-list-content .file-info').live('dblclick',function(event) {
			event.preventDefault();
			if (!event.metaKey && !event.shiftKey && !event.ctrlKey) { // Only do double-click action if no modifiers are being held down
				FileManager.handle_double_click(this);
			}
			return false;
		});
		$('#file-list-content .file-info').live('click',function(event) {
			event.preventDefault();
			if (!event.metaKey && !event.shiftKey && !event.ctrlKey) {
				var was_already_selected = $(this).hasClass('selected');
				var others_were_selected = ($('#file-list-content .selected').length > 1);
				$('#file-list-content .selected').removeClass('selected');
				$(this).addClass('selected');
				if (was_already_selected && !others_were_selected) {
					clearTimeout(FileManager.rename_timeout);
					FileManager.rename_timeout = setTimeout('FileManager.rename_selected();', 550);
				}
			} else if (event.metaKey || event.ctrlKey) {
				clearTimeout(FileManager.rename_timeout);
				$(this).toggleClass('selected');
			} else if (event.shiftKey) {
				clearTimeout(FileManager.rename_timeout);
				$(this).addClass('selected');
				var first_index = $('#file-list-content .selected:first').index('#file-list-content .file-info')+1;
				var last_index = $('#file-list-content .selected:last').index('#file-list-content .file-info');
				$('#file-list-content .file-info').slice(first_index, last_index).addClass('selected');
			}
			if ($('#file-list-content .selected').length > 0 && FileManager.can_delete) {
				$('#trash-button').show();
			} else {
				$('#trash-button').hide();
			}
			if ($('#file-list-content .selected').length == 1 && FileManager.can_rename) {
				$('#bfm-rename-file').show();
			} else {
				$('#bfm-rename-file').hide();
			}
			return false;
		});
		$('a.sort-column').live('click',function() {
			FileManager.sort.do_sort(this);
			return false;
		});
	},
	handle_double_click: function(file_element) {
		clearTimeout(FileManager.rename_timeout);
		if (!$(file_element).hasClass('selected')) {
			$(file_element).addClass('selected');
		}
		var type = $(file_element).data('type');
		if (type == 'folder') {
			FileManager.change_folder($(file_element).data('path'));
		} else {
			FileManager.show_file_info($(file_element).data('parent-path'), $(file_element).data('file-index'));
		}
	},
	init_upload_actions: function() {
		if (!this.can_upload) {
			return;
		}
		$('#upload-submit-button').hide();
		$('#bfm-file-field').change(function() {
			$('#file-upload-form').submit();
		});
		$('#uploadify-cancel-all').live('click', function(event) {
			event.preventDefault();
			$('#bfm-file-field').uploadifyClearQueue();
			FileManager.uploadify.total_file_count = 0;
			FileManager.uploadify.total_file_size = 0;
			FileManager.uploadify.last_known_speed = 0;
			$('#uploadify-progress #uploadify-progress-bar').progressbar('value', 0);
			$('#uploadify-progress #uploadify-current-file').text('');
			$('#uploadify-progress #uploadify-progress-info').text('');
			$('#uploadify-progress').hide();
			$('body').css({
				'cursor': ''
			});
			FileManager.refresh_files();
			return false;
		});
		if (this.uploadify_module_installed && $.isFunction($.fn.uploadify) && swfobject != undefined && $.isFunction(swfobject.hasFlashPlayerVersion)) {
			this.can_use_uploadify = swfobject.hasFlashPlayerVersion("9.0.24");
		}
		if (this.can_use_uploadify) {
			this.uploadify.enabled = true;
			$('#upload-bar').addClass('uploadify');
			$('#uploadify-progress #uploadify-progress-bar').progressbar({
				value: 0
			});
			$('#bfm-file-field').uploadify({
				folder: this.base_upload_path+'/'+this.active_path,
				uploader: '/modules/uploadify/vendor/jquery.uploadify/uploadify.swf',
				script: '/uploadify/handle_upload',
				fileDataName: 'uploadify_files',
				fileDesc: __('uploadify_file_data_name'),
				fileExt: '*',
				auto: true,
				multi: true,
				cancelImg: '/framework/themes/sea_biscuit/images/x-button.png',
				checkScript: '/uploadify/check_existing',
				sizeLimit: UploadifyHelpers.max_file_size,
				scriptData: {
					session_id: this.uploadify.session_id,
					overwrite: true
				},
				onCheck: function(event, data, key) {
					if (typeof(data[key]) == 'string') {
						var replaceFile = confirm(__('confirm_overwrite', [data[key]]));
						if (!replaceFile) {
							$(event.target).uploadifyCancel(key);
						}
					}
					return false;
				},
				onComplete: function(event, ID, fileObj, response, data) {
					if (response != '1') {
						$('#bfm-file-field').uploadifyCancel(id);
						if (UploadifyHelpers.upload_errors['bfm-file-field'] == undefined) {
							UploadifyHelpers.upload_errors['bfm-file-field'] = [];
						}
						UploadifyHelpers.upload_errors['bfm-file-field'].push('<strong>'+fileObj.name+'</strong>: '+response);
					}
					FileManager.uploadify.total_file_count -= 1;
					Biscuit.Session.Extend();
				},
				onAllComplete: function() {
					UploadifyHelpers.all_complete('bfm-file-field');
					FileManager.uploadify.total_file_count = 0;
					FileManager.uploadify.total_file_size = 0;
					FileManager.uploadify.last_known_speed = 0;
					$('#uploadify-progress #uploadify-progress-bar').progressbar('value', 0);
					$('#uploadify-progress #uploadify-current-file').text('');
					$('#uploadify-progress #uploadify-progress-info').text('');
					$('#uploadify-progress').hide();
					$('body').css({
						'cursor': ''
					});
					FileManager.refresh_files();
				},
				onSelect: function(event, id, fileObj) {
					if (fileObj.size > UploadifyHelpers.max_file_size) {
						$('#bfm-file-field').uploadifyCancel(id);
						UploadifyHelpers.oversize_files.push(fileObj.name);
					}
				},
				onSelectOnce: function(event, data) {
					FileManager.uploadify.total_file_size = data.allBytesTotal;
					FileManager.uploadify.total_file_count = data.fileCount;
					// Ensure uploadify is using the currently active upload folder:
					$('#bfm-file-field').uploadifySettings('folder', FileManager.base_upload_path+'/'+FileManager.active_path);
					if (UploadifyHelpers.oversize_files.length > 0) {
						var message = '<h4>'+__('files_too_big_msg')+'</h4><ul><li>'+UploadifyHelpers.oversize_files.join('</li><li>')+'</li></ul>';
						UploadifyHelpers.oversize_files = []
						Biscuit.Crumbs.Alert(message, __('error_box_title'));
					}
				},
				onOpen: function(event, id, fileObj) {
					if ($('#uploadify-progress').css('display') == 'none') {
						$('#uploadify-progress').show();
						$('body').css({
							'cursor': 'progress'
						});
					}
					$('#uploadify-progress #uploadify-current-file').text(fileObj.name);
					$('#uploadify-progress #uploadify-progress-info').text(__('uploadify_upload_info', [FileManager.uploadify.last_known_speed, FileManager.uploadify.total_file_count]));
				},
				onProgress: function(event, id, fileObj, data) {
					FileManager.uploadify.last_known_speed = (Math.round(data.speed*10)/10);
					var total_percentage = Math.round((data.allBytesLoaded/FileManager.uploadify.total_file_size)*100);
					$('#uploadify-progress #uploadify-progress-bar').progressbar('value', total_percentage);
					$('#uploadify-progress #uploadify-progress-info').text(__('uploadify_upload_info', [FileManager.uploadify.last_known_speed, FileManager.uploadify.total_file_count]));
				},
				onError: function(event, id, fileObj, errorObj) {
					$('#bfm-file-field').uploadifyCancel(id);
					if (UploadifyHelpers.upload_errors['bfm-file-field'] == undefined) {
						UploadifyHelpers.upload_errors['bfm-file-field'] = [];
					}
					if (errorObj.type != 'File Size') {
						UploadifyHelpers.upload_errors['bfm-file-field'].push('<strong>'+fileObj.name+'</strong>: '+errorObj.type+' '+errorObj.info);
					}
					Biscuit.Session.Extend();
				}
			});
		}
	},
	init_drag_and_drop: function() {
		this.init_draggables();
		this.init_droppables('#folder-list .folder, #file-list-content .folder');
	},
	init_draggables: function() {
		if (!this.can_copy_move) {
			return;
		}
		$('#file-list-content .file-info').draggable({
			revert: true,
			revertDuration: 0,
			zIndex: 5000,
			cursor: 'move',
			cursorAt: {
				left: 13,
				top: 12
			},
			opacity: 0.95,
			appendTo: 'body',
			scroll: false,
			start: function(event, ui) {
				$('body').css({'overflow': 'hidden'});
				FileManager.currently_dragging_files = true;
				if (event.altKey || event.ctrlKey) {
					FileManager.set_drag_operation_indicator('copy');
				} else {
					FileManager.set_drag_operation_indicator('move');
				}
			},
			stop: function(event, ui) {
				$('body').css({'overflow': ''});
				FileManager.currently_dragging_files = false;
				if (event.altKey || event.ctrlKey) {
					FileManager.set_drag_operation_indicator('copy');
				} else {
					FileManager.set_drag_operation_indicator('move');
				}
			},
			helper: function(event) {
				if (FileManager.view_mode == 'list') {
					if ($(event.target).is('tr')) {
						var item = $(event.target);
					} else {
						var item = $(event.target).parents('tr');
					}
				} else {
					if ($(event.target).is('a')) {
						var item = $(event.target);
					} else {
						var item = $(event.target).parents('a');
					}
				}
				if (!item.hasClass('selected')) {
					$('#file-list-content .selected').removeClass('selected');
					item.addClass('selected');
				}
				var selected_filename_els = $('#file-list-content .selected');
				var html = '<div class="ui-icon ui-icon-transferthick-e-w"></div><div id="file-dragging-operation"><h5>'+__('file_move_items')+'</h5></div>';
				var filenames = []
				var count = 1;
				selected_filename_els.each(function() {
					var this_file_info = FileManager.files[$(this).data('parent-path')][$(this).data('file-index')];
					Biscuit.Console.log(this_file_info);
					var filename = $(this).children('.filename').text();
					if (this_file_info.thumb_path != '') {
						var img_src = this_file_info.thumb_path;
					} else {
						var img_src = '/framework/modules/file_manager/images/icons/scalable/'+this_file_info.icon;
					}
					filenames.push('<div class="helper-file-item"><img src="'+img_src+'" width="32" height="32" style="vertical-align: middle; margin: 0 4px 0 0; width: 32px; height: 32px;">'+filename+'</div>');
					if (count == 10) {
						return false;
					}
					count++;
				});
				var remaining = '';
				if (selected_filename_els.length > 10) {
					remaining = '<div class="helper-file-item" style="text-align: center;">'+__('plus_x_more_files', [selected_filename_els.length-10])+'</div>';
				}
				html += '<div id="drag-items">'+filenames.join('')+remaining+'</div>';
				return $('<div>').attr('id','dragging-helper').html(html);
			}
		});
	},
	init_droppables: function(selector) {
		if (!this.can_copy_move) {
			return;
		}
		$(selector).droppable({
			tolerance: 'pointer',
			hoverClass: 'highlight',
			drop: function(event, ui) {
				var mode = 'move';
				if (event.altKey || event.ctrlKey) {
					mode = 'copy';
				}
				FileManager.copy_move_selected($(event.target).data('path'), mode);
			}
		});
	},
	set_drag_operation_indicator: function(mode) {
		if (mode == 'copy') {
			$('#file-dragging-operation').html('<h5>'+__('file_copy_items')+'</h5>');
			$('#dragging-helper .ui-icon').removeClass('ui-icon-transferthick-e-w').addClass('ui-icon-copy');
		} else if (mode == 'move') {
			$('#file-dragging-operation').html('<h5>'+__('file_move_items')+'</h5>');
			$('#dragging-helper .ui-icon').removeClass('ui-icon-copy').addClass('ui-icon-transferthick-e-w');
		}
	},
	init_drag_to_select: function() {
		// Make sure selectable is first destroyed. This is necessary when the file list is redrawn (ie. when switching folders), plus we don't want it
		// functioning in list view
		$('#file-list-selectable-area').selectable('destroy');
		if (this.view_mode == 'icon') {
			// Enabled selectable in icon view
			$('#file-list-selectable-area').selectable({
				filter: '.file-info',
				delay: 20,
				start: function() {
					// Prevent dragging off the edges of the document from causing scrollbars to appear on the window:
					$('body').css({
						'overflow': 'hidden'
					})
				},
				stop: function() {
					// Restore normal body overflow:
					$('body').css({
						'overflow': ''
					})
				},
				selecting: function(event, ui) {
					$(event.target).find('.ui-selectee').each(function() {
						if ($(this).hasClass('ui-selecting')) {
							$(this).addClass('selected');
						}
					});
					if ($('#file-list-content .selected').length >= 1) {
						$('#trash-button').show();
						if ($('#file-list-content .selected').length == 1) {
							$('#bfm-rename-file').show();
						} else {
							$('#bfm-rename-file').hide();
						}
					}
				},
				unselecting: function(event, ui) {
					$(event.target).find('.ui-selectee').each(function() {
						if (!$(this).hasClass('ui-selecting')) {
							$(this).removeClass('selected');
						}
					});
					if ($('#file-list-content .selected').length >= 1) {
						$('#trash-button').show();
						if ($('#file-list-content .selected').length == 1) {
							$('#bfm-rename-file').show();
						} else {
							$('#bfm-rename-file').hide();
						}
					} else {
						$('#trash-button').hide();
						$('#bfm-rename-file').hide();
					}
				}
			});
		}
	},
	deselect_all: function() {
		$('#file-list-content .selected').removeClass('selected');
		$('#trash-button').hide();
		$('#bfm-rename-file').hide();
	},
	init_keyboard_shortcuts: function() {
		$(document).keydown(function(event) {
			if (FileManager.currently_dragging_files && (event.keyCode == 17 || event.keyCode == 18)) {
				// When Alt or CTRL are pressed while dragging files, make sure the drag helper element indicates copying rather than moving:
				FileManager.set_drag_operation_indicator('copy');
			}
		});
		$(document).keyup(function(event) {
			if ($('.ui-dialog').length == 0) { // Only respond to keyboard shortcuts when there are no dialogs open
				event.preventDefault();
				if (event.ctrlKey && event.shiftKey) {
					switch (event.keyCode) {
						case 65: // letter A
							$('#file-list-content .file-info').addClass('selected');
							$('#trash-button').show();
							break;
						case 76: // letter L
							FileManager.set_view_mode('list');
							break;
						case 73: // letter I
							FileManager.set_view_mode('icon');
							break;
						case 78: // letter N
							FileManager.new_folder();
							break;
						case 82: // letter R
							if ($('#file-list-content .selected').length == 1) {
								FileManager.rename_selected();
							}
							break;
					}
				} else {
					if (FileManager.currently_dragging_files && (event.keyCode == 17 || event.keyCode == 18)) {
						// When Alt or CTRL are released while dragging files, make sure the drag helper element indicates moving rather than copying:
						FileManager.set_drag_operation_indicator('move');
					} else if ($('#file-list-content .selected').length > 0) {
						if (event.keyCode == 8 || event.keyCode == 46) {
							// Delete or backspace key
							FileManager.delete_selected_items();
						} else if (event.keyCode == 27) {
							// Escape key
							FileManager.deselect_all();
						}
					}
				}
				return false;
			}
		});
	},
	copy_move_selected: function(destination, mode) {
		if (!this.can_copy_move) {
			return;
		}
		var files_to_copy_or_move = [];
		var existing_files = [];
		var source_files_to_confirm = [];
		$('#file-list-content .selected').each(function() {
			var my_type = $(this).data('type');
			var my_path = $(this).data('path');
			var my_parent_path = $(this).data('parent-path');
			if (my_path != destination && my_parent_path != destination) {
				var path_bits = my_path.split('/');
				var filename = path_bits.pop();
				var dest_path = destination+'/'+filename;
				var file_exists = false;
				for (var i in FileManager.files[destination]) {
					if (FileManager.files[destination][i].path == dest_path) {
						existing_files.push(filename);
						source_files_to_confirm.push(my_path);
						file_exists = true;
						break;
					}
				}
				if (!file_exists) {
					files_to_copy_or_move.push(my_path);
				}
			}
		});
		if (files_to_copy_or_move.length == 0 && existing_files.length == 0) {
			// List ended up empty, which means all the selected files were trying to be moved/copied into their current destination. We'll just stop
			// silently as it should be obvious and it's annoying to be given a warning about it every time it happens.
			return;
		}
		var count = 0;
		var denied_items = [];
		var overwrite_all = false;
		var cancel_all = false;
		var skip_all = false;
		confirm_overwrite();
		function confirm_overwrite() {
			if (cancel_all) {
				return;
			}
			if (count < existing_files.length) {
				var apply_to_all_markup = '';
				if (existing_files.length > 1) {
					apply_to_all_markup = '<div style="position: absolute; right: 15px; bottom: 5px;"><input id="overwrite-confirm-apply-to-all" type="checkbox" value="1"><label for="overwrite-confirm-apply-to-all">'+__('file_overwrite_confirm_apply_to_all')+'</div>';
				}
				$('body').append($('<div>')
					.attr('id','overwrite-confirmation-msg')
					.css({'display': 'none'})
					.html('<h4>'+__('file_overwrite_confirm', [existing_files[count]])+'</h4>'+apply_to_all_markup)
				);
				// If the window is scrolled down when the dialog opens it scrolls to the top on open but the dialog will try to center based
				// on where the viewport was scrolled to before it opened. This results in the dialog being off-screen. To avoid this issue, we
				// force scroll the window to the top prior to opening the dialog, which then always gets position 30px from the top
				var buttons = {};
				buttons[__('file_overwrite_label')] = function() {
					if ($('#overwrite-confirm-apply-to-all').length > 0 && $('#overwrite-confirm-apply-to-all').attr('checked')) {
						for (var i in source_files_to_confirm) {
							if ($.inArray(source_files_to_confirm[i], denied_items) < 0 && !$.inArray(source_files_to_confirm[i], files_to_copy_or_move) < 0) {
								files_to_copy_or_move.push(source_files_to_confirm[i]);
							}
						}
						overwrite_all = true;
					} else {
						files_to_copy_or_move.push(source_files_to_confirm[count]);
					}
					$(this).dialog('close');
				}
				buttons[__('file_overwrite_skip_label')] = function() {
					if ($('#overwrite-confirm-apply-to-all').length > 0 && $('#overwrite-confirm-apply-to-all').attr('checked')) {
						skip_all = true;
					} else {
						denied_items.push(source_files_to_confirm[count]);
					}
					$(this).dialog('close');
				}
				buttons[__('file_operation_stop')] = function() {
					cancel_all = true;
					$(this).dialog('close');
				}
				$('#overwrite-confirmation-msg').dialog({
					modal: true,
					closeOnEscape: false,
					title: __('confirm_box_title'),
					width: 560,
					position: 'center',
					show: 'fade',
					hide: 'fade',
					resizable: false,
					open: function(event, ui) {
						$('#overwrite-confirmation-msg').prev().find('.ui-dialog-titlebar-close').hide();
						$('#overwrite-confirmation-msg').next('.ui-dialog-buttonpane').find('button:first').addClass('attention');
					},
					close: function(event, ui) {
						if (!cancel_all) {
							if (overwrite_all || skip_all) {
								count = existing_files.length;
							} else {
								count++;
							}
						}
						$('#overwrite-confirmation-msg').remove();
						confirm_overwrite();
					},
					buttons: buttons
				});
			} else {
				if (files_to_copy_or_move.length == 0) {
					return;
				}
				var post_data = {
					mode: mode,
					destination: destination
				}
				for (var x in files_to_copy_or_move) {
					post_data['source_paths['+x+']'] = files_to_copy_or_move[x];
				}
				if (mode == 'move') {
					var msg = __('file_moving_message');
				} else if (mode == 'copy') {
					var msg = __('file_copying_message');
				}
				Biscuit.Crumbs.ShowCoverThrobber('bfm-content', msg);
				Biscuit.Ajax.Request(FileManager.base_url+'/copy_move','json', {
					type: 'post',
					data: post_data,
					complete: function() {
						FileManager.refresh_files();
					},
					error: function() {
						Biscuit.Crumbs.Alert(__('file_copy_move_error', [mode]));
					}
				});
			}
		}
	},
	rename_selected: function() {
		var source_path = $('#file-list-content .selected').data('path');
		var path_bits = source_path.split('/');
		var original_filename = path_bits.pop();
		var source_parent_path = path_bits.join('/');
		if (source_parent_path.substr(0,1) != '/') {
			source_parent_path = '/'+source_parent_path;
		}
		$('body').append($('<div>')
			.attr('id','file-rename-content')
			.css({'display': 'none'})
			.html($('#file-rename-template').tmpl({
				form_action: this.base_url+'/rename',
				original_filename: original_filename
			}))
		);
		var cancel_action_label = __('cancel_button_label');
		var buttons = {}
		var primary_action = __('file_rename');
		buttons[primary_action] = function() {
			$('#rename-file-form').submit();
		}
		buttons[cancel_button_label] = function() {
			$(this).dialog('close');
		}
		$('#file-rename-content').dialog({
			modal: true,
			closeOnEscape: false,
			title: __('file_rename_title'),
			width: 560,
			position: 'center',
			show: 'fade',
			hide: 'fade',
			resizable: false,
			open: function(event, ui) {
				$('#rename-file-form').submit(function() {
					var file_name = $('#attr_new_name').val();
					if (file_name == '' || file_name.match(/[^a-zA-Z0-9-_\.]/)) {
						Biscuit.Crumbs.Alert(__('file_name_error_message'), __('error_box_title'), function() {
							$('#attr_new_name').focus();
						});
					} else if (file_name == original_filename) {
						$('#file-rename-content').dialog('close');
					} else {
						// Check to see if the new file already exists:
						var files = FileManager.files[source_parent_path];
						var file_exists = false;
						var path_to_check = source_parent_path+'/'+file_name;
						if (path_to_check.substr(0,2) == '//') {
							path_to_check = path_to_check.substr(1);
						}
						for (var i in files) {
							if (files[i].path == path_to_check) {
								file_exists = true;
								break;
							}
						}
						if (file_exists) {
							Biscuit.Crumbs.Alert(__('file_name_existing_error_message'), __('error_box_title'), function() {
								$('#attr_new_name').focus();
							});
						} else {
							var post_url = $('#rename-file-form').attr('action');
							$('#file-rename-content').dialog('close');
							Biscuit.Crumbs.ShowCoverThrobber('bfm-content');
							Biscuit.Ajax.Request(post_url, 'json', {
								type: 'post',
								data: {
									'source_file': source_path,
									'new_name': file_name
								},
								success: function() {
									FileManager.refresh_files();
								},
								error: function() {
									Biscuit.Crumbs.HideCoverThrobber('bfm-content');
									Biscuit.Crumbs.Alert(__('file_rename_error'), __('error_box_title'));
								}
							});
						}
					}
					return false;
				});
				$('#file-rename-content').next('.ui-dialog-buttonpane').find('button:first').addClass('attention');
				$('#attr_new_name').focus();
				var filename = $('#attr_new_name').val();
				var select_end = filename.lastIndexOf('.');
				if (select_end <= 0) {
					select_end = filename.length;
				}
				$('#attr_new_name').selectRange(0, select_end);
			},
			close: function(event, ui) {
				$('#file-rename-content').remove();
			},
			buttons: buttons
		});
	},
	set_view_mode: function(view_mode) {
		if (this.view_mode != view_mode) {
			$('#bfm-header .controls a.active').removeClass('active');
			$('#bfm-'+view_mode+'-view-button').addClass('active');
			this.view_mode = view_mode;
			this.update_file_list('/'+this.active_path);
			Biscuit.Cookie('BFMViewOptions',this.view_mode+','+this.icon_size+','+this.sort.sort_by+','+this.sort.sort_dir, {
				'expires': 365,
				'path': '/file-manager'
			});
		}
	},
	new_folder: function() {
		$('body').append($('<div>')
			.attr('id','folder-form-content')
			.css({'display': 'none'})
			.html($('#new-folder-template').tmpl({
				form_action: this.base_url+'/new_folder'
			}))
		);
		var cancel_action_label = __('cancel_button_label');
		var buttons = {}
		var primary_action = __('folder_create_label');
		buttons[primary_action] = function() {
			$('#new-folder-form').submit();
		}
		buttons[cancel_button_label] = function() {
			$(this).dialog('close');
		}
		$('#folder-form-content').dialog({
			modal: true,
			closeOnEscape: false,
			title: __('file_new_folder'),
			width: 560,
			position: 'center',
			show: 'fade',
			hide: 'fade',
			resizable: false,
			open: function(event, ui) {
				$('#new-folder-form').submit(function() {
					var folder_name = $('#attr_folder_name').val();
					if (folder_name == '' || folder_name.match(/[^a-zA-Z0-9-_\.]/)) {
						Biscuit.Crumbs.Alert(__('file_name_error_message'), __('error_box_title'), function() {
							$('#attr_folder_name').focus();
						});
					} else {
						var post_url = $('#new-folder-form').attr('action');
						$('#folder-form-content').dialog('close');
						Biscuit.Crumbs.ShowCoverThrobber('bfm-content');
						Biscuit.Ajax.Request(post_url, 'json', {
							type: 'post',
							data: {
								'folder_name': folder_name
							},
							success: function() {
								FileManager.refresh_files();
							},
							error: function() {
								Biscuit.Crumbs.HideCoverThrobber('bfm-content');
								Biscuit.Crumbs.Alert(__('folder_creation_fail_message'), __('error_box_title'));
							}
						});
					}
					return false;
				});
				$('#folder-form-content').next('.ui-dialog-buttonpane').find('button:first').addClass('attention');
			},
			close: function(event, ui) {
				$('#folder-form-content').remove();
			},
			buttons: buttons
		});
	},
	handle_delete: function() {
		if ($('#file-list-content .selected').length > 0 && this.can_delete) {
			this.delete_selected_items();
		}
	},
	delete_selected_items: function() {
		if (!this.can_delete) {
			return;
		}
		var filenames = []
		var has_folders = false;
		$('#file-list-content .selected').each(function() {
			var my_path = $(this).data('path');
			var path_bits = my_path.split('/');
			var my_filename = path_bits.pop();
			if ($(this).data('type') == 'folder') {
				has_folders = true;
				my_filename += ' ('+__('folder_kind')+')';
			}
			filenames.push(my_filename);
		});
		var message = '<h4>'+__('file_delete_multi_confirm')+'</h4><div style="max-height: 200px; overflow: auto; margin: 0 0 1.5em;"><ul style="margin-bottom: 0 !important;"><li>'+filenames.join('</li><li>')+'</li></ul></div><p>'+__('file_multi_link_breakage_warning')+'</p>';
		if (has_folders) {
			message += '<p style="color: red;">'+__('file_multi_delete_folder_warning')+'</p>';
		}
		message += '<p>'+__('cannot_undo_warning')+'</p>';
		Biscuit.Crumbs.Confirm(message, function() {
			var post_data = {};
			var index = 0;
			$('#file-list-content .selected').each(function() {
				post_data['delete_path['+index+']'] = $(this).data('path');
				index++;
			});
			Biscuit.Ajax.Request(FileManager.base_url+'/delete', 'json', {
				type: 'post',
				data: post_data,
				error: function(text_status,xhr) {
					var response = jQuery.parseJSON(xhr.responseText);
					if (response.error_message != undefined) {
						Biscuit.Crumbs.Alert(data.error_message, __('error_box_title'));
					}
				},
				complete: function() {
					FileManager.refresh_files();
				}
			});
		}, __('file_delete_all'));
	},
	refresh_files: function() {
		if (this.currently_refreshing) {
			return;
		}
		this.currently_refreshing = true;
		$('#trash-button').hide();
		$('#bfm-rename-file').hide();
		Biscuit.Crumbs.ShowCoverThrobber('bfm-content');
		Biscuit.Ajax.Request(this.base_url+'?json', 'json', {
			type: 'get',
			complete: function() {
				FileManager.currently_refreshing = false;
			},
			success: function(data,text_status,xhr) {
				FileManager.active_path = data.active_path;
				if (FileManager.active_path == null) {
					FileManager.active_path = '';
				}
				FileManager.icon_size = parseInt(data.icon_size);
				FileManager.view_mode = data.view_mode;
				FileManager.files = data.files;
				FileManager.update_file_list('/'+FileManager.active_path);
				FileManager.highlight_active_folder('/'+FileManager.active_path);
				FileManager.update_breadcrumbs('/'+FileManager.active_path);
				$('#folder-list-outer .content-inner').html(FileManager.render_folder_hierarchy());
				FileManager.init_droppables('#folder-list a.folder');
				Biscuit.Crumbs.HideCoverThrobber('bfm-content');
			},
			error: function() {
				Biscuit.Crumbs.HideCoverThrobber('bfm-content');
				Biscuit.Crumbs.Alert('<p>'+__('files_refresh_fail')+'</p>', __('error_box_title'));
			}
		});
	},
	change_folder: function(path) {
		this.update_breadcrumbs(path);
		this.update_file_list(path);
		this.highlight_active_folder(path);
		this.active_path = path.substr(1);
		$('#trash-button').hide();
		$('#bfm-rename-file').hide();
		Biscuit.Ajax.Request(this.base_url+'/set_active_folder','server_action',{
			type: 'post',
			data: {
				bfm_folder: path
			}
		});
	},
	highlight_active_folder: function(path) {
		$('#folder-list-outer a.active').removeClass('active').addClass('inactive');
		$('#folder-list-outer a').each(function() {
			if ($(this).data('path') == path) {
				$(this).removeClass('inactive').addClass('active');
				FileManager.expand_parents($(this).parent());
			}
		});
	},
	expand_parents: function(parent_element) {
		if ($(parent_element).find('a.folder').length > 1) {
			$(parent_element).removeClass('inactive').addClass('active');
			$(parent_element).children('a.expander').removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-s');
			if ($(parent_element).children('dl').css('display') == 'none') {
				$(parent_element).children('dl').slideDown('fast');
			}
			if ($(parent_element).parent().parent().is('dd')) {
				this.expand_parents($(parent_element).parent().parent());
			}
		}
	},
	update_breadcrumbs: function(path) {
		var crumbs = [];
		if (path != '/') {
			if ($('#bfm-breadcrumbs').length == 0) {
				$('#file-list-outer .content-inner').prepend(
					$('<div>')
					.attr('id', 'bfm-breadcrumbs')
				);
			}
			$('#bfm-breadcrumbs').html($('<a>').addClass('folder').data('path', '/').attr('href', this.base_url+'?folder=/').text(__('file_list_home')));
			path = path.substr(1); // Everything after the first slash
			var folder_bits = path.split('/');
			var last_folder = '';
			var folder = '';
			for (var i in folder_bits) {
				if (typeof(folder_bits[i]) != 'function') {
					$('#bfm-breadcrumbs').append(
						$('<div>')
						.addClass('spacer')
						.html('&raquo;')
					);
					if (i < folder_bits.length-1) {
						folder = last_folder+'/'+folder_bits[i];
						$('#bfm-breadcrumbs').append(
							$('<a>')
							.addClass('folder')
							.data('path', folder)
							.attr('href', this.base_url+'?folder='+encodeURI(folder))
							.text(folder_bits[i])
						);
						last_folder = folder;
					} else {
						$('#bfm-breadcrumbs').append(
							$('<div>').addClass('active-item').text(folder_bits[i])
						);
					}
				}
			}
		} else {
			$('#bfm-breadcrumbs').html(
				$('<div>').addClass('active-item').text(__('file_list_home'))
			);
		}
	},
	update_file_list: function(path) {
		if (this.files[path] != undefined) {
			this.render_files(path, this.view_mode);
		} else {
			$('#file-list-content').html($('<p>').addClass('none-found').text(__('file_list_empty')));
		}
	},
	render_files: function(path, mode) {
		$('#file-list-outer').removeClass('list').removeClass('icon');
		$('#file-list-outer').addClass(mode);
		$('#file-list-content').html('');
		if (mode == 'icon') {
			$('#icon-sizer').show();
		} else {
			$('#icon-sizer').hide();
		}
		var files = this.files[path];
		var template_vars = {
			outer_width: this.icon_size+30,
			outer_height: this.icon_size+70,
			icon_size: this.icon_size,
			files: []
		}
		for (var i in files) {
			if (typeof(files[i]) == 'object') {
				if (i%2 == 0) {
					var stripe_class = 'stripe-odd';
				} else {
					var stripe_class = 'stripe-even';
				}
				if (files[i]['is_dir'] == 1) {
					var type = 'folder';
					if (this.files[files[i].path] !== undefined) {
						var file_title = files[i].name+' ('+__('folder_item_count', [this.files[files[i].path].length])+')';
					} else {
						var file_title = files[i].name+' ('+__('file_list_empty')+')';
					}
				} else {
					var type = 'file';
					var file_title = files[i].name+' ('+files[i].filesize+' '+files[i].kind+')';
				}
				var extra_class = '';
				if (mode == 'icon' && files[i].is_file && files[i].thumb_path != '') {
					extra_class = 'thumbnail';
					var icon_path = files[i].thumb_path;
				} else {
					if (mode == 'icon') {
						var icon_path = '/framework/modules/file_manager/images/icons/scalable/'+files[i].icon;
					} else if (mode == 'list') {
						var icon_path = '/framework/modules/file_manager/images/icons/16/'+files[i].icon;
					}
				}
				template_vars.files.push({
					file_title: file_title,
					name: files[i].name,
					extra_class: extra_class,
					stripe_class: stripe_class,
					type: type,
					last_modified: files[i].last_modified,
					path: files[i].path,
					parent_path: files[i].parent_path,
					file_index: i,
					icon_path: icon_path
				});
			}
		}
		$('#file-list-content').html($('#file-'+mode+'-template').tmpl(template_vars));
		this.init_draggables();
		this.init_droppables('#file-list-content .folder');
		this.init_drag_to_select();
		
	},
	render_folder_hierarchy: function(folder) {
		// Same as PHP function, for refreshing folder list when needed
		var returnHtml = '';
		if (folder == undefined) {
			var link_class = '';
			var folder = '/';
			if (this.active_path == '' || this.active_path == null) {
				var link_class = 'active';
			}
			returnHtml = '<dl id="folder-list"><dd class="active"><a id="folder-link-home" class="folder '+link_class+'" data-path="/" href="'+this.base_url+'?folder=/" title="'+__('file_list_home')+'">'+__('file_list_home')+'</a>';
		}
		if (this.files[folder] != undefined && this.files[folder].length > 0) {
			var index = 0;
			var li_class = '';
			var link_class = '';
			var icon = '';
			for (var i in this.files[folder]) {
				var file = this.files[folder][i];
				if (file.is_dir) {
					if (index == 0) {
						returnHtml += '<dl>';
					}
					li_class = 'inactive';
					link_class = '';
					if (this.active_path == file.path.substr(1)) {
						link_class = 'active';
					}
					icon = 'ui-icon-triangle-1-e';
					if (this.active_path.match(file.path.substr(1))) {
						li_class = 'active';
						if (this.files[file.path] != undefined && this.files[file.path].length > 0 && this.path_has_sub_folders(this.files[file.path])) {
							icon = 'ui-icon-triangle-1-s';
						}
					}
					returnHtml += '<dd class="'+li_class+'"><a class="ui-icon '+icon+' expander" href="#expand-collapse">'+__('file_list_expand_collapse')+'</a><a class="folder '+link_class+'" data-path="'+file.path+'" href="'+this.base_url+'?folder='+encodeURI(file.path)+'" title="'+file.path+'">'+file.name+'</a>';
					if (this.files[file.path] != undefined && this.files[file.path].length > 0) {
						returnHtml += this.render_folder_hierarchy(file.path);
					}
					returnHtml += '</dd>';
					index += 1;
				}
			}
			if (returnHtml != '') {
				returnHtml += '</dl>';
			}
		}
		if (folder == '/') {
			returnHtml += '</dd></dl>';
		}
		return returnHtml;
	},
	path_has_sub_folders: function(files) {
		for (var i in files) {
			if (files[i].is_dir) {
				return true;
				break;
			}
		}
		return false;
	},
	show_file_info: function(parent_path, index) {
		var template_vars = {
			selectable: this.is_selector_dialog,
			has_variants: false
		}
		var file_data = this.files[parent_path][index];
		if (file_data.thumb_path != '') {
			template_vars.thumbnail_source = file_data.thumb_path;
		} else {
			template_vars.thumbnail_source = '/framework/modules/file_manager/images/icons/scalable/'+file_data.icon;
		}
		template_vars.file_data = {
			path: this.base_upload_path+file_data.path,
			web_link: top.location.protocol+'//'+top.location.hostname+this.base_upload_path+file_data.path,
			name: file_data.name,
			kind: file_data.kind,
			size: file_data.filesize,
			last_modified: file_data.last_modified,
			width: file_data.image_width,
			height: file_data.image_height
		}
		if (typeof(file_data.variants) == 'object') {
			template_vars.has_variants = true;
			template_vars.variants = []
			for (var variant_name in file_data.variants) {
				template_vars.variants.push({
					name: variant_name,
					path: file_data.variants[variant_name]
				})
			}
		}
		this.file_info_dialog = Biscuit.Crumbs.Alert($('#file-info-template').tmpl(template_vars), __('file_info_title'), function() {
			FileManager.file_info_dialog = null;
		});
		$('#file-info-content a').blur();
	},
	select_file: function(url) {
		if (this.tiny_mce) {
			var win = tinyMCEPopup.getWindowArg("window");
			// insert information now
			win.document.getElementById(tinyMCEPopup.getWindowArg("input")).value = url;
			// close popup window
			tinyMCEPopup.close();
		} else {
			opener.document.getElementById(this.target_el_id).value = url;
			window.close();
		}
	},
	display_help: function() {
		$('body').append($('#bfm-help-content')
			.clone()
			.attr('id','help-dialog-content')
			.css({'display': 'none'})
		);
		var buttons = {};
		buttons[__('dismiss_button_label')] = function() {
			$(this).dialog('close');
		}
		$('#help-dialog-content').dialog({
			modal: true,
			title: __('file_manager_help'),
			width: 940,
			position: 'top',
			show: 'fade',
			hide: 'fade',
			resizable: false,
			buttons: buttons,
			open: function() {
				window.scroll(0,0);
			},
			close: function(event, ui) {
				$('#help-dialog-content').remove();
			}
		});
	},
	sort: {
		sort_by: null,
		sort_dir: null,
		do_sort: function(sort_element) {
			var old_sort_by = this.sort_by;
			this.sort_by = $(sort_element).data('sort-by');
			if (this.sort_by == old_sort_by) {
				if (this.sort_dir == 'asc') {
					this.sort_dir = 'desc';
				} else {
					this.sort_dir = 'asc';
				}
				$('.sort-column .sort-by-'+this.sort_by).removeClass('sort-asc').removeClass('sort-desc').addClass('sort-'+this.sort_dir);
			} else {
				$('.sort-column .sort-by-'+old_sort_by).removeClass('sort-active').removeClass('sort-asc').removeClass('sort-desc');
				$('.sort-column .sort-by-'+old_sort_by+' .ui-icon').hide();
				$('.sort-column .sort-by-'+this.sort_by).addClass('sort-active').addClass('sort-'+this.sort_dir);
				$('.sort-column .sort-by-'+this.sort_by+' .ui-icon').show();
			}
			if (this.sort_dir == 'asc') {
				var icon_dir = 'n';
			} else {
				var icon_dir = 's';
			}
			$('.sort-column .sort-by-'+this.sort_by+' .ui-icon').removeClass('ui-icon-triangle-1-n').removeClass('ui-icon-triangle-1-s').addClass('ui-icon-triangle-1-'+icon_dir);
			this.resort_files();
			FileManager.update_file_list('/'+FileManager.active_path);
		},
		resort_files: function() {
			for (var folder in FileManager.files) {
				if (typeof(FileManager.files[folder]) == 'object' && FileManager.files[folder].length > 0) {
					FileManager.files[folder].sort(FileManager.sort.compare);
					if (FileManager.sort.sort_dir == 'desc') {
						FileManager.files[folder].reverse();
					}
				}
			}
			Biscuit.Cookie('BFMViewOptions',FileManager.view_mode+','+FileManager.icon_size+','+this.sort_by+','+this.sort_dir, {
				'expires': 365,
				'path': '/file-manager'
			});
		},
		compare: function(a, b) {
			if (FileManager.sort.sort_by == 'last_modified') {
				a = parseInt(a.timestamp);
				b = parseInt(b.timestamp);
				return (a == b) ? 0 : ((a < b) ? -1 : 1);
			} else {
				return FileManager.sort.naturalSort(a[FileManager.sort.sort_by].toLowerCase(), b[FileManager.sort.sort_by].toLowerCase());
			}
		},
		/*
		 * Natural Sort algorithm for Javascript - Version 0.6 - Released under MIT license
		 * Author: Jim Palmer (based on chunking idea from Dave Koelle)
		 * Contributors: Mike Grier (mgrier.com), Clint Priest, Kyle Adams, guillermo
		 */
		naturalSort: function(a, b) {
			var re = /(^-?[0-9]+(\.?[0-9]*)[df]?e?[0-9]?$|^0x[0-9a-f]+$|[0-9]+)/gi,
				sre = /(^[ ]*|[ ]*$)/g,
				dre = /(^([\w ]+,?[\w ]+)?[\w ]+,?[\w ]+\d+:\d+(:\d+)?[\w ]?|^\d{1,4}[\/\-]\d{1,4}[\/\-]\d{1,4}|^\w+, \w+ \d+, \d{4})/,
				hre = /^0x[0-9a-f]+$/i,
				ore = /^0/,
				// convert all to strings and trim()
				x = a.toString().replace(sre, '') || '',
				y = b.toString().replace(sre, '') || '',
				// chunk/tokenize
				xN = x.replace(re, '\0$1\0').replace(/\0$/,'').replace(/^\0/,'').split('\0'),
				yN = y.replace(re, '\0$1\0').replace(/\0$/,'').replace(/^\0/,'').split('\0'),
				// numeric, hex or date detection
				xD = parseInt(x.match(hre)) || (xN.length != 1 && x.match(dre) && Date.parse(x)),
				yD = parseInt(y.match(hre)) || xD && y.match(dre) && Date.parse(y) || null;
			// first try and sort Hex codes or Dates
			if (yD)
				if ( xD < yD ) return -1;
				else if ( xD > yD )	return 1;
			// natural sorting through split numeric strings and default strings
			for(var cLoc=0, numS=Math.max(xN.length, yN.length); cLoc < numS; cLoc++) {
				// find floats not starting with '0', string or 0 if not defined (Clint Priest)
				oFxNcL = !(xN[cLoc] || '').match(ore) && parseFloat(xN[cLoc]) || xN[cLoc] || 0;
				oFyNcL = !(yN[cLoc] || '').match(ore) && parseFloat(yN[cLoc]) || yN[cLoc] || 0;
				// handle numeric vs string comparison - number < string - (Kyle Adams)
				if (isNaN(oFxNcL) !== isNaN(oFyNcL)) return (isNaN(oFxNcL)) ? 1 : -1; 
				// rely on string comparison if different types - i.e. '02' < 2 != '02' < '2'
				else if (typeof oFxNcL !== typeof oFyNcL) {
					oFxNcL += ''; 
					oFyNcL += ''; 
				}
				if (oFxNcL < oFyNcL) return -1;
				if (oFxNcL > oFyNcL) return 1;
			}
			return 0;
		}
	}
}

;(function($) {
	$.fn.selectRange = function(start, end) {
		return this.each(function() {
			if(this.setSelectionRange) {
				this.focus();
				this.setSelectionRange(start, end);
			} else if(this.createTextRange) {
				var range = this.createTextRange();
				range.collapse(true);
				range.moveEnd('character', end);
				range.moveStart('character', start);
				range.select();
			}
		});
	};
})(jQuery);
