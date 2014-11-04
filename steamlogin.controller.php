<?php
/**
 * @class  naverloginController
 * @author YJSoft (yjsoft@yjsoft.pe.kr)
 * @brief  naverlogin module controller class.
 * @TODO 네이버 계정으로 가입한 뒤 비밀번호 변경 유도
 */

class steamloginController extends steamlogin
{
	private $error_message;
	private $redirect_Url;

	function init()
	{
	}

	function triggerDisablePWChk($args)
	{
		$cond = new stdClass();
		$cond->srl = $args->member_srl;
		$output = executeQuery('steamlogin.getSteamloginMemberbySrl', $cond);
		if(isset($output->data->enc_id)) $_SESSION['rechecked_password_step'] = 'INPUT_DATA';
		return;
	}

	/**
	 * @brief 회원 탈퇴시 스팀 로그인 DB에서도 삭제
	 * @param $args->member_srl
	 * @return mixed
	 */
	function triggerDeleteSteamloginMember($args)
	{
		$cond = new stdClass();
		$cond->srl = $args->member_srl;
		$output = executeQuery('steamlogin.deleteSteamloginMember', $cond);

		return;
	}

	/**
	 * @brief 아무 것도 안함
	 * @param void
	 * @return void
	 */
	function triggerChkID($args)
	{
		return;
	}

	/**
	 * @brief 스팀으로부터 아이디를 받아와서 회원가입여부 확인뒤 가입 혹은 로그인 처리
	 * @param void
	 * @return mixed
	 */
	function procSteamloginOAuth()
	{
		$id = $this->validate();

		if($id=='')
		{
			$this->error_message = 'OpenID Error.';
			return new Object(-1, $this->error_message);
		}
		//API 전솔 실패
		if($this->send($id)=='')
		{
			return new Object(-1, $this->error_message);
		}
		else
		{
			$this->setRedirectUrl($this->redirect_Url);
		}
	}

