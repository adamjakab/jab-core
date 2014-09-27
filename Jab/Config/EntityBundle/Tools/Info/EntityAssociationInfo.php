<?php
namespace Jab\Config\EntityBundle\Tools\Info;

use Doctrine\Common\Util\Inflector;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class EntityAssociationInfo
 */
class EntityAssociationInfo {
	/**
	 * @var string
	 */
	private $fieldName;

	/**
	 * @var bool
	 */
	private $id;

	/**
	 * @var integer
	 */
	private $type;

	/**
	 * @var integer
	 */
	private $fetch;

	/**
	 * @var string
	 */
	private $sourceEntity;

	/**
	 * @var string
	 */
	private $targetEntity;

	/**
	 * @var string
	 */
	private $mappedBy;

	/**
	 * @var string
	 */
	private $inversedBy;

	/**
	 * @var array
	 */
	private $joinTable = [];

	/**
	 * @var array
	 */
	private $joinColumns = [];

	/**
	 * @var array
	 */
	private $joinColumnFieldNames = [];

	/**
	 * @var array
	 */
	private $sourceToTargetKeyColumns = [];

	/**
	 * @var array
	 */
	private $targetToSourceKeyColumns = [];

	/**
	 * @var bool
	 */
	private $orphanRemoval = false;

	/**
	 * @var bool
	 */
	private $isOwningSide = false;

	/**
	 * @var array
	 */
	private $cascade = [];

	/**
	 * @var bool
	 */
	private $isCascadeRemove = false;

	/**
	 * @var bool
	 */
	private $isCascadePersist = false;

	/**
	 * @var bool
	 */
	private $isCascadeRefresh = false;

	/**
	 * @var bool
	 */
	private $isCascadeMerge = false;

	/**
	 * @var bool
	 */
	private $isCascadeDetach = false;

	/**
	 * @var array
	 */
	private $orderBy = [];

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

	/**
	 * @return string
	 */
	public function getFieldName() {
		return $this->fieldName;
	}

	/**
	 * @return boolean
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function getFetch() {
		return $this->fetch;
	}

	/**
	 * @return string
	 */
	public function getSourceEntity() {
		return $this->sourceEntity;
	}

	/**
	 * @return string
	 */
	public function getTargetEntity() {
		return $this->targetEntity;
	}

	/**
	 * @return string
	 */
	public function getMappedBy() {
		return $this->mappedBy;
	}

	/**
	 * @return string
	 */
	public function getInversedBy() {
		return $this->inversedBy;
	}

	/**
	 * @return array
	 */
	public function getJoinTable() {
		return $this->joinTable;
	}

	/**
	 * @return array
	 */
	public function getJoinColumns() {
		return $this->joinColumns;
	}

	/**
	 * @return array
	 */
	public function getJoinColumnFieldNames() {
		return $this->joinColumnFieldNames;
	}

	/**
	 * @return array
	 */
	public function getSourceToTargetKeyColumns() {
		return $this->sourceToTargetKeyColumns;
	}

	/**
	 * @return array
	 */
	public function getTargetToSourceKeyColumns() {
		return $this->targetToSourceKeyColumns;
	}

	/**
	 * @return boolean
	 */
	public function getOrphanRemoval() {
		return $this->orphanRemoval;
	}

	/**
	 * @return boolean
	 */
	public function getIsOwningSide() {
		return $this->isOwningSide;
	}

	/**
	 * @return array
	 */
	public function getCascade() {
		return $this->cascade;
	}

	/**
	 * @return boolean
	 */
	public function getIsCascadeDetach() {
		return $this->isCascadeDetach;
	}

	/**
	 * @return boolean
	 */
	public function getIsCascadeMerge() {
		return $this->isCascadeMerge;
	}

	/**
	 * @return boolean
	 */
	public function getIsCascadePersist() {
		return $this->isCascadePersist;
	}

	/**
	 * @return boolean
	 */
	public function getIsCascadeRefresh() {
		return $this->isCascadeRefresh;
	}

	/**
	 * @return boolean
	 */
	public function getIsCascadeRemove() {
		return $this->isCascadeRemove;
	}

	/**
	 * @return array
	 */
	public function getOrderBy() {
		return $this->orderBy;
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
	 * @return string
	 */
	public function getDocComment() {
		return $this->docComment;
	}

	/**
	 * @return array
	 */
	public function getMethods() {
		return $this->methods;
	}

	/**
	 * @return string
	 */
	public function getParentClass() {
		return $this->parentClass;
	}

	/**
	 * @return boolean
	 */
	public function getReadOnly() {
		return $this->readOnly;
	}


	//------------------------------------------------------------------------------------------------CALCULATED GETTERS
	/**
	 * @param string $type - normally "get" or "set" or "add" or "remove"
	 * @param string $infoname - normally "name", "body", "comment"
	 * @return string
	 */
	public function getMethodInfoData($type, $infoname) {
		return( (isset($this->methods[$type])&&isset($this->methods[$type][$infoname])) ? $this->methods[$type][$infoname] : "" );
	}

	/**
	 * A not owned association comes from an extended mapped Superclass
	 * @return bool
	 */
	public function isOwned() {
		return($this->declaringClass === $this->parentClass);
	}





	//---------------------------------------------------------------------------------------------------PRIVATE METHODS
	private function _setReflectionInfo() {
		if(!$this->declaringClass) {
			$IF = $this->_getKernel()->getContainer()->get("jab.config.entity.info_factory");
			$declaringReflClass = $IF->getDeclaringClassForProperty(new \ReflectionClass($this->parentClass), $this->getFieldName());
			if(!$declaringReflClass) {
				throw new \LogicException(sprintf("Unable to find the declaring class for this association(%s)!", $this->getFieldName()));
			} else {
				$this->declaringClass = $declaringReflClass->getName();
				if( ($rFld = $declaringReflClass->getProperty($this->getFieldName())) ) {
					$this->docComment = $rFld->getDocComment();
					$this->access = ($rFld->isPublic()?"public":($rFld->isProtected()?"protected":"private"));
				};
				//find getters, setters, adders and removers
				$this->methods["get"] = $IF->getDefaultMethodForField("GET", $this->getFieldName(), $declaringReflClass);
				$this->methods["set"] = $IF->getDefaultMethodForField("SET", $this->getFieldName(), $declaringReflClass);
				$this->methods["add"] = $IF->getDefaultMethodForField("ADD", $this->getFieldName(), $declaringReflClass);
				$this->methods["remove"] = $IF->getDefaultMethodForField("REMOVE", $this->getFieldName(), $declaringReflClass);
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
}