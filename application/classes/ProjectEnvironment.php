<?php

namespace Zynga;

use LogicException;
use Zynga\Cache\IRedisConnector;
use Zynga\Cache\RedisConnector;
use Zynga\DB\IDataSource;
use Zynga\DB\MySQLDataSource;
use Predis\Session\Handler as SessionHandler;

final class ProjectEnvironment
{
	/**
	 * @var ProjectEnvironment
	 */
	private static $_instance = null;

	/**
	 * @return ProjectEnvironment
	 */
	public static function Instance(): self {
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * @var IDataSource
	 */
	private $db;

	/**
	 * @return IDataSource
	 */
	public function getDb(): IDataSource {
		return $this->db;
	}

	/**
	 * @var IRedisConnector
	 */
	private $cache;

	/**
	 * @return IRedisConnector
	 */
	public function getCache(): IRedisConnector {
		return $this->cache;
	}

	/**
	 * @var IRedisConnector
	 */
	private $sessionCache = null;

	/**
	 * @return IRedisConnector
	 */
	public function getSessionCache(): IRedisConnector {
		if (is_null($this->sessionCache)) {
			ini_set('session.gc_probability', 2);
			ini_set('session.save_handler', 'user');
			ini_set('session.gc_maxlifetime', 86400);

			$CI = get_instance();
			$cacheConfig = $CI->config->item("redisSessionServer");
			$redisOptions = $CI->config->item("redisOptions");
			$this->sessionCache = new RedisConnector($cacheConfig, $redisOptions);
			if ($this->sessionCache->TryConnect()) {
				$redis = $this->sessionCache->getConnection();
				$sessHandler = new SessionHandler($redis);
				$sessHandler->register();
				session_start();
			} else {
				throw new LogicException("Cannot connect to redis");
			}
		}
		return $this->sessionCache;
	}

	private function __construct() {
		$CI = get_instance();

		$cacheConfig = $CI->config->item("redisCacheServer");
		$redisOptions = $CI->config->item("redisOptions");
		$this->cache = new RedisConnector($cacheConfig, $redisOptions);

		$gameDBOptions = $CI->config->item("gameDBOptions");
		$this->db = MySQLDataSource::CreateDataSource($gameDBOptions["hostname"], $gameDBOptions["database"], $gameDBOptions["username"], $gameDBOptions["password"]);
	}
}
