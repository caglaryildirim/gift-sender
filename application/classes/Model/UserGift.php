<?php

/**
 * @package Zynga\Model
 */

namespace Zynga\Model;

use Zynga\DB\SelectDataQuery;
use Zynga\DB\UpdateDataQuery;

class UserGift
{
	use ZyngaObjectTrait;

// <editor-fold defaultstate="collapsed" desc="Properties">
	/**
	 * @var int
	 */
	public $fromUserID;

	/**
	 * @var int
	 */
	public $toUserID;

	/**
	 * @var string
	 */
	public $giftDate;

	/**
	 * @var int
	 */
	public $giftTime;

	/**
	 * @var int
	 */
	public $giftSendType;

	/**
	 * @var int
	 */
	public $giftAccepted;

	/**
	 * @var string
	 */
	public $userEmail = "";

	/**
	 * @var string
	 */
	public $userFullName = "";

	/**
	 * @var string
	 */
	public $giftTypeName = "";

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Constructors">
	public function __construct($row) {
		$this->fromUserID = (int)$row['fromUserID'];
		$this->toUserID = (int)$row['toUserID'];
		$this->giftDate = $row['giftDate'];
		$this->giftTime = (int)$row['giftTime'];
		$this->giftSendType = (int)$row['giftSendType'];
		$this->giftAccepted = (int)$row['giftAccepted'];
		if (isset($row["userEmail"]))
			$this->userEmail = $row["userEmail"];
		if (isset($row["userFullName"]))
			$this->userFullName = $row["userFullName"];
		if (isset($row["gtName"]))
			$this->giftTypeName = $row["gtName"];
	}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Data read methods">
	private static $tableName = "tblUserGiftQueue";
	private static $selectList = "ugq.fromUserID,ugq.toUserID,ugq.giftDate,ugq.giftTime,ugq.giftSendType,ugq.giftAccepted";

	/**
	 * Returns all active objects
	 * @return UserGift|null
	 */
	public static function getUserGiftInfo($fromUserID, $toUserID, $date): ?UserGift {
		$selectQuery = SelectDataQuery::Create("SELECT " . self::$selectList . " FROM " . self::$tableName . " AS ugq" .
			" WHERE ugq.fromUserID=? AND ugq.toUserID=? AND ugq.giftDate=?", [$fromUserID, $toUserID, $date]);
		return self::GetObject($selectQuery, __CLASS__);
	}

	public static function sendUserGift($fromUserID, $toUserID, $timestamp, $giftTypeID) {
		$fieldValues = [
			"fromUserID" => $fromUserID,
			"toUserID" => $toUserID,
			"giftDate" => date("Y-m-d", $timestamp),
			"giftTime" => $timestamp,
			"giftSendType" => $giftTypeID,
		];
		$opRes = UpdateDataQuery::CreateInsert(self::$tableName)
			->SetMulti($fieldValues)
			->ExecuteUpdate(self::GameDB());
		if ($opRes->success) {
			UpdateDataQuery::CreateInsert("tblUserGiftLog")
				->SetMulti($fieldValues)
				->ExecuteUpdate(self::GameDB());
		}
		return OperationResult::FromUpdateQueryResult($opRes);
	}

	/**
	 * Returns all active objects
	 * @return UserGift[]
	 */
	public static function getUserGiftQueue($toUserID) {
		$selectQuery = SelectDataQuery::Create("SELECT " . self::$selectList .
			",u.userEmail,u.userFullName,gt.gtName" .
			" FROM " . self::$tableName . " AS ugq" .
			" INNER JOIN tblUser AS u ON ugq.fromUserID=u.userID" .
			" INNER JOIN tblGiftType AS gt ON ugq.giftSendType=gt.gtID" .
			" WHERE ugq.toUserID=? AND ugq.giftAccepted=? AND ugq.giftDate > '%s' AND gt.gtStatus=1", [$toUserID, 0, date("Y-m-d", strtotime("-7 days"))]);
		return self::GetObjectSet($selectQuery, __CLASS__);
	}
// </editor-fold>

	public function acceptGift() {
		$this->giftAccepted = 1;
		$dbRes = UpdateDataQuery::CreateUpdate(self::$tableName)
			->Where("fromUserID", $this->fromUserID)
			->Where("toUserID", $this->toUserID)
			->Where("giftDate", $this->giftDate)
			->Where("giftAccepted", 0)
			->Set("giftAccepted", 1)
			->ExecuteUpdate(self::GameDB());
		return OperationResult::FromUpdateQueryResult($dbRes);
	}
}
