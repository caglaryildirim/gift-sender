<?php

namespace Zynga\DB;

use mysqli;
use mysqli_result;
use Exception;
use stdClass;

/**
 * Implements IDataSource using mysqli extension
 */
class MySQLDataSource implements IDataSource
{
// <editor-fold defaultstate="collapsed" desc="Fields">
	/**
	 * Database configuration information.
	 * This must be a DB configuration array.
	 */
	protected $dbConfig;

	/**
	 * Database connection object.
	 * @var mysqli
	 */
	private $db = null;

	/**
	 * internal flag to check whether the function load database is called in the stack trace
	 * @var bool
	 */
	private $loadDatabaseCalled = false;
// </editor-fold>

	/**
	 * Constructs the data source object.
	 * The actual database object is created at the first database access.
	 *
	 * @param mixed $dbConfig This parameter can be a DB configuration name or an array of DB configuration options.
	 */
	public function __construct($dbConfig = null) {
		$this->dbConfig = $dbConfig;
	}

	/**
	 * Destructor
	 */
	public function __destruct() {
		$this->Close();
	}

// <editor-fold defaultstate="collapsed" desc="Connection creator helpers">

	/**
	 * Returns the database options to instantiate a GenericDataSource object.
	 * - PLEASE DON'T USE THIS METHOD EXCEPT CBGMVC and CBGModules CLASSES!!!
	 * @param string $dbHost Database host address
	 * @param string $dbName Database name
	 * @param string $dbUser Database username
	 * @param string $dbPass Database password
	 * @param int $dbPort Optional database connection port
	 * @param string $charset Database connection charset
	 * @param string $collation Database collation
	 * @return array
	 */
	private static function __GetDatabaseOptions($dbHost, $dbName, $dbUser, $dbPass, $dbPort = null, $charset = "utf8", $collation = "utf8_turkish_ci") {
		$db = array();
		$db['hostname'] = $dbHost;
		$db['username'] = $dbUser;
		$db['password'] = $dbPass;
		$db['database'] = $dbName;
		$db['char_set'] = $charset;
		$db['dbcollat'] = $collation;
		if (!empty($dbPort)) {
			$db['port'] = $dbPort;
		}
		return $db;
	}

	/**
	 * Creates and returns a GenericDataSource object based on the connection options
	 *
	 * @param string $dbHost Database host address
	 * @param string $dbName Database name
	 * @param string $dbUser Database username
	 * @param string $dbPass Database password
	 * @param int $dbPort Optional database connection port
	 * @param string $charset Database connection charset
	 * @param string $collation Database collation
	 * @return MySQLDataSource
	 */
	public static function CreateDataSource($dbHost, $dbName, $dbUser, $dbPass, $dbPort = null, $charset = "utf8", $collation = "utf8_turkish_ci") {
		$dbOptions = self::__GetDatabaseOptions($dbHost, $dbName, $dbUser, $dbPass, $dbPort, $charset, $collation);
		return new self($dbOptions);
	}
// </editor-fold>

	/**
	 * This magic method is execute before serialization of the object.
	 *
	 * @return array
	 */
	public function __sleep() {
		// we just need to store the database configuration
		return array('dbConfig');
	}

	private function GetConnectionDebugInfoReduced() {
		if (is_array($this->dbConfig) && isset($this->dbConfig["hostname"])) {
			return sprintf("server:%s", $this->dbConfig["hostname"]);
		} else {
			return "";
		}
	}

	private function GetConnectionDebugInfo() {
		if (is_array($this->dbConfig) &&
			isset($this->dbConfig["username"]) &&
			isset($this->dbConfig["hostname"]) &&
			isset($this->dbConfig["database"])) {
			return sprintf("%s@%s:%s", $this->dbConfig["username"], $this->dbConfig["hostname"], $this->dbConfig["database"]);
		} else {
			return "";
		}
	}