	/**
	 * @param $state
	 * @param $id
	 * @return bool
	 */
	function send($id) {
		//오류 메세지 변수 초기화
		$this->error_message = '';

		$oModuleModel = getModel('module');
		$oModuleConfig = $oModuleModel->getModuleConfig('steamlogin');

		$oMemberModel = getModel('member');
		$oMemberController = getController('member');

		//설정이 되어있지 않은 경우 리턴
		if(!$oModuleConfig->clientid)
		{
			//TODO 다국어화
			$this->error_message = '설정이 되어 있지 않습니다.';
			return false;
		}

		//API 서버에 key와 id값을 보내 프로필을 받아 온다.
		$ping_url = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $oModuleConfig->clientid . '&steamids=' . $id;
		$ping_header = array();
		$ping_header['Host'] = 'api.steampowered.com';
		$ping_header['Pragma'] = 'no-cache';
		$ping_header['Accept'] = '*/*';

		$buff = FileHandler::getRemoteResource($ping_url, null, 10, 'GET', 'application/x-www-form-urlencoded', $ping_header);
		$data = json_decode($buff);
		$xmlDoc = $data->response->players[0];

		//회원 설정 불러옴
		$config = $oMemberModel->getMemberConfig();

		//steamid로 회원이 있는지 조회
		$cond = new stdClass();
		$cond->enc_id=$xmlDoc->steamid;
		$output = executeQuery('steamlogin.getSteamloginMemberbyEncID', $cond);

		//srl이 있다면(로그인 시도)
		if(isset($output->data->srl))
		{
			$member_Info = $oMemberModel->getMemberInfoByMemberSrl($output->data->srl);
			if($config->identifier == 'email_address')
			{
				$oMemberController->doLogin($member_Info->email_address,'',false);
			}
			else
			{
				$oMemberController->doLogin($member_Info->user_id,'',false);
			}

			//회원정보 변경시 비밀번호 입력 없이 변경 가능하도록 수정
			$_SESSION['rechecked_password_step'] = 'INPUT_DATA';

			if($config->after_login_url) $this->redirect_Url = $config->after_login_url;
			$this->redirect_Url = getUrl('');

			return true;
		}
		else
		{
			// call a trigger (before)
			$trigger_output = ModuleHandler::triggerCall ('member.procMemberInsert', 'before', $config);
			if(!$trigger_output->toBool ()) return $trigger_output;
			// Check if an administrator allows a membership
			if($config->enable_join != 'Y')
			{
				$this->error_message = 'msg_signup_disabled';
				return false;
			}

			$args = new stdClass();
			$args->email_id = "s" . $xmlDoc->steamid;
			$args->email_host = "steam.com";
			$args->allow_mailing="N";
			$args->allow_message="Y";
			$args->email_address=substr($args->email_id,0,10) . '@' . $args->email_host;
			while($oMemberModel->getMemberSrlByEmailAddress($args->email_address)){
				$args->email_address=substr($args->email_id,0,5) . substr(md5($id . rand(0,9999)),0,5) . '@' . $args->email_host;
			}

			$args->find_account_answer=md5($id) . '@' . $args->email_host;
			$args->find_account_question="1";
			$args->nick_name=$xmlDoc->personaname;
			while($oMemberModel->getMemberSrlByNickName($args->nick_name)){
				$args->nick_name=$xmlDoc->personaname . substr(md5($id . rand(0,9999)),0,5);
			}
			$args->password=md5($id) . "a1#";
			$args->user_id=substr($args->email_id,0,20);
			while($oMemberModel->getMemberInfoByUserID($args->user_id)){
				$args->user_id=substr($args->email_id,0,10) . substr(md5($id . rand(0,9999)),0,10);
			}
			$args->user_name=$xmlDoc->realname;

			// remove whitespace
			$checkInfos = array('user_id', 'nick_name', 'email_address');
			$replaceStr = array("\r\n", "\r", "\n", " ", "\t", "\xC2\xAD");
			foreach($checkInfos as $val)
			{
				if(isset($args->{$val}))
				{
					$args->{$val} = str_replace($replaceStr, '', $args->{$val});
				}
			}

			$output = $oMemberController->insertMember($args);
			if(!$output->toBool())
			{
				$this->error_message = $output->message;
				return true;
			}

			$site_module_info = Context::get('site_module_info');
			if($site_module_info->site_srl > 0)
			{
				$columnList = array('site_srl', 'group_srl');
				$default_group = $oMemberModel->getDefaultGroup($site_module_info->site_srl, $columnList);
				if($default_group->group_srl)
				{
					$this->addMemberToGroup($args->member_srl, $default_group->group_srl, $site_module_info->site_srl);
				}

			}

			$steamloginmember = new stdClass();
			$steamloginmember->srl = $args->member_srl;
			$steamloginmember->enc_id = $xmlDoc->steamid;

			$output = executeQuery('steamlogin.insertSteamloginMember', $steamloginmember);
			if(!$output->toBool())
			{
				return false;
			}

			$tmp_file = sprintf('./files/cache/tmp/%d', md5(rand(111111,999999).$args->email_id));
			if(!is_dir('./files/cache/tmp')) FileHandler::makeDir('./files/cache/tmp');

			$ping_header = array();
			$ping_header['Pragma'] = 'no-cache';
			$ping_header['Accept'] = '*/*';

			$request_config = array();
			$request_config['ssl_verify_peer'] = false;

			FileHandler::getRemoteFile($xmlDoc->avatarfull, $tmp_file,null, 10, 'GET', null,$ping_header,array(),array(),$request_config);

			if(file_exists($tmp_file))
			{
				$oMemberController->insertProfileImage($args->member_srl, $tmp_file);
			}

			if($config->identifier == 'email_address')
			{
				$oMemberController->doLogin($args->email_address);
			}
			else
			{
				$oMemberController->doLogin($args->user_id);
			}

			$_SESSION['rechecked_password_step'] = 'INPUT_DATA';

			if($config->redirect_url) $this->redirect_Url = $config->redirect_url;
			else $this->redirect_Url = getUrl('', 'act', 'dispMemberModifyEmailAddress');

			FileHandler::removeFile($tmp_file);

			return true;
		}
	}
}

/* End of file naverlogin.controller.php */
/* Location: ./modules/naverlogin/naverlogin.controller.php */
