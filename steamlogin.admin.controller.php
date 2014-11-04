<?php
/**
 * @class  naverloginAdminController
 * @author YJSoft (yjsoft@yjsoft.pe.kr)
 * @brief  naverlogin module admin controller class.
 */

class steamloginAdminController extends steamlogin
{
	function init()
	{
	}

	function procSteamloginAdminInsertConfig()
	{
		$oModuleController = getController('module');
		$oSteamloginModel = getModel('steamlogin');

		$vars = Context::getRequestVars();
		$section = $vars->_config_section;

		$config = $oSteamloginModel->getConfig();
		$config->clientid = $vars->clientid;
		$config->def_url = $vars->def_url;

		if(substr($config->def_url,-1)!='/')
		{
			$config->def_url .= '/';
		}

		$oModuleController->updateModuleConfig('steamlogin', $config);


		$this->setMessage('success_updated');
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSteamloginAdminConfig'));
	}
}

/* End of file naverlogin.admin.controller.php */
/* Location: ./modules/naverlogin/naverlogin.admin.controller.php */
