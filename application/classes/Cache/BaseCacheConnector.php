<?php
namespace Zynga\Cache;

use Exception;
use Throwable;
use function Sentry\captureMessage;

/**
 * Base class for cache connectors implementing IGenericCacheConnector
 */
abstract class BaseCacheConnector implements IGenericCacheConnector
{
// <editor-fold defaultstate="collapsed" desc="Class variables">
	/**
	 * The key prefix to append to each entry automatically
	 * @var string
	 */
	protected $key_prefix;
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Constructor">
	/**
	 * Base constructor
	 */
	protected function __construct() {
	}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Shared helper methods">
	/**
	 * Logs a cache error uniquely grouped by the error message and date
	 * @param string $errorMessage
	 */
	protected static function LogCacheError($errorMessage) {
		captureMessage($errorMessage);
	}

	/**
	 * Logs the cache exception
	 * @param Throwable $ex
	 */
	protected static function LogCacheException($ex) 
	{
		self::LogCacheError($ex->getMessage());
	}

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Mapping helper functions">
	/**
	 * Maps the item keys to a specific prefixed key name
	 * @param string $key Original key to map with key prefix
	 * @return string
	 */
	protected function MapKey($key)
	{
		return empty($this->key_prefix) ? $key : ($this->key_prefix . $key);
	}

	/**
	 * Reverse maps the cache keys to unprefixed item keys
	 * @param string $key Key to unmap from the key prefix
	 * @return string
	 */
	protected function UnmapKey($key)
	{
		return substr($key, strlen($this->key_prefix));
	}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Primitive cached object methods">
	/**
	 * Adds a cache object serialized
	 * @param string $key
	 * @param mixed $object
	 * @param int $expireTime
	 * @return bool
	 */
	public function AddObject($key, $object, $expireTime) {
		$objectData = serialize($object);
		return $this->Add($key, $objectData, $expireTime);
	}

	/**
	 * Sets a cache object serialized
	 * @param string $key
	 * @param mixed $object
	 * @param int $expireTime
	 * @return bool
	 */
	public function SetObject($key, $object, $expireTime) {
		$objectData = serialize($object);
		return $this->Set($key, $objectData, $expireTime);
	}

	/**
	 * Gets a cached object unserialized
	 * - Returns false on failure
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function GetObject($key)	{
		$objectData = $this->Get($key);
		if ($objectData === false) {
			return false;
		} else {
			return unserialize($objectData);
		}
	}

	/**
	 * Selects a list of cached objects.
	 *
	 * @param array $keys Keys to be requested
	 * @return array
	 */
	public function GetObjectList(array $keys)
	{
		$arrObjectData = $this->GetList($keys);
		$arrObject = array();
		foreach ($arrObjectData as $key => $value) {
			$arrObject[$key] = unserialize($value);
		}
		return $arrObject;
	}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Complex cached object methods">
	/**
	 * Invalidates a cached object by its type and ID
	 * @param int $objectID Object record ID
	 * @param string $objectType Type name of the object
	 * @param string $callbackFunction Name of the static function that creates the object
	 * @return bool
	 */
	public function InvalidateCachedObject($objectID, $objectType, $callbackFunction = "GetObjectByID") {
		if ($callbackFunction == "GetObjectByID") {
			$cacheKey = "O." . $objectType . "_" . $objectID;
		} else {
			$cacheKey = "O." . $objectType . "_" . $callbackFunction . "_" . $objectID;
		}
		return $this->Delete($cacheKey);
	}

	/**
	 * Returns a cached object.
	 * - The object type must implement a static creator function 'GetObjectByID($yayin_id, $objectID)'.
	 * - The object is cached according to its ID and object type.
	 * - Returns FALSE on failure
	 *
	 * @param mixed $objectID Object record ID
	 * @param string $objectType Type name of the object to create
	 * @param int $cacheTime Time of cache in seconds
	 * @param string $callbackFunction Optional name of the static function that creates the object
	 * @return mixed
	 */
	public function GetCachedObject($objectID, $objectType, $cacheTime = 30, $callbackFunction = "GetObjectByID") {
		if ($callbackFunction == "GetObjectByID") {
			$cacheKey = "O." . $objectType . "_" . $objectID;
		} else {
			$cacheKey = "O." . $objectType . "_" . $callbackFunction . "_" . $objectID;
		}
		$object = ($cacheTime <= 0) ? FALSE : $this->GetObject($cacheKey);

		if ($object === FALSE) {
			$creatorFunctionCallback = array($objectType, $callbackFunction);
			$object = call_user_func($creatorFunctionCallback, $objectID);
			if ($cacheTime > 0 && $object !== FALSE) {
				$this->SetObject($cacheKey, $object, $cacheTime);
			}
		}

		return $object;
	}

	/**
	 * Returns a cached object.
	 * - The object type must implement a static creator function and specify it as '$creatorCallback($yayin_id, $objectID)'.
	 * - The object is cached according to its ID and object type.
	 * - Returns FALSE on failure
	 *
	 * @param int $yayinID Object publication ID
	 * @param mixed $objectID Object record ID
	 * @param array $creatorCallback Static callback in the class for object creation
	 * @param int $cacheTime Time of cache in seconds
	 * @return mixed
	 */
	public function GetCachedObjectCustom($yayinID, $objectID, $creatorCallback, $cacheTime) {
		$objectType = $creatorCallback[0];
		$callbackFunction = $creatorCallback[1];
		$cacheKey = "O." . $objectType . "_" . $callbackFunction . $yayinID . "_" . $objectID;
		$object = ($cacheTime <= 0) ? FALSE : $this->GetObject($cacheKey);

		if ($object === FALSE) {
			$object = call_user_func($creatorCallback, $yayinID, $objectID);
			if ($cacheTime > 0 && $object !== FALSE) {
				$this->SetObject($cacheKey, $object, $cacheTime);
			}
		}

		return $object;
	}