	/**
	 * Returns an exception for database connection load operation
	 * @param string $errorMessage Base error message
	 * @param Exception $innerException Optional previous exception
	 * @param bool $isFatal Optional fatal error status
	 * @return DBOperationException
	 */
	private function GetLoadExceptionFromMessage($errorMessage, $innerException = null, $isFatal = true) {
		$errorMessageWithDetails = $errorMessage . $this->GetConnectionDebugInfoReduced();
		$ex = new DBOperationException($errorMessageWithDetails, 0, $innerException, $isFatal);
		$ex->setAdditionalData(array("connectionInfo" => $this->GetConnectionDebugInfo()));
		return $ex;
	}

	/**
	 * This function creates the database object and the database manager object.
	 *
	 * @param bool $throwOnRecursion Flag to check for recursive calls and throw an exception
	 * @return mysqli The database object
	 * @throws DBOperationException
	 */
	private function LoadDatabase($throwOnRecursion = true) {
		if ($this->loadDatabaseCalled) {
			if ($throwOnRecursion && !is_object($this->db)) {
				throw $this->GetLoadExceptionFromMessage("Recursive failed LoadDatabase call!");
			}
			return $this->db;
		}
		$this->loadDatabaseCalled = true;

		if (is_null($this->db)) {
			try {
				$dbConf = $this->dbConfig;
				if (isset($dbConf["port"])) {
					$this->db = new mysqli($dbConf["hostname"], $dbConf["username"], $dbConf["password"], $dbConf["database"], $dbConf["port"]);
				} else {
					$this->db = new mysqli($dbConf["hostname"], $dbConf["username"], $dbConf["password"], $dbConf["database"]);
				}
				if (!is_object($this->db)) {
					throw $this->GetLoadExceptionFromMessage("Invalid DB connection object!");
				} elseif ($this->db->connect_errno != 0) {
					$errorMessage = $this->db->connect_error;
					$this->db = null;
					throw $this->GetLoadExceptionFromMessage($errorMessage);
				}
				$this->db->set_charset($dbConf["char_set"]);
				$this->db->query("SET collation_connection = " . $dbConf["dbcollat"]);

			} catch (DBOperationException $ex) {
				// If the exception is of type NonReportableException, forward it directly
				throw $ex;
			} catch (Exception $ex) {
				throw $this->GetLoadExceptionFromMessage("DB Connection failed!", $ex);
			}
		}
		return $this->db;
	}

	/**
	 * Tries actually connecting to the database
	 * @return bool
	 */
	public function TryConnect() {
		try {
			$dbLoaded = $this->LoadDatabase(false);
			$connectSuccess = !is_null($dbLoaded);
		} catch (Exception $ex) {
			$connectSuccess = false;
		}
		return $connectSuccess;
	}

	/**
	 * Tries pinging a connection if the connection did not fail before
	 * @return bool
	 */
	public function PingConnection() {
		try {
			$dbLoaded = $this->LoadDatabase(false);
			$connectSuccess = !is_null($dbLoaded) && $dbLoaded->ping();
		} catch (Exception $ex) {
			$connectSuccess = false;
		}
		return $connectSuccess;
	}

	/**
	 * Closes the active DB connection
	 * @return bool TRUE on success, FALSE on failure
	 */
	public function Close() {
		if ($this->loadDatabaseCalled && $this->db instanceof mysqli) {
			$success = $this->db->close();
			if ($success) {
				$this->db = null;
				$this->loadDatabaseCalled = false;
			}
		} else {
			// if there is no active connection, succeed
			$success = true;
		}
		return $success;
	}

	/**
	 * Escapes an identifier like a column name for using in SQL queries.
	 *
	 * @param mixed $identifierName Name of the identifiers.
	 * @return string
	 */
	public function EscapeIdentifier($identifierName) {
		$_escape_char = '`';
		if (substr($identifierName, 0, 1) == $_escape_char) {
			return $identifierName;
		}
		return $_escape_char . $identifierName . $_escape_char;
	}

