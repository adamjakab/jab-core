<?php
namespace Jab\Config\EntityBundle\Tools\Info;

use Doctrine\Common\Util\Inflector;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class JabEntityFieldInfo
 *
 * @Assert\Callback(methods={"classValidator"})
 */
class EntityFieldInfo {
	/**
	 * @Assert\NotBlank()
	 * @Assert\Regex(pattern="#^[a-zA-Z_][a-zA-Z0-9_]*$#", message="Invalid field name!")
	 * @var string
	 */
	private $fieldName;

	/**
	 * @Assert\Regex(pattern="#^[a-z_][a-z0-9_]*$#", message="Invalid column name!")
	 * @var string
	 */
	private $columnName;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var int
	 */
	private $length;

	/**
	 * @var boolean
	 */
	private $id = false;

	/**
	 * @var mixed
	 */
	private $default;

	/**
	 * @var boolean
	 */
	private $nullable = true;

	/**
	 * @var string
	 */
	private $columnDefinition;

	/**
	 * @var int
	 */
	private $precision;

	/**
	 * @var int
	 */
	private $scale;

	/**
	 * @var boolean
	 */
	private $unique = false;

	/**
	 * @var string - the name of the class which declares this field
	 */
	private $declaringClass;

	/**
	 * @var string - the name of the class on which this field is being listed (some descendant of the $declaringClass)
	 */
	private $parentClass;

	/**
	 * @var string - (private, protected, public)
	 */
	private $access = 'private';

	/**
	 * @var bool
	 */
	private $readOnly = false;

	/**
	 * @var string
	 */
	private $docComment;

	/**
	 * @var array - info about getter/setter methods
	 */
	private $methods = [];



	/**
	 * @param array $mappingData
	 * @throws \LogicException
	 */
	public function __construct($mappingData=[]) {
		if(!isset($mappingData["parentClass"]) || empty($mappingData["parentClass"])) {
			throw new \LogicException("You must declare the parentClass when creating an EntityFieldInfo instance!");
		}
		foreach($mappingData as $propName => $propValue) {
			if (property_exists($this, $propName)) {
				$this->$propName = $propValue;
			}
		}
		$this->_setReflectionInfo();
	}



	//@todo: needs uniqueness validation
	/**
	 * @param string $fieldName
	 * @throws \Exception
	 */
	public function setFieldName($fieldName) {
		$this->fieldName = $fieldName;
		if(!$this->columnName) {
			$this->setColumnName($fieldName);
		}
	}

	/**
	 * @return string
	 */
	public function getFieldName() {
		return $this->fieldName;
	}

	/**
	 * @return string
	 */
	public function getFieldLabel() {
		return ucwords(strtolower(str_replace("_"," ", $this->fieldName)));
	}


	//@todo: needs uniqueness validation
	/**
	 * @param string $columnName
	 * @throws \Exception
	 */
	public function setColumnName($columnName) {
		$this->columnName = strtolower($columnName);
	}

	/**
	 * @return string
	 */
	public function getColumnName() {
		return $this->columnName;
	}

	/**
	 * @param boolean $id
	 */
	public function setId($id) {
		$this->id = ($id===1 || $id===true);
	}

	/**
	 * @return boolean
	 */
	public function isId() {
		return $this->id;
	}

	/**
	 * @param mixed $default
	 */
	public function setDefault($default) {
		$this->default = $default;
	}

	/**
	 * @return mixed
	 */
	public function getDefault() {
		return $this->default;
	}

	/**
	 * @param int $length
	 */
	public function setLength($length) {
		$this->length = $length;
	}

	/**
	 * @return int
	 */
	public function getLength() {
		return $this->length;
	}

	/**
	 * @param boolean $nullable
	 */
	public function setNullable($nullable) {
		$this->nullable = ($nullable===1 || $nullable===true);
	}

	/**
	 * @return boolean
	 */
	public function isNullable() {
		return $this->nullable;
	}

	/**
	 * @param int $precision
	 */
	public function setPrecision($precision) {
		$this->precision = $precision;
	}

	/**
	 * @return int
	 */
	public function getPrecision() {
		return $this->precision;
	}

	/**
	 * @param int $scale
	 */
	public function setScale($scale) {
		$this->scale = $scale;
	}

	/**
	 * @return int
	 */
	public function getScale() {
		return $this->scale;
	}

	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param boolean $unique
	 */
	public function setUnique($unique) {
		$this->unique = ($unique===1 || $unique===true);
	}

	/**
	 * @return boolean
	 */
	public function isUnique() {
		return $this->unique;
	}

	/**
	 * @param string $columnDefinition
	 */
	public function setColumnDefinition($columnDefinition) {
		$this->columnDefinition = $columnDefinition;
	}

