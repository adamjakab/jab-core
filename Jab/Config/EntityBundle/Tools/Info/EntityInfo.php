<?php
namespace Jab\Config\EntityBundle\Tools\Info;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Reflection\Psr0FindFile;
use Doctrine\Common\Reflection\StaticReflectionParser;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Jab\Config\EntityBundle\Event\EntityChangeEvent;
use Jab\Config\EntityBundle\Event\PlatformEvents;
use Jab\Config\EntityBundle\Annotation\Entity as JabAnnotationEntity;

/**
 * EntityInfo
 *
 * Given a FQCN entity class name to the constructor, this class will gather all information needed to be able to
 * modify the entity in all its aspects and to (re)generate the entity code after modification.
 */
class EntityInfo {

	/**
	 * @var string - the FQCN of the entity
	 */
	private $entityName;

	/**
	 * @var string
	 */
	private $namespace;

	/**
	 * @var string
	 */
	private $className;

	/**
	 * @var string
	 */
	private $extendedClass;

	/**
	 * @var string - full path to entity file
	 */
	private $entityPath;

	/**
	 * @var string
	 */
	private $entityFileHash;

	/**
	 * @var string - [path to application]/(src|vendor|???)
	 */
	private $vendorDir;

	/**
	 * @var string
	 */
	private $bundleName;

	/**
	 * @var string
	 */
	private $bundleNamespace;

	/**
	 * @var string
	 */
	private $bundleDir;

	/**
	 * @var boolean
	 */
	private $inSync = false;

	/**
	 * List of sql commands to bring entity in sync with db
	 * @var array
	 */
	private $sqlToSync = [];

	/**
	 * @var string
	 */
	private $customRepositoryClassName;

	/**
	 * @var boolean
	 */
	private $isMappedSuperclass = false;

	/**
	 * The inheritance mapping type used by the class.
	 * @var integer
	 */
	private $inheritanceType;

	/**
	 * The Id generator type used by the class
	 * @var integer
	 */
	private $generatorType;

	/**
	 * The discriminator map of all mapped classes in the hierarchy
	 * @var mixed
	 */
	private $discriminatorMap;

	/**
	 * The definition of the discriminator column used in JOINED and SINGLE_TABLE inheritance mappings.
	 * @var mixed
	 */
	private $discriminatorColumn;

	/**
	 * @Assert\NotBlank()
	 * @Assert\Regex(pattern="#^[a-zA-Z_][a-zA-Z0-9_]*$#", message="Invalid name!")
	 * @var string
	 */
	private $databaseTableName;

	/**
	 * @var string
	 */
	private $databaseTableSchema;

	/**
	 * @var array
	 */
	private $databaseTableIndexes = [];

	/**
	 * @var array
	 */
	private $databaseTableUniqueConstraints = [];

	/**
	 * @var array
	 */
	private $databaseTableOptions = [];

	/**
	 * @var array
	 */
	private $databaseTableStatus = [];

	/**
	 * The registered lifecycle callbacks for entities of this class
	 * @var array
	 */
	private $lifecycleCallbacks = [];

	/**
	 * The association mappings of this class - @todo: 2B removed
	 * @var array
	 */
	//private $associationMappings = [];

	/**
	 * The definition of the sequence generator of this class. Only used for the SEQUENCE generation strategy
	 * @var array
	 */
	private $sequenceGeneratorDefinition;

	/**
	 * @var JabAnnotationEntity
	 */
	private $jabAnnotationInfo;

	/**
	 * @var array
	 */
	private $reflectionInfo = [];

	/**
	 * @var ArrayCollection
	 */
	private $fields;

	/**
	 * @var ArrayCollection
	 */
	private $associations;

	/**
	 * @param string|null $entityName - FQCN(Fully Qualified Class Name) or Null in new entity
	 * @param boolean $isNew - set up class for a brand new entity
	 */
	public function __construct($entityName, $isNew = false) {
		$this->entityName = $entityName;
		if($isNew) {
			$this->_setupNewEntity();
		} else {
			$this->_checkEntityName($entityName);
			$this->_setPathInfo();
			$this->_setReflectionInfo();
			$this->_setJabAnnotationInfo();
			$this->_setBundleInfo();
			$this->_setClassMetadataInfo();
			$this->_setFields();
			$this->_setAssociations();
			$this->_setReflectionCustomInfo();
			$this->_setSyncState();
			$this->_updateStatabaseTableStatus();
		}
	}

	/**
	 * @return array
	 */
	public function __sleep() {
		$answer = [];
		$bannedProperties = ["databaseTableStatus"];
		foreach(get_object_vars($this) as $propName => $propValue) {
			if (!in_array($propName, $bannedProperties)) {
				$answer[] = $propName;
			}
		}
		return($answer);
	}

	/**
	 *
	 */
	public function __wakeup() {
		//$this->_setSyncState(); - why not this one as well?
		$this->_updateStatabaseTableStatus();
	}



	//-----------------------------------------------------------------------------------------------------------GETTERS
	/**
	 * @return string
	 */
	public function getEntityName() {
		return $this->entityName;
	}

	/**
	 * @return string
	 */
	public function getNamespace() {
		return $this->namespace;
	}

	/**
	 * @return string
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * @return string
	 */
	public function getEntityPath() {
		return $this->entityPath;
	}

	/**
	 * @return string
	 */
	public function getVendorDir() {
		return $this->vendorDir;
	}

	/**
	 * @return string
	 */
	public function getBundleName() {
		return $this->bundleName;
	}

	/**
	 * @return string
	 */
	public function getBundleNamespace() {
		return $this->bundleNamespace;
	}

	/**
	 * @return string
	 */
	public function getBundleDir() {
		return $this->bundleDir;
	}

	/**
	 * @return boolean
	 */
	public function isInSync() {
		return $this->inSync;
	}

	/**
	 * @return array
	 */
	public function getSqlToSync() {
		return $this->sqlToSync;
	}

	/**
	 * @return string
	 */
	public function getCustomRepositoryClassName() {
		return $this->customRepositoryClassName;
	}

	/**
	 * @return boolean
	 */
	public function isMappedSuperclass() {
		return $this->isMappedSuperclass;
	}

	/**
	 * @return int
	 */
	public function getInheritanceType() {
		return $this->inheritanceType;
	}

	/**
	 * @return int
	 */
	public function getGeneratorType() {
		return $this->generatorType;
	}

	/**
	 * @return mixed
	 */
	public function getDiscriminatorMap() {
		return $this->discriminatorMap;
	}

	/**
	 * @return mixed
	 */
	public function getDiscriminatorColumn() {
		return $this->discriminatorColumn;
	}

	/**
	 * @return array
	 */
	public function getLifecycleCallbacks() {
		return $this->lifecycleCallbacks;
	}

	/**
	 * @return array

	public function getAssociationMappings() {
		return $this->associationMappings;
	}*/

	/**
	 * @return array
	 */
	public function getSequenceGeneratorDefinition() {
		return $this->sequenceGeneratorDefinition;
	}

	/**
	 * @return string
	 */
	public function getDatabaseTableName() {
		return $this->databaseTableName;
	}

	/**
	 * @return string
	 */
	public function getDatabaseTableSchema() {
		return $this->databaseTableSchema;
	}

	/**
	 * @return array
	 */
	public function getDatabaseTableIndexes() {
		return $this->databaseTableIndexes;
	}

	/**
	 * @return array
	 */
	public function getDatabaseTableUniqueConstraints() {
		return $this->databaseTableUniqueConstraints;
	}

	/**
	 * @return array
	 */
	public function getDatabaseTableOptions() {
		return $this->databaseTableOptions;
	}

	/**
	 * @return string
	 */
	public function getExtendedClass() {
		return $this->extendedClass;
	}

	/**
	 * @return ArrayCollection
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * @param string $fieldName
	 * @return EntityFieldInfo|null
	 */
	public function getField($fieldName) {
		return($this->fields->get($fieldName));
	}

	/**
	 * @return ArrayCollection
	 */
	public function getAssociations() {
		return $this->associations;
	}

	/**
	 * @param string $fieldName
	 * @return EntityAssociationInfo|null
	 */
	public function getAssociation($fieldName) {
		return($this->associations->get($fieldName));
	}

	//---------------------------------------------------------------------------------------------------COMPLEX GETTERS

	/**
	 * All saved entities (a class file exists) will have an entityFileHash
	 * @return bool
	 */
	public function isUnsavedEntity() {
		return(empty($this->entityFileHash));
	}

	/**
	 * @return bool
	 */
	public function isEditable() {
		return(!in_array($this->getType(), ["SYSTEM", "UNMANAGED"]));
	}

	/**
	 * The use statements extracted from the reflection info
	 * @return array
	 */
	public function getUseStatements() {
		return (isset($this->reflectionInfo["use_statements"])?$this->reflectionInfo["use_statements"]:[]);
	}

	/**
	 * The doc comments extracted from the reflection info
	 * @return string
	 */
	public function getDocComment() {
		return (isset($this->reflectionInfo["doc_comment"])?$this->reflectionInfo["doc_comment"]:"");
	}


	/**
	 * All methods extracted from the reflection info which are not related to any property
	 * @return array - methodName indexed array with name, body, comment keys
	 */
	public function getCustomMethods() {
		return (isset($this->reflectionInfo["customMethods"])?$this->reflectionInfo["customMethods"]:[]);
	}

	/**
	 * Specific custom method getter
	 * @param $methodName
	 * @return array|bool
	 */
	public function getCustomMethod($methodName) {
		return (isset($this->reflectionInfo["customMethods"])&&isset($this->reflectionInfo["customMethods"][$methodName])?$this->reflectionInfo["customMethods"][$methodName]:false);
	}




	/**
	 * Returns array of identifier fields
	 * @return array
	 */
	public function getIdFields() {
		$answer = [];
		/** @var EntityFieldInfo $field */
		foreach($this->fields as $field) {
			if($field->isId()) {
				array_push($answer, $field->getFieldName());
			}
		}
		return($answer);
	}

	/**
	 * @param string $fieldName
	 * @return bool
	 */
	public function hasField($fieldName) {
		$answer = $this->getField($fieldName);//by key
		if(!$answer) {
			/** @var EntityFieldInfo $field */
			foreach($this->fields as $field) {
				if($field->getFieldName() == $fieldName) {
					$answer = true;
					break;
				}
			}
		}
		return($answer);
	}

	/**
	 * @param string $fieldName
	 * @return bool
	 */
	public function hasAssociation($fieldName) {
		$answer = $this->getAssociation($fieldName);//by key
		if(!$answer) {
			/** @var EntityAssociationInfo $association */
			foreach($this->associations as $association) {
				if($association->getFieldName() == $fieldName) {
					$answer = true;
					break;
				}
			}
		}
		return($answer);
	}



	/**
	 * @param string $columnName
	 * @return bool
	 */
	public function hasDatabaseColumn($columnName) {
		$answer = false;
		/** @var EntityFieldInfo $field */
		foreach($this->fields as $field) {
			if($field->getColumnName() == $columnName) {
				$answer = true;
				break;
			}
		}
		return($answer);
	}

	/**
	 * @return int
	 */
	public function countFields() {
		return $this->fields->count();
	}

	/**
	 * @return int
	 */
	public function countAssociations() {
		return $this->associations->count();
	}

	/**
	 * @return string
	 */
	public function getType() {
		return ($this->jabAnnotationInfo?$this->jabAnnotationInfo->getType():"UNMANAGED");
	}

	/**
	 * @return boolean
	 */
	public function isManagedEntity() {
		return ($this->jabAnnotationInfo?$this->jabAnnotationInfo->isManagedEntity():false);
	}

	/**
	 * The method name is such so to underline that this array of fields comes from the Jab Annotation declaration
	 * and not from the fields list where read-only is more complex
	 * @return array
	 */
	public function getDeclaredReadOnlyFieldList() {
		return ($this->jabAnnotationInfo?$this->jabAnnotationInfo->getReadOnlyFields():[]);
	}

	/**
	 * @return ClassMetadata
	 */
	public function getMetadata() {
		return($this->_getClassMetadata());
	}

	/**
	 * Used to check if file hash value saved into cached class info corresponds to fresh hash
	 * If not, it means that cached file is out of date and should be recreated
	 * @return bool
	 */
	public function isFileHashStillValid() {
		return( (!empty($this->entityPath) && hash_file("crc32", $this->entityPath) == $this->entityFileHash));
	}

	/**
	 * @return array
	 */
	public function getDatabaseTableStatus() {
		return $this->databaseTableStatus;
	}

	/**
	 * @param $prop
	 * @return mixed
	 */
	public function getDatabaseTableStatusProperty($prop) {
		return (isset($this->databaseTableStatus[$prop])?$this->databaseTableStatus[$prop]:"");
	}


	//-----------------------------------------------------------------------------------------------------------SETTERS
	/**
	 * @param string $extendedClass
	 */
	public function setExtendedClass($extendedClass) {
		$this->extendedClass = $extendedClass;
	}



	//---------------------------------------------------------------------------------------------------COMPLEX SETTERS

	/**
	 * Set if the entity should have a repository (ONLY NEW UNSAVED ENTITIES!)
	 * @param bool $hasRepository
	 */
	public function setHasRepository($hasRepository) {
		if ($this->isUnsavedEntity()) {
			if ($hasRepository) {
				$this->customRepositoryClassName = $this->namespace . '\\' . $this->className . 'Repository';
			} else {
				$this->customRepositoryClassName = null;
			}
		}
	}

	/**
	 * @return bool
	 */
	public function hasRepository() {
		return(!empty($this->customRepositoryClassName));
	}

	/**
	 * Set the class name and all related info for new entities (ONLY NEW UNSAVED ENTITIES!)
	 * @param string $className
	 */
	public function setClassName($className) {
		if ($this->isUnsavedEntity()) {
			$this->className = $className;
			$this->entityName = $this->namespace . '\\' . $this->className;
			$this->entityPath = $this->vendorDir . '/' . str_replace("\\", "/", $this->entityName) . '.php';
			//$this->customRepositoryClassName = $this->namespace . '\\' . $this->className . 'Repository';
			$this->setHasRepository($this->hasRepository());
		}
	}

	/**
	 * Set the bundle name and all related info for new entities (ONLY NEW UNSAVED ENTITIES!)
	 * @param string $bundleName
	 */
	public function setBundleName($bundleName) {
		if($this->isUnsavedEntity()) {
			$bundleInterface = $this->_getKernel()->getBundle($bundleName);
			$this->bundleName = $bundleInterface->getName();
			$this->bundleNamespace = $bundleInterface->getNamespace();
			$this->bundleDir = $bundleInterface->getPath();
			$this->namespace = $this->bundleNamespace . '\Entity';
			$this->vendorDir = str_replace("/".str_replace("\\","/", $this->bundleNamespace), "", $this->bundleDir);
		}
	}


