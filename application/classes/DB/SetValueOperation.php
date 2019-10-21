<?php

namespace Zynga\DB;

/**
 * Represents a simple set value operation
 */
class SetValueOperation
{

	/**
	 * Field name to set
	 * @var string
	 */
	public $field;

	/**
	 * Field value or expression to set
	 * @var string
	 */
	public $value;

	/**
	 * Flag to specify that the set value is a custom expression
	 * @var bool
	 */
	public $isExpression;

	/**
	 * Public constructor
	 * @param string $field Field name to set
	 * @param string $value Field value or expression to set
	 * @param bool $isExpression Optional flag to specify that the set value is a custom expression
	 */
	public function __construct($field, $value, $isExpression = false) {
		$this->field = $field;
		$this->value = $value;
		$this->isExpression = $isExpression;
	}
	
	/**
	 * Converts the set value operation to a valid set value expression for a data source
	 * @param IDataSource $dataSource
	 * @return string
	 */
	public function __getValueForDB(IDataSource $dataSource) {
		if ($this->isExpression) {
			$value = "(" . $this->value . ")";
		} else {
			$value = $dataSource->EscapeValue($this->value);
		}
		return $value;
	}
}
