<?php
namespace Zynga\DB;

use stdClass;

/**
 * This class represents a generic SQL select query with optional paremeters.
 */
class SelectDataQuery implements ISelectQuery
{
	/**
	 * The base SQL query.
	 */
	public $commandSql;
	
	/**
	 * Parameters in the base SQL query.
	 */
	public $parameters;
	
	/**
	 * 0-based index of the first record to return.
	 */
	public $offset;

	/**
	 * Maximum number of records to return.
	 */
	public $limit;
	
	/**
	 * Creates a new instance of select-data query.
	 * 
	 * @param string $commandSql The base SQL query
	 * @param array $parameters Optional parameters in the base SQL query
	 * @param integer $offset 0-based index of the first record to return.
	 * @param integer $limit Maximum number of records to return. 
	 */
	public function __construct(
		$commandSql = "", array $parameters = array(), 
		$offset = -1, $limit = -1)
	{
		$this->commandSql = $commandSql;
		$this->parameters = $parameters;
		$this->offset = $offset;
		$this->limit = $limit;
	}
	
	/**
	 * Creates and returns a new instance of select-data query.
	 * 
	 * @param string $commandSql The base SQL query
	 * @param array $parameters Optional parameters in the base SQL query
	 * @param integer $offset 0-based index of the first record to return.
	 * @param integer $limit Maximum number of records to return.
	 * 
	 * @return SelectDataQuery
	 */
	public static function Create(
		$commandSql = "", array $parameters = array(), 
		$offset = -1, $limit = -1)
	{
		return new SelectDataQuery($commandSql, $parameters, $offset, $limit);
	}
	
	/**
	 * Adds a new SQL parameter to the select command.
	 * 
	 * @param mixed $parameter Parameter to the SQL select command.
	 *
	 * @return SelectDataQuery
	 */
	public function AddParameter($parameter)
	{
		$this->parameters[] = $parameter;
		
		// allow method chaining
		return $this;
	}
	
	/**
	 * This method registers the desired limits on the select query.
	 * Set a field to -1 to disable it.
	 * 
	 * @param integer $offset 0-based index of the first record to return.
	 * @param integer $limit Maximum number of records to return. 
	 * 
	 * @return SelectDataQuery
	 */
	public function SetLimit($offset, $limit = -1)
	{
		$this->offset = $offset;
		$this->limit = $limit;

		// allow method chaining
		return $this;
	}

// <editor-fold defaultstate="collapsed" desc="Methods to return plain data">
	/**
	 * Executes a select query and returns the resulting recordset.
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @return array
	 */
	public function GetRecordset(IDataSource $dataSource)
	{
		return $dataSource->GetRecordset($this);
	}

	/**
	 * Executes a select query and returns the resulting record.
	 * Returns FALSE if the result is empty.
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @return array
	 */
	public function GetRecord(IDataSource $dataSource)
	{
		return $dataSource->GetRecord($this);
	}

	/**
	 * Executes a select query and returns the resulting single result.
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @param mixed $defaultValue Default value to return, in case the result is empty.
	 * @return mixed
	 */
	public function GetResult(IDataSource $dataSource, $defaultValue = NULL)
	{
		return $dataSource->GetResult($this, $defaultValue);
	}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Methods to return strongly-typed data">
	/**
	 * Returns a single object with a constructor taking a database record as parameter
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @param string $objectType Optional type of the object to return. Default is stdClass.
	 * @return mixed
	 */
	public function GetObject(IDataSource $dataSource, $objectType = stdClass::class)
	{
		$dataRow = $dataSource->GetRecord($this);
		if (empty($dataRow)) {
			return null;
		} elseif (strcasecmp($objectType, stdClass::class) == 0) {
			return (object)$dataRow;
		} else {
			return new $objectType($dataRow);
		}
	}

	/**
	 * Returns an object set with a constructor taking a database record as parameter
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @param string $objectType Optional type of the objects to return. Default is stdClass.
	 * @return mixed
	 */
	public function GetObjectSet(IDataSource $dataSource, $objectType = stdClass::class)
	{
		$dataRowSet = $dataSource->GetRecordset($this);
		$objSet = array();

		// for perfomance reasons check for return type outside of the loop
		if (strcasecmp($objectType, stdClass::class) == 0) {
			foreach ($dataRowSet as $dataRow) {
				$objSet[] = (object)$dataRow;
			}
		} else {
			foreach ($dataRowSet as $dataRow) {
				$objSet[] = new $objectType($dataRow);
			}
		}

		return $objSet;
	}
// </editor-fold>
}
