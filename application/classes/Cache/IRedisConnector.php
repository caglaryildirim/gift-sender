<?php
namespace Zynga\Cache;

/**
 * Extends IGenericCacheConnector with methods specific to REDIS servers
 */
interface IRedisConnector extends IGenericCacheConnector
{
	/**
	 * Returns the value associated with field in the hash stored at key.
	 * @param string $hash
	 * @param string $key
	 * @param bool $disableHashPrefix Optional parameter to disable adding the key prefix to the hash
	 * @return mixed
	 */
	function HGet($hash, $key, $disableHashPrefix);

	/**
	 * Sets field in the hash stored at key to value.
	 * - If key does not exist, a new key holding a hash is created.
	 * - If field already exists in the hash, it is overwritten.
	 * @param string $hash
	 * @param string $key
	 * @param mixed $value
	 * @param bool $disableHashPrefix Optional parameter to disable adding the key prefix to the hash
	 * @return bool
	 */
	function HSet($hash, $key, $value, $disableHashPrefix);

	/**
	 * Returns the values associated with the specified fields in the hash stored at key.
	 * @param string $hash
	 * @param array $keys
	 * @param bool $disableHashPrefix Optional parameter to disable adding the key prefix to the hash
	 * @return array
	 */
	function HMGet($hash, $keys, $disableHashPrefix);

	/**
	 * Sets the specified fields to their respective values in the hash stored at key.
	 * - This command overwrites any specified fields already existing in the hash. If key does not exist, a new key holding a hash is created.
	 * @param string $hash
	 * @param array $keyValuePairs
	 * @param bool $disableHashPrefix Optional parameter to disable adding the key prefix to the hash
	 * @return bool
	 */
	function HMSet($hash, $keyValuePairs, $disableHashPrefix);

	/**
	 * Returns all fields and values of the hash stored at key.
	 * - In the returned value, every field name is followed by its value, so the length of the reply is twice the size of the hash.
	 * @param string $hash
	 * @param bool $disableHashPrefix Optional parameter to disable adding the key prefix to the hash
	 * @return array List of fields and their values stored in the hash, or an empty list when key does not exist.
	 */
	function HGetAll($hash, $disableHashPrefix);
}