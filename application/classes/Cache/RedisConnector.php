<?php
namespace Zynga\Cache;

use Exception;
use Predis\Client as RedisClient;

/**
 * Redis connector library that can read/write to the master of a Redis replication server group.
 */
class RedisConnector extends BaseCacheConnector implements IRedisConnector
{
// <editor-fold defaultstate="collapsed" desc="Class variables">
	/**
	 * Connection parameters for one or multiple servers.
	 * @var array
	 */
	private $config;

	/**
	 * Options that specify certain behaviours for the client.
	 * @var array
	 */
	private $options;

	/**
	 * List of connection objects
	 * @var RedisClient
	 */
	private $connection = FALSE;

	/**
	 * Flag that is set if the connection method is invoked
	 * @var bool
	 */
	private $connected = false;

	/**
	 * Unix timestamp at construction time
	 * @var int
	 */
	private $constructTimestamp;
// </editor-fold>

	/**
	 * Public constructor
	 * @param array $config Connection parameters for one or multiple servers.
	 * @param string $options Options that specify certain behaviours for the client.
	 */
	public function __construct($config = array(), $options = null) {
		// call base constructor first
		parent::__construct();

		// set the default options
		if (!isset($config["ENABLED"])) {
			$config["ENABLED"] = true;
		}
		if (!isset($config["DATABASE"])) {
			$config["DATABASE"] = 0;
		}
		if (!isset($config["PREFIX"])) {
			$config["PREFIX"] = "";
		}

		$this->key_prefix = $config["PREFIX"];
		$this->constructTimestamp = time();

		$this->config = $config;
		$this->options = $options;
	}

	/**
	 * Destructor
	 */
	public function __destruct() {
		$this->Disconnect();
	}

	private function EnsureConnect() {
		if (!$this->config["ENABLED"]) {
			return false;
		}
		if ($this->connected) {
			return true;
		}

		// iterate through hosts to find out the current master
		$connected = FALSE;
		$redisOptions = $this->options;
		if (!is_array($redisOptions)) {
			$redisOptions = array();
		}
		try {
			$redisParams = array(
				"host" => $this->config["SERVER"],
				"port" => $this->config["PORT"],
				"database" => $this->config["DATABASE"],
			);
			if (isset($this->config["TIMEOUT"])) {
				$redisParams["timeout"] = $this->config["TIMEOUT"];
			}
			$redisTemp = new RedisClient($redisParams, $redisOptions);
			$redisTemp->info();
			$connected = TRUE;
			$this->connection = $redisTemp;
		} catch (Exception $e) {
			self::LogCacheException($e);
		}

		$this->connected = $connected;
		return $connected;
	}

// <editor-fold defaultstate="collapsed" desc="Mapping helper functions">
	/**
	 * Maps an expire time converting unix timestamps to expire times in seconds
	 * @param int $expireTime Relative time in seconds or absolute expire time as UNIX timestamp
	 * @return int
	 */
	private function MapExpireTime($expireTime) {
		if ($expireTime >= $this->constructTimestamp) {
			$expireTime -= $this->constructTimestamp;
		}
		return $expireTime;
	}
// </editor-fold>

	/**
	 * Tries connecting to the cache server and returns the success status
	 * @return bool
	 */
	public function TryConnect() {
		return $this->EnsureConnect();
	}

	/**
	 * @return RedisClient
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * Disconnects from the cache server
	 * @return void
	 */
	public function Disconnect() {
		if ($this->config["ENABLED"]) {
			try {
				if ($this->connection !== FALSE) {
					$this->connection->disconnect();
					$this->connection = false;
					$this->connected = false;
				}
			} catch (Exception $ex) {
				// on any unhandled error in cache connection
				// Log the exception and try next connection in the pool
				//TODO: ??? self::LogCacheException($ex);
			}
		}
	}

	/**
	 * Returns true if the cache connector is successfully connected to the cache server
	 * @return bool
	 */
	public function IsConnected() {
		return $this->connected;
	}

	/**
	 * Flushes all contents on the target cache server
	 */
	public function Flush() {
		if ($this->EnsureConnect()) {
			$this->connection->flushdb();
		}
	}

	private function AddOrSet($key, $variable, $expireTime, $useNX) {
		if (!$this->EnsureConnect()) {
			return false;
		}

		try {
			$keyEffective = $this->MapKey($key);
			$expireTimeEffective = $this->MapExpireTime($expireTime);

			if (is_object($variable) || is_array($variable)) {
				$objectData = serialize($variable);
				$setParams = array($keyEffective, $objectData);
			} else {
				$setParams = array($keyEffective, $variable);
			}

			if ($expireTimeEffective > 0) {
				$setParams[] = "EX";
				$setParams[] = $expireTimeEffective;
			}
			if ($useNX) {
				$setParams[] = "NX";
			}
			$setResult = self::RedisExecuteCommand($this->connection, "set", $setParams);
			unset($setParams);
		} catch (Exception $ex) {
			$setResult = "";
			// on any unhandled error in cache connection log the exception 
			self::LogCacheException($ex);
		}
		return $setResult == "OK";
	}

