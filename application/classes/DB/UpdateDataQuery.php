<?php

namespace Zynga\DB;

use DomainException;
use InvalidArgumentException;
use Traversable;

/**
 * This class represents a generic SQL data modification query (INSERT, UPDATE or DELETE).
 */
class UpdateDataQuery
{
	/**
	 * Type of the operation (INSERT, UPDATE or DELETE).
	 * @var string
	 */
	public $queryType;
	
	/**
	 * Target database table of the operation. 
	 * @var string
	 */
	public $tableName;
	
	/**
	 * SET clauses in the query.
	 *  
	 * A 'set' clause can be used in INSERT and UPDATE queries and the 
	 * value can be a primitive value or an expression. 
	 * @var SetValueOperation[]
	 */
	public $fieldSet = array();
	
	/**
	 * WHERE clauses in the query.
	 *  
	 * A 'where' clause can be used in UPDATE and DELETE queries and the 
	 * value can be a primitive value, an expression or an EXISTS constraint. 
	 */
	public $fieldWhere = array();

	/**
	 * Value of the primary key field specified in an operation using WhereKey().
	 * @var mixed
	 */
	public $primaryKeyValue = NULL;
	
	/**
	 * Creates a new instance of update-data query.
	 * 
	 * @param string $tableName Target database table of the operation
	 * @param string $queryType Type of the operation (INSERT, UPDATE or DELETE)
	 */
	public function __construct($tableName, $queryType)
	{
		$this->queryType = $queryType;
		$this->tableName = $tableName;
	}

	/**
	 * Creates and returns a new instance of update-data query.
	 * 
	 * @param mixed $tableName Target database table of the operation
	 * @param mixed $queryType Type of the operation (insert, update or delete)
	 * @return UpdateDataQuery
	 */
	public static function Create($tableName, $queryType)
	{
		return new UpdateDataQuery($tableName, $queryType);
	}

	/**
	 * Creates and returns a new instance of insert query.
	 * 
	 * @param mixed $tableName Target database table of the operation
	 * @return UpdateDataQuery
	 */
	public static function CreateInsert($tableName)
	{
		return new UpdateDataQuery($tableName, 'INSERT');
	}

	/**
	 * Creates and returns a new instance of update query.
	 * 
	 * @param mixed $tableName Target database table of the operation
	 * @return UpdateDataQuery
	 */
	public static function CreateUpdate($tableName)
	{
		return new UpdateDataQuery($tableName, 'UPDATE');
	}

	/**
	 * Creates and returns a new instance of delete query.
	 * 
	 * @param mixed $tableName Target database table of the operation
	 * @return UpdateDataQuery
	 */
	public static function CreateDelete($tableName)
	{
		return new UpdateDataQuery($tableName, 'DELETE');
	}

	/**
	 * Registers a WHERE clause internally.
	 *
	 * @param mixed $type Type of the WHERE clause (value, expression, in, like, exists)
	 * @param mixed $fieldName Target field of the where clause
	 * @param mixed $value Value associated with the clause
	 * @param mixed $operator Operator that binds the field and the value (=,!=,<,<=,>,>=)
	 * @return UpdateDataQuery
	 * @throws DomainException
	 */
	private function WhereInternal($type, $fieldName, $value = NULL, $operator = '=')
	{
		if ($this->queryType != 'UPDATE' &&
			$this->queryType != 'DELETE') {
			throw new DomainException('Where clause is only supported in update and delete queries.');
		}

		$this->fieldWhere[] = array('type' => $type, 'field' => $fieldName, 
			'value' => $value, 'operator' => $operator);

		// allow method chaining
		return $this;
	}

	/**
	 * Adds a new simple where clause to the query object.
	 *
	 * @param string $fieldName Name of the database field
	 * @param mixed $value Value of the field
	 * @param mixed $operator Operator that binds the field and the value (=,!=,<,<=,>,>=)
	 * @return UpdateDataQuery
	 */
	public function Where($fieldName, $value, $operator = '=')
	{
		return $this->WhereInternal('value', $fieldName, $value, $operator);
	}

