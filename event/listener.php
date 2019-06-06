<?php
/**
*
* @package phpBB Extension - Addon for Contact Admin
* @copyright (c) 2019 Sheer
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/
namespace sheer\contactadmin_addon\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
/**
* Assign functions defined in this class to event listeners in the core
*
* @return array
* @static
* @access public
*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'									=> 'load_language_on_setup',
			'rmcgirr83.contactadmin.modify_data_and_error'		=> 'modify_data',
		);
	}

	/** @var \phpbb\user */
	protected $user;

	/**
	* Constructor
	*/
	public function __construct(\phpbb\user $user)
	{
		$this->user = $user;
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'sheer/contactadmin_addon',
			'lang_set' => 'sfs_chk',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function modify_data($event)
	{
		$data = $event['data'];

		$ch_data = array(
			$data['username'],
			$this->user->data['session_ip'],
			$data['email'],
		);

		$result = $this->check_stopforumspam($ch_data);
		if ($result['ip'] == 'yes' || $result['email'] == 'yes')
		{
			$event['error'] = array_merge($event['error'], array($this->user->lang['SPAM']));
		}
	}

	private function check_stopforumspam($chk_data)
	{
		$result = array();
		$chk_data[0] = str_replace(' ', '%20', $chk_data[0]);
		$insp = array();
		if ($chk_data[0] != '' || $chk_data[1] != '' || $chk_data[2] != '')
		{
			$xmlUrl = 'http://api.stopforumspam.org/api?';
			$xmlUrl .= (!empty($chk_data[0])) ? 'username=' . urlencode(iconv('GBK', 'UTF-8', $chk_data[0])) . '&' : '';
			$xmlUrl .= (!empty($chk_data[1])) ? 'ip=' . $chk_data[1] . '&' : '';
			$xmlUrl .= (!empty($chk_data[2])) ? 'email=' . $chk_data[2] . '' : '';
			$xmlUrl .= '&serial';

			// Try to get data from stopforumspam
			$xmlStr = @file_get_contents($xmlUrl);
			if (!$xmlStr)
			{
				// Fail get data via file_get_contents() - just try use curl
				$xmlStr = $this->get_file($xmlUrl);
			}

			$data = unserialize($xmlStr);
			if ($data['success'])
			{
				$result['username'] = (isset($data['username']['appears']) && $data['username']['appears']) ? 'yes' : 'no';
				$result['ip'] = (isset($data['ip']['appears']) && $data['ip']['appears']) ? 'yes' : 'no';
				$result['email'] = (isset($data['email']['appears']) && $data['email']['appears']) ? 'yes' : 'no';
			}
		}

		return $result;
	}

	// use curl to get response from SFS
	private function get_file($url)
	{
		// We'll use curl..most servers have it installed as default
		if (function_exists('curl_init'))
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			$contents = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			// if nothing is returned (SFS is down)
			if ($httpcode != 200)
			{
				return false;
			}

			return $contents;
		}

		return false;
	}
}
