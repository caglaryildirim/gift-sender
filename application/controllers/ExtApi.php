<?php


use Zynga\Model\UserInfo;

class ExtApi extends MY_Controller
{
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Returns bool
	 */
	public function resetUserSendGift() {
		$userID = (int)$this->input->post("userID");
		return UserInfo::resetUserSendGifts($userID);
	}

	/**
	 * Returns bool
	 */
	public function expireUnclimedGifts() {
		return UserInfo::expireUnclaimedGifts();
	}
}