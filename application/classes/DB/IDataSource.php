<?php
namespace Zynga\DB;

/**
 * This interface defines the methods that abstract 
 * the database provider specific functionality.
 */
interface IDataSource 
{
	/**
	 * Escapes an identifier like a column name for using in SQL queries.
	 * 
	 * @param mixed $identifierName Name of the identifiers.
	 * @return string
	 */
	function EscapeIdentifier($identifierName);
	
	/**
	 * Escapes a value for using in SQL queries.
	 * 
	 * @param mixed $value Value to be escaped
	 * @return string
	 */
	function EscapeValue($value);
	
	/**
	 * Converts a binary operator to the one that matches the data source provider.
	 * 
	 * @param string $operator Native operator value (=,!=,<,<=,>,>=)
	 * @return string
	 */
	function GetOperator($operator);
	
	/**
	 * Returns the last inserted auto-increment value.
	 */
	function GetIdentity();

	/**
	 * Executes a select query and returns the query object.
	 * 
	 * @param SelectDataQuery $query Select query
	 */
	function SelectQuery(SelectDataQuery $query);

	/**
	 * Executes a select query and returns the resulting recordset.
	 * 
	 * @param SelectDataQuery $query Select query
	 * @return array
	 */
	function GetRecordset(SelectDataQuery $query);
	
	/**
	 * Executes a select query and returns the resulting record.
	 * 
	 * @param SelectDataQuery $query Select query
	 * @return array
	 */
	function GetRecord(SelectDataQuery $query);
	
	/**
	 * Executes a select query and returns the resulting single result.
	 * 
	 * @param SelectDataQuery $query Select query
	 * @param mixed $defaultValue Default value to return, in case the result is empty.
	 */
	function GetResult(SelectDataQuery $query, $defaultValue = NULL);

	/**
	 * Executes an SQL query and returns the query result.
	 *
	 * @param string $sql SQL query to execute
	 * @param mixed ...$parameters Additional SQL bind parameters
	 * @return mixed
	 */
	function ExecuteQuery(string $sql, ...$parameters);
	
	/**
	 * Executes a data mofication query (INSERT, UPDATE or DELETE) and returns the result.
	 * 
	 * @param UpdateDataQuery $query Data modication query
	 * @return UpdateQueryResult
	 */
	function ExecuteUpdateQuery(UpdateDataQuery $query);
}
