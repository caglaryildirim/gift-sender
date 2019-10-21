<?php
namespace Zynga\Cache;

/**
 * Defines the common methods that a cache connector (Redis/Memcache) object must implement
 */
interface IGenericCacheConnector
{
	/**
	 * Returns a cached object.
	 * - The object type must implement a static creator function 'GetObjectByID($yayin_id, $objectID)'.
	 * - The object is memcached according to its ID and object type.
	 * - Returns FALSE on failure
	 *
	 * @param mixed $objectID Object record ID
	 * @param string $objectType Type name of the object to create
	 * @param int $cacheTime Time of cache in seconds
	 * @param string $callbackFunction Name of the static function that creates the object
	 * @return mixed
	 */
	function GetCachedObject($objectID, $objectType, $cacheTime = 30, $callbackFunction = "GetObjectByID");

	/**
	 * Returns a cached object.
	 * - The object type must implement a static creator function and specify it as '$creatorCallback($yayin_id, $objectID)'.
	 * - The object is memcached according to its ID and object type.
	 * - Returns FALSE on failure
	 *
	 * @param int $yayinID Object publication ID
	 * @param mixed $objectID Object record ID
	 * @param array $creatorCallback Static callback in the class for object creation
	 * @param int $cacheTime Time of cache in seconds
	 * @return mixed
	 */
	function GetCachedObjectCustom($yayinID, $objectID, $creatorCallback, $cacheTime);

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
	function GetCachedObjectCallback($cacheKey, $cacheTime, $creatorCallback);

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
	function GetLocalCachedObject($cacheKey, $cacheTime, $creatorCallback);

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
	 */
	function GetCachedSimpleDataCallback($cacheKey, $cacheTime, $creatorCallback);

	/**
	 * Invalidates a cached object by its type and ID
	 * @param int $objectID Object record ID
	 * @param string $objectType Type name of the object
	 * @param string $callbackFunction Name of the static function that creates the object
	 */
	function InvalidateCachedObject($objectID, $objectType, $callbackFunction = "GetObjectByID");

	/**
	 * Tries connecting to the cache server and returns the success status
	 * @return bool
	 */
	function TryConnect();

	/**
	 * Disconnects from the cache server
	 * @return void
	 */
	function Disconnect();

	/**
	 * Returns true if the cache connector is successfully connected to the cache server
	 * @return bool
	 */
	function IsConnected();

	/**
	 * Flushes all contents on the target cache server
	 */
	function Flush();

	/**
	 * Adds a cache data
	 * @param string $key
	 * @param mixed $variable
	 * @param int $expireTime
	 * @return bool
	 */
	function Add($key, $variable, $expireTime);

	/**
	 * Adds a cache object serialized
	 * @param string $key
	 * @param mixed $object
	 * @param int $expireTime
	 * @return bool
	 */
	function AddObject($key, $object, $expireTime);

	/**
	 * Sets a cache data
	 * @param string $key
	 * @param mixed $variable
	 * @param int $expireTime
	 * @return bool
	 */
	function Set($key, $variable, $expireTime);

	/**
	 * Sets a cache object serialized
	 * @param string $key
	 * @param mixed $object
	 * @param int $expireTime
	 * @return bool
	 */
	function SetObject($key, $object, $expireTime);

	/**
	 * Returns a cached data by its key
	 * @param string $key
	 */
	function Get($key);

	/**
	 * Gets a cached object unserialized
	 * - Returns false on failure
	 *
	 * @param string $key
	 * @return mixed
	 */
	function GetObject($key);

	/**
	 * Returns a list of cached data indexed by their keys
	 * @param array $keys
	 */
	function GetList(array $keys);

	/**
	 * Selects a list of cached objects.
	 *
	 * @param array $keys Keys to be requested
	 * @return array
	 */
	function GetObjectList(array $keys);

	/**
	 * Deletes a cache item by its key
	 * @param string $key
	 * @return bool
	 */
	function Delete($key);

	/**
	 * Increments a cache counter
	 *
	 * @param string $key Counter item key
	 * @param int $value Increment value
	 * @return mixed NULL if cache cannot be connected, FALSE if the item is not found in cache, otherwise the new value of the counter is returned
	 */
	function Increment($key, $value = 1);

	/**
	 * Decrements a cache counter
	 *
	 * @param string $key Counter item key
	 * @param int $value Increment value
	 * @return mixed FALSE if the item is not found in cache otherwise the new value of the counter is returned
	 */
	function Decrement($key, $value = 1);
}