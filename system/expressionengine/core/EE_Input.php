<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Core Input Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Input extends CI_Input {

	var $SID = ''; // Session ID extracted from the URI segments


	// --------------------------------------------------------------------

	/**
 	 * Delete a Cookie
	 * 
	 * Delete a cookie with the given name.  Prefix will be automatically set
	 * from the configuation file, as will domain and path.  Httponly must be
	 * must be equal to the value used when setting the cookie.  
	 * 
	 * @param	string	The name of the cookie to be deleted.
	 * @param	boolean	The value of HTTP only used to when setting the cookie.
	 * 
	 * @return	boolean FALSE if output has already been sent (and thus the 
	 * 						cookie not set), TRUE otherwise.
	 */
	public function delete_cookie($name, $httponly=FALSE)
	{
		$data = array(
			'name' => $name,
			'value' => '',
			'expire' => time() - 86500,
			'httponly' => $httponly
		);
	
		return $this->_set_cookie($data);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Set a Cookie
	 *
	 * Set a cookie with a particular name, value and expiration.  Determine
	 * whether the cookie should be HTTP only or not.  Domain, path and prefix
	 * are kept as parameters to maintain compatibility with CI_Input::set_cookie()
	 * however, they are ignored in favor of the configuration file values. Expiration
	 * may be set to 0 to create an unexpiring cookie, or given a time in seconds to
	 * indicate that a cookie should expire that many seconds from the moment it is set.
	 * 
	 * @param	string	The name to assign the cookie.  This will be prefixed with
	 * 						the value from the config file or exp_.
	 * @param	string	The value to assign the cookie. This will be 
	 * 						automatically URL encoded when set and decoded
	 * 						when retrieved.
	 * @param	string	A time in seconds after which the cookie should expire.
	 * 						The cookie will be set to expire this many seconds
	 * 						after it is set.
	 * @param	boolean	Whether to use HTTP only cookies (which are not accessable)
	 * 						from javascript, or not.  TRUE to use HTTP only. 
	 * 						defaults to FALSE.
	 * 
	 * @return	boolean	FALSE if output has already been sent, TRUE otherwise.	
	 */
	public function set_cookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $httponly = FALSE)
	{

		$data = array(
			'name' => $name,
			'value' => $value,
			'expire' => $expire,
			'httponly' => $httponly,
			// We have to set these so we can 
			// check them and give the deprecation
			// warning.  However, they will be
			// ignored.
			'domain' => $domain,
			'path' => $path,
			'prefix' => $prefix
		);

		// If name is an array, then most of the values we just set in the data array
		// are probably their defaults.  Override the defaults with whatever happens
		// to be in the array.  Yes, this is ugly as all get out.
		if (is_array($name))
		{
			foreach (array('value', 'expire', 'name', 'domain', 'path', 'prefix') as $item)
			{
				if (isset($name[$item]))
				{
					$data[$item] = $name[$item];
				}
			}
		}

		if ($data['domain'] !== '' || $data['path'] !== '/' || $data['prefix'] !== '')
		{
			$EE = get_instance();
			$EE->load->library('logger');
			$EE->logger->developer('Warning: domain, path and prefix must be set in EE\'s configuration files and cannot be overriden in set_cookie.');
		}


		// Clean up the value.
		$data['value'] = stripslashes($data['value']);

		// Handle expiration dates.  
		if ( ! is_numeric($data['expire']))
		{
			
			$EE = get_instance();
			$EE->load->library('logger');
			$EE->logger->deprecated('2.6', 'EE_Input::delete_cookie()');
			$data['expire'] = time() - 86500;
		}
		else if ($data['expire'] > 0)
		{
			$data['expire'] = time() + $expire;
		}
		else 
		{
			$data['expire'] = 0;
		}
		
		$this->_set_cookie($data);
	}

	/**
	 * Set a Cookie
	 * 
	 * Protected method called from EE_Input::set_cookie() and 
	 * EE_Input::delete_cookie(). Handles the common config file logic, calls 
	 * the set_cookie_end hook and sets the cookie. 
	 *
	 * Must recieve name, value, expire and httponly in the parameter array or 
	 * will throw an exception.
 	 * 
	 * @param	mixed[]	The array of data containing name, value, expire and 
	 * 						httponly.  Must contain those parameters.
	 * @return	bool	If output exists prior to calling this method it will 
	 * 						fail with FALSE, otherwise it will return TRUE.  
	 * 						This does not indicate whether the user accepts the 
	 * 						cookie.
	 */
	protected function _set_cookie(array $data)
	{
		$EE = get_instance();

		// Always assume we'll forget and catch ourselves.  The earlier you catch this sort of screw up the better.
		if(!isset($data['name']) || !isset($data['value']) || !isset($data['expire']) || !isset($data['httponly']))
		{
			throw new RuntimeException('EE_Input::_set_cookie() is missing key data.');
		}

		// Set previx, path and domain. We'll pull em out of config.
		if (REQ == 'CP' && $EE->config->item('multiple_sites_enabled') == 'y')
		{
			$data['prefix'] = ( ! $EE->config->cp_cookie_prefix) ? 'exp_' : $EE->config->cp_cookie_prefix;
			$data['path']	= ( ! $EE->config->cp_cookie_path) ? '/' : $EE->config->cp_cookie_path;
			$data['domain'] = ( ! $EE->config->cp_cookie_domain) ? '' : $EE->config->cp_cookie_domain;
		}
		else
		{
			$data['prefix'] = ( ! $EE->config->item('cookie_prefix')) ? 'exp_' : $EE->config->item('cookie_prefix').'_';
			$data['path']	= ( ! $EE->config->item('cookie_path'))	? '/'	: $EE->config->item('cookie_path');
			$data['domain'] = ( ! $EE->config->item('cookie_domain')) ? '' : $EE->config->item('cookie_domain');
		}
	
		// Deal with secure cookies.	
		$data['secure_cookie'] = ($EE->config->item('cookie_secure') === TRUE) ? 1 : 0;
		if ($data['secure_cookie'])
		{
			$req = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : FALSE;
			if ( ! $req OR $req == 'off')
			{
				return FALSE;
			}
		}

		/* -------------------------------------------
		/* 'set_cookie_end' hook.
		/*  - Take control of Cookie setting routine
		/*  - Added EE 2.5.0
		*/
			$EE->extensions->call('set_cookie_end', $data);
			if ($EE->extensions->end_script === TRUE) return;
		/*
		/* -------------------------------------------*/

					
		return setcookie($data['prefix'].$data['name'], $data['value'], $data['expire'], 
			$data['path'], $data['domain'], $data['secure_cookie'], $data['httponly']);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Fetch an item from the COOKIE array
	 *
	 * This method overrides the one in the CI class since EE cookies have a particular prefix
	 *
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	function cookie($index = '', $xss_clean = FALSE)
	{
		$EE =& get_instance();
		
		$prefix = ( ! $EE->config->item('cookie_prefix')) ? 'exp_' : $EE->config->item('cookie_prefix').'_';
		
		return ( ! isset($_COOKIE[$prefix.$index]) ) ? FALSE : stripslashes($_COOKIE[$prefix.$index]);
	}

	// --------------------------------------------------------------------
		
	/**
	 * Filter GET Data
	 *
	 * Filters GET data for security
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function filter_get_data($request_type = 'PAGE')
	{
		$EE =& get_instance();

		/*
 		* --------------------------------------------------------------------
 		*  Is the request a URL redirect redirect?  Moved from the index so we can have config variables!
 		* --------------------------------------------------------------------
 		*
 		* All external links that appear in the ExpressionEngine control panel
 		* are redirected to this index.php file first, before being sent to the
 		* final destination, so that the location of the control panel will not 
 		* end up in the referrer logs of other sites.
 		*
 		*/	

		if (isset($_GET['URL'])) 
		{ 
			if ( ! file_exists(APPPATH.'libraries/Redirect.php'))
			{
				exit('Some components appear to be missing from your ExpressionEngine installation.');	
			}
			
			require(APPPATH.'libraries/Redirect.php');

			exit();  // We halt system execution since we're done
		}		

		$filter_keys = TRUE;
	
		if ($request_type == 'CP'
			&& isset($_GET['BK'])
			&& isset($_GET['channel_id'])
			&& isset($_GET['title'])
			&& $EE->session->userdata('admin_sess') == 1)
		{
			if (in_array($EE->input->get_post('channel_id'), $EE->functions->fetch_assigned_channels()))
			{			
				$filter_keys = FALSE;
			}		
		}
	
		if (isset($_GET))
		{
			foreach($_GET as $key => $val)
			{
				if ($filter_keys == TRUE)
				{
					if (preg_match("#(;|exec\s*\(|system\s*\(|passthru\s*\(|cmd\s*\()#i", $val))
					{
						// Only notify super admins of the offending data
						if ($EE->session->userdata('group_id') == 1)
						{
							$data = ((int) config_item('debug') == 2) ? '<br>'.htmlentities($val) : '';
							
							set_status_header(503);
							exit(sprintf("Invalid GET Data %s", $data));
						}
						// Otherwise, handle it more gracefully and just unset the variable
						else
						{
							unset($_GET[$key]);
						}
					}   
				}
			}	
		}	
	}

	// --------------------------------------------------------------------

	/**
	 * Remove session ID from string
	 *
	 * This function is used mainly by the Input class to strip
	 * session IDs if they are used in public pages.
	 *
	 * @param	string
	 * @return	string
	 */	
	public function remove_session_id($str)
	{
		return preg_replace("#S=.+?/#", "", $str);
	}

	// --------------------------------------------------------------------
	
	/**
	 * Extend _sanitize_globals to allow css
	 *
	 * For action requests we need to fully allow GET variables, so we set
	 * an exception in EE_Config. For css, we only need that one and it's a
	 * path, so we'll do some stricter cleaning.
	 *
	 * @param	string
	 * @return	string
	 */
	function _sanitize_globals()
	{
		$_css = $this->get('css');
		
		parent::_sanitize_globals();
		
		if ($_css)
		{
			$_GET['css'] = remove_invisible_characters($_css);
		}
	}
	
}
// END CLASS

/* End of file EE_Input.php */
/* Location: ./system/expressionengine/libraries/EE_Input.php */