	/**
	 * Returns a cached object which can be created by a callback when necessary.
	 * - The callable $creatorCallback must return the non-cached object or FALSE if the object is invalid or not found.
	 * - The object is cached according to $cacheKey
	 * - Returns FALSE on failure
	 *
	 * @param string $cacheKey The cache key of the object
	 * @param int $cacheTime Time of cache in seconds
	 * @param callable $creatorCallback Callback that will create the object if it is not found in the cache
	 * @return mixed
	 */
	public function GetCachedObjectCallback($cacheKey, $cacheTime, $creatorCallback) {
		$cachedObject = ($cacheTime <= 0) ? FALSE : $this->GetObject($cacheKey);

		if ($cachedObject === FALSE) {
			$cachedObject = call_user_func($creatorCallback);
			if ($cacheTime > 0 && $cachedObject !== FALSE) {
				$this->SetObject($cacheKey, $cachedObject, $cacheTime);
			}
		}

		return $cachedObject;
	}

	/**
	 * Implements a local data cache for reusable data items
	 * @var array
	 */
	private $__localDataCache = array();

	/**
	 * Returns a cached object which can be created by a callback when necessary.
	 * - The callable $creatorCallback must return the non-cached object or FALSE if the object is invalid or not found.
	 * - The object is cached according to $cacheKey
	 * - Returns FALSE on failure
	 * - If the return value is non-empty, it is also cached in a local data cache array
	 * 
	 * @param string $cacheKey The cache key of the object
	 * @param int $cacheTime Time of cache in seconds
	 * @param callable $creatorCallback Callback that will create the object if it is not found in the cache
	 * @return mixed
	 */
	public function GetLocalCachedObject($cacheKey, $cacheTime, $creatorCallback) {
		if (isset($this->__localDataCache[$cacheKey])) {
			return $this->__localDataCache[$cacheKey];
		}

		$cachedObject = $this->GetCachedObjectCallback($cacheKey, $cacheTime, $creatorCallback);
		if (!empty($cachedObject)) {
			$this->__localDataCache[$cacheKey] = $cachedObject;
		}

		return $cachedObject;
	}

	/**
	 * Returns a cached simple data (string, int etc.) which can be retrieved by a callback when necessary.
	 * - The callable $creatorCallback must return the non-cached data or FALSE if the data is invalid or not found.
	 * - The data is cached according to $cacheKey
	 * - Returns FALSE on failure
	 *
	 * @param string $cacheKey The cache key of the object
	 * @param int $cacheTime Time of cache in seconds
	 * @param callable $creatorCallback Callback that will create the data if it is not found in the cache
	 * @return mixed
	 * @throws Exception Function cannot return objects and arrays
	 */
	public function GetCachedSimpleDataCallback($cacheKey, $cacheTime, $creatorCallback) {
		$cachedObject = ($cacheTime <= 0) ? FALSE : $this->Get($cacheKey);

		if ($cachedObject === FALSE) {
			$cachedObject = call_user_func($creatorCallback);

			// validate that the return value is not object or array
			if (is_object($cachedObject) || is_array($cachedObject)) {
				throw new Exception("GetCachedSimpleDataCallback cannot return objects or arrays");
			}

			if ($cacheTime > 0 && $cachedObject !== FALSE) {
				$this->Set($cacheKey, $cachedObject, $cacheTime);
			}
		}

		return $cachedObject;
	}
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc="Abstract low level methods">
	/**
	 * Tries connecting to the cache server and returns the success status
	 * @return bool
	 */
	public abstract function TryConnect();

	/**
	 * Disconnects from the cache server
	 * @return void
	 */
	public abstract function Disconnect();

	/**
	 * Returns true if the cache connector is successfully connected to the cache server
	 * @return bool
	 */
	public abstract function IsConnected();

	/**
	 * Flushes all contents on the target cache server
	 * @return bool
	 */
	public abstract function Flush();

	/**
	 * Adds a cache data
	 * @param string $key
	 * @param mixed $variable
	 * @param int $expireTime
	 * @return bool
	 */
	public abstract function Add($key, $variable, $expireTime);

	/**
	 * Sets a cache data
	 * @param string $key
	 * @param mixed $variable
	 * @param int $expireTime
	 * @return bool
	 */
	public abstract function Set($key, $variable, $expireTime);

	/**
	 * Returns a cached data by its key
	 * @param string $key
	 * @return string|bool The cache content is returned as a string. If the cache item is not found, FALSE is returned.
	 */
	public abstract function Get($key);

	/**
	 * Returns a list of cached data indexed by their keys
	 * @param array $keys
	 * @return array
	 */
	public abstract function GetList(array $keys);

	/**
	 * Deletes a cache item by its key
	 * @param string $key
	 * @return bool
	 */
	public abstract function Delete($key);

	/**
	 * Increments a cache counter
	 *
	 * @param string $key Counter item key
	 * @param int $value Increment value
	 * @return mixed NULL if cache cannot be connected, FALSE if the item is not found in cache, otherwise the new value of the counter is returned
	 */
	public abstract function Increment($key, $value = 1);

	/**
	 * Decrements a cache counter
	 *
	 * @param string $key Counter item key
	 * @param int $value Increment value
	 * @return mixed FALSE if the item is not found in cache otherwise the new value of the counter is returned
	 */
	public abstract function Decrement($key, $value = 1);
// </editor-fold>
}
