<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  naverlogin
 * @author YJSoft (yjsoft@yjsoft.pe.kr)
 * @brief  Naver Login module high class.
 */

class steamlogin extends ModuleObject
{
	//$output = ModuleHandler::triggerCall('member.updateMember', 'before', $args);
	private $triggers = array(
		array('member.deleteMember', 'steamlogin', 'controller', 'triggerDeleteSteamloginMember', 'after'),
		array('member.procMemberModifyInfo', 'steamlogin', 'controller', 'triggerDisablePWChk', 'after')
	);

	function moduleInstall()
	{
		$oModuleController = getController('module');

		foreach($this->triggers as $trigger)
		{
			$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
		}

		return new Object();
	}

	function checkUpdate()
	{
		$oModuleModel = getModel('module');

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		return new Object(0, 'success_updated');
	}

	function moduleUninstall()
	{
		return new Object();
	}

	function recompileCache()
	{
		return new Object();
	}

	function checkOpenSSLSupport()
	{
		if(!in_array('ssl', stream_get_transports())) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 *
	 * @package Steam Community API
	 * @copyright (c) 2010 ichimonai.com
	 * @license http://opensource.org/licenses/mit-license.php The MIT License
	 *
	 */

	const STEAM_LOGIN = 'https://steamcommunity.com/openid/login';

	/**
	 * Get the URL to sign into steam
	 *
	 * @param mixed returnTo URI to tell steam where to return, MUST BE THE FULL URI WITH THE PROTOCOL
	 * @param bool useAmp Use &amp; in the URL, true; or just &, false.
	 * @return string The string to go in the URL
	 */
	public static function genUrl($returnTo = false, $useAmp = true)
	{
		$returnTo = (!$returnTo) ? (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] : $returnTo;

		$params = array(
			'openid.ns'			=> 'http://specs.openid.net/auth/2.0',
			'openid.mode'		=> 'checkid_setup',
			'openid.return_to'	=> $returnTo,
			'openid.realm'		=> (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'],
			'openid.identity'	=> 'http://specs.openid.net/auth/2.0/identifier_select',
			'openid.claimed_id'	=> 'http://specs.openid.net/auth/2.0/identifier_select',
		);

		$sep = ($useAmp) ? '&amp;' : '&';
		return self::STEAM_LOGIN . '?' . http_build_query($params, '', $sep);
	}

	/**
	 * Validate the incoming data
	 *
	 * @return string Returns the SteamID64 if successful or empty string on failure
	 */
	public static function validate()
	{
		// Star off with some basic params
		$params = array(
			'openid.assoc_handle'	=> $_GET['openid_assoc_handle'],
			'openid.signed'			=> $_GET['openid_signed'],
			'openid.sig'			=> $_GET['openid_sig'],
			'openid.ns'				=> 'http://specs.openid.net/auth/2.0',
		);

		// Get all the params that were sent back and resend them for validation
		$signed = explode(',', $_GET['openid_signed']);
		foreach($signed as $item)
		{
			$val = $_GET['openid_' . str_replace('.', '_', $item)];
			$params['openid.' . $item] = get_magic_quotes_gpc() ? stripslashes($val) : $val;
		}

		// Finally, add the all important mode.
		$params['openid.mode'] = 'check_authentication';

		// Stored to send a Content-Length header
		$data =  http_build_query($params);
		$context = stream_context_create(array(
			'http' => array(
				'method'  => 'POST',
				'header'  =>
					"Accept-language: en\r\n".
					"Content-type: application/x-www-form-urlencoded\r\n" .
					"Content-Length: " . strlen($data) . "\r\n",
				'content' => $data,
			),
		));

		$result = file_get_contents(self::STEAM_LOGIN, false, $context);

		// Validate wheather it's true and if we have a good ID
		preg_match("#^http://steamcommunity.com/openid/id/([0-9]{17,25})#", $_GET['openid_claimed_id'], $matches);
		$steamID64 = is_numeric($matches[1]) ? $matches[1] : 0;

		// Return our final value
		return preg_match("#is_valid\s*:\s*true#i", $result) == 1 ? $steamID64 : '';
	}
}


/* End of file naverlogin.class.php */
/* Location: ./modules/naverlogin/naverlogin.class.php */