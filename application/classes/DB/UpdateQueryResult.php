<?php

namespace Zynga\DB;

/**
 * Represents the result of an insert, update or delete query.
 */
class UpdateQueryResult
{
	/**
	 * Success status of the operation.
	 * @var bool
	 */
	public $success;

	/**
	 * Error message if the operation failed.
	 * @var string
	 */
	public $errorMessage;

	/**
	 * Number of rows modified in the operation.
	 * @var int
	 */
	public $rowsModified = 0;

	/**
	 * Inserted auto-increment value for insert requests. 
	 * @var int
	 */
	public $insertedID = 0;

	/**
	 * Creates a new instance of UpdateQueryResult.
	 * 
	 * @param mixed $success Success status of the operation
	 * @param string $message Detail message of the operation
	 */
	public function __construct($success, $message = "")
	{
		$this->success = ($success) ? 1 : 0;
		$this->errorMessage = $message;
	}

	/**
	 * Creates a new instance of UpdateQueryResult for success.
	 *
	 * @param string $message Detail message of the operation
	 * @return UpdateQueryResult
	 */
	public static function CreateSuccess($message = "")
	{		
		return new UpdateQueryResult(true, $message);
	}

	/**
	 * Creates a new instance of UpdateQueryResult for error.
	 *
	 * @param string $message Detail message of the operation
	 * @return UpdateQueryResult
	 */
	public static function CreateError($message = "")
	{
		return new UpdateQueryResult(false, $message);
	}
}
