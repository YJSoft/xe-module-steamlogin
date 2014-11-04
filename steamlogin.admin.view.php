<?php
/**
 * @class  steamloginAdminView
 * @author YJSoft (yjsoft@yjsoft.pe.kr)
 * @brief  steamlogin module admin view class.
 */
class steamloginAdminView extends steamlogin
{
	function init()
	{
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile(strtolower(str_replace('dispSteamloginAdmin', '', $this->act)));
	}

	function dispSteamloginAdminConfig()
	{
		$oSteamloginModel = getModel('steamlogin');
		$module_config = $oSteamloginModel->getConfig();

		Context::set('module_config', $module_config);
	}
}

/* End of file steamlogin.admin.view.php */
/* Location: ./modules/steamlogin/steamlogin.admin.view.php */
