<?php

class steamloginModel extends steamlogin
{
	private $config;

	function init()
	{
	}

	/**
	 * @brief 모듈 설정 반환
	 */
	function getConfig()
	{
		if(!$this->config)
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('steamlogin');

			$this->config = $config;
		}

		return $this->config;
	}
}

/* End of file steamlogin.model.php */
/* Location: ./modules/steamlogin/steamlogin.model.php */