	/**
	 * @return string
	 */
	public function getColumnDefinition() {
		return $this->columnDefinition;
	}

	/**
	 * @param string $access
	 */
	public function setAccess($access) {
		$this->access = $access;
	}

	/**
	 * @return string
	 */
	public function getAccess() {
		return $this->access;
	}

	/**
	 * @return string
	 */
	public function getDeclaringClass() {
		return $this->declaringClass;
	}

	/**
	 * @param boolean $readOnly
	 */
	public function setReadOnly($readOnly) {
		$this->readOnly = $readOnly;
	}

	/**
	 * @return boolean
	 */
	public function isReadOnly() {
		return $this->readOnly;
	}

	/**
	 * @return string
	 */
	public function getDocComment() {
		return $this->docComment;
	}


	//------------------------------------------------------------------------------------------------CALCULATED GETTERS

	/**
	 * @param string $type - normally "get" or "set"
	 * @param string $infoname - normally "name", "body", "comment"
	 * @return string
	 */
	public function getMethodInfoData($type, $infoname) {
		return( (isset($this->methods[$type])&&isset($this->methods[$type][$infoname])) ? $this->methods[$type][$infoname] : "" );
	}

	/**
	 * A field can be non-editable for two reasons:
	 * 1) it is declared on another class (on a mapped Superclass)
	 * 2) it has been listed on JAB\Entity(...,readOnlyFields={"fld1","fld2"},...) and therefore declared read-only
	 * @return bool
	 */
	public function isEditable() {
		return($this->isOwned() && !$this->isReadOnly());
	}

	/**
	 * A not owned field comes from an extended mapped Superclass
	 * @return bool
	 */
	public function isOwned() {
		return($this->declaringClass === $this->parentClass);
	}

	/**
	 * @return string
	 */
	public function getDeclaringClassName() {
		$NSA = explode("\\", $this->declaringClass);
		return array_pop($NSA);
	}

	/**
	 * Returns the mappingData array as usually found in ClassMetadata::fieldMappings
	 * @return array

	public function getMappingArray() {
		$answer = [];
		if($this->fieldName) {$answer["fieldName"]=$this->fieldName;}
		if($this->columnName) {$answer["columnName"]=$this->columnName;}
		if($this->type) {$answer["type"]=$this->type;}
		if($this->length) {$answer["length"]=$this->length;}
		if($this->id) {$answer["id"]=$this->id;}
		if($this->default) {$answer["default"]=$this->default;}
		if($this->nullable) {$answer["nullable"]=$this->nullable;}
		if($this->precision) {$answer["precision"]=$this->precision;}
		if($this->scale) {$answer["scale"]=$this->scale;}
		if($this->unique) {$answer["unique"]=$this->unique;}
		if($this->access) {$answer["access"]=$this->access;}
		return($answer);
	}	 */



	//---------------------------------------------------------------------------------------------------PRIVATE METHODS
	/**
	 * @throws \LogicException
	 */
	private function _setReflectionInfo() {
		if(!$this->declaringClass) {
			$IF = $this->_getKernel()->getContainer()->get("jab.config.entity.info_factory");
			$declaringReflClass = $IF->getDeclaringClassForProperty(new \ReflectionClass($this->parentClass), $this->getFieldName());


			if(!$declaringReflClass) {
				throw new \LogicException(sprintf("Unable to find the declaring class for this field(%s)!", $this->getFieldName()));
			} else {
				$this->declaringClass = $declaringReflClass->getName();
				if( ($rFld = $declaringReflClass->getProperty($this->getFieldName())) ) {
					$this->docComment = $rFld->getDocComment();
					$this->access = ($rFld->isPublic()?"public":($rFld->isProtected()?"protected":"private"));
				};
				//find getters and setters
				$this->methods["get"] = $IF->getDefaultMethodForField("GET", $this->getFieldName(), $declaringReflClass);
				$this->methods["set"] = $IF->getDefaultMethodForField("SET", $this->getFieldName(), $declaringReflClass);
			}
		}
	}

	/**
	 * @return KernelInterface
	 */
	private function _getKernel() {
		global $kernel;
		if ('AppCache' == get_class($kernel)) {
			/** @var \AppCache $kernel */
			$kernel = $kernel->getKernel();
		}
		return($kernel);
	}

	//---------------------------------------------------------------------------------------------------------VALIDATOR
	/**
	 * @param ExecutionContextInterface $context
	 */
	public function classValidator(ExecutionContextInterface $context) {
		if($this->isId()) {
			if($this->getFieldName() != "id" || $this->getColumnName() != "id") {
				$context->buildViolation("You cannot do this operation on a %param% field!")
					->setParameter("%param%","ID")
					->addViolation();
			}
		}
	}
}