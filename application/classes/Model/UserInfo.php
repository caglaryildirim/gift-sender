<?php

/**
 * @package Zynga\Model
 */

namespace Zynga\Model;

use JsonSerializable;
use Zynga\DB\SelectDataQuery;
use Zynga\DB\UpdateDataQuery;

class UserInfo implements JsonSerializable
{
	use ZyngaObjectTrait, JSONConvertibleTrait;

// <editor-fold defaultstate="collapsed" desc="Properties">
	/**
	 * @var int
	 */
	public $userID = 0;

	/**
	 * @var string
	 */
	public $userUID = "";

	/**
	 * @var string
	 */
	public $userEmail = "";

	/**
	 * @var string
	 */
	public $userPassword = "";

	/**
	 * @var string
	 */
	public $userFullName = "";

	/**
	 * @var int
	 */
	public $userCoin = 0;

	/**
	 * @var int
	 */
	public $userLifeCount = 0;

	/**
	 * @var string
	 */
	public $userItems = "";

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Constructors">
	public function __construct($row) {
		$this->userID = (int)$row['userID'];
		$this->userUID = $row['userUID'];
		$this->userEmail = $row['userEmail'];
		$this->userFullName = $row['userFullName'];
		if (isset($row['userPassword'])) {
			$this->userPassword = $row['userPassword'];
		}
		if (isset($row['userCoin'])) {
			$this->userCoin = (int)$row['userCoin'];
			$this->userLifeCount = (int)$row['userLifeCount'];
			$this->userItems = $row['userItems'];
		}
	}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Data read methods">
	private static $tableName = "tblUser";
	private static $selectList = "u.userID,u.userUID,u.userEmail,u.userFullName,u.userCoin,u.userLifeCount,u.userItems";
	private static $selectListSimple = "u.userID,u.userUID,u.userEmail,u.userFullName";

	/**
	 * Returns a single object by its ID
	 * - This may return false if the object cannot be found
	 * @param string $userEmail
	 * @return UserInfo|null
	 */
	public static function getUserInfoByEmail($userEmail) {
		$selectQuery = SelectDataQuery::Create("SELECT " . self::$selectList . ",u.userPassword FROM " . self::$tableName . " AS u" .
			" WHERE u.userEmail=? AND u.userStatus=1", array($userEmail));
		return self::GetObject($selectQuery, __CLASS__);
	}

	/**
	 * Returns all active objects
	 * @param int $userID
	 * @return UserInfo[]
	 */
	public static function getUserFriendList($userID) {
		$cacheKey = "OCache_UserFriendList_" . $userID;
		return self::GetCachedDataWithCallback($cacheKey, 30, function () use ($userID) {
			$selectQuery = SelectDataQuery::Create("SELECT " . self::$selectListSimple .
				" FROM " . self::$tableName . " AS u" .
				" INNER JOIN tblUserFriend AS uf ON uf.friendTargetUserID=u.userID" .
				" WHERE u.userStatus=1 AND uf.friendBlocked=0 AND uf.friendSourceUserID=?" .
				" ORDER BY u.userFullName,u.userID", array($userID));
			return self::GetObjectSet($selectQuery, __CLASS__);
		});
	}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Data update methods">

	/**
	 * Delete the object on the database
	 * @param $userID
	 * @return OperationResult
	 */
	public static function resetUserSendGifts($userID) {
		$dataSource = self::GameDB();
		$updateResult = UpdateDataQuery::CreateDelete(self::$tableName)
			->Where("fromUserID", $userID)
			->Where("giftDate", date("Y-m-d"))
			->ExecuteUpdate($dataSource);
		return OperationResult::FromUpdateQueryResult($updateResult);
	}

	/**
	 * Delete the object on the database
	 * @return OperationResult
	 */
	public static function expireUnclaimedGifts() {
		$dataSource = self::GameDB();

		$updateResult = UpdateDataQuery::CreateDelete(self::$tableName)
			->Where("giftAccepted", 0)
			->ExecuteUpdate($dataSource);

		return OperationResult::FromUpdateQueryResult($updateResult);
	}

	/**
	 * Updates the object on the database
	 * @return OperationResult
	 */
	public function updateUserInfo() {
		$dataSource = self::GameDB();
		$isInsert = $this->userID <= 0;
		if ($isInsert) {
			$updQuery = UpdateDataQuery::CreateInsert(self::$tableName)
				->Set("userEmail", $this->userEmail)
				->Set("userPassword", md5($this->userPassword))
				->Set("userUID", $this->userUID)
			;
		} else {
			$updQuery = UpdateDataQuery::CreateUpdate(self::$tableName)
				->Where("userID", $this->userID);
		}
		$updateResult = $updQuery
			->Set("userFullName", $this->userFullName)
			->Set("userCoin", $this->userCoin)
			->Set("userLifeCount", $this->userLifeCount)
			->Set("userItems", $this->userItems)
			->ExecuteUpdate($dataSource);

		if ($isInsert && $updateResult->success) {
			$this->userID = $updateResult->insertedID;
		}

		return OperationResult::FromUpdateQueryResult($updateResult);
	}
// </editor-fold>

	/**
	 * Specify data which should be serialized to JSON
	 * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize() {
		return self::jsonSerializeDefault([
			"userID" => 0,
			"userUID" => "",
			"userEmail" => "",
			"userPassword" => "",
			"userFullName" => "",
			"userCoin" => 0,
			"userLifeCount" => 0,
			"userItems" => "",
		]);
	}
}