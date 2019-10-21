<?php
namespace Zynga\DB;

use stdClass;

/**
 * This class represents an advanced abstract SELECT query that 
 * can be managed by SQL parts.
 */
class SelectExtendedDataQuery implements ISelectQuery
{
	/**
	 * SQL SELECT definitions.
	 */
	private $select = array();

	/**
	 * SQL FROM and JOIN definitions.
	 */
	private $from = array();

	/**
	 * SQL WHERE filters.
	 */
	private $where = array();

	/**
	 * SQL GROUP BY definitions.
	 */
	private $groupBy = array();

	/**
	 * SQL ORDER BY definitions.
	 */
	private $orderBy = array();

	/**
	 * 0-based index of the first record to return.
	 */
	private $offset = -1;

	/**
	 * Maximum number of records to return.
	 */
	private $limit = -1;

	/**
	 * Flag to enable distinct on the select clause
	 * @var bool
	 */
	private $distinct = false;

	/**
	 * Creates a new instance of SelectExtendedDataQuery.
	 * 
	 * @param string $select Optional initial SQL SELECT specification.
	 * @param string $from Optional initial SQL FROM specification.
	 * @param string $where Optional initial SQL WHERE specification.
	 * @param string $groupBy Optional initial SQL GROUP BY specification.
	 * @param string $orderBy Optional initial SQL ORDER BY specification.
	 */
	public function __construct($select = "", $from = "", $where = "", $groupBy = "", $orderBy = "") {
		if (!empty($select)) {
			$this->select[] = $select;
		}

		if (!empty($from)) {
			$this->from[] = $from;
		}

		if (!empty($where)) {
			$this->where[] = $where;
		}

		if (!empty($groupBy)) {
			$this->groupBy[] = $groupBy;
		}

		if (!empty($orderBy)) {
			$this->orderBy[] = $orderBy;
		}
	}

	/**
	 * Creates and returns a new instance of SelectExtendedDataQuery.
	 * 
	 * @param string $select Optional initial SQL SELECT specification
	 * @param string $from Optional initial SQL FROM specification.
	 * 
	 * @return SelectExtendedDataQuery
	 */
	public static function Create($select = "", $from = "") {
		return new SelectExtendedDataQuery($select, $from);
	}

	/**
	 * Registers a new SQL SELECT specification.
	 * 
	 * @param mixed $select SQL SELECT specification
	 * @return SelectExtendedDataQuery
	 */
	public function Select($select) {
		$this->select[] = $select;

		// allow method chaining
		return $this;
	}

	/**
	 * Sets the distinct flag in the query
	 * @return SelectExtendedDataQuery
	 */
	public function Distinct() {
		$this->distinct = true;

		// allow method chaining
		return $this;
	}

	/**
	 * Registers a new SQL FROM specification.
	 * 
	 * @param mixed $from SQL FROM specification
	 * @return SelectExtendedDataQuery
	 */
	public function From($from) {
		$this->from[] = $from;

		// allow method chaining
		return $this;
	}

	/**
	 * Registers a new SQL JOIN specification to the FROM list.
	 * 
	 * @param string $tableName Table name and optional alias to join to the query
	 * @param string $joinOnFields The join condition
	 * @param string $joinType The type of join: 'LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'
	 * @return SelectExtendedDataQuery
	 */
	public function Join($tableName, $joinOnFields, $joinType = "INNER") {
		$joinTypeUpr = strtoupper(trim($joinType));
		if (!in_array($joinTypeUpr, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'))) {
			$joinTypeUpr = 'INNER';
		}
		$this->from[] = sprintf("%s JOIN %s ON %s", $joinTypeUpr, $tableName, $joinOnFields);

		// allow method chaining
		return $this;
	}

	/**
	 * Registers a WHERE clause internally.
	 * 
	 * @param mixed $type Type of the WHERE clause (value, expression, in, like, exists)
	 * @param mixed $fieldName Target field of the where clause
	 * @param mixed $value Value associated with the clause
	 * @param mixed $operator Operator that binds the field and the value (=,!=,<,<=,>,>=)
	 * @return SelectExtendedDataQuery
	 */
	public function WhereInternal($type, $fieldName, $value = NULL, $operator = '=') {
		$this->where[] = array('type' => $type, 'field' => $fieldName,
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
	 * @return SelectExtendedDataQuery
	 */
	public function Where($fieldName, $value, $operator = '=') {
		return $this->WhereInternal('value', $fieldName, $value, $operator);
	}

	/**
	 * Adds a new where clause with a custom expression to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @param mixed $expression Custom expression that must match with the field
	 * @param mixed $operator Operator that binds the field and the value (=,!=,<,<=,>,>=)
	 * @return SelectExtendedDataQuery
	 */
	public function WhereExpression($fieldName, $expression, $operator = '=') {
		return $this->WhereInternal('expression', $fieldName, $expression, $operator);
	}

	/**
	 * Adds a new custom where clause with a full SQL to the query object.
	 * @param string $whereClause
	 * @return SelectExtendedDataQuery
	 */
	public function WhereCustom($whereClause) {
		return $this->WhereInternal('custom', '', $whereClause, '=');
	}

	/**
	 * Adds a new WHERE IS NULL clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @return SelectExtendedDataQuery
	 */
	public function WhereNull($fieldName) {
		return $this->WhereInternal('value', $fieldName, NULL, '=');
	}

	/**
	 * Adds a new WHERE IS NOT NULL clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @return SelectExtendedDataQuery
	 */
	public function WhereNotNull($fieldName) {
		return $this->WhereInternal('value', $fieldName, NULL, '!=');
	}

	/**
	 * Adds a new WHERE IN clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @param array $values Values that the database field can have
	 * @return SelectExtendedDataQuery
	 */
	public function WhereIn($fieldName, $values) {
		return $this->WhereInternal('in', $fieldName, $values, '=');
	}

	/**
	 * Adds a new WHERE NOT IN clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @param array $values Values that the database field cannot have
	 * @return SelectExtendedDataQuery
	 */
	public function WhereNotIn($fieldName, $values) {
		return $this->WhereInternal('in', $fieldName, $values, '!=');
	}

	/**
	 * Adds a new WHERE LIKE clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @param string $value Value for LIKE check
	 * @return SelectExtendedDataQuery
	 */
	public function WhereLike($fieldName, $value) {
		return $this->WhereInternal('like', $fieldName, $value, '=');
	}

	/**
	 * Adds a new WHERE LIKE clause to the query object.
	 * 
	 * @param string $fieldName Name of the database field
	 * @param string $value Value for LIKE check
	 * @return SelectExtendedDataQuery
	 */
	public function WhereNotLike($fieldName, $value) {
		return $this->WhereInternal('like', $fieldName, $value, '!=');
	}

	/**
	 * Adds a new WHERE EXISTS clause to the query object.
	 * 
	 * @param string $expression EXISTS clause
	 * @return SelectExtendedDataQuery
	 */
	public function WhereExists($expression) {
		return $this->WhereInternal('exists', '', $expression, '=');
	}

	/**
	 * Adds a new WHERE NOT EXISTS clause to the query object.
	 * 
	 * @param string $expression NOT EXISTS clause
	 * @return SelectExtendedDataQuery
	 */
	public function WhereNotExists($expression) {
		return $this->WhereInternal('exists', '', $expression, '!=');
	}

	/**
	 * Registers a new SQL GROUP BY specification.
	 * 
	 * @param mixed $groupBy SQL GROUP BY specification
	 * @return SelectExtendedDataQuery
	 */
	public function GroupBy($groupBy) {
		$this->groupBy[] = $groupBy;

		// allow method chaining
		return $this;
	}

	/**
	 * Registers a new SQL ORDER BY specification.
	 * 
	 * @param mixed $orderBy SQL ORDER BY specification
	 * @return SelectExtendedDataQuery
	 */
	public function OrderBy($orderBy) {
		$this->orderBy[] = $orderBy;

		// allow method chaining
		return $this;
	}

	/**
	 * This method registers the desired limits on the select query.
	 * Set a field to -1 to disable it.
	 *
	 * @param integer $offset 0-based index of the first record to return.
	 * @param integer $limit Maximum number of records to return.
	 * @return SelectExtendedDataQuery
	 */
	public function SetLimit($offset, $limit = -1) {
		$this->offset = $offset;
		$this->limit = $limit;

		// allow method chaining
		return $this;
	}

	/**
	 * Returns the current limit setting
	 * @return int
	 */
	public function GetLimit() {
		return $this->limit;
	}

	/**
	 * Converts the select query to a primitive select query to be run on a target data source.
	 * 
	 * @param IDataSource $dataSource The target data source for the operation 
	 * @return SelectDataQuery
	 */
	public function GetSelectDataQuery(IDataSource $dataSource) {
		$arrSql = array();
		$arrSql[] = "SELECT ";
		if ($this->distinct) {
			$arrSql[] = "DISTINCT ";
		}
		$arrSql[] = implode(", ", $this->select);
		$arrSql[] = " FROM ";
		$arrSql[] = implode(" ", $this->from);
		if (count($this->where) > 0) {
			$arrSql[] = " WHERE ";
			$arrSql[] = UpdateDataQuery::GetGenericWhereClause(
							$dataSource, $this->where, FALSE);
		}
		if (count($this->groupBy) > 0) {
			$arrSql[] = " GROUP BY ";
			$arrSql[] = implode(", ", $this->groupBy);
		}
		if (count($this->orderBy) > 0) {
			$arrSql[] = " ORDER BY ";
			$arrSql[] = implode(", ", $this->orderBy);
		}

		// join the SQL parts and return the result as a SelectDataQuery object.
		$sql = implode("", $arrSql);
		return SelectDataQuery::Create($sql, array(), $this->offset, $this->limit);
	}

// <editor-fold defaultstate="collapsed" desc="Methods to return plain data">
	/**
	 * Executes a select query and returns the resulting recordset.
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @return array
	 */
	public function GetRecordset(IDataSource $dataSource) {
		$simpleQuery = $this->GetSelectDataQuery($dataSource);
		return $dataSource->GetRecordset($simpleQuery);
	}

	/**
	 * Executes a select query and returns the resulting record.
	 * Returns FALSE if the result is empty.
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @return array
	 */
	public function GetRecord(IDataSource $dataSource) {
		$simpleQuery = $this->GetSelectDataQuery($dataSource);
		return $dataSource->GetRecord($simpleQuery);
	}

	/**
	 * Executes a select query and returns the resulting single result.
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @param mixed $defaultValue Default value to return, in case the result is empty.
	 * @return mixed
	 */
	public function GetResult(IDataSource $dataSource, $defaultValue = NULL) {
		$simpleQuery = $this->GetSelectDataQuery($dataSource);
		return $dataSource->GetResult($simpleQuery, $defaultValue);
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
	public function GetObject(IDataSource $dataSource, $objectType = stdClass::class) {
		$simpleQuery = $this->GetSelectDataQuery($dataSource);
		return $simpleQuery->GetObject($dataSource, $objectType);
	}

	/**
	 * Returns an object set with a constructor taking a database record as parameter
	 * 
	 * @param IDataSource $dataSource The data source object 
	 * @param string $objectType Optional type of the objects to return. Default is stdClass.
	 * @return mixed
	 */
	public function GetObjectSet(IDataSource $dataSource, $objectType = stdClass::class) {
		$simpleQuery = $this->GetSelectDataQuery($dataSource);
		return $simpleQuery->GetObjectSet($dataSource, $objectType);
	}
// </editor-fold>
}
