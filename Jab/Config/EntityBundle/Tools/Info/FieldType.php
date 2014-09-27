<?php
namespace Jab\Config\EntityBundle\Tools\Info;

use Doctrine\DBAL\Types\Type;

/**
 * Class FieldType - definitions
 */
class FieldType {

	/** @var array - http://doctrine-dbal.readthedocs.org/en/latest/reference/types.html#mapping-matrix */
	private static $_map = [
		//numeric
		Type::SMALLINT   => ["label"=>"Smallint(16 bit)", "hasLength"=>false, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>true, "canBeNullable"=>true],
		Type::INTEGER    => ["label"=>"Integer(32 bit)", "hasLength"=>false, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>true, "canBeNullable"=>true],
		Type::BIGINT     => ["label"=>"Bigint(64 bit)", "hasLength"=>false, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>true, "canBeNullable"=>true],
		Type::DECIMAL    => ["label"=>"Decimal", "hasLength"=>false, "hasScale"=>true, "hasPrecision"=>true, "canBeUnique"=>true, "canBeNullable"=>true],
		Type::FLOAT      => ["label"=>"Float", "hasLength"=>false, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>true, "canBeNullable"=>true],
		//text
		Type::STRING     => ["label"=>"String", "hasLength"=>true, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>true, "canBeNullable"=>true],
		Type::TEXT       => ["label"=>"Text", "hasLength"=>true, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>false, "canBeNullable"=>true],
		//binary(from doctrine\dbal 2.4) & blob
		/*
		"binary"         => ["label"=>"Binary", "hasLength"=>true, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>false, "canBeNullable"=>true],
		*/
		Type::BLOB       => ["label"=>"Blob", "hasLength"=>true, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>false, "canBeNullable"=>true],
		//
		Type::BOOLEAN    => ["label"=>"Boolean", "hasLength"=>false, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>false, "canBeNullable"=>true],
		//date
		Type::DATE       => ["label"=>"Date", "hasLength"=>false, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>true, "canBeNullable"=>true],
		Type::TIME       => ["label"=>"Time", "hasLength"=>false, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>true, "canBeNullable"=>true],
		Type::DATETIME   => ["label"=>"DateTime", "hasLength"=>false, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>true, "canBeNullable"=>true],
		//complex
		Type::TARRAY     => ["label"=>"Array", "hasLength"=>true, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>false, "canBeNullable"=>true],
		Type::JSON_ARRAY => ["label"=>"Json Array", "hasLength"=>true, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>false, "canBeNullable"=>true],
		Type::OBJECT     => ["label"=>"Object", "hasLength"=>true, "hasScale"=>false, "hasPrecision"=>false, "canBeUnique"=>false, "canBeNullable"=>true]
	];

	/**
	 * @param $name
	 * @return array
	 * @throws \Exception
	 */
	public static function getTypeDefinition($name) {
		if (!isset(self::$_map[$name])) {
			throw new \Exception(sprintf("Unknown type(%s)!",$name));
		}
		return self::$_map[$name];
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public static function hasTypeDefinition($name) {
		return isset(self::$_map[$name]);
	}

	/**
	 * @return array
	 */
	public static function getDefinitionList() {
		$answer = [];
		foreach(self::$_map as $type => $FD) {
			$answer[$type] = $FD["label"];
		}
		return($answer);
	}

	/**
	 * @return array
	 */
	public static function getDefinitionMap() {
		return(self::$_map);
	}

}
