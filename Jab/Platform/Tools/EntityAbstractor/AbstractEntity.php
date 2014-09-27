<?php
namespace Jab\Platform\Tools\EntityAbstractor;

use Jab\Config\EntityBundle\Tools\Info\EntityFieldInfo;
use Jab\Config\EntityBundle\Tools\Info\EntityInfo;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class AbstractEntity -
 * The idea is to have a very lightweight class from which with the aid of the EntityInfo class
 * we can accommodate any entity
 */
class AbstractEntity {
	/**
	 * @var string
	 */
	private $_entityName;

	/**
	 * @var ArrayCollection
	 */
	private $_displayFields;

	/**
	 * @var ArrayCollection
	 */
	private $_fields = [];

	/**
	 * @var bool - when false fields with empty value will NOT be added
	 */
	private $_addFieldsWithValuesOnly = true;

	/**
	 * @param object $entityObject
	 * @param EntityInfo $entityInfo
	 * @param ArrayCollection $displayFields
	 * @param boolean $addFieldsWithValuesOnly
	 */
	public function __construct($entityObject, $entityInfo, $displayFields, $addFieldsWithValuesOnly=true) {
		$this->_addFieldsWithValuesOnly = $addFieldsWithValuesOnly;
		$this->_setupEntity($entityObject, $entityInfo);
		$this->_setFieldsData($entityObject, $entityInfo, $displayFields);
	}

	/**
	 * @param string $fieldName
	 * @return mixed|null
	 */
	public function __get($fieldName) {
		return($this->getFieldAttribute($fieldName, "value"));
	}

	/**
	 * @param string $fieldName
	 * @param mixed $fieldValue
	 */
	public function __set($fieldName, $fieldValue) {
		$this->setFieldAttribute($fieldName, "value", $fieldValue);
	}

	/**
	 * @param string $fieldName
	 * @return bool
	 */
	public function hasField($fieldName) {
		return($this->_fields->containsKey($fieldName));
	}

	/**
	 * @param string $fieldName
	 * @return array|bool
	 */
	public function getField($fieldName) {
		$answer = false;
		if($this->hasField($fieldName)) {
			$answer = $this->_fields->get($fieldName);
		}
		return($answer);
	}

	/**
	 * @param string $fieldName
	 * @param string $attribute
	 * @param mixed $default
	 * @return mixed|null
	 */
	public function getFieldAttribute($fieldName, $attribute, $default = null) {
		$answer = $default;
		if( ($field = $this->getField($fieldName)) ) {
			if (array_key_exists($attribute, $field)) {
				$answer = $field[$attribute];
			}
		}
		return($answer);
	}

	/**
	 * @param string $fieldName
	 * @param string $attribute
	 * @param mixed $value
	 */
	public function setFieldAttribute($fieldName, $attribute, $value) {
		if( ($field = $this->getField($fieldName)) ) {
			$field[$attribute] = $value;
			$this->_fields->set($fieldName, $field);
		}
	}

	/**
	 * Convenience method
	 * @return mixed|null
	 */
	public function getId() {
		return($this->getFieldAttribute("id", "value"));
	}

	/**
	 * Convenience method
	 * @return string
	 */
	public function getName() {
		$answer = "";
		$possibleNameFields = ["name", "title", "label"];
		foreach($possibleNameFields as $fldName) {
			if ( ($field = $this->getField($fldName)) ) {
				$answer = $field["value"];
				break;
			}
		}
		return($answer);
	}

	/**
	 * @return string
	 */
	public function getEntityName() {
		return $this->_entityName;
	}


	/**
	 * @param object $entityObject
	 * @param EntityInfo $entityInfo
	 * @param ArrayCollection $displayFields
	 */
	private function _setFieldsData($entityObject, $entityInfo, $displayFields) {
		$this->_displayFields = $displayFields;

		//always set Id fields
		foreach($entityInfo->getIdFields() as $fieldName) {
			if ( ($field = $entityInfo->getField($fieldName)) ) {
				$fieldValue = null;
				if(gettype($entityObject) == "object" && is_callable([$entityObject, $field->getMethodInfoData("get", "name")])) {
					$fieldValue = call_user_func([$entityObject, $field->getMethodInfoData("get", "name")]);
				}
				$this->_setField($fieldName, $field->getType(), $fieldValue, $field->getMethodInfoData("get", "name"), $field->getMethodInfoData("set", "name"));
			}
		}

		//set fields specified in  $displayFields
		foreach($this->_displayFields as $fieldName) {
			if ( ($field = $entityInfo->getField($fieldName)) ) {
				$fieldValue = null;
				if(gettype($entityObject) == "object" && is_callable([$entityObject, $field->getMethodInfoData("get", "name")])) {
					$fieldValue = call_user_func([$entityObject, $field->getMethodInfoData("get", "name")]);
				}
				$this->_setField($fieldName, $field->getType(), $fieldValue, $field->getMethodInfoData("get", "name"), $field->getMethodInfoData("set", "name"));
			} else {
				//this could be a getter method name
				$methodName = 'get' . $fieldName;//todo: fix this!
				if ( ($customMethod = $entityInfo->getCustomMethod($methodName)) ) {
					$getterMethodName = $customMethod["name"];
					$fieldValue = null;
					if(gettype($entityObject) == "object" && is_callable([$entityObject, $getterMethodName])) {
						$fieldValue = call_user_func([$entityObject, $getterMethodName]);
					}
					//todo:!!! gettype($fieldValue) is php type - need to map this because above/below we have doctrine types
					$this->_setField($fieldName, gettype($fieldValue), $fieldValue, $getterMethodName, "");
				}
			}
		}

		/*If display fields have not been specified - let's add them all*/
		if(!$this->_displayFields->count()) {
			/** @var EntityFieldInfo $field */
			foreach($entityInfo->getFields() as $field) {
				$fieldValue = null;
				if(gettype($entityObject) == "object" && is_callable([$entityObject, $field->getMethodInfoData("get", "name")])) {
					$fieldValue = call_user_func([$entityObject, $field->getMethodInfoData("get", "name")]);
				}
				$this->_setField($field->getFieldName(), $field->getType(), $fieldValue, $field->getMethodInfoData("get", "name"), $field->getMethodInfoData("set", "name"));
			}
		}
	}

	private function _setField($name, $type, $value, $getter, $setter) {
		if($value || $this->_addFieldsWithValuesOnly === false) {
			$fieldData = [
				"name" => $name,
				"type" => $type,
				"value" => $value,
				"getter" => $getter,
				"setter" => $setter
			];
			$this->_fields->set($name, $fieldData);
		}
	}


	/**
	 * @param object $entityObject
	 * @param EntityInfo $entityInfo
	 */
	private function _setupEntity($entityObject, $entityInfo) {
		$this->_entityName = $entityInfo->getEntityName();
		$this->_fields = new ArrayCollection();
	}



	//----------------------------------------------------------------------------------------UPDATING REAL ENTITY
	/**
	 * @param object $entityObject
	 * @param EntityInfo $entityInfo
	 */
	public function updateEntityObjectValues($entityObject, $entityInfo) {
		/** @var EntityFieldInfo $field */
		foreach($entityInfo->getFields() as $field) {
			//DO NOT UPDATE ID FIELD ON ENTITY
			if($field->isId()) {continue;}
			$fieldName = $field->getFieldName();
			if( ($abstractField = $this->getField($fieldName)) ) {
				$entitySetterMethod = [$entityObject, $abstractField["setter"]];
				if(is_callable($entitySetterMethod)) {
					call_user_func($entitySetterMethod, $abstractField["value"]);
				}
			}
		}
	}


}