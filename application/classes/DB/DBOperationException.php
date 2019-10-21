<?php

namespace Zynga\DB;

use Exception;

/**
 * Represents an Exception that must not be reported to the global error reporting handler like Sentry
 */
class DBOperationException extends Exception
{
	/**
	 * Flag to specify a fatal exception, where the execution must not continue
	 * @var bool
	 */
	private $isFatal;

	/**
	 * Optional array of extra data attributes to be reported along with the error message
	 * @var array
	 */
	private $additionalData = array();

	/**
	 * Construct the exception
	 *
	 * @param string $message [optional] The Exception message to throw.
	 * @param int $code [optional] The Exception code.
	 * @param Exception $previous [optional] The previous exception used for the exception chaining.
	 * @param bool $isFatal [optional] The exception is fatal the execution must not continue
	 */
	public function __construct($message = "", $code = 0, Exception $previous = null, $isFatal = false) {
		parent::__construct($message, $code, $previous);

		$this->isFatal = $isFatal;
	}

	/**
	 * Sets the additional data attributes to be reported along with the error message
	 * @param array $additionalData Data attributes as an associative array
	 */
	public function setAdditionalData(array $additionalData) {
		$this->additionalData = $additionalData;
	}

	/**
	 * Returns whether the exception is fatal (the execution must not continue)
	 * @return bool
	 */
	public function isFatalException() {
		return $this->isFatal;
	}

	/**
	 * Returns optional additional report data as an associative array
	 * @return array
	 */
	public function getAdditionalData() {
		return $this->additionalData;
	}
}