	/**
	 * Escapes a value for using in SQL queries.
	 *
	 * @param mixed $value Value to be escaped
	 * @return string
	 * @throws DBOperationException
	 */
	public function EscapeValue($value) {
		$dbLoaded = $this->LoadDatabase();

		if (is_null($value)) {
			return 'NULL';
		} elseif (is_string($value)) {
			$escapedValue = "'" . $dbLoaded->real_escape_string($value) . "'";
		} elseif (is_bool($value)) {
			$escapedValue = ($value === FALSE) ? "0" : "1";
		} else {
			$escapedValue = (string)$value;
		}
		return $escapedValue;
	}

	/**
	 * Converts a binary operator to the one that matches the data source provider.
	 *
	 * @param string $operator Native operator value (=,!=,<,<=,>,>=)
	 * @return string
	 * @throws DBOperationException
	 */
	public function GetOperator($operator) {
		$this->LoadDatabase();
		return $operator;
	}

	/**
	 * Returns the last inserted auto-increment value.
	 * @throws DBOperationException
	 */
	public function GetIdentity() {
		$this->LoadDatabase();
		return $this->db->insert_id;
	}

	/**
	 * Returns the last database error message.
	 */
	public function GetErrorMessage() {
		return $this->db->error;
	}

	/**
	 * Compile Bindings.
	 *
	 * @param string $sql the sql statement
	 * @param array $binds an array of bind data
	 * @return string
	 * @throws DBOperationException
	 */
	private function CompileBinds($sql, $binds) {
		$bind_marker = '?';

		if (strpos($sql, $bind_marker) === FALSE || empty($binds)) {
			return $sql;
		}

		// Get the sql segments around the bind markers
		$segments = explode($bind_marker, $sql);

		// The count of bind should be 1 less then the count of segments
		// If there are more bind arguments trim it down
		if (count($binds) >= count($segments)) {
			$binds = array_slice($binds, 0, count($segments) - 1);
		}

		// Construct the binded query
		$result = array($segments[0]);
		$i = 0;
		foreach ($binds as $bind) {
			$result[] = $this->EscapeValue($bind);
			$result[] = $segments[++$i];
		}

		return implode("", $result);
	}

	/**
	 * Creates and returns the exception for a failed select query
	 * @param string $selectSql Select SQL that failed on the server
	 * @return DBOperationException
	 */
	private function GetSelectSqlException($selectSql) {
		$errorMessage = $this->GetErrorMessage();
		if (mb_strpos($errorMessage, "server has gone away") !== FALSE ||
			strcasecmp($errorMessage, "WSREP has not yet prepared node for application use") == 0 ||
			strcasecmp($errorMessage, "Lost connection to MySQL server during query") == 0) {
			return $this->GetLoadExceptionFromMessage($errorMessage . "-");
		} else {
			return new DBOperationException("Error executing select query: " . $selectSql . "-" . $errorMessage);
		}
	}

	/**
	 * Executes an SQL select query and returns the query object.
	 *
	 * @param mixed $sql SQL command with optional parameter placeholders
	 * @param mixed $parameters Optional SQL command parameter values
	 * @return mysqli_result
	 * @throws DBOperationException
	 */
	private function SqlSelectQuery($sql, $parameters) {
		$this->LoadDatabase();

		//*** SELECT QUERY
		$sqlCompiled = $this->CompileBinds($sql, $parameters);
		try {
			$query = $this->db->query($sqlCompiled);
		} catch (Exception $ex) {
			// Normally query method does not throw an exception or error.
			// If an unhandled error occurs while executing the query, we throw a fatal exception
			throw $this->GetLoadExceptionFromMessage($ex->getMessage() . "-");
		}
		if ($query === FALSE) {
			throw $this->GetSelectSqlException($sqlCompiled);
		}
		return $query;
	}