	/**
	 * Adds multiple where clauses to the query object with an associative array of values
	 * @param array|Traversable $keyValueList Associative array of values to add as a where filter
	 * @return UpdateDataQuery
	 */
	public function WhereMulti($keyValueList) {
		if (!is_array($keyValueList) && !($keyValueList instanceof Traversable)) {
			throw new InvalidArgumentException("keyValueList must be an array or traversable");
		}
		foreach ($keyValueList as $fieldName => $value) {
			$this->WhereInternal('value', $fieldName, $value);
		}
		return $this;
	}

	/**
	 * Adds a new where clause with the primary key field.
	 *
	 * @param string $fieldName Name of the database primary key field
	 * @param mixed $value Value of the field
	 * @return UpdateDataQuery
	 */
	public function WhereKey($fieldName, $value)
	{
		$this->primaryKeyValue = $value;
		return $this->WhereInternal('value', $fieldName, $value);
	}

	/**
	 * Adds a new where clause with a custom expression to the query object.
	 *
	 * @param string $fieldName Name of the database field
	 * @param mixed $expression Custom expression that must match with the field
	 * @param mixed $operator Operator that binds the field and the value (=,!=,<,<=,>,>=)
	 * @return UpdateDataQuery
	 */
	public function WhereExpression($fieldName, $expression, $operator = '=')
	{
		return $this->WhereInternal('expression', $fieldName, $expression, $operator);
	}
	
	/**
	 * Adds a new WHERE IS NULL clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @return UpdateDataQuery
	 */
	public function WhereNull($fieldName)
	{
		return $this->WhereInternal('value', $fieldName, NULL, '=');
	}

	/**
	 * Adds a new WHERE IS NOT NULL clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @return UpdateDataQuery
	 */
	public function WhereNotNull($fieldName)
	{
		return $this->WhereInternal('value', $fieldName, NULL, '!=');
	}

	/**
	 * Adds a new WHERE IN clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @param array $values Values that the database field can have
	 * @return UpdateDataQuery
	 */
	public function WhereIn($fieldName, $values)
	{
		return $this->WhereInternal('in', $fieldName, $values, '=');
	}

	/**
	 * Adds a new WHERE NOT IN clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @param array $values Values that the database field cannot have
	 * @return UpdateDataQuery
	 */
	public function WhereNotIn($fieldName, $values)
	{
		return $this->WhereInternal('in', $fieldName, $values, '!=');
	}

	/**
	 * Adds a new WHERE LIKE clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @param mixed $value Like filter of the field
	 * @return UpdateDataQuery
	 */
	public function WhereLike($fieldName, $value)
	{
		return $this->WhereInternal('like', $fieldName, $value, '=');
	}
	
	/**
	 * Adds a new WHERE LIKE clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @param mixed $value Like filter of the field
	 * @return UpdateDataQuery
	 */
	public function WhereNotLike($fieldName, $value)
	{
		return $this->WhereInternal('like', $fieldName, $value, '!=');
	}

	/**
	 * Adds a new WHERE EXISTS clause to the query object.
	 * 
	 * @param string $expression EXISTS clause
	 * @return UpdateDataQuery
	 */
	public function WhereExists($expression)
	{
		return $this->WhereInternal('exists', '', $expression, '=');
	}

	/**
	 * Adds a new WHERE NOT EXISTS clause to the query object.
	 *
	 * @param string $expression NOT EXISTS clause
	 * @return UpdateDataQuery
	 */
	public function WhereNotExists($expression)
	{
		return $this->WhereInternal('exists', '', $expression, '!=');
	}
	
	/**
	 * Registers a SET clause internally.
	 * 
	 * @param mixed $fieldName Target field of the where clause
	 * @param mixed $value Value associated with the clause
	 * @param bool $isExpression Optional flag to specify that the set value is a custom expression
	 * @return UpdateDataQuery
	 */
	private function SetInternal($fieldName, $value = NULL, $isExpression = false)
	{
		if ($this->queryType != 'INSERT' &&
			$this->queryType != 'UPDATE') {
			throw new DomainException('Set clause is only supported in insert and update queries.');
		}

		$this->fieldSet[] = new SetValueOperation($fieldName, $value, $isExpression);

		// allow method chaining
		return $this;
	}