	/**
	 * Adds a cache data
	 * @param string $key
	 * @param mixed $variable
	 * @param int $expireTime
	 * @return bool
	 */
	public function Add($key, $variable, $expireTime = 0) {
		return $this->AddOrSet($key, $variable, $expireTime, true);
	}

	/**
	 * Sets a cache data
	 * @param string $key
	 * @param mixed $variable
	 * @param int $expireTime
	 * @return bool
	 */
	public function Set($key, $variable, $expireTime = 0) {
		return $this->AddOrSet($key, $variable, $expireTime, false);
	}

	/**
	 * Sets a cache object serialized
	 * @param string $key
	 * @param mixed $object
	 * @param int $expireTime
	 * @return bool
	 */
	public function SetObject($key, $object, $expireTime = 0) {
		$objectData = serialize($object);
		return $this->AddOrSet($key, $objectData, $expireTime, false);
	}

	/**
	 * - Returns false on failure
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function Get($key) {
		if (!$this->EnsureConnect()) {
			return false;
		}

		$mappedKey = $this->MapKey($key);
		try {
			$value = $this->connection->get($mappedKey);
		} catch (Exception $ex) {
			// on any unhandled error in redis connection log the exception
			self::LogCacheException($ex);
			$value = null;
		}
		if (is_null($value)) {
			$value = FALSE;
		}
		return $value;
	}

	/**
	 * Selects a list of cached items.
	 *
	 * @param array $keys Keys to be requested
	 * @return array
	 */
	public function GetList(array $keys) {
		$mapped_keys = array();
		foreach ($keys as $key) {
			$mapped_keys[] = $this->MapKey($key);
		}

		if (count($mapped_keys) == 0 || !$this->EnsureConnect()) {
			return array();
		}

		try {
			$arrData = $this->connection->mget($mapped_keys);
		} catch (Exception $ex) {
			$arrData = array();
			// on any unhandled error in redis connection log the exception
			self::LogCacheException($ex);
		}

		// map item keys
		$arrRet = array();
		foreach ($arrData as $index => $value) {
			if (isset($keys[$index]) && !is_null($value)) {
				$key = $keys[$index];
				$arrRet[$key] = $value;
			}
		}
		return $arrRet;
	}

	/**
	 * Deletes a cache item by its key
	 * @param string $key
	 * @return bool
	 */
	public function Delete($key) {
		if (!$this->EnsureConnect()) {
			return false;
		}

		$mappedKey = $this->MapKey($key);
		try {
			$numDeleted = $this->connection->del($mappedKey);
			return $numDeleted > 0;
		} catch (Exception $ex) {
			// on any unhandled error in cache connection
			self::LogCacheException($ex);
			return false;
		}
	}

	/**
	 * Increments a cache counter
	 *
	 * @param string $key Counter item key
	 * @param int $value Increment value
	 * @return mixed NULL if cache cannot be connected, FALSE if the item is not found in cache, otherwise the new value of the counter is returned
	 */
	public function Increment($key, $value = 1) {
		if (!$this->EnsureConnect()) {
			return null;
		}

		$mappedKey = $this->MapKey($key);
		$result = FALSE;
		try {
			$retVal = $this->connection->incrby($mappedKey, $value);
			// overwrite the return value with counter value
			if (is_numeric($retVal)) {
				$result = $retVal;
			}
		} catch (Exception $ex) {
			// on any unhandled error in cache connection
			// Log the exception and return null
			self::LogCacheException($ex);
			$result = null;
		}
		return $result;
	}

	/**
	 * Decrements a cache counter
	 *
	 * @param string $key Counter item key
	 * @param int $value Increment value
	 * @return mixed FALSE if the item is not found in cache otherwise the new value of the counter is returned
	 */
	public function Decrement($key, $value = 1) {
		if (!$this->EnsureConnect()) {
			return null;
		}

		$mappedKey = $this->MapKey($key);
		$result = FALSE;
		try {
			$retVal = $this->connection->decrby($mappedKey, $value);
			// overwrite the return value with counter value
			if (is_numeric($retVal)) {
				$result = $retVal;
			}
		} catch (Exception $ex) {
			// on any unhandled error in cache connection
			// Log the exception and return null
			self::LogCacheException($ex);
			$result = null;
		}
		return $result;
	}

// <editor-fold defaultstate="collapsed" desc="IRedisConnector implementation">
	/**
	 * Helper method to invoke a named REDIS command on a valid REDIS connection
	 * @param RedisClient $connection Predis client object
	 * @param string $commandName Name of the command to execute
	 * @param array $commandArguments Command arguments
	 * @return mixed
	 */
	protected static function RedisExecuteCommand($connection, $commandName, $commandArguments)
	{
		$cmd = $connection->createCommand($commandName, $commandArguments);
		return $connection->executeCommand($cmd);
	}

