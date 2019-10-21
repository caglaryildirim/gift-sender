<?php

namespace Zynga\Model;

use CBGMVC\DB\MySQLDataSource As DataSource;

class DataClassGenerator
{
	private static $genDB = null;

	private static function getDB() {
		if (is_null(self::$genDB)) {
			self::$genDB = DataSource::CreateDataSource("10.34.101.202", "caglartest", "cbg", "cbg1493");
		}
		return self::$genDB;
	}

	private static function generatePropertyName($fieldName) {
		$fieldNameParts = explode("_", $fieldName);
		$namePartsTr = array();
		$reservedMappings = array(
			"id" => "ID",
		);
		foreach ($fieldNameParts as $namePart) {
			if (isset($reservedMappings[$namePart])) {
				$namePartsTr[] = $reservedMappings[$namePart];
			} elseif ($namePart == strtolower($namePart)) {
				$namePartsTr[] = strtoupper(substr($namePart, 0, 1)) . strtolower(substr($namePart, 1));
			} else {
				$namePartsTr[] = $namePart;
			}
		}
		return implode("", $namePartsTr);
	}

	private static function generateTableAlias($tableName) {
		$tableName = preg_replace('#^(tbl|def|rel)_?#', '', $tableName);

		$nameParts = explode("_", $tableName);
		$namePartsTr = array();
		if (count($nameParts) == 1) {
			$firstPart = $nameParts[0];
			$partLen = mb_strlen($firstPart);
			for ($i = 0; $i < $partLen; $i++) {
				$char = mb_substr($firstPart, $i, 1);
				if ("A" <= $char && $char <= "Z") {
					$namePartsTr[] = strtolower($char);
				}
			}
		} else {
			foreach ($nameParts as $partIndex => $namePart) {
				$namePartsTr[] = strtolower(substr($namePart, 0, 1));
			}
		}

		return implode("", $namePartsTr);
	}

