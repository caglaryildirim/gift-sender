<?php

namespace Zynga\Model;

use Zynga\DB\IDataSource;
use Zynga\DB\ISelectQuery;
use Zynga\ProjectEnvironment;

trait ZyngaObjectTrait
{
	/**
	 * Returns the News DB containing news and news saved scenes
	 * @return IDataSource
	 */
	private static function GameDB() {
		return ProjectEnvironment::Instance()->getDb();
	}

	/**
	 * @param ISelectQuery $selectQuery
	 * @param string $objectType
	 * @return mixed
	 */
	protected static function GetObject(ISelectQuery $selectQuery, $objectType) {
		return $selectQuery->GetObject(self::GameDB(), $objectType);
	}

	/**
	 * @param ISelectQuery $selectQuery
	 * @param string $objectType
	 * @return mixed
	 */
	protected static function GetObjectSet(ISelectQuery $selectQuery, $objectType) {
		return $selectQuery->GetObjectset(self::GameDB(), $objectType);
	}

	protected static function GetCachedDataWithCallback(string $cacheKey, $cacheTime, $dataCallback) {
		return ProjectEnvironment::Instance()->getCache()->GetCachedObjectCallback($cacheKey, $cacheTime, $dataCallback);
	}
}
