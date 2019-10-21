<?php
namespace Zynga\Model;

/**
 * Interface to encapsulate a class that can restore its values from a JSON encoded data
 * @package CBGApi\Data
 */
interface ReadableFromJSON
{
	/**
	 * Reads the values from a json_decode'd array
	 * @param array $values Array of JSON decoded values
	 * @return void
	 */
	function readFromJSON(&$values): void;
}
