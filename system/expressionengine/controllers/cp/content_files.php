<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Content_files extends Controller {


	function Content_files()
	{
		// Call the Controller constructor.  
		// Without this, the world as we know it will end!
		parent::Controller();

		// Does the "core" class exist?  Normally it's initialized
		// automatically via the autoload.php file.  If it doesn't
		// exist it means there's a problem.
		if ( ! isset($this->core) OR ! is_object($this->core))
		{
			show_error('The ExpressionEngine Core was not initialized.  Please make sure your autoloader is correctly set up.');
		}

		if ( ! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->lang->loadfile('filemanager');
		$this->load->vars(array('cp_page_id'=>'content_files'));
		
		if (isset($_GET['ajax']))
        {
            $this->output->enable_profiler(FALSE);
        }
		
		$this->javascript->compile();
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function index()
	{
		if ( ! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper(array('form', 'string', 'url', 'file'));
		$this->load->library('table');
		$this->load->library('encrypt');
		$this->load->model('tools_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('content_files'));
		
		$this->cp->add_js_script(array(
		            'plugin'    => array('fancybox', 'tablesorter', 'ee_upload'),
					'file'		=> 'cp/file_manager_home'
		    )
		);
		
		$this->cp->add_to_head('<link type="text/css" rel="stylesheet" href="'.BASE.AMP.'C=css'.AMP.'M=fancybox" />');

		$this->javascript->set_global('lang.loading', $this->lang->line('loading'));
		$this->javascript->set_global('lang.uploading_file', $this->lang->line('uploading_file').'&hellip;');
		$this->javascript->set_global('lang.show_toolbar', $this->lang->line('show_toolbar'));
		$this->javascript->set_global('lang.hide_toolbar', $this->lang->line('hide_toolbar'));

		$vars = array();

		if ($this->input->get_post('ajax') == 'true')
		{
			$id = $this->input->get_post('directory');
			$path = $this->input->get_post('enc_path');
			$vars = array_merge($vars, $this->_map($id, $path));
			
			$vars['EE_view_disable'] = TRUE;
		}
		else
		{
			$vars = array_merge($vars, $this->_map());
		}

		$this->javascript->compile();

		$this->load->view('content/file_browse', $vars);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get a directory map
	 *
	 * Creates an array of directories and their content
	 * 
	 * @access	public
	 * @param	int		optional directory id (defaults to all)
	 * @return	mixed
	 */
	function _map($dir_id = FALSE, $enc_path = FALSE)
	{
		$upload_directories = $this->tools_model->get_upload_preferences($this->session->userdata('group_id'));
		// if a user has no directories available to them, then they have no right to be here
		if ($this->session->userdata['group_id'] != 1 && $upload_directories->num_rows() == 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$vars['file_list'] = array(); // will hold the list of directories and files
		$vars['upload_directories'] = array();

		$this->load->helper('directory');
		
		if ($enc_path)
		{
			$enc_path = $this->encrypt->decode(rawurldecode($enc_path), $this->session->sess_crypt_key);
		}

		foreach ($upload_directories->result() as $dir)
		{
			if ($dir_id && $dir->id != $dir_id)
			{
				continue;
			}
			
			// we need to know the dirs for the purposes of uploads, so grab them here
			$vars['upload_directories'][$dir->id] = $dir->name;

			$vars['file_list'][$dir->id]['id'] = $dir->id;
			$vars['file_list'][$dir->id]['name'] = $dir->name;
			$vars['file_list'][$dir->id]['url'] = $dir->url;
			$vars['file_list'][$dir->id]['display'] = ($this->input->cookie('hide_upload_dir_id_'.$dir->id) == 'true') ? 'none' : 'block';
			$files = $this->tools_model->get_files($dir->server_path, $dir->allowed_types);

			$file_count = 0;
			$vars['file_list'][$dir->id]['files'] = array(); // initialize so empty dirs don't throw errors

			// construct table row arrays
			foreach($files as $file)
			{
				if ($enc_path && $enc_path != $file['relative_path'].$file['name'])
				{
					continue;
				}

				if ($file['name'] == '_thumbs' OR $file['name'] == 'folder')
				{
					continue;
				}

				$enc_url_path = rawurlencode($this->encrypt->encode($dir->url.$file['name'], $this->session->sess_crypt_key)); //needed for displaying image in edit mode

				// the extension check is not needed, as allowed_types takes care of that.
				// if (strncmp($file['mime'], 'image', 5) == 0 AND (in_array(strtolower(substr($file['name'], -3)), array('jpg', 'gif', 'png'))))

				if (strncmp($file['mime'], 'image', 5) == 0)
				{
					$vars['file_list'][$dir->id]['files'][$file_count] = array(
						array(
							'class'=>'fancybox', 
							'data' => '<a class="fancybox" id="img_'.str_replace(".", '', $file['name']).'" href="'.$dir->url.$file['name'].'" title="'.$file['name'].NBS.'" rel="'.$file['encrypted_path'].'">'.$file['name'].'</a>',
						),
						array(
							'class'=>'fancybox align_right', 
							'data' => number_format($file['size']/1000, 1).NBS.lang('file_size_unit'),
						),
						array(
							'class'=>'fancybox', 
							'data' => $file['mime'],
						),
						array(
							'class'=>'fancybox', 
							'data' => date('M d Y - H:ia', $file['date']),
						),
						array(
							'id' => 'edit_img_'.str_replace(".", '', $file['name']), 
							'data' => '<a href="'.BASE.AMP.'C=content_files'.AMP.'M=prep_edit_image'.AMP.'url_path='.$enc_url_path.AMP.'file='.$file['encrypted_path'].'" title="'.$file['name'].'">'.lang('edit').'</a>'
						),
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=download_files'.AMP.'file='.$file['encrypted_path'].'" title="'.$file['name'].'">'.lang('file_download').'</a> | '.
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=delete_files_confirm'.AMP.'file='.$file['encrypted_path'].'" title="'.$file['name'].'">'.lang('delete').'</a>',
						array(
							'class' => 'file_select', 
							'data' => form_checkbox('file[]', $file['encrypted_path'], FALSE, 'class="toggle"')
						)
					);
				}
				else
				{
					$vars['file_list'][$dir->id]['files'][$file_count] = array(
						$file['name'],
						array(
							'class'=>'align_right', 
							'data' => number_format($file['size']/1000, 1).NBS.lang('file_size_unit'),
						),
						$file['mime'],
						date('M d Y - H:ia', $file['date']),
						'--',
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=download_files'.AMP.'file='.$file['encrypted_path'].'" title="'.$file['name'].'">'.lang('file_download').'</a> | '.
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=delete_files_confirm'.AMP.'file='.$file['encrypted_path'].'" title="'.$file['name'].'">'.lang('delete').'</a>',
						array(
							'class' => 'file_select', 
							'data' => form_checkbox('file[]', $file['encrypted_path'], FALSE, 'class="toggle"')
						)
					);
				}

				$file_count++;
			}

		}

		return $vars;
	}

	// --------------------------------------------------------------------

	/**
	 * File Info
	 *
	 * Used in the file previews ajax call
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function file_info()
	{
		if ( ! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->output->enable_profiler(FALSE);
		$this->load->helper(array('file', 'html'));

		$this->load->library('encrypt');
		$file = $this->encrypt->decode(rawurldecode($this->input->get_post('file')), $this->session->sess_crypt_key);

		$file_info = get_file_info($file, array('name', 'size', 'fileperms'));

		if ( ! $file_info)
		{
			exit($this->lang->line('no_file'));
		}
		else
		{
			$file_type = get_mime_by_extension($file);
			$where = str_replace(str_replace(SYSDIR.'/', '', BASEPATH), '', substr($file, 0, strrpos($file, '/')).'/');

			$output = '<ul>';

			$output .= '<li class="file_name">'.$file_info['name'].'</li>';
			$output .= '<li><span>'.$this->lang->line('size').':</span> '.number_format($file_info['size']/1000, 1).'KB</li>';

			if ($file_type != FALSE)
			{
				$output .= '<li><span>'.$this->lang->line('kind').':</span> '.$file_type.'</li>';
			}

			$output .= '<li><span>'.$this->lang->line('where').':</span> '.$where.'</li>';
			$output .= '<li><span>'.$this->lang->line('permissions').':</span> '.symbolic_permissions($file_info['fileperms']).'</li>';
			$output .= '</ul>';

//			$output .= '<div id="file_tags"></div>'; // not currently used, but in there for potential future compatibility

			exit($output);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Upload File
	 *
	 * @access	public
	 * @return	mixed
	 */
	function upload_file()
	{
		if ( ! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// @todo: this function should be migrated to file manager lib
		$this->load->library('filemanager');
		$this->load->model('tools_model');
		// get upload dir info
		$upload_id = $this->input->get_post('upload_dir');

		$upload_dir_result = $this->tools_model->get_upload_preferences($this->session->userdata('member_group'), $upload_id);
		$upload_dir_prefs = $upload_dir_result->row();
		
		$filename = $_FILES['userfile']['name'];

		$filename = substr($filename, 0, strrpos($filename, '.'));

		switch($upload_dir_prefs->allowed_types)
		{
			case 'all' : $config['allowed_types'] = '*';
				break;
			case 'img' : $config['allowed_types'] = 'jpg|png|gif';
				break;
			default :
				$config['allowed_types'] = $upload_dir_prefs->allowed_types;
		}

		$config['file_name'] = url_title($filename, $this->config->item('word_separator'), TRUE);
		$config['upload_path'] = $upload_dir_prefs->server_path;
		$config['max_size']	= $upload_dir_prefs->max_size;
		$config['max_width']  = $upload_dir_prefs->max_width;
		$config['max_height']  = $upload_dir_prefs->max_height;

		$this->load->library('upload', $config);

		$try_upload = $this->upload->do_upload();

		// We use an iframe to simulate asynchronous uploading.  Files submitted
		// in this way will have the "is_ajax" field, otherwise they where normal
		// file upload submissions.

		if ( ! $try_upload)
		{
			if ($this->input->get_post('is_ajax') == 'true')
			{
				echo '<script type="text/javascript">parent.EE_uploads.'.$this->input->get('frame_id').' = '.$this->javascript->generate_json(array('error' => $this->upload->display_errors())).';</script>';
				exit;
			}
			else
			{
				$this->session->set_flashdata('message_failure', $this->upload->display_errors());
				$this->functions->redirect(BASE.AMP.'C=content_files');
			}
		}
		else
		{
			$this->load->library('encrypt');
			$file_info = $this->upload->data();
			$encrypted_path = rawurlencode($this->encrypt->encode($file_info['full_path'], $this->session->sess_crypt_key));

			// upload sucessful, now create thumb
			$thumb_path = $file_info['file_path'].'_thumbs'.DIRECTORY_SEPARATOR;

			if ( ! is_dir($thumb_path))
			{
				mkdir($thumb_path);
			}

			$resize['source_image']		= $file_info['full_path'];
			$resize['new_image']		= $thumb_path.'thumb_'.$file_info['file_name'];
			$resize['maintain_ratio']	= FALSE;
			$resize['image_library']	= $this->config->item('image_resize_protocol');
			$resize['library_path']		= $this->config->item('image_library_path');
			$resize['width']			= 73;
			$resize['height']			= 60;

			$this->load->library('image_lib', $resize);

			$thumb_errors = '';

			if ( ! $this->image_lib->resize())
			{
				// @todo find a good way to display errors
				$thumb_errors = $this->image_lib->display_errors();
			}

			if ($this->input->get_post('is_ajax') == 'true')
			{
				$response = array(
					'success'		=> $this->lang->line('upload_success'),
					'enc_path'		=> $encrypted_path,
					'filename'		=> $file_info['file_name'],
					'filesize'		=> $file_info['file_size'],
					'filetype'		=> $file_info['file_type'],
					'date'			=> date('M d Y - H:ia')
				);

				echo '<script type="text/javascript">parent.EE_uploads.'.$this->input->get('frame_id').' = '.$this->javascript->generate_json($response).';</script>';
			}
			else
			{
				$this->session->set_flashdata('message_success', $this->lang->line('upload_success'));
				$this->functions->redirect(BASE.AMP.'C=content_files');
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Download Files
	 *
	 * If a single file is passed, it is offered as a download, but if an array
	 * is passed, they are zipped up and offered as download
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function download_files($files = array())
	{
		if ( ! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ($this->input->get_post('file') == '')
		{
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		$this->load->library('encrypt');

		if (is_array($this->input->get_post('file')))
		{

			// extract each filename, add to files array
			foreach ($this->input->get_post('file') as $scrambled_file)
			{
				$files[] = $this->encrypt->decode(rawurldecode($scrambled_file), $this->session->sess_crypt_key);
			}
		}
		else
		{
			$file_offered = $this->encrypt->decode(rawurldecode($this->input->get_post('file')), $this->session->sess_crypt_key);

			if ($file_offered != '')
			{
				$files = array($file_offered);
			}
		}

		$files_count = count($files);

		if ($files_count == 0)
		{
			return; // move along
		}
		elseif ($files_count == 1)
		{
			// no point in zipping for a single file... let's just send the file

			$this->load->helper('download');

			$data = file_get_contents($files[0]);
			$name = substr(strrchr($files[0], '/'), 1);
			force_download($name, $data);
		}
		else
		{
			// its an array of files, zip 'em all

			$this->load->library('zip');

			foreach ($files as $file)
			{
				$this->zip->read_file($file);
			}

			$this->zip->download('images.zip'); 
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Files Confirm
	 *
	 * Used to confirm deleting files
	 *
	 * @access	public
	 * @return	mixed
	 */
	function delete_files_confirm()
	{
		if ( ! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper('form');

		$files = $this->input->get_post('file');
		
		if ( ! is_array($files))
		{
			$files = array($files);
		}

		$vars['files'] = $files;

		if ($vars['files'] == 1)
		{
			$vars['del_notice'] =  'confirm_del_file';
		}
		else
		{
			$vars['del_notice'] = 'confirm_del_files';
		}

		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_selected_files'));

		$this->javascript->compile();

		$this->load->view('content/file_delete_confirm', $vars);

	}

	// --------------------------------------------------------------------

	/**
	 * Delete Files
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function delete_files()
	{
		if ( ! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('encrypt');

		$files = $this->input->get_post('file');

		if ($files == '')
		{
			// nothing for you here
			$this->session->set_flashdata('message_failure', $this->lang->line('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		$delete_problem = 0;

		foreach($files as $file)
		{
			$file = rtrim($this->encrypt->decode(rawurldecode($file), $this->session->sess_crypt_key), DIRECTORY_SEPARATOR);

			$path = substr($file, 0, strrpos($file, DIRECTORY_SEPARATOR)+1);
			$thumb = substr($file, strrpos($file, DIRECTORY_SEPARATOR)+1);
			$thumb_path = $path.'_thumbs'.DIRECTORY_SEPARATOR.'thumb_'.$thumb;

			if ( ! @unlink($file))
			{
				$delete_problem++;
			}

			// Delete thumb also
			if (file_exists($thumb_path))
			{
				@unlink($thumb_path);
			}
		}

		if ($delete_problem == 0)
		{
			// no problems
			$this->session->set_flashdata('message_success', $this->lang->line('delete_success'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}
		else
		{
			// something's up.
			$this->session->set_flashdata('message_failure', $this->lang->line('delete_fail'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Show an Image
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function display_image()
	{
		if ( ! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('encrypt');
		$this->load->helper('file');

		$this->output->set_header('Content-Type: image/png');
		$file = $this->encrypt->decode(rawurldecode($this->input->get_post('file')), $this->session->sess_crypt_key);
		echo read_file($file);
	}

	// --------------------------------------------------------------------

	/**
	 * Show Image Editing Screen
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function prep_edit_image()
	{
		if ( ! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->load->helper(array('form', 'string', 'url', 'file'));
		$this->load->library('encrypt');

		$vars['file'] = $this->encrypt->decode(rawurldecode($this->input->get_post('file')), $this->session->sess_crypt_key);
		$vars['url_path'] = $this->encrypt->decode(rawurldecode($this->input->get_post('url_path')), $this->session->sess_crypt_key).'?f='.time();
		
		$this->javascript->set_global('filemanager.url_path', $vars['url_path']);
		$this->javascript->set_global('lang.hide_toolbar', $this->lang->line('hide_toolbar'));
		$this->javascript->set_global('lang.show_toolbar', $this->lang->line('show_toolbar'));
		
		$this->cp->add_js_script(array(
				'ui'		=> 'resizable',
				'plugin'	=> array('jcrop', 'simplemodal'),
				'file'		=> 'cp/file_manager_edit'
			)
		);

		if ($vars['file'] == '')
		{
			// nothing for you here
			$this->session->set_flashdata('message_failure', $this->lang->line('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		$this->cp->set_variable('cp_page_title', $this->lang->line('edit') .' '.substr(strrchr($vars['file'], '/'), 1));

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=content_files'=> $this->lang->line('content_files')
		));

		$vars['form_hidden']['file'] = $this->input->get_post('file');
		$vars['form_hidden']['url_path'] = $this->input->get_post('url_path');

		$vars['resize_width'] = array(
										'autocomplete' => 'off',
										'name' => 'resize_width',
										'id' => 'resize_width',
										'size' => 5,
										'value' => 0
									);
		$vars['resize_height'] = array(
										'autocomplete' => 'off',
										'name' => 'resize_height',
										'id' => 'resize_height',
										'size' => 5,
										'value' => 0
									);
		$vars['crop_width'] = array(
										'autocomplete' => 'off',
										'name' => 'crop_width',
										'id' => 'crop_width',
										'class' => 'crop_dim',
										'size' => 5,
										'value' => 0
									);
		$vars['crop_height'] = array(
										'autocomplete' => 'off',
										'name' => 'crop_height',
										'id' => 'crop_height',
										'class' => 'crop_dim',
										'size' => 5,
										'value' => 0
									);
		$vars['crop_x'] = array(
										'autocomplete' => 'off',
										'name' => 'crop_x',
										'id' => 'crop_x',
										'class' => 'crop_dim',
										'size' => 5,
										'value' => 0
									);
		$vars['crop_y'] = array(
										'autocomplete' => 'off',
										'name' => 'crop_y',
										'id' => 'crop_y',
										'class' => 'crop_dim',
										'size' => 5,
										'value' => 0
									);

		$vars['rotate_options'] = array(
										"none"			=> lang('none'),
										"90"			=> lang('rotate_90r'),
										"270"			=> lang('rotate_90l'),
										"180"			=> lang('rotate_180'),
										"vrt"			=> lang('rotate_flip_vert'),
										"hor"			=> lang('rotate_flip_hor'),
										);

		$vars['rotate_selected'] = 'none';

		$this->javascript->compile();

		$this->load->view('content/prep_edit_image', $vars);
	}

	// --------------------------------------------------------------------

	// @todo: this method is duplicated in the filemanager library. Remove it from here
	// after tools > filemanager has been migrated to that lib.
	/**
	 * Handle the edit actions
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function edit_image()
	{
		if ( ! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ($this->input->get_post('edit_done'))
		{
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		$this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
		$this->output->set_header("Pragma: no-cache");

		$this->load->library('encrypt');

		$file = $this->encrypt->decode(rawurldecode($this->input->get_post('file')), $this->session->sess_crypt_key);

		if ($file == '')
		{
			// nothing for you here
			$this->session->set_flashdata('message_failure', $this->lang->line('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		// crop takes precendence over resize
		// we need at least a width
		if ($this->input->get_post('crop_width') != '' AND $this->input->get_post('crop_width') != 0)
		{

			$config['width'] = $this->input->get_post('crop_width');
			$config['maintain_ratio'] = FALSE;
			$config['x_axis'] = $this->input->get_post('crop_x');
			$config['y_axis'] = $this->input->get_post('crop_y');
			$action = 'crop';

			if ($this->input->get_post('crop_height') != '')
			{
				$config['height'] = $this->input->get_post('crop_height');
			}
			else
			{
				$config['master_dim'] = 'width';
			}
		}
		elseif ($this->input->get_post('resize_width') != '' AND $this->input->get_post('resize_width') != 0)
		{
			$config['width'] = $this->input->get_post('resize_width');
			$config['maintain_ratio'] = $this->input->get_post("constrain");
			$action = 'resize';

			if ($this->input->get_post('resize_height') != '')
			{
				$config['height'] = $this->input->get_post('resize_height');
			}
			else
			{
				$config['master_dim'] = 'width';
			}
		}
		elseif ($this->input->get_post('rotate') != '' AND $this->input->get_post('rotate') != 'none')
		{
			$action = 'rotate';
			$config['rotation_angle'] = $this->input->get_post('rotate');
		}
		else
		{
			if ($this->input->get_post('is_ajax'))
			{
				header('HTTP', true, 500);
				exit($this->lang->line('width_needed'));
			}
			else
			{
				show_error($this->lang->line('width_needed'));
			}
		}

		$config['image_library'] = $this->config->item('image_resize_protocol');
		$config['source_image'] = $file;
		$config['new_image'] = $file;

//		$config['dynamic_output'] = TRUE;

		$this->load->library('image_lib', $config);

		$errors = '';

		// Cropping and Resizing
		if ($action == 'resize')
		{
			if ( ! $this->image_lib->resize())
			{
		    	$errors = $this->image_lib->display_errors();
			}
		}
		elseif ($action == 'rotate')
		{

			if ( ! $this->image_lib->rotate())
			{
			    $errors = $this->image_lib->display_errors();
			}
		}
		else
		{
			if ( ! $this->image_lib->crop())
			{
			    $errors = $this->image_lib->display_errors();
			}
		}

		// Any reportable errors? If this is coming from ajax, just the error messages will suffice
		if ($errors != '')
		{
			if ($this->input->get_post('is_ajax'))
			{
				header('HTTP', true, 500);
				echo $errors;
				exit;
			}
			else
			{
				show_error($errors);
			}
		}

		$dimensions = $this->image_lib->get_image_properties('', TRUE);
		$this->image_lib->clear();

		// Rebuild thumb
		// @todo
		// $this->create_thumb(array('server_path'=>substr($file, 0, strrpos($file, DIRECTORY_SEPARATOR))), array('name'=>$image_reference));

		$url = BASE.AMP.'C=content_files'.AMP.'M=prep_edit_image'.AMP.'file='.rawurlencode($this->input->get_post('file')).AMP.'url_path='.rawurlencode($this->input->get_post('url_path'));

		if ($this->input->get_post('is_ajax'))
		{
			echo 'width="'.$dimensions['width'].'" height="'.$dimensions['height'].'" ';
			exit;
		}
		else
		{
			$this->functions->redirect($url);
		}
	}
}
// END CLASS

/* End of file content_files.php */
/* Location: ./system/expressionengine/controllers/cp/content_files.php */