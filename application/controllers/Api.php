<?php

use Zynga\Model\GiftType;
use Zynga\Model\OperationResult;
use Zynga\Model\SessionProfile;
use Zynga\Model\UserGift;
use Zynga\Model\UserInfo;

class Api extends MY_Controller
{
	/**
	 * @var SessionProfile
	 */
	private $session;

	public function __construct() {
		parent::__construct();
		$this->session = SessionProfile::GetInformation();
		if (!$this->session->authenticated) {
			show_404();
		}
	}

	/**
	 * Returns the data of the game main page
	 */
	public function mainPage() {
		$data = array(
			"giftTypes" => GiftType::getGiftTypeList(),
			"friends" => UserInfo::getUserFriendList($this->session->userInfo->userID),
			"giftQueue" => UserGift::getUserGiftQueue($this->session->userInfo->userID),
		);
		$this->load->jsonResponse($data);
	}

	function querySendToUser() {
		$sourceUserID = $this->session->userInfo->userID;
		$targetUserID = $this->input->post("userID");
		$date = date("Y-m-d");
		$sendInfo = UserGift::getUserGiftInfo($sourceUserID, $targetUserID, $date);
		if (empty($sendInfo)) {
			$data = OperationResult::Success();
		} else {
			$data = OperationResult::Failed("You already sent a gift to this user!");
		}
		$this->load->jsonResponse($data);
	}

	function sendToUser() {
		$sourceUserID = $this->session->userInfo->userID;
		$targetUserID = (int)$this->input->post("userID");
		$giftTypeID = (int)$this->input->post("gtID");
		$ts = time();
		$sendInfo = UserGift::getUserGiftInfo($sourceUserID, $targetUserID, date("Y-m-d", $ts));
		if (empty($sendInfo)) {
			$dbRes = UserGift::sendUserGift($sourceUserID, $targetUserID, $ts, $giftTypeID);
			if ($dbRes->Success) {
				$data = OperationResult::Success("Your gift is sent!");
			} else {
				$data = OperationResult::Failed("An error occured sending your gift!");
			}
		} else {
			$data = OperationResult::Failed("You already sent a gift to this user!");
		}
		$this->load->jsonResponse($data);
	}

	function acceptGift() {
		$fromUserID = (int)$this->input->post("userID");
		$giftDate = (string)$this->input->post("giftDate");
		$targetUserID = $this->session->userInfo->userID;

		$sendInfo = UserGift::getUserGiftInfo($fromUserID, $targetUserID, $giftDate);
		if (empty($sendInfo)) {
			$data = OperationResult::Failed("Gift not found!");
		} else {
			$opRes = $sendInfo->acceptGift();
			if ($opRes->Success) {
				$data = OperationResult::Success();
			} else {
				$data = OperationResult::Failed("An error occured receiving your gift!");
			}
		}

		$this->load->jsonResponse($data);
	}
}
