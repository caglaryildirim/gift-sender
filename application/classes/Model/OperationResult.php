<?php

namespace Zynga\Model;

use Zynga\DB\UpdateQueryResult;

/**
 * Represents the result of an operation.
 */
class OperationResult
{

// <editor-fold defaultstate="collapsed" desc="Properties">
	/**
	 * Operation success status
	 * @var bool
	 */
	public $Success;

	/**
	 * Optional status message
	 * @var string
	 */
	public $Message;

	/**
	 * Optional return data for the operation
	 * @var mixed
	 */
	public $ReturnData = null;

// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="Constructors">
	private function __construct($success, $message, $returnData = null) {
		$this->Success = $success;
		$this->Message = $message;
		$this->ReturnData = $returnData;
	}

	private static function GetFormattedMessage($args) {
		$messageFormat = "";

		$argCount = count($args);
		if ($argCount > 0) {
			// the first parameter must be the format
			$messageFormat = array_shift($args);
			if ($argCount > 1) {
				return vsprintf($messageFormat, $args);
			}
		}

		return $messageFormat;
	}

	/**
	 * Returns a successful operation result.
	 * @param string $message Optional response message
	 * @param mixed $returnData Optional return data for the operation
	 * @return OperationResult
	 */
	public static function Success($message = "", $returnData = null) {
		return new self(true, $message, $returnData);
	}

	/**
	 * Returns a successful operation result with formatted return message.
	 * @param string $messageFormat Format of the return message
	 * @param mixed Any number of additional bind parameters
	 * @return OperationResult
	 */
	public static function SuccessFormatted() {
		$args = func_get_args();
		$message = self::GetFormattedMessage($args);
		return new self(true, $message);
	}

	/**
	 * Returns a failed operation result.
	 * @param string $message Optional response message
	 * @param mixed $returnData Optional return data for the operation
	 * @return OperationResult
	 */
	public static function Failed($message = "", $returnData = null) {
		return new self(false, $message, $returnData);
	}

	/**
	 * Returns a failed operation result with formatted return message.
	 * @param string $messageFormat Format of the return message
	 * @param mixed any number of additional bind parameters
	 * @return OperationResult
	 */
	public static function FailedFormatted() {
		$args = func_get_args();
		$message = self::GetFormattedMessage($args);
		return new self(false, $message);
	}

	/**
	 * Converts a database update query result to an OperationResult
	 * @param UpdateQueryResult $result Database qupdate query result
	 * @return OperationResult
	 */
	public static function FromUpdateQueryResult(UpdateQueryResult $result) {
		return ($result->success) ?
			self::Success($result->errorMessage) :
			self::Failed($result->errorMessage);
	}
// </editor-fold>
}
