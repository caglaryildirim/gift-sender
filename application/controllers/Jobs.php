<?php
use Zynga\Model\DataClassGenerator;

class Jobs extends MY_Controller
{
	public function __construct() {
		parent::__construct();

		require_once ("/home/virtualservers/cbglib/cbgcore/ConfigManagerLoader.php");
	}

	/**
	 * http://sd.secim.test/jobs/t2
	 * http://sd.secim.test/jobs/t2/tblGiftType/GiftType
	 * http://sd.secim.test/jobs/t2/tblUser/UserInfo
	 * http://sd.secim.test/jobs/t2/tblUserGiftQueue/UserGift
	 */
	public function t2($tableName = 'tblGiftType', $className = 'GiftType') {
		$code = DataClassGenerator::generateTableCodeClass($tableName, $className);
		header("Content-Type: text/plain; charset=utf-8");
		echo $code;
	}
}