	/**
	 * Adds a new simple set clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @param mixed $value Value of the field
	 * @return UpdateDataQuery
	 */
	public function Set($fieldName, $value)
	{
		return $this->SetInternal($fieldName, $value);
	}

	/**
	 * Adds multiple set clauses to the query object with an associative array of values
	 * @param array|Traversable $keyValueList Associative array of values to add as a set value
	 * @return UpdateDataQuery
	 */
	public function SetMulti($keyValueList) {
		if (!is_array($keyValueList) && !($keyValueList instanceof Traversable)) {
			throw new InvalidArgumentException("keyValueList must be an array or traversable");
		}
		foreach ($keyValueList as $fieldName => $value) {
			$this->SetInternal($fieldName, $value);
		}
		return $this;
	}

	/**
	 * Adds a new simple set clause to the query object with the primary key field.
	 * 
	 * @param string $fieldName Name of the database primary key field
	 * @param mixed $value Value of the field
	 * @return UpdateDataQuery
	 */
	public function SetKey($fieldName, $value)
	{
		$this->primaryKeyValue = $value;
		return $this->SetInternal($fieldName, $value);
	}

	/**
	 * Adds a new set clause with a custom expression to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @param mixed $expression Custom expression that updates the field
	 * @return UpdateDataQuery
	 */
	public function SetExpression($fieldName, $expression)
	{
		return $this->SetInternal($fieldName, $expression, true);
	}
	
	/**
	 * Adds a new SET NULL clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @return UpdateDataQuery
	 */
	public function SetNull($fieldName)
	{
		return $this->SetInternal($fieldName, NULL);
	}

	/**
	 * Generates the SQL for the insert query.
	 *
	 * @param IDataSource $dataSource The target data source for the operation
	 * @return string
	 */
	public function InsertSql(IDataSource $dataSource)
	{
		$fields = array();
		$values = array();
		
		foreach ($this->fieldSet as $setOpt) {
			$fields[] = $dataSource->EscapeIdentifier($setOpt->field);
			$values[] = $setOpt->__getValueForDB($dataSource);
		}
		$opPrefix = "INSERT";
		$sql = sprintf("%s INTO %s(%s) VALUES(%s)",
			$opPrefix, $dataSource->EscapeIdentifier($this->tableName),
			implode(",", $fields), 
			implode(",", $values));

		return $sql;
	}

	/**
	 * Returns the WHERE clause built from a list of WHERE specifications.
	 * 
	 * @param IDataSource $dataSource The target data source for the operation 
	 * @param mixed $fieldWhere List of WHERE specifications
	 * @param bool $escapeIdentifiers Flag that specifies to escape the filtering field names
	 * @return string
	 */
	public static function GetGenericWhereClause(
		IDataSource $dataSource, $fieldWhere, 
		$escapeIdentifiers = true)
	{
		$arrWhere = array();
		
		foreach ($fieldWhere as $whereOpt) {
			$type = $whereOpt['type'];
			$field = $whereOpt['field'];
			if ($escapeIdentifiers) {
				$field = $dataSource->EscapeIdentifier($field);
			}
			$value = $whereOpt['value'];
			$operator = $whereOpt['operator'];
			$operatorConverted = $dataSource->GetOperator($operator);
			
			if ($type == 'value') {
				if (is_null($value)) {
					// handle null values
					if ($operator == '=') {
						$arrWhere[] = sprintf("%s IS NULL", $field);
					} else {
						$arrWhere[] = sprintf("%s IS NOT NULL", $field);
					}
				} else {
					$arrWhere[] = sprintf("%s%s%s", $field, $operatorConverted, 
						$dataSource->EscapeValue($value));
				}

			} elseif ($type == 'expression') {
				$arrWhere[] = sprintf("%s%s%s", $field, $operatorConverted, $value);
			
			} elseif ($type == 'in') {
				// escape each value in the array
				$newValues = array();
				foreach ($value as $inItem) {
					$newValues[] = $dataSource->EscapeValue($inItem);
				}
				if (count($newValues) > 0) {
					$newValuesConcat = implode(",", $newValues);
					if ($operator == '=') {
						$arrWhere[] = sprintf("%s IN (%s)", $field, $newValuesConcat);
					} else {
						$arrWhere[] = sprintf("%s NOT IN (%s)", $field, $newValuesConcat);
					}
				} else {
					if ($operator == '=') {
						// hard-code a FALSE clause
						$arrWhere[] = "0=1";
					} else {
						// hard-code a TRUE clause: DO NOTHING
					}
				}
			
			} elseif ($type == 'like') {
				$value = $dataSource->EscapeValue($value);
				if ($operator == '=') {
					$arrWhere[] = sprintf("%s LIKE %s", $field, $value);
				} else {
					$arrWhere[] = sprintf("%s NOT LIKE %s", $field, $value);
				}
			} elseif ($type == 'exists') {
				if ($operator == '=') {
					$arrWhere[] = sprintf("EXISTS (%s)", $value);
				} else {
					$arrWhere[] = sprintf("NOT EXISTS (%s)", $value);
				}
			} elseif ($type == 'custom') {
				$arrWhere[] = sprintf("(%s)", $value);
			}
		}
		
		if (count($arrWhere) > 0) {
			return implode(" AND ", $arrWhere);
		} else {
			return "";
		}
	}
	