	/**
	 * Converts the result of an REDIS HGETALL operation to an associative array
	 * @param array $keyValueList
	 * @return array
	 */
	protected static function RedisConvertHGETALLResult($keyValueList)
	{
		return $keyValueList;
	}

	/**
	 * Shared helper method to return an empty result set for REDIS HMGET operations
	 * @param array $keys
	 * @return array
	 */
	protected static function GetRedisHMGETDefaultResult($keys)
	{
		$returnSet = array();
		foreach ($keys as $key) {
			$returnSet[$key] = null;
		}
		return $returnSet;
	}

	/**
	 * Returns the value associated with field in the hash stored at key.
	 * @param string $hash
	 * @param string $key
	 * @param bool $disableHashPrefix Optional parameter to disable adding the key prefix to the hash
	 * @return mixed The value at the hash or null if the hash is missing or key is missing in the hash
	 */
	public function HGet($hash, $key, $disableHashPrefix = false) {
		if (!$this->EnsureConnect()) {
			return null;
		}

		$mappedHash = $disableHashPrefix ? $hash : $this->MapKey($hash);
		return $this->connection->hget($mappedHash, $key);
	}

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
	public function HSet($hash, $key, $value, $disableHashPrefix = false) {
		if (!$this->EnsureConnect()) {
			return false;
		}

		$mappedHash = $disableHashPrefix ? $hash : $this->MapKey($hash);
		$setResult = $this->connection->hset($mappedHash, $key, $value);
		//$setResult:
		//- 1 if field is a new field in the hash and value was set.
		//- 0 if field already exists in the hash and the value was updated.
		return $setResult == 0 || $setResult == 1;
	}

	/**
	 * Returns the values associated with the specified fields in the hash stored at key.
	 * @param string $hash
	 * @param array $keys
	 * @param bool $disableHashPrefix Optional parameter to disable adding the key prefix to the hash
	 * @return array
	 */
	public function HMGet($hash, $keys, $disableHashPrefix = false) {
		if (!$this->EnsureConnect()) {
			return self::GetRedisHMGETDefaultResult($keys);
		}

		$mappedHash = $disableHashPrefix ? $hash : $this->MapKey($hash);
		$cmdParams = array($mappedHash);
		$cmdParams = array_merge($cmdParams, $keys);
		$cmdResult = self::RedisExecuteCommand($this->connection, "hmget", $cmdParams);
		unset($cmdParams);
		return $cmdResult;
	}

	/**
	 * Sets the specified fields to their respective values in the hash stored at key.
	 * - This command overwrites any specified fields already existing in the hash. If key does not exist, a new key holding a hash is created.
	 * @param string $hash
	 * @param array $keyValuePairs
	 * @param bool $disableHashPrefix Optional parameter to disable adding the key prefix to the hash
	 * @return bool
	 */
	public function HMSet($hash, $keyValuePairs, $disableHashPrefix = false) {
		if (!$this->EnsureConnect()) {
			return false;
		}

		$mappedHash = $disableHashPrefix ? $hash : $this->MapKey($hash);
		$cmdParams = array($mappedHash);
		foreach ($keyValuePairs as $key => $value) {
			$cmdParams[] = $key;
			$cmdParams[] = $value;
		}
		$cmdResult = self::RedisExecuteCommand($this->connection, "hmset", $cmdParams);
		unset($cmdParams);
		return true;
	}

	/**
	 * Returns all fields and values of the hash stored at key.
	 * - In the returned value, every field name is followed by its value, so the length of the reply is twice the size of the hash.
	 * @param string $hash
	 * @param bool $disableHashPrefix Optional parameter to disable adding the key prefix to the hash
	 * @return array List of fields and their values stored in the hash, or an empty list when key does not exist.
	 */
	public function HGetAll($hash, $disableHashPrefix = false) {
		if (!$this->EnsureConnect()) {
			return array();
		}

		$mappedHash = $disableHashPrefix ? $hash : $this->MapKey($hash);
		$keyValueList = $this->connection->hgetall($mappedHash);
		return self::RedisConvertHGETALLResult($keyValueList);
	}
// </editor-fold>
}
