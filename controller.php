<?php
/**
 * File manager module
 *
 * @package Modules
 * @subpackage FileManager
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: controller.php 14585 2012-03-15 18:24:09Z teknocat $
 */
class FileManager extends AbstractModuleController {
	/**
	 * Currently active folder
	 *
	 * @var string
	 */
	protected $_active_path = '';
	/**
	 * Viewing mode - 'list' or 'icon'
	 *
	 * @var string
	 */
	protected $_view_mode = 'list';
	/**
	 * Icon size to display
	 *
	 * @var string
	 */
	protected $_icon_size = 100;
	/**
	 * What element to sort by
	 *
	 * @var string
	 */
	protected $_sort_by = 'name';
	/**
	 * What direction to sort in
	 *
	 * @var string
	 */
	protected $_sort_dir = 'asc';
	/**
	 * Whether or not the base directory exists and is writable, false until deemed otherwise
	 *
	 * @var string
	 */
	protected $_base_dir_is_ok = false;
	/**
	 * Place to cache files list
	 *
	 * @var string
	 */
	protected $_files = array();
	/**
	 * List of other available image sizes
	 *
	 * @var array
	 */
	protected $_other_image_sizes = array();
	/**
	 * Base path for the upload directory
	 *
	 * @var string
	 */
	protected $_base_upload_path = '/var/uploads/files';
	/**
	 * List of folders containing other variations of uploaded files besides the standard ones
	 *
	 * @var string
	 */
	protected $_other_file_variation_folders = array('_thumbs/bfm');
	/**
	 * Ensure base dir exists and is writable on run:
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function run() {
		$this->_set_active_path();
		if ($this->is_primary()) {
			LibraryLoader::load('JqueryTmpl');
			$this->Biscuit->set_never_cache();
			$this->_set_base_upload_path();
			$this->register_js('footer', 'file-manager.js');
			$this->register_css(array('filename' => 'file-manager.css', 'media' => 'all'));
			if (!empty($this->params['tiny_mce'])) {
				$this->_dependencies['index'] = 'TinyMce';
			}
		} else {
			$this->register_js('footer', 'file-manager-activators.js');
		}
		parent::run();
	}
	/**
	 * Get file listing and render
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_index() {
		if (!$this->_base_dir_is_ok) {
			$this->render('bad_upload_dir');
		} else {
			if (!empty($_COOKIE['BFMViewOptions'])) {
				list($this->_view_mode, $this->_icon_size, $this->_sort_by, $this->_sort_dir) = explode(',', $_COOKIE['BFMViewOptions']);
				if ($this->_view_mode != 'list' && $this->_view_mode != 'icon') {
					$this->_view_mode = 'list';
				}
				$this->_icon_size = (int)$this->_icon_size;
				if ($this->_icon_size < 64 && $this->_icon_size > 256) {
					$this->_icon_size = 100;
				}
				$allowed_sort_cols = array('name', 'last_modified');
				if (!in_array($this->_sort_by, $allowed_sort_cols)) {
					$this->_sort_by = 'name';
				}
				if ($this->_sort_dir != 'asc' && $this->_sort_dir != 'desc') {
					$this->_sort_dir = 'asc';
				}
			}
			// Make sure the cookie with the view options gets set/extended:
			Response::set_cookie('BFMViewOptions', implode(',',array($this->_view_mode, $this->_icon_size, $this->_sort_by, $this->_sort_dir)), (time()+(60*60*24*365)), '/file-manager');
			$this->_files = $this->fetch_files();
			if (Request::is_ajax()) {
				$data = array(
					'files' => $this->_files,
					'active_path' => $this->_active_path,
					'view_mode' => $this->_view_mode,
					'icon_size' => $this->_icon_size
				);
				$this->Biscuit->render_json($data);
			} else {
				$this->set_index_title();
				if (!empty($this->params['tiny_mce'])) {
					$this->Biscuit->ExtensionTinyMce()->register_popup_helper_script();
					$this->set_view_var('tiny_mce', 1);
				}
				if (!empty($this->params['target_el_id'])) {
					$this->set_view_var('target_el_id', $this->params['target_el_id']);
				}
				$this->set_view_var('files', $this->_files);
				$this->set_view_var('active_path', $this->_active_path);
				$this->set_view_var('view_mode', $this->_view_mode);
				$this->set_view_var('icon_size', $this->_icon_size);
				$this->set_view_var('sort_by', $this->_sort_by);
				$this->set_view_var('sort_dir', $this->_sort_dir);
				$this->render();
			}
		}
	}
	/**
	 * Render the hierarchy of folders
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function render_folder_hierarchy($folder = '/') {
		if ($folder == '/') {
			$link_class = '';
			if (empty($this->_active_path)) {
				$link_class = 'active';
			}
			$returnHtml = '<dl id="folder-list"><dd class="active"><a id="folder-link-home" class="folder '.$link_class.'" data-path="/" href="'.$this->url().'?bfm_folder=/" title="'.__('Home').'">'.__('Home').'</a>';
		}
		if (!empty($this->_files[$folder])) {
			$index = 0;
			foreach ($this->_files[$folder] as $file) {
				if ($file['is_dir']) {
					if ($index == 0) {
						$returnHtml .= '<dl>';
					}
					$li_class = 'inactive';
					$link_class = '';
					if ($this->_active_path == substr($file['path'],1)) {
						$link_class = 'active';
					}
					$icon = 'ui-icon-triangle-1-e';
					if (stristr($this->_active_path, substr($file['path'],1))) {
						$li_class = 'active';
						if (!empty($this->_files[$file['path']]) && $this->has_sub_folders($this->_files[$file['path']])) {
							$icon = 'ui-icon-triangle-1-s';
						}
					}
					$returnHtml .= '<dd class="'.$li_class.'"><a class="ui-icon '.$icon.' expander" href="#expand-collapse">'.__('Expand/Collapse').'</a><a class="folder '.$link_class.'" data-path="'.$file['path'].'" href="'.$this->url().'?bfm_folder='.rawurlencode($file['path']).'" title="'.$file['path'].'">'.$file['name'].'</a>';
					if (!empty($this->_files[$file['path']])) {
						$returnHtml .= $this->render_folder_hierarchy($file['path']);
					}
					$returnHtml .= '</dd>';
					$index += 1;
				}
			}
			if (!empty($returnHtml)) {
				$returnHtml .= '</dl>';
			}
		}
		if ($folder == '/') {
			$returnHtml .= '</dd></dl>';
		}
		return $returnHtml;
	}
	/**
	 * Whether or not a set of files in hierarchy has any sub-folders
	 *
	 * @param string $files 
	 * @return void
	 * @author Peter Epp
	 */
	private function has_sub_folders($files) {
		foreach ($files as $file) {
			if ($file['is_dir']) {
				return true;
				break;
			}
		}
		return false;
	}
	/**
	 * Render folder breadcrumbs
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function render_breadcrumbs() {
		$crumbs = array();
		if (!empty($this->_active_path)) {
			$folder_bits = explode('/',$this->_active_path);
			$crumbs[] = '<a class="folder" data-path="/" href="'.$this->url().'?bfm_folder=/">'.__('Home').'</a>';
			$last_folder = '';
			foreach ($folder_bits as $index => $folder_name) {
				if ($index < count($folder_bits)-1) {
					$folder = $last_folder.'/'.$folder_name;
					$crumbs[] = '<a class="folder" data-path="'.$folder.'" href="'.$this->url().'?bfm_folder='.rawurlencode($folder).'">'.$folder_name.'</a>';
					$last_folder = $folder;
				} else {
					$crumbs[] = '<div class="active-item">'.$folder_name.'</div>';
				}
			}
		} else {
			$crumbs[] = '<div class="active-item">'.__('Home').'</div>';
		}
		return implode('<div class="spacer">&raquo;</div>', $crumbs).'<div class="clearance"></div>';
	}
	/**
	 * Create a new folder
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_new_folder() {
		if (Request::is_post()) {
			if ($this->validate_edit_folder()) {
				$full_path = SITE_ROOT.$this->_base_upload_path.'/'.$this->_active_path.'/'.$this->params['folder_name'];
				$created = Crumbs::ensure_directory($full_path);
				if (!$created) {
					$error = __('Failed to create new folder!');
				}
				if (Request::is_ajax()) {
					if (!$created) {
						Response::http_status(406);
					}
					return;
				} else {
					Session::flash('user_error', __('Failed to create new folder!'));
					Response::redirect($this->url());
				}
			}
		}
		$this->set_view_var('active_path', $this->_active_path);
		$this->render();
	}
	/**
	 * Delete a file or folder
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_delete() {
		$error = '';
		if (Request::is_post()) {
			$success = true;
			if (empty($this->params['delete_path'])) {
				$success = false;
				if (empty($this->params['delete_path'])) {
					$error = __('You must provide the path to the file or folder to delete');
				}
			} else {
				$this->params['delete_path'] = (array)$this->params['delete_path'];
				$errors = array();
				foreach ($this->params['delete_path'] as $path) {
					$message = $this->delete($path);
					if (!empty($message)) {
						$errors[] = $message;
					}
				}
				if (!empty($errors)) {
					$error = implode('<br>',$errors);
					$success = false;
				}
			}
		} else {
			$success = false;
		}
		if (!$success) {
			if (Request::is_ajax()) {
				Response::http_status(406);
				$this->Biscuit->render_json(array('error_message' => $error));
			} else {
				if (!empty($error)) {
					Session::flash('user_error', $error);
				}
			}
		} else {
			if (!Request::is_ajax()) {
				Response::redirect($this->url());
			} else {
				// Just render an empty array so it doesn't try to render views
				$this->Biscuit->render_json(array());
			}
		}
	}
	/**
	 * Delete a given path
	 *
	 * @param string $path 
	 * @return void
	 * @author Peter Epp
	 */
	private function delete($path) {
		$error = '';
		$full_delete_path = SITE_ROOT.$this->_base_upload_path.$path;
		if (Crumbs::delete_file_or_folder($full_delete_path, $this->_other_file_variation_folders)) {
			if ($this->_active_path == trim($path, '/')) {
				// If we just deleted the active path, change to the parent folder:
				$path_info = new SplFileInfo($path);
				$this->_active_path = $path_info->getPath();
				Session::set('bfm_path', $this->_active_path);
			}
		} else {
			if (is_file($full_delete_path)) {
				$error = __("Unable to delete ".$filename.". This is most likely due to file permission restrictions. Please contact the system administrator.");
			} else if (is_dir($full_delete_path)) {
				$error = __("Unable to delete the folder or one of it's files or sub-folders. This is most likely due to file permission restrictions. Please contact the system administrator.");
			}
		}
		return $error;
	}
	/**
	 * Copy or move one or more files to a destination folder
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_copy_move() {
		// Assume success by default so we don't report an error if nothing at all happens, only if we tried and failed to copy or move the files
		$success = true;
		if (Request::is_post()) {
			if ((!empty($this->params['mode']) && ($this->params['mode'] == 'copy' || $this->params['mode'] == 'move')) && !empty($this->params['source_paths']) && !empty($this->params['destination'])) {
				$full_destination_path = rtrim(SITE_ROOT.$this->_base_upload_path.$this->params['destination'], '/');
				if (file_exists($full_destination_path)) {
					$source_paths = array();
					foreach ($this->params['source_paths'] as $source_path) {
						$full_source_path = SITE_ROOT.$this->_base_upload_path.$source_path;
						if (file_exists($full_source_path)) {
							$is_file = is_file($full_source_path);
							$file_info = new SplFileInfo($source_path);
							$filename = $file_info->getBasename();
							$destination_file_path = $full_destination_path.'/'.$filename;
							if ($this->params['mode'] == 'move') {
								$success = Crumbs::rename_file_or_folder($full_source_path, $destination_file_path, $this->_other_file_variation_folders);
							} else if ($this->params['mode'] == 'copy') {
								$success = Crumbs::copy_file_or_folder($full_source_path, $destination_file_path, $this->_other_file_variation_folders);
							}
						}
					}
				}
			}
		}
		if (!$success) {
			if (Request::is_ajax()) {
				Response::http_status(406);
			} else {
				Session::flash('user_error', __('Failed to '.$this->params['mode'].' files/folders. This is most likely due to file permission restrictions. Please contact the system administrator.'));
			}
		} else {
			// Just render an empty array so it doesn't try to render views
			$this->Biscuit->render_json(array());
		}
		if (!Request::is_ajax()) {
			Response::redirect($this->url());
		}
	}
	/**
	 * Rename a file
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_rename() {
		$success = true;
		if (Request::is_post()) {
			if (!empty($this->params['source_file']) && !empty($this->params['new_name'])) {
				$full_source_path = SITE_ROOT.$this->_base_upload_path.$this->params['source_file'];
				if (file_exists($full_source_path)) {
					$is_file = is_file($full_source_path);
					$file_info = new SplFileInfo($this->params['source_file']);
					$old_filename = $file_info->getBasename();
					$path_only = $file_info->getPath();
					$new_file_path = SITE_ROOT.$this->_base_upload_path.$path_only.'/'.$this->params['new_name'];
					$success = Crumbs::rename_file_or_folder($full_source_path, $new_file_path, $this->_other_file_variation_folders);
				}
			}
		}
		if (!$success) {
			if (Request::is_ajax()) {
				Response::http_status(406);
			} else {
				Session::flash('user_error', __('Failed to rename the file/folder. This is most likely due to file permission restrictions. Please contact the system administrator.'));
			}
		} else {
			// Just render an empty array so it doesn't try to render views
			$this->Biscuit->render_json(array());
		}
		if (!Request::is_ajax()) {
			Response::redirect($this->url());
		}
	}
	/**
	 * Validate new folder creation
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function validate_edit_folder() {
		if (empty($this->params['folder_name']) || preg_match('/[^a-zA-Z0-9-_\.]/', $this->params['folder_name'])) {
			$this->_validation_errors[] = __('Enter a name containing only numbers, letters, hyphens, periods or underscores');
			$this->_invalid_fields[] = 'folder_name';
			return false;
		}
		return true;
	}
	/**
	 * Set active folder, view mode and/or icon size in session. This is a fire and forget action to be called via Ajax
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_set_active_folder() {
		if (!empty($this->params['bfm_folder'])) {
			$this->params['bfm_folder'] = trim($this->params['bfm_folder'], '/ ');
			$folder = Crumbs::file_exists_in_load_path('var/uploads/files/'.$this->params['bfm_folder'], SITE_ROOT_RELATIVE);
			if (empty($folder)) {
				$active_path = '';
			} else {
				$active_path = trim($this->params['bfm_folder'],'/');
			}
			Session::set('bfm_path', $active_path);
		}
	}
	/**
	 * Handle normal file upload
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_upload() {
		if (Request::is_post()) {
			$all_uploads = Request::files();
			if (!empty($all_uploads) && !empty($all_uploads['bfm_upload'])) {
				$uploaded_file = new FileUpload($all_uploads['bfm_upload'], $this->_base_upload_path.'/'.$this->_active_path, true);
				if (!$uploaded_file->is_okay()) {
					if ($uploaded_file->no_file_sent()) {
						Session::flash('user_message', __('No file uploaded'));
					} else {
						Session::flash('user_error', sprintf(__('Upload of <strong>%s</strong> failed: %s'), $uploaded_file->file_name(), $uploaded_file->get_error_message()));
					}
				}
			} else {
				if (!Request::is_ajax()) {
					Session::flash('user_message', __('No file uploaded'));
				}
			}
		}
		if (!Request::is_ajax()) {
			Response::redirect($this->url());
		} else {
			// Return an empty response on Ajax get requests
			$this->Biscuit->render('');
		}
	}
	/**
	 * Make a big 256x256 thumbnail image for the file browser
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_big_thumb() {
		$error = false;
		$path = '/'.trim(substr($this->params['path'],strlen($this->_base_upload_path)-1),'/');
		$full_source_path = SITE_ROOT.$this->_base_upload_path.$path.'/'.$this->params['filename'];
		if (!file_exists($full_source_path)) {
			$error = true;
		} else {
			$bfm_thumb_folder = SITE_ROOT.$this->_base_upload_path.$path.'/_thumbs/bfm';
			$bfm_thumb_path = $bfm_thumb_folder.'/'.$this->params['filename'];
			if (!Crumbs::ensure_directory($bfm_thumb_folder)) {
				$error = true;
			} else {
				$standard_thumb_path = SITE_ROOT.$this->_base_upload_path.$path.'/_thumbs/_'.$this->params['filename'];
				if (THUMB_WIDTH == 256 && THUMB_HEIGHT == 256 && file_exists($standard_thumb_path)) {
					// If the default thumbnail size is already what we desire, just copy the thumbnail file and be done with it:
					$error = copy($standard_thumb_path, SITE_ROOT.$this->_base_upload_path.$path.'/_thumbs/bfm/'.$this->params['filename']);
				} else {
					// Everything looks good and we don't aready have a thumb we can use, create it now
					$image = new Image($full_source_path);
					if (!$image->image_is_valid()) {
						$error = true;
					} else {
						$image->resize(256, 256, Image::RESIZE_AND_CROP, $bfm_thumb_path);
						$image->destroy();
						unset($image);
					}
				}
				if (!$error) {
					$image_type = exif_imagetype($bfm_thumb_path);
					switch ($image_type) {
						case IMAGETYPE_JPEG:
							$mime_type = 'image/jpeg';
							break;
						case IMAGETYPE_GIF:
							$mime_type = 'image/gif';
							break;
						case IMAGETYPE_PNG:
							$mime_type = 'image/png';
							break;
					}
					Response::content_type($mime_type);
					$this->Biscuit->render(file_get_contents($bfm_thumb_path));
				}
			}
		}
		if ($error) {
			// If anything went wrong, just render the default jpeg icon
			Response::content_type('image/png');
			$this->Biscuit->render(file_get_contents(FW_ROOT.'/modules/file_manager/images/icons/scalable/jpg.png'));
		}
	}
	/**
	 * Return an array of files for the current path
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function fetch_files() {
		// Fire an event to get a list of any other images sizes with corresponding base paths:
		Event::fire('get_image_sizes', $this);
		$files = FindFiles::ls($this->_base_upload_path, array('include_directories' => true, 'excludes' => array('_thumbs', '_originals')), false, true);
		$files_array = array();
		if (!empty($files)) {
			$base_upload_path = SITE_ROOT.$this->_base_upload_path;
			foreach ($files as $file) {
				if ($file->isLink()) {
					// Ignore symbolic links, they shouldn't exist in the uploads folder anyway
					continue;
				}
				$my_filename = $file->getBasename();
				$my_full_path = $file->getPathname();
				$my_path = substr($my_full_path, strlen($base_upload_path));
				$parent_path = $file->getPath();
				$parent_path = substr($parent_path, strlen($base_upload_path));
				if (empty($parent_path)) {
					$parent_path = '/';
				}
				$image_width = false;
				$image_height = false;
				$variants = false;
				$thumb_path = '';
				$extension = '';
				if ($file->isDir()) {
					$icon = 'folder.png';
					$kind = 'folder';
				} else {
					if (method_exists($file, 'getExtension')) {
						$extension = $file->getExtension();
					} else {
						$extension = substr($my_filename, strrpos($my_filename, '.')+1);
					}
					$extension = strtolower($extension);
					$icon_path = '/framework/modules/file_manager/images/icons/scalable/'.$extension.'.png';
					if (!file_exists(SITE_ROOT.$icon_path)) {
						$icon = 'default.png';
					} else {
						$icon = $extension.'.png';
					}
					if (function_exists('finfo_file')) {
						// The PHP 5.3 way:
						$finfo = finfo_open(FILEINFO_MIME_TYPE);
						$kind = finfo_file($finfo, $my_full_path);
						finfo_close($finfo);
					} else if (function_exists('mime_content_type')) {
						// The old, deprecated way:
						$kind = mime_content_type($my_full_path);
					}
					if (empty($kind)) {
						// If no mime type could be determined via php functions, fall back to the unix command way:
						$kind = exec('file -bi '.$my_full_path);
						if (empty($kind)) {
							// Still no dice, admit defeat
							$kind = __('unknown');
						}
					}
					if ($extension == 'jpg' || $extension == 'jpeg' || $extension == 'gif' || $extension == 'png') {
						$full_parent_path = rtrim($this->_base_upload_path.$parent_path,'/');
						$thumb_path = $full_parent_path.'/_thumbs/bfm/'.$my_filename;
						$image_info = getimagesize($my_full_path);
						if (!empty($image_info)) {
							$image_width = $image_info[0];
							$image_height = $image_info[1];
						}
						// See if stock Biscuit full size and thumbnails also exist:
						if (file_exists(SITE_ROOT.$full_parent_path.'/_thumbs/_'.$my_filename)) {
							$width = THUMB_WIDTH;
							if (empty($width)) {
								$width = __('Proportional');
							}
							$height = THUMB_HEIGHT;
							if (empty($height)) {
								$height = __('Proportional');
							}
							$name = sprintf(__('Thumbnail (%d x %d)'),$width, $height);
							if (!$variants) {
								$variants = array();
							}
							$variants[$name] = $full_parent_path.'/_thumbs/_'.$my_filename;
						}
						if (file_exists(SITE_ROOT.$full_parent_path.'/_originals/'.$my_filename)) {
							$image_info = getimagesize(SITE_ROOT.$full_parent_path.'/_originals/'.$my_filename);
							$width = $image_info[0];
							$height = $image_info[1];
							if (empty($width)) {
								$width = __('Proportional');
							}
							if (empty($height)) {
								$height = __('Proportional');
							}
							$name = sprintf(__('Original (%d x %d)'),$width, $height);
							if (!$variants) {
								$variants = array();
							}
							$variants[$name] = $full_parent_path.'/_originals/'.$my_filename;
						}
						if (!empty($this->_other_image_sizes)) {
							if (!$variants) {
								$variants = array();
							}
							foreach ($this->_other_image_sizes as $name => $base_url) {
								$variants[__($name)] = $base_url.$this->_base_upload_path.$my_path;
							}
						}
					}
				}
				$files_array[$parent_path][] = array(
					'name'          => $my_filename,
					'last_modified' => strftime('%b %e, %Y %l:%I%p', $file->getMTime()),
					'timestamp'     => (int)$file->getMTime(),
					'filesize'      => Crumbs::formatted_filesize_from_bytes($file->getSize()),
					'extension'     => $extension,
					'path'          => $my_path,
					'parent_path'   => $parent_path,
					'thumb_path'    => $thumb_path,
					'icon'          => $icon,
					'kind'          => $kind,
					'image_width'   => $image_width,
					'image_height'  => $image_height,
					'variants'      => $variants,
					'is_dir'        => $file->isDir(),
					'is_file'       => $file->isFile(),
					'is_link'       => $file->isLink()
				);
			}
		}
		ksort($files_array);
		$files_array = $this->sort_files($files_array);
		return $files_array;
	}
	/**
	 * Custom function for comparing two values for sorting. Does normal string comparison unless it's the last modified date
	 *
	 * @param string $a 
	 * @param string $b 
	 * @return void
	 * @author Peter Epp
	 */
	private function sort_compare($a, $b) {
		if ($this->_sort_by == 'last_modified') {
			return ($a['timestamp'] == $b['timestamp']) ? 0 : (($a['timestamp'] < $b['timestamp']) ? -1 : 1);
		} else {
			return strnatcasecmp($a[$this->_sort_by], $b[$this->_sort_by]);
		}
	}
	/**
	 * Use array multisort to sort by the specified column
	 *
	 * @param string $files_array 
	 * @return void
	 * @author Peter Epp
	 */
	private function sort_files($files_array) {
		foreach ($files_array as $folder => $files) {
			usort($files_array[$folder], array($this, 'sort_compare'));
			if ($this->_sort_dir == 'desc') {
				$files_array[$folder] = array_reverse($files_array[$folder]);
			}
		}
		return $files_array;
	}
	/**
	 * Hook for other modules to add their image sizes
	 *
	 * @param string $sizes 
	 * @return void
	 * @author Peter Epp
	 */
	public function add_image_sizes($sizes) {
		if (!empty($sizes)) {
			foreach ($sizes as $size_info) {
				if (empty($size_info['width'])) {
					$size_info['width'] = 'Proportional';
				}
				if (empty($size_info['height'])) {
					$size_info['height'] = 'Proportional';
				}
				$name = $size_info['name'].' ('.$size_info['width'].'x'.$size_info['height'].')';
				$this->_other_image_sizes[$name] = $size_info['base_url'];
			}
		}
	}
	/**
	 * Set the base upload path, using home folder for the current user if applicable, otherwise the default
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function _set_base_upload_path() {
		$user_home_folder_used = false;
		if (defined('USER_HOME_FOLDER_LEVELS') && $this->Biscuit->ModuleAuthenticator()->user_is_logged_in()) {
			$home_folder_levels = explode(',',USER_HOME_FOLDER_LEVELS);
			if (!empty($home_folder_levels) && in_array($this->Biscuit->ModuleAuthenticator()->active_user()->user_level(), $home_folder_levels)) {
				$user_home_folder_used = true;
				$this->_base_upload_path .= '/users/'.$this->Biscuit->ModuleAuthenticator()->active_user()->friendly_slug();
			}
		}
		if (!$user_home_folder_used) {
			Console::log("Firing event for setting the base upload path...");
			Event::fire('bfm_set_base_upload_path', $this);
		}
		$this->_base_dir_is_ok = Crumbs::ensure_directory(SITE_ROOT.$this->_base_upload_path);
	}
	/**
	 * Hook function to allow others to set the base upload sub-path (within the /var/uploads/files directory). This should be called when acting on the
	 * "bfm_set_base_upload_path" event
	 *
	 * @param string $path 
	 * @return void
	 * @author Peter Epp
	 */
	public function set_base_upload_subpath($path) {
		$path = trim($path, '/');
		$this->_base_upload_path .= '/'.$path;
	}
	/**
	 * Set the active path in session from user input, if present, otherwise fetch from session and set as the active path
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function _set_active_path() {
		if (!$this->is_primary() || $this->action() != 'set_active_folder') {
			$set_from_params = false;
			if (!empty($this->params['bfm_folder'])) {
				$this->_active_path = trim($this->params['bfm_folder'], '/ ');
				$set_from_params = true;
			} else {
				$this->_active_path = Session::get('bfm_path');
			}
			$full_path = SITE_ROOT.'/'.trim($this->_base_upload_path.'/'.$this->_active_path, '/ ');
			if (!file_exists($full_path) || !is_dir($full_path)) {
				if ($set_from_params) {
					Session::flash('user_message', sprintf(__('Folder not found: %s'), $this->_active_path));
				}
				$this->_active_path = '';
			}
			Session::set('bfm_path', $this->_active_path);
		}
	}
	/**
	 * Return the base upload path
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function base_upload_path() {
		return $this->_base_upload_path;
	}
	/**
	 * Set custom URLs
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function uri_mapping_rules() {
		return array(
			'/(?P<page_slug>file-manager)\/(?P<action>upload)$/',
			'/(?P<page_slug>file-manager)\/(?P<action>new_folder)$/',
			'/(?P<page_slug>file-manager)\/(?P<action>delete)$/',
			'/(?P<page_slug>file-manager)\/(?P<action>set_active_folder)$/',
			'/(?P<page_slug>file-manager)\/(?P<action>copy_move)$/',
			'/(?P<page_slug>file-manager)\/(?P<action>rename)$/',
			'page_slug=file-manager&action=big_thumb' => '/(?P<path>var\/uploads\/files[^\.]*)\/_thumbs\/bfm\/(?P<filename>.+)$/'
		);
	}
	/**
	 * Prevent admin menu when primary, otherwise add a manage files link to the menu
	 *
	 * @param string $caller 
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_build_admin_menu($caller) {
		if ($this->is_primary()) {
			$caller->prevent_admin_menu();
		} else if ($this->user_can_index()) {
			$menu_items['Manage Files'] = array(
				'url' => $this->url(),
				'ui-icon' => 'ui-icon-folder-open',
				'target' => '_blank'
			);
			$caller->add_admin_menu_items('File Manager',$menu_items);
		}
	}
	/**
	 * Add link to help menu
	 *
	 * @param string $caller 
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_build_help_menu($caller) {
		$caller->add_help_for('FileManager');
	}
	/**
	 * When installed as secondary, append to the footer some code to supply the file manager activators with the base URL
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_compile_footer() {
		if (!$this->is_primary()) {
			$url = $this->url();
			$js_code = <<<JAVASCRIPT
<script type="text/javascript">
	$(document).ready(function() {
		FileManagerActivate.base_url = '$url';
	});
</script>
JAVASCRIPT;
			$this->Biscuit->append_view_var('footer',$js_code);
		}
	}
	/**
	 * Always ignore request tokens for ajax requests when this module is primary
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_request_token_check() {
		if ($this->is_primary() && Request::is_ajax()) {
			RequestTokens::set_ignore($this->primary_page());
		}
	}
	/**
	 * Installus
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function install_migration($module_id) {
		// Ensure existence of admin page:
		$existing_page = DB::fetch_one("SELECT `id` FROM `page_index` WHERE `slug` = 'file-manager'");
		if (!$existing_page) {
			DB::query("INSERT INTO `page_index` SET `parent` = 9999999, `slug` = 'file-manager', `title` = 'File Manager'");
		}
		// Ensure clean install:
		DB::query("DELETE FROM `module_pages` WHERE `module_id` = {$module_id}");
		// Install on page:
		DB::query("INSERT INTO `module_pages` (`module_id`, `page_name`, `is_primary`) VALUES ({$module_id}, 'file-manager', 1), ({$module_id}, '*', 0)");
		$uploadify_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = 'Uploadify'");
		if (!empty($uploadify_id)) {
			DB::query("INSERT INTO `module_pages` (`module_id`, `page_name`, `is_primary`) VALUES ({$uploadify_id}, 'file-manager', 0)");
		}
		$tiny_mce_installed = DB::fetch_one("SELECT `id` FROM `extensions` WHERE `name` = 'TinyMce'");
		if (!empty($tiny_mce_installed)) {
			// Run Tiny MCE uninstall migration (this removes the system settings for tinybrowser)
			TinyMce::uninstall_migration();
		}
		DB::query("REPLACE INTO `system_settings` (`constant_name`, `friendly_name`, `description`, `value_type`, `required`, `group_name`) VALUES
		('USER_HOME_FOLDER_LEVELS','Home Folder Restricted Users','Select the user roles that should be limited to their own home folder in the file manager','permissionmultiselect',0, 'Biscuit File Manager')");
		Permissions::add(__CLASS__, array('index' => 99, 'upload' => 99, 'new_folder' => 99, 'rename' => 99, 'delete' => 99, 'copy_move' => 99));
	}
	/**
	 * Destallus
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function uninstall_migration($module_id) {
		DB::query("DELETE FROM `page_index` WHERE `slug` = 'file-manager'");
		DB::query("DELETE FROM `module_pages` WHERE `module_id` = {$module_id}");
		$tiny_mce_installed = DB::fetch_one("SELECT `id` FROM `extensions` WHERE `name` = 'TinyMce'");
		if (!empty($tiny_mce_installed)) {
			TinyMce::install_migration();
		}
		DB::query("DELETE FROM `system_settings` WHERE `constant_name` = 'USER_HOME_FOLDER_LEVELS'");
		Permissions::remove(__CLASS__);
	}
}
