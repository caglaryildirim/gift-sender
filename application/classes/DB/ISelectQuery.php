<?php

namespace Zynga\DB;

/**
 * Encapsulates common query methods for SelectDataQuery and SelectExtendedDataQuery
 */
interface ISelectQuery
{

	/**
	 * Executes a select query and returns the resulting recordset.
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @return array
	 */
	function GetRecordset(IDataSource $dataSource);

	/**
	 * Executes a select query and returns the resulting record.
	 * Returns FALSE if the result is empty.
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @return array
	 */
	function GetRecord(IDataSource $dataSource);

	/**
	 * Executes a select query and returns the resulting single result.
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @param mixed $defaultValue Default value to return, in case the result is empty.
	 * @return mixed
	 */
	function GetResult(IDataSource $dataSource, $defaultValue);

	/**
	 * Returns a single object with a constructor taking a database record as parameter
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @param string $objectType Optional type of the object to return. Default is stdClass.
	 * @return mixed
	 */
	function GetObject(IDataSource $dataSource, $objectType);

	/**
	 * Returns an object set with a constructor taking a database record as parameter
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @param string $objectType Optional type of the objects to return. Default is stdClass.
	 * @return mixed
	 */
	function GetObjectSet(IDataSource $dataSource, $objectType);
}