	/**
	 * Changing the database table name will have immediate effect by
	 * executing change on database table and updating entity class file
	 * @param string $tableName
	 */
	public function setDatabaseTableName($tableName) {
		if(!$this->isUnsavedEntity()) {
			if($tableName != $this->databaseTableName) {
				//Let SchemaManager do the renaming
				/** @var EntityManager $em */
				$em = $this->_getKernel()->getContainer()->get("doctrine")->getManager();
				$sm = $em->getConnection()->getSchemaManager();
				$sm->renameTable($this->databaseTableName,$tableName);
			}
		}

		$this->databaseTableName = $tableName;

		if(!$this->isUnsavedEntity()) {
			//notify listeners about this
			//$dispatcher = $this->_getKernel()->getContainer()->get("event_dispatcher");
			//$event = new EntityChangeEvent($this);
			//$dispatcher->dispatch(PlatformEvents::JAB_ENTITY_UPDATE, $event);
		}
	}

	/**
	 * @param array $fieldMappingData
	 * @return EntityFieldInfo
	 */
	public function addField($fieldMappingData=[]) {
		$fieldMappingData['parentClass'] = $this->getEntityName();
		$entityFieldInfo = new EntityFieldInfo($fieldMappingData);
		$this->fields->set($entityFieldInfo->getFieldName(), $entityFieldInfo);
		return($entityFieldInfo);
	}

	/**
	 * @param array $assocMappingData
	 * @return EntityAssociationInfo
	 */
	public function addAssociation($assocMappingData=[]) {
		$assocMappingData['parentClass'] = $this->getEntityName();
		$entityAssociationInfo = new EntityAssociationInfo($assocMappingData);
		$this->associations->set($entityAssociationInfo->getFieldName(), $entityAssociationInfo);
		return($entityAssociationInfo);
	}


	/**
	 * @param string $fieldName
	 * @throws \LogicException
	 */
	public function removeField($fieldName) {
		$field = $this->getField($fieldName);
		if(!$field) {
			throw new \LogicException(sprintf("There is no field by this name(%s)!", $fieldName));
		} else if(!$field->isEditable()) {
			throw new \LogicException(sprintf("This field(%s) cannot be removed!", $fieldName));
		} else if(strtolower($fieldName) == "id") {
			throw new \LogicException("The default identity field 'id' cannot be deleted!");
		}
		//$this->fields->remove($fieldName);
		$this->fields->removeElement($field);
	}



	//---------------------------------------------------------------------------------------------------PRIVATE METHODS

	/**
	 * This is a modified version of SchemaTool::getUpdateSchemaSql
	 * It considers only the current Entity (by feeding only its own database table in the $tables list)
	 * and checks if there are any SQLs to execute.
	 */
	private function _setSyncState() {
		/** @var EntityManager $em */
		$em = $this->_getKernel()->getContainer()->get("doctrine")->getManager();
		$sm = $em->getConnection()->getSchemaManager();
		$st = new SchemaTool($em);

		//FROM Schema
		$tables = [];
		if($sm->tablesExist([$this->getDatabaseTableName()])) {
			$tables[] = $sm->listTableDetails($this->getDatabaseTableName());
		}
		$sequences = ($sm->getDatabasePlatform()->supportsSequences()?$sm->listSequences():[]);
		$fromSchema = new Schema($tables, $sequences, $sm->createSchemaConfig());


		//TO Schema
		$toSchema = $st->getSchemaFromMetadata([$this->_getClassMetadata()]);
		/* Classes like JMS\JobQueueBundle\Entity\Listener\ManyToAnyListener::postGenerateSchema add custom(not entity defined)
		 * database tables to the schema which will false the result so we need to check if the table in this schema
		 * is the one defined on this entity
		 */
		foreach($toSchema->getTables() as $table) {
			if($table->getName() != $this->getDatabaseTableName()) {
				$toSchema->dropTable($table->getName());
			}
		}

		//COMPARE
		$comparator = new Comparator();
		$schemaDiff = $comparator->compare($fromSchema, $toSchema);
		$this->sqlToSync = $schemaDiff->toSaveSql($sm->getDatabasePlatform());
		$this->inSync = (count($this->sqlToSync)===0);
	}

	/**
	 * Updates database table status
	 */
	private function _updateStatabaseTableStatus() {
		/** @var EntityManager $em */
		$em = $this->_getKernel()->getContainer()->get("doctrine")->getManager();
		$sm = $em->getConnection()->getSchemaManager();
		if($sm->tablesExist([$this->getDatabaseTableName()])) {
			$this->databaseTableStatus = $em->getConnection()->fetchAssoc("SHOW TABLE STATUS FROM " . $em->getConnection()->getDatabase() . " WHERE Name = '" . $this->getDatabaseTableName() . "'" );
		}
	}

	/**
	 * Setup fields
	 */
	private function _setFields() {
		$this->fields = new ArrayCollection();
		/** @var array $fieldMappingData */
		foreach($this->_getClassMetadata()->fieldMappings as $fieldMappingData) {
			$fieldMappingData["readOnly"] = in_array($fieldMappingData["fieldName"], $this->getDeclaredReadOnlyFieldList());
			$this->addField($fieldMappingData);
		}
	}

	private function _setAssociations() {
		$this->associations = new ArrayCollection();
		foreach($this->_getClassMetadata()->associationMappings as $assocMappingData) {
			$assocMappingData["readOnly"] = in_array($assocMappingData["fieldName"], $this->getDeclaredReadOnlyFieldList());
			$this->addAssociation($assocMappingData);
		}
	}



	/** ClassMetadata field names (SKIPPED means there is not property in this class to map field to):
	[0] => name                             BANNED (this is entityName and already set by constructor)
	[1] => namespace                        BANNED (already set by reflectionInfo)
	[2] => rootEntityName                   SKIPPED (not used in EntityGenerator)
	[3] => customGeneratorDefinition        SKIPPED (not used in EntityGenerator)
	[4] => customRepositoryClassName        ok
	[5] => isMappedSuperclass               ok
	[6] => parentClasses                    SKIPPED (not used in EntityGenerator)
	[7] => subClasses                       SKIPPED (not used in EntityGenerator)
	[8] => namedQueries                     SKIPPED (not used in EntityGenerator)
	[9] => namedNativeQueries               SKIPPED (not used in EntityGenerator)
	[10] => sqlResultSetMappings            SKIPPED (not used in EntityGenerator)
	[11] => identifier                      SKIPPED (not used in EntityGenerator and it can be generated dynamically from fields)
	[12] => inheritanceType                 ok
	[13] => generatorType                   ok
	[14] => fieldMappings                   SKIPPED (we have FieldInfo classes which can generate this dynamically)
	[15] => fieldNames                      SKIPPED (not used in EntityGenerator)
	[16] => columnNames                     SKIPPED (not used in EntityGenerator)
	[17] => discriminatorValue              SKIPPED (not used in EntityGenerator)
	[18] => discriminatorMap                ok
	[19] => discriminatorColumn             ok
	[20] => table                           BANNED (we need to set array values to single properties)
	[21] => lifecycleCallbacks              ok
	[22] => entityListeners                 SKIPPED (not used in EntityGenerator)
	[23] => associationMappings             ok (this will need its own class like fields)
	[24] => isIdentifierComposite           SKIPPED (not used in EntityGenerator)
	[25] => containsForeignIdentifier       SKIPPED (not used in EntityGenerator)
	[26] => idGenerator                     SKIPPED (not used in EntityGenerator)
	[27] => sequenceGeneratorDefinition     ok
	[28] => tableGeneratorDefinition        SKIPPED (not used in EntityGenerator)
	[29] => changeTrackingPolicy            SKIPPED (not used in EntityGenerator)
	[30] => isVersioned                     SKIPPED (not used in EntityGenerator)
	[31] => versionField                    SKIPPED (not used in EntityGenerator)
	[32] => reflClass                       SKIPPED (already have it)
	[33] => isReadOnly                      SKIPPED (not used in EntityGenerator)
	[34] => reflFields                      SKIPPED (already have it)
	 */
	/**
	 * Set info from Class Metadata
	 */
	private function _setClassMetadataInfo() {
		$metadata = $this->_getClassMetadata();
		$bannedFieldNames = ["name", "namespace", "table"];
		foreach(get_object_vars($metadata) as $propName => $propValue) {
			if (!in_array($propName, $bannedFieldNames)) {
				if (property_exists($this, $propName)) {
					$this->$propName = $propValue;
				}
			} else {
				if($propName == "table") {
					if(!$this->isMappedSuperclass) {
						$this->databaseTableName = (isset($propValue["name"])?$propValue["name"]:"");
						$this->databaseTableSchema = (isset($propValue["schema"])?$propValue["schema"]:"");
						$this->databaseTableIndexes = (isset($propValue["indexes"])?$propValue["indexes"]:[]);
						$this->databaseTableUniqueConstraints = (isset($propValue["uniqueConstraints"])?$propValue["uniqueConstraints"]:[]);
						$this->databaseTableOptions = (isset($propValue["options"])?$propValue["options"]:[]);
					} else {
						$this->databaseTableName = 'N\T';
					}
				}
			}
		}
	}


	/**
	 * Set info for bundle in which this entity is registered
	 */
	private function _setBundleInfo() {
		$bundles = $this->_getKernel()->getContainer()->getParameter("kernel.bundles");
		$kernelBundles = [];

		//find to which bundle this entity belongs to by confronting namespaces
		foreach($bundles as $bClassName => $bFullName) {
			$bFullName = preg_replace('/\\\\'.$bClassName.'$/', '', $bFullName);
			$kernelBundles[$bFullName] = $bClassName;
		}
		//echo '<hr/><pre>bundles: '.print_r($kernelBundles, true).'</pre>';
		$NSA = explode('\\', $this->namespace);
		while (count($NSA) > 1) {
			array_pop($NSA);
			$entityNamespaceToCheck = implode('\\', $NSA);
			//echo '<hr/><pre>CHECKING EntityNamespace: '.print_r($entityNamespaceToCheck, true).'</pre>';
			if(array_key_exists($entityNamespaceToCheck, $kernelBundles)) {
				$bundleName = $kernelBundles[$entityNamespaceToCheck];
				break;
			}
		}

		if(isset($bundleName)) {
			try {
				$bundleInterface = $this->_getKernel()->getBundle($bundleName);
				$this->bundleName = $bundleInterface->getName();
				$this->bundleNamespace = $bundleInterface->getNamespace();
				$this->bundleDir = $bundleInterface->getPath();
			} catch (\InvalidArgumentException $e) {
				//this should not have happened!
			}
		}
	}

	/**
	 * Set info from Jab Annotations
	 */
	private function _setJabAnnotationInfo() {
		if ( ($classReflection = $this->getClassReflection()) ) {
			$reader = new AnnotationReader();
			try {
				/** @var JabAnnotationEntity $JabAnnotationEntity */
				$this->jabAnnotationInfo = $reader->getClassAnnotation($classReflection, 'Jab\Config\EntityBundle\Annotation\Entity');
			} catch (\Exception $e) {
				//ooops
			}
		}
	}

	/**
	 * Set info from reflection
	 */
	private function _setReflectionInfo() {
		if ( ($classReflection = $this->getClassReflection()) ) {
			//GENERIC
			$this->namespace = $classReflection->getNamespaceName();
			$this->className = $classReflection->getShortName();

			//USE STATEMENTS AND DOC COMMENTS
			$finder = new Psr0FindFile([$classReflection->getNamespaceName() => [$this->vendorDir]]);
			$staticParser = new StaticReflectionParser($classReflection->getName(), $finder);
			$this->reflectionInfo["use_statements"] = $staticParser->getUseStatements();
			$this->reflectionInfo["doc_comment"] = $staticParser->getDocComment();

			//EXTENDED CLASS
			$this->extendedClass = ($classReflection->getParentClass()?$classReflection->getParentClass()->getName():null);

		}
	}

