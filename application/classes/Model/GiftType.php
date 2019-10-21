<?php

/**
 * @package Zynga\Model
 */

namespace Zynga\Model;

use Zynga\DB\SelectDataQuery;

class GiftType
{
	use ZyngaObjectTrait;

// <editor-fold defaultstate="collapsed" desc="Properties">
	/**
	 * @var int
	 */
	public $gtID;

	/**
	 * @var string
	 */
	public $gtName;

	/**
	 * @var int
	 */
	public $gtOrder;

	/**
	 * @var int
	 */
	public $gtStatus;

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Constructors">
	public function __construct($row) {
		$this->gtID = (int)$row['gtID'];
		$this->gtName = $row['gtName'];
		$this->gtOrder = (int)$row['gtOrder'];
		$this->gtStatus = (int)$row['gtStatus'];
	}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Data read methods">
	private static $tableName = "tblGiftType";
	private static $selectList = "t.gtID,t.gtName,t.gtOrder,t.gtStatus";

	/**
	 * Returns all active objects
	 * @return GiftType[]
	 */
	public static function getGiftTypeList() {
		return self::GetCachedDataWithCallback("OCache_GiftType_List", 300, function () {
			$selectQuery = SelectDataQuery::Create("SELECT " . self::$selectList . " FROM " . self::$tableName . " AS t" .
				" WHERE t.gtStatus=1 ORDER BY t.gtOrder");
			return self::GetObjectSet($selectQuery, __CLASS__);
		});
	}
// </editor-fold>
}