	/**
	 * Returns the combined WHERE clause of an update or delete query.
	 * 
	 * @param IDataSource $dataSource The target data source for the operation 
	 * @return string
	 */
	private function GetWhereClause(IDataSource $dataSource)
	{
		return self::GetGenericWhereClause($dataSource, $this->fieldWhere);
	}

	/**
	 * Generates the SQL for the update query.
	 *
	 * @param IDataSource $dataSource The target data source for the operation
	 * @return string
	 */
	public function UpdateSql(IDataSource $dataSource)
	{
		$arrSet = array();
		foreach ($this->fieldSet as $setOpt) {
			$field = $dataSource->EscapeIdentifier($setOpt->field);
			$value = $setOpt->__getValueForDB($dataSource);
			$arrSet[] = sprintf("%s=%s", $field, $value);
		}

		$whereClause = $this->GetWhereClause($dataSource);
		
		$sql = sprintf("UPDATE %s SET %s", 
			$dataSource->EscapeIdentifier($this->tableName), 
			implode(",", $arrSet));
		if (!empty($whereClause)) {
			$sql .= " WHERE " . $whereClause;
		}

		return $sql;
	}

	/**
	 * Generates the SQL for the delete query.
	 *
	 * @param IDataSource $dataSource The target data source for the operation
	 * @return string
	 */
	public function DeleteSql(IDataSource $dataSource)
	{
		$whereClause = $this->GetWhereClause($dataSource);
		$sql = sprintf("DELETE FROM %s", 
			$dataSource->EscapeIdentifier($this->tableName));
		if (!empty($whereClause)) {
			$sql .= " WHERE " . $whereClause;
		}

		return $sql;
	}

	/**
	 * Returns the current SQL to execute against the data source
	 * @param IDataSource $dataSource
	 * @return string SQL to execute on the data source for this update query
	 * @throws DomainException Invalid query type
	 * @access internal
	 */
	public function _SqlByCurrentType(IDataSource $dataSource) {
		switch ($this->queryType) {
			case 'INSERT':
				$sql = $this->InsertSql($dataSource);
				break;
			case 'UPDATE':
				$sql = $this->UpdateSql($dataSource);
				break;
			case 'DELETE':
				$sql = $this->DeleteSql($dataSource);
				break;
			default:
				throw new DomainException('Invalid query type: ' . $this->queryType);
		}
		return $sql;
	}

	/**
	 * Executes the data mofication query (INSERT, UPDATE or DELETE) on a data source
	 * and returns the result.
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @return UpdateQueryResult
	 */
	public function ExecuteUpdate(IDataSource $dataSource)
	{
		return $dataSource->ExecuteUpdateQuery($this);
	}
}
