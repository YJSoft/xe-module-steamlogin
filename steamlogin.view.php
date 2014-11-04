<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  naverloginView
 * @author NAVER (developers@xpressengine.com)
 * @brief naverlogin view class of the module
 */
class steamloginView extends steamlogin
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile(strtolower(str_replace('dispSteamlogin', '', $this->act)));
	}

	/**
	 * @brief General request output
	 */
	function dispSteamloginOAuth()
	{
		$oSteamloginModel = getModel('steamlogin');
		$module_config = $oSteamloginModel->getConfig();

		$returnTo = $module_config->def_url . 'index.php?act=procSteamloginOAuth';

		$params = array(
			'openid.ns'			=> 'http://specs.openid.net/auth/2.0',
			'openid.mode'		=> 'checkid_setup',
			'openid.return_to'	=> $returnTo,
			'openid.realm'		=> $module_config->def_url,
			'openid.identity'	=> 'http://specs.openid.net/auth/2.0/identifier_select',
			'openid.claimed_id'	=> 'http://specs.openid.net/auth/2.0/identifier_select',
		);

		$sep = '&';
		$module_config->auth_url =  'https://steamcommunity.com/openid/login?' . http_build_query($params, '', $sep);

		Context::set('module_config', $module_config);
	}
}
/* End of file steamlogin.view.php */
/* Location: ./modules/steamlogin/steamlogin.view.php */