	/**
	 * Executes a select query and returns the query object.
	 *
	 * @param SelectDataQuery $selectQuery Select query
	 * @return mysqli_result
	 * @throws DBOperationException
	 */
	public function SelectQuery(SelectDataQuery $selectQuery) {
		$sql = $selectQuery->commandSql;
		if ($selectQuery->limit >= 0) {
			$sql .= " LIMIT " . $selectQuery->limit;
		}
		if ($selectQuery->offset >= 0) {
			$sql .= " OFFSET " . $selectQuery->offset;
		}

		return $this->SqlSelectQuery($sql, $selectQuery->parameters);
	}

	/**
	 * Executes a simple SQL select query and returns the resulting recordset.
	 *
	 * @param mixed $commandText SQL command
	 * @param mixed $commandParameters An array of parameter values used to execute the command
	 * @return array
	 * @throws DBOperationException
	 */
	public function GetSqlRecordset($commandText, array $commandParameters = array()) {
		$query = $this->SqlSelectQuery($commandText, $commandParameters);
		$rows = array();
		if ($query instanceof mysqli_result) {
			while ($row = $query->fetch_assoc()) {
				$rows[] = $row;
			}
			$query->free_result();
		}
		return $rows;
	}

	/**
	 * Executes a simple SQL select query and returns the resulting objectset.
	 *
	 * @param mixed $commandText SQL command
	 * @param mixed $commandParameters An array of parameter values used to execute the command
	 * @return array
	 * @throws DBOperationException
	 */
	public function GetSqlObjectset($commandText, array $commandParameters = array()) {
		$query = $this->SqlSelectQuery($commandText, $commandParameters);
		$rows = array();
		if ($query instanceof mysqli_result) {
			while ($row = $query->fetch_object()) {
				$rows[] = $row;
			}
			$query->free_result();
		}
		return $rows;
	}

	/**
	 * Executes a simple SQL select query and returns the resulting record.
	 * - Returns FALSE if the result is empty.
	 *
	 * @param mixed $commandText SQL command
	 * @param mixed $commandParameters An array of parameter values used to execute the command
	 * @return array
	 * @throws DBOperationException
	 */
	public function GetSqlRecord($commandText, array $commandParameters = array()) {
		$query = $this->SqlSelectQuery($commandText, $commandParameters);
		$row = FALSE;
		if ($query instanceof mysqli_result) {
			if ($query->num_rows > 0) {
				$row = $query->fetch_assoc();
			}
			$query->free_result();
		}
		return $row;
	}

	/**
	 * Executes a simple SQL select query and returns the resulting object.
	 * - Returns FALSE if the result is empty.
	 *
	 * @param mixed $commandText SQL command
	 * @param mixed $commandParameters An array of parameter values used to execute the command
	 * @return stdClass|null
	 * @throws DBOperationException
	 */
	public function GetSqlObject($commandText, array $commandParameters = array()) {
		$query = $this->SqlSelectQuery($commandText, $commandParameters);
		$row = null;
		if ($query instanceof mysqli_result) {
			if ($query->num_rows > 0) {
				$row = $query->fetch_object();
			}
			$query->free_result();
		}
		return $row;
	}

	/**
	 * Executes a select query and returns the resulting recordset.
	 *
	 * @param SelectDataQuery $selectQuery Select query
	 * @return array
	 * @throws DBOperationException
	 */
	public function GetRecordset(SelectDataQuery $selectQuery) {
		$query = $this->SelectQuery($selectQuery);
		$rows = array();
		if ($query instanceof mysqli_result) {
			while ($row = $query->fetch_assoc()) {
				$rows[] = $row;
			}
			$query->free_result();
		}
		return $rows;
	}

	/**
	 * Executes a select query and returns the resulting record.
	 * - Returns FALSE if the result is empty.
	 *
	 * @param SelectDataQuery $selectQuery Select query
	 * @return array|null
	 * @throws DBOperationException
	 */
	public function GetRecord(SelectDataQuery $selectQuery) {
		$query = $this->SelectQuery($selectQuery);
		$row = null;
		if ($query instanceof mysqli_result) {
			if ($query->num_rows > 0) {
				$row = $query->fetch_assoc();
			}
			$query->free_result();
		}
		return $row;
	}

