<?php
namespace Zynga\Model;

use DomainException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use stdClass;

/**
 * A shared implementation for JSON object serialization/deserialization
 */
trait JSONConvertibleTrait
{
// <editor-fold defaultstate="collapsed" desc="Helper functions for readFromJSON implementations">
	/**
	 * A default implementation of readFromJSON using ReadableFromJSON interface and a fallback to reflection classes
	 * Reads the values from a json_decode'd array
	 * @param mixed $result An object to deserialize from JSON decoded values
	 * @param array $values Array of JSON decoded values
	 */
	private static function readFromJSONDefault($result, &$values): void {
		if ($result instanceof ReadableFromJSON) {
			$result->readFromJSON($values);
		} else {
			try {
				$class = new ReflectionClass($result);
				$publicProps = $class->getProperties(ReflectionProperty::IS_PUBLIC);
				foreach ($publicProps as $prop) {
					$propName = $prop->name;
					if (array_key_exists($propName, $values)) {
						$existingValue = $prop->getValue($result);
						if (is_object($existingValue) && $existingValue instanceof ReadableFromJSON) {
							$existingValue->readFromJSON($values[$propName]);
						} else {
							$prop->setValue($result, $values[$propName]);
						}
					}
				}
			} catch (ReflectionException $e) {
			}
		}
	}

	/**
	 * Tries converting and returning a data array as a strongly typed object instance
	 * @param array $values
	 * @param string $typeName Type name of the object
	 * @return mixed
	 */
	protected static function getAsObject(&$values, string $typeName) {
		$instance = new $typeName();
		self::readFromJSONDefault($instance, $values);
		return $instance;
	}

	/**
	 * Reads property values from an array
	 * @param array $values
	 * @param string[] $propertyNames
	 */
	protected function readValuesFromArray(&$values, array $propertyNames): void {
		if (!empty($values)) {
			foreach ($propertyNames as $propertyName) {
				if (array_key_exists($propertyName, $values)) {
					$this->$propertyName = $values[$propertyName];
				}
			}
		}
	}

	/**
	 * Converts an array of data to a strongly typed array of objects
	 * @param array $values
	 * @param string $typeName Type of the objects in the collection property
	 * @return array
	 */
	protected function getObjectArray(&$values, string $typeName): array {
		$convertedSet = array();
		if (!empty($values)) {
			foreach ($values as $key => $valueArray) {
				$newObj = new $typeName();
				self::readFromJSONDefault($newObj, $valueArray);
				$convertedSet[] = $newObj;
			}
		}
		return $convertedSet;
	}

	/**
	 * Converts an array of data to a strongly typed array of objects
	 * @param array $values
	 * @param string $typeName Type of the objects in the collection property
	 * @return array
	 */
	protected function getAssociativeObjectArray(&$values, string $typeName): array {
		$convertedSet = array();
		if (!empty($values)) {
			foreach ($values as $key => $valueArray) {
				$newObj = new $typeName();
				self::readFromJSONDefault($newObj, $valueArray);
				$convertedSet[$key] = $newObj;
			}
		}
		return $convertedSet;
	}

	/**
	 * Reads a single property value for a strongly typed object property
	 * @param array $values
	 * @param string $propertyName Name of the property
	 * @param string $typeName Type name of the object property
	 */
	protected function readObjectProperty(&$values, string $propertyName, string $typeName): void {
		if (!empty($values) && array_key_exists($propertyName, $values)) {
			if (is_null($this->$propertyName)) {
				$this->$propertyName = new $typeName();
			}
			self::readFromJSONDefault($this->$propertyName, $values[$propertyName]);
		}
	}

	/**
	 * Reads a single property value for an associative array of objects
	 * @param array $values
	 * @param string $propertyName Name of the collection property
	 * @param string $typeName Type of the objects in the collection property
	 */
	protected function readObjectArray(&$values, string $propertyName, string $typeName): void {
		if (is_null($this->$propertyName)) {
			$this->$propertyName = array();
		}
		if (!empty($values) && array_key_exists($propertyName, $values)) {
			foreach ($values[$propertyName] as $key => $valueArray) {
				$newObj = new $typeName();
				self::readFromJSONDefault($newObj, $valueArray);
				$this->$propertyName[] = $newObj;
			}
		}
	}

	/**
	 * Reads a single property value for an associative array of objects
	 * @param array $values
	 * @param string $propertyName Name of the collection property
	 * @param string $typeName Type of the objects in the collection property
	 */
	protected function readAssociativeObjectArray(&$values, string $propertyName, string $typeName): void {
		if (is_null($this->$propertyName)) {
			$this->$propertyName = array();
		}
		if (!empty($values) && array_key_exists($propertyName, $values)) {
			foreach ($values[$propertyName] as $key => $valueArray) {
				$newObj = new $typeName();
				self::readFromJSONDefault($newObj, $valueArray);
				$this->$propertyName[$key] = $newObj;
			}
		}
	}
// </editor-fold>

	/**
	 * Returns an associative array of non-default values for simple object properties.
	 * This return value may be used to implement interface 'JsonSerializable'
	 * @param array $defaultValues Associative array of simple properties and their default values, which will be included in the serialization only when necessary
	 * @return array|stdClass
	 */
	protected function jsonSerializeDefault(array $defaultValues) {
		$serializeValues = [];
		foreach ($defaultValues as $key => $defaultValue) {
			$currentValue = $this->$key;
			if ($currentValue !== $defaultValue) {
				$serializeValues[$key] = $currentValue;
			}
		}
		return empty($serializeValues) ? new stdClass() : $serializeValues;
	}

	/**
	 * Converts a JSON encoded string data to a strongly typed object representation
	 * @param string $jsonData
	 * @return mixed
	 * @throws DomainException If the expected JSON data is invalid and cannot be decoded
	 */
	protected static function fromJSONInternal(?string $jsonData) {
		$result = new static();
		$arrJson = is_null($jsonData) ? null : json_decode($jsonData, true);
		if (is_null($arrJson)) {
			throw new DomainException("Invalid JSON Data");
		}
		self::readFromJSONDefault($result, $arrJson);
		return $result;
	}

	/**
	 * Converts the current object to JSON representation
	 * @return string
	 */
	public function toJSON(): string {
		return json_encode($this);
	}
}