	/**
	 * Auto-generates the class code for a database table
	 * @param string $tableName
	 * @param string $className
	 * @return string
	 */
	public static function generateTableCodeClass($tableName = '', $className = 'NewsStory') {
		$db = self::getDB();
		$dbAccessCode = 'self::GameDB()';
		$fields = $db->GetFieldDefinitions($tableName);
		// var_dump($fields);

		$classCode = array();
		$classCode[] = '<?php';
		$classCode[] = '';
		$classCode[] = '/**';
		$classCode[] = ' * @package Zynga\Model';
		$classCode[] = ' */';
		$classCode[] = '';
		$classCode[] = 'namespace Zynga\Model;';
		$classCode[] = '';
		$classCode[] = 'use Zynga\DB\SelectDataQuery;';
		$classCode[] = 'use Zynga\DB\SelectExtendedDataQuery;';
		$classCode[] = 'use Zynga\DB\UpdateDataQuery;';
		$classCode[] = 'use Zynga\DB\UpdateQueryResult;';
		$classCode[] = '';
		$classCode[] = 'class ' . $className;
		$classCode[] = '{';
		$classCode[] = "\t" . 'use ZyngaObjectQuery;';
		$classCode[] = '// <editor-fold defaultstate="collapsed" desc="Properties">';
		$fieldTypes = array();
		$fieldProperties = array();
		foreach ($fields as $field) {
			$typeName = $field->type;
			if (in_array($typeName, array("varchar", "text", "mediumtext", "longtext", "tinytext", "date", "time", "datetime", "timestamp"))) {
				$typeName = "string";
			} elseif (in_array($typeName, array("tinyint", "smallint", "mediumint", "int", "bigint"))) {
				$typeName = "int";
			}
			$fieldTypes[$field->name] = $typeName;
			$fieldProperties[$field->name] = $propName = self::generatePropertyName($field->name);
			$classCode[] = sprintf(
				'	/**
	 * @var %s
	 */
	public $%s;', $typeName, $propName);

			$classCode[] = '';
		}
		$classCode[] = '// </editor-fold>';

		$classCode[] = '';
		$classCode[] = '// <editor-fold defaultstate="collapsed" desc="Constructors">';
		$classCode[] = '	public function __construct($row) {';
		foreach ($fields as $field) {
			$typeName = $fieldTypes[$field->name];
			$propName = $fieldProperties[$field->name];
			$castOperator = "";
			if ($typeName == "int") {
				$castOperator = '(int)';
			} elseif (in_array($typeName, array("double", "float", "decimal"))) {
				$castOperator = '(float)';
			}
			$classCode[] = sprintf('		$this->%s = %s$row[\'%s\'];', $propName, $castOperator, $field->name);
		}
		$classCode[] = '	}';
		$classCode[] = '// </editor-fold>';
		$classCode[] = '';
		$classCode[] = '// <editor-fold defaultstate="collapsed" desc="Data read methods">';
		$tableAlias = self::generateTableAlias($tableName);
		$defaultSelectList = array();
		$pkFields = array();
		$statusField = null;
		$orderField = null;
		foreach ($fields as $field) {
			$defaultSelectList[] = $tableAlias . "." . $field->name;

			if ($field->primaryKey) {
				$pkFields[] = $field;
			}
			if (empty($statusField) &&
				(preg_match('#^(status|is_enabled|is_active)$#i', $field->name) ||
					preg_match('#(_status)$#i', $field->name) ||
					preg_match('#(Status)$#', $field->name))) {
				$statusField = $field;
			}
			if (empty($orderField) &&
				(preg_match('#^(sira|sira_no|order|order_no)$#i', $field->name) ||
					preg_match('#(_sira)$#i', $field->name) ||
					preg_match('#(Order)$#', $field->name))) {
				$orderField = $field;
			}
		}
		$classCode[] = '	private static $tableName = "' . $tableName . '";';
		$classCode[] = '	private static $selectList = "' . implode(",", $defaultSelectList) . '";';

		$objectCreateTypeName = $className;

		// Generate the method to read a single object by ID
		if (count($pkFields) == 1) {
			$idFieldName = $pkFields[0]->name;
			$idFieldType = $fieldTypes[$idFieldName];
			$idFieldTypeMapped = in_array($idFieldType, array("int", "string")) ? $idFieldType : "mixed";
			$idParamCast = $idFieldTypeMapped == "int" ? "(int) " : "";
			$idFieldParam = $fieldProperties[$idFieldName];
			$idFieldParam = strtolower(substr($idFieldParam, 0, 1)) . substr($idFieldParam, 1);
			$extraSql = empty($statusField) ? "" : sprintf(" AND %s.%s=1", $tableAlias, $statusField->name);
			$methodTemplate = <<<EOS
	/**
	 * Returns a single object by its ID
	 * - This may return false if the object cannot be found
	 * @param $idFieldTypeMapped \$$idFieldParam
	 * @return $objectCreateTypeName|null
	 */
	public static function get${objectCreateTypeName}ByID(\$$idFieldParam) {
		\$selectQuery = SelectDataQuery::Create("SELECT " . self::\$selectList . " FROM " . self::\$tableName . " AS $tableAlias" . 
			" WHERE $tableAlias.$idFieldName=?${extraSql}", array($idParamCast\$$idFieldParam));
		return self::GetObject(\$selectQuery, __CLASS__);
	}
EOS;
			$classCode[] = '';
			$classCode[] = $methodTemplate;
		}

		$extraSql = "";
		if (!empty($statusField)) {
			$extraSql .= sprintf(" WHERE %s.%s=1", $tableAlias, $statusField->name);
		}
		if (!empty($orderField)) {
			$extraSql .= sprintf(" ORDER BY %s.%s", $tableAlias, $orderField->name);
		}

		// Generate a sample data set method
		$methodTemplate = <<<EOS
	/**
	 * Returns all active objects
	 * @return ${objectCreateTypeName}[]
	 */
	public static function get${objectCreateTypeName}List() {
		\$selectQuery = SelectDataQuery::Create("SELECT " . self::\$selectList . " FROM " . self::\$tableName . " AS $tableAlias" . 
			"${extraSql}");
		return self::GetObjectSet(\$selectQuery, __CLASS__);
	}
EOS;
		$classCode[] = '';
		$classCode[] = $methodTemplate;
		$classCode[] = '// </editor-fold>';

		if (count($pkFields) == 1) {
			$idFieldName = $pkFields[0]->name;
			$idFieldParam = $fieldProperties[$idFieldName];
			$classCode[] = '';
			$classCode[] = '// <editor-fold defaultstate="collapsed" desc="Data update methods">';
			$classCode[] = <<<EOS
	/**
	 * Updates the object on the database
	 * @return OperationResult
	 */
EOS;
			$classCode[] = "	public function update${className}() {";
			$classCode[] = '		$dataSource = ' . $dbAccessCode . ";";
			$classCode[] = '		$isInsert = $this->' . $idFieldParam . ' <= 0;';
			$classCode[] = '		if ($isInsert) {';
			$classCode[] = '			$updQuery = UpdateDataQuery::CreateInsert(self::$tableName);';
			$classCode[] = '		} else {';
			$classCode[] = '			$updQuery = UpdateDataQuery::CreateUpdate(self::$tableName)';
			$classCode[] = '				->Where("' . $idFieldName . '", $this->' . $idFieldParam . ');';
			$classCode[] = '		}';
			$classCode[] = '		$updateResult = $updQuery';
			foreach ($fields as $field) {
				if ($field->name != $idFieldName) {
					$classCode[] = '			->Set("' . $field->name . '", $this->' . $fieldProperties[$field->name] . ')';
				}
			}
			$classCode[] = '			->ExecuteUpdate($dataSource);';
			$classCode[] = '';
			$classCode[] = '		if ($isInsert && $updateResult->success) {';
			$classCode[] = '			$this->' . $idFieldParam . ' = $updateResult->insertedID;';
			$classCode[] = '		}';
			$classCode[] = '';
			$classCode[] = '		return OperationResult::FromUpdateQueryResult($updateResult);';
			$classCode[] = '	}';
			$classCode[] = '// </editor-fold>';
		}

		$classCode[] = '';
		$classCode[] = '}';        //*** Generate and dump the code
		$code = implode("\n", $classCode);
		return $code;
	}
}