	/**
	 * Executes a simple SQL select query and returns the single result.
	 *
	 * @param string $commandText SQL command
	 * @param mixed $commandParameters An array of parameter values used to execute the command
	 * @param mixed $defaultValue Default value to return, in case the result is empty
	 * @return mixed
	 * @throws DBOperationException
	 */
	public function GetSqlResult($commandText, array $commandParameters = array(), $defaultValue = NULL) {
		$query = $this->SqlSelectQuery($commandText, $commandParameters);

		$value = $defaultValue;
		if ($query instanceof mysqli_result && $query->num_rows > 0 && $query->field_count > 0) {
			$row = $query->fetch_array(MYSQLI_NUM);
			foreach ($row as $rowValue) {
				$value = $rowValue;
				break;
			}
			$query->free_result();
		}
		return $value;
	}

	/**
	 * Executes a select query and returns the resulting single result.
	 *
	 * @param SelectDataQuery $selectQuery Select query
	 * @param mixed $defaultValue Default value to return, in case the result is empty.
	 * @return mixed
	 * @throws DBOperationException
	 */
	public function GetResult(SelectDataQuery $selectQuery, $defaultValue = NULL) {
		$query = $this->SelectQuery($selectQuery);

		if (!($query instanceof mysqli_result) ||
			$query->num_rows == 0 ||
			$query->field_count == 0) {
			$value = $defaultValue;
		} else {
			$value = null;
			$row = $query->fetch_array(MYSQLI_NUM);
			foreach ($row as $rowValue) {
				$value = $rowValue;
				break;
			}
			$query->free_result();
		}
		return $value;
	}

	/**
	 * Executes an SQL query and returns the query result.
	 *
	 * @param string $sql SQL query to execute
	 * @param mixed ...$parameters Additional SQL bind parameters
	 * @return mixed
	 * @throws DBOperationException
	 */
	public function ExecuteQuery(string $sql, ...$parameters) {
		$dbLoaded = $this->LoadDatabase(false);
		if (is_null($dbLoaded)) {
			return false;
		}

		// if there are any binding query parameters, bind them here
		if (count($parameters) > 0) {
			$sql = $this->CompileBinds($sql, $parameters);
		}

		//*** UPDATE QUERY
		return $dbLoaded->query($sql);
	}

	/**
	 * Executes a data mofication query (INSERT, UPDATE or DELETE) and returns the result.
	 *
	 * @param UpdateDataQuery $updateQuery Data modication query
	 * @return UpdateQueryResult
	 * @throws DBOperationException
	 */
	public function ExecuteUpdateQuery(UpdateDataQuery $updateQuery) {
		$dbLoaded = $this->LoadDatabase(false);
		if (is_null($dbLoaded)) {
			return UpdateQueryResult::CreateError("Database connection error");
		}

		if ($updateQuery->queryType != 'INSERT' &&
			$updateQuery->queryType != 'UPDATE' &&
			$updateQuery->queryType != 'DELETE') {
			$result = UpdateQueryResult::CreateError("Unknown update operation type: " . $updateQuery->queryType);

		} elseif ($updateQuery->queryType == 'INSERT') {
			$sql = $updateQuery->InsertSql($this);
			//*** UPDATE QUERY
			$query = $dbLoaded->query($sql);
			$result = new UpdateQueryResult($query);
			if (!$result->success) {
				$result->errorMessage = $this->GetErrorMessage();
			} else {
				$result->insertedID = $this->GetIdentity();
			}

		} else {
			if ($updateQuery->queryType == 'DELETE') {
				$sql = $updateQuery->DeleteSql($this);
			} else {
				$sql = $updateQuery->UpdateSql($this);
			}

			//*** UPDATE QUERY
			$query = $dbLoaded->query($sql);
			$result = new UpdateQueryResult($query !== FALSE);
			if (!$result->success) {
				$result->errorMessage = $this->GetErrorMessage();
			} else {
				$result->rowsModified = $dbLoaded->affected_rows;
			}
		}

		return $result;
	}
}