	/**
	 * After fields have been added and all reflection properties and methods have been set up
	 * now we can check for custom methods which do not correspond to any getter/setter method of the fields
	 * "customMethods" -> These will be preserved and put back to entity during code generation
	 *
	 * Missing the same thing on AssociationMapping - we need proper class for that just like EntityFieldInfo
	 */
	private function _setReflectionCustomInfo() {
		if ( ($classReflection = $this->getClassReflection()) ) {
			$this->reflectionInfo["customMethods"] = [];
			$sourceCodeArray = file($classReflection->getFileName());
			foreach($classReflection->getMethods() as $method) {
				if($method->getDeclaringClass() == $classReflection) {
					$found = false;

					//Check in fields
					/** @var EntityFieldInfo $field */
					foreach($this->fields as $field) {
						if($method->getName() == $field->getMethodInfoData("get","name")
							|| $method->getName() == $field->getMethodInfoData("set","name")) {
							$found = true;
							break;
						}
					}

					//Check in associations
					/** @var EntityAssociationInfo $association */
					foreach($this->associations as $association) {
						if($method->getName() == $association->getMethodInfoData("get","name")
							|| $method->getName() == $association->getMethodInfoData("set","name")
							|| $method->getName() == $association->getMethodInfoData("add","name")
							|| $method->getName() == $association->getMethodInfoData("remove","name")) {
							$found = true;
							break;
						}
					}

					if(!$found) {
						$mA = [];
						$mA["name"] = $method->getName();
						$mA["body"] = rtrim(implode("", array_slice($sourceCodeArray, $method->getStartLine()-1, ($method->getEndLine()-$method->getStartLine()+1))));
						$mA["comment"] = $method->getDocComment();
						$this->reflectionInfo["customMethods"][$method->getName()] = $mA;
					}
				}
			}
		}
	}

	/**
	 * Register file paths and registers a hash for the class file (used by InfoFactory to test if file has changed outside)
	 */
	private function _setPathInfo() {
		if ( ($classReflection = $this->getClassReflection()) ) {
			$this->entityPath = $classReflection->getFileName();// /[app_root]/src/Jab/Bundle/TestBundle/Entity/myClass.php
			$this->vendorDir = str_replace("/" . str_replace("\\","/",$classReflection->getNamespaceName()), "", dirname($this->entityPath));// /[app_root]/src
			$this->entityFileHash = hash_file("crc32", $this->entityPath);
		}
	}

	/**
	 * Returns reflection for entity - we allow exceptions to be thrown because this class without reflection is useless
	 *
	 * @return bool|\ReflectionClass
	 */
	public function getClassReflection() {
		return(new \ReflectionClass($this->entityName));
	}

	/**
	 * @return ClassMetadata
	 */
	private function _getClassMetadata() {
		return($this->_getKernel()->getContainer()->get("doctrine")->getManager()->getClassMetadata($this->entityName));
	}

	/**
	 * Entity has not got a class file yet - so no reflection - we need to do this manually
	 */
	private function _setupNewEntity() {
		$this->jabAnnotationInfo = new JabAnnotationEntity(["managedEntity"=>true, "type"=>"CUSTOM"]);
		$this->extendedClass = 'Jab\Config\EntityBundle\Entity\JabEntity';
		$this->fields = new ArrayCollection();
		$this->associations = new ArrayCollection();
		$this->inheritanceType = ClassMetadataInfo::INHERITANCE_TYPE_NONE;
		$this->reflectionInfo = [
			'use_statements' => [
				'orm' => 'Doctrine\ORM\Mapping',
				'jab' => 'Jab\Config\EntityBundle\Annotation'
			]
		];
	}

	/**
	 * @param $entityName
	 * @throws \LogicException
	 */
	private function _checkEntityName($entityName) {
		if(empty($entityName)) {
			throw new \LogicException("A fully qualified class name is required! None given!");
		}
		if(!class_exists($entityName)) {
			throw new \LogicException(sprintf("There is no class by this name(%s)!", $entityName));
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