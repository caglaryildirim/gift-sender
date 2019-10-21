<?php

namespace Zynga\Model;

use Zynga\ProjectEnvironment;

class SessionProfile
{
	/**
	 * Session key for the instance of the profile information.
	 */
	const ProfileInfokey = "ProfileInfo";

	/**
	 * @var bool
	 */
	public $authenticated = false;

	/**
	 * @var string
	 */
	public $loginPasswordSalt = "";

	/**
	 * @var UserInfo|null
	 */
	public $userInfo = null;

	/**
	 * Returns or creates the session profile information.
	 *
	 * @return SessionProfile
	 */
	public static function GetInformation() {
		ProjectEnvironment::Instance()->getSessionCache();

		// create the session instance if there is no valid session instance
		if (!isset($_SESSION[self::ProfileInfokey]) ||
			!($_SESSION[self::ProfileInfokey] instanceof SessionProfile)) {
			$_SESSION[self::ProfileInfokey] = new SessionProfile();
		}

		/** @var SessionProfile $profileInstance */
		$profileInstance = $_SESSION[self::ProfileInfokey];
		return $profileInstance;
	}

	public function GenerateLoginRandomSalt() {
		$this->loginPasswordSalt = md5(uniqid(mt_rand(), true) . session_id() . rand(1, 100000));
	}

	public function TryAuthenticate($username, $passwordMD5) {
		$userInfo = UserInfo::getUserInfoByEmail($username);
		$lastPasswordSalt = $this->loginPasswordSalt;
		$this->authenticated = false;
		$this->userInfo = null;
		$this->loginPasswordSalt = "";

		if (empty($lastPasswordSalt)) {
			return OperationResult::Failed("Invalid password code");
		}


		if (empty($userInfo)) {
			return OperationResult::Failed("User not found");
		}

		$passwordExpectedMD5 = md5($lastPasswordSalt . $userInfo->userPassword . $lastPasswordSalt);
		if ($passwordMD5 != $passwordExpectedMD5) {
			return OperationResult::Failed("Invalid password");
		}

		$this->authenticated = true;
		$this->userInfo = $userInfo;
		return OperationResult::Success();
	}
}
