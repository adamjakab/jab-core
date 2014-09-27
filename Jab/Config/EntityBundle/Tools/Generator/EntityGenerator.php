<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 *
 * This is a modified copy of Doctrine\ORM\Tools\EntityGenerator
 *
 */
namespace Jab\Config\EntityBundle\Tools\Generator;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Util\Inflector;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Tools\EntityRepositoryGenerator;
use Jab\Config\EntityBundle\Tools\Info\EntityInfo;
use Jab\Config\EntityBundle\Tools\Info\EntityFieldInfo;
use Jab\Config\EntityBundle\Tools\Info\EntityAssociationInfo;


/**
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Adam Jakab <jakabadambalazs@gmail.com>
 *
 * Class EntityGenerator
 */
class EntityGenerator {
	/**
	 * Specifies class fields should be protected.
	 */
	const FIELD_VISIBLE_PROTECTED = 'protected';

	/**
	 * Specifies class fields should be private.
	 */
	const FIELD_VISIBLE_PRIVATE = 'private';

	/**
	 * @var bool
	 */
	protected $backupExisting = true;

	/**
	 * @var array
	 */
	protected $staticReflection = array();

	/**
	 * Number of spaces to use for indention in generated code.
	 */
	protected $numSpaces = 4;

	/**
	 * The actual spaces to use for indention.
	 *
	 * @var string
	 */
	protected $spaces = '    ';

	/**
	 * @var string
	 */
	protected $annotationsPrefix = 'ORM\\';

	/**
	 * Whether or not to update the entity class if it exists already.
	 *
	 * @var boolean
	 */
	protected $updateEntityIfExists = false;

	/**
	 * Whether or not to re-generate entity class if it exists already.
	 *
	 * @var boolean
	 */
	protected $regenerateEntityIfExists = false;

	/**
	 * @var string - always private
	 */
	protected $fieldVisibility = 'private';

	/**
	 * Hash-map for handle types.
	 *
	 * @var array
	 */
	protected $typeAlias = array(
		Type::DATETIMETZ    => '\DateTime',
		Type::DATETIME      => '\DateTime',
		Type::DATE          => '\DateTime',
		Type::TIME          => '\DateTime',
		Type::OBJECT        => '\stdClass',
		Type::BIGINT        => 'integer',
		Type::SMALLINT      => 'integer',
		Type::TEXT          => 'string',
		Type::BLOB          => 'string',
		Type::DECIMAL       => 'string',
		Type::JSON_ARRAY    => 'array',
		Type::SIMPLE_ARRAY  => 'array',
	);

	/**
	 * Hash-map to handle generator types string.
	 *
	 * @var array
	 */
	protected static $generatorStrategyMap = array(
		ClassMetadataInfo::GENERATOR_TYPE_AUTO      => 'AUTO',
		ClassMetadataInfo::GENERATOR_TYPE_SEQUENCE  => 'SEQUENCE',
		ClassMetadataInfo::GENERATOR_TYPE_TABLE     => 'TABLE',
		ClassMetadataInfo::GENERATOR_TYPE_IDENTITY  => 'IDENTITY',
		ClassMetadataInfo::GENERATOR_TYPE_NONE      => 'NONE',
		ClassMetadataInfo::GENERATOR_TYPE_UUID      => 'UUID',
		ClassMetadataInfo::GENERATOR_TYPE_CUSTOM    => 'CUSTOM'
	);

	/**
	 * Hash-map to handle the change tracking policy string.
	 *
	 * @var array
	 */
	protected static $changeTrackingPolicyMap = array(
		ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT  => 'DEFERRED_IMPLICIT',
		ClassMetadataInfo::CHANGETRACKING_DEFERRED_EXPLICIT  => 'DEFERRED_EXPLICIT',
		ClassMetadataInfo::CHANGETRACKING_NOTIFY             => 'NOTIFY',
	);

	/**
	 * Hash-map to handle the inheritance type string.
	 *
	 * @var array
	 */
	protected static $inheritanceTypeMap = array(
		ClassMetadataInfo::INHERITANCE_TYPE_NONE            => 'NONE',
		ClassMetadataInfo::INHERITANCE_TYPE_JOINED          => 'JOINED',
		ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE    => 'SINGLE_TABLE',
		ClassMetadataInfo::INHERITANCE_TYPE_TABLE_PER_CLASS => 'TABLE_PER_CLASS',
	);


	/**
	 * @var string
	 */
	protected static $classTemplate =
'<?php
<namespace>

<useStatements>

<entityAnnotation>
<entityClassName>
{
<entityBody>
}
';

	    /**
     * @var string
     */
    protected static $getMethodTemplate =
'/**
 * <description>
 *
 * @return <variableType>
 */
public function <methodName>() {
<spaces>return $this-><fieldName>;
}';

    /**
     * @var string
     */
    protected static $setMethodTemplate =
'/**
 * <description>
 *
 * @param <variableType>$<variableName>
 * @return <entity>
 */
public function <methodName>(<methodTypeHint>$<variableName><variableDefault>) {
<spaces>$this-><fieldName> = $<variableName>;
<spaces>return $this;
}';

    /**
     * @var string
     */
    protected static $addMethodTemplate =
'/**
 * <description>
 *
 * @param <variableType>$<variableName>
 * @return <entity>
 */
public function <methodName>(<methodTypeHint>$<variableName>) {
<spaces>$this-><fieldName>[] = $<variableName>;

<spaces>return $this;
}';

    /**
     * @var string
     */
    protected static $removeMethodTemplate =
'/**
 * <description>
 *
 * @param <variableType>$<variableName>
 */
public function <methodName>(<methodTypeHint>$<variableName>) {
<spaces>$this-><fieldName>->removeElement($<variableName>);
}';

    /**
     * @var string
     */
    protected static $lifecycleCallbackMethodTemplate =
'/**
 * @<name>
 */
public function <methodName>() {
<spaces>// Add your code here
}';

    /**
     * @var string
     */
    protected static $constructorMethodTemplate =
'/**
 * Constructor
 */
public function __construct() {
<spaces><collections>
}
';


	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->setNumSpaces(4);
	}


	/**
	 * Generates and writes entity class for the given EntityInfo instance
	 * @param EntityInfo $entityInfo
	 * @return void
	 * @throws \RuntimeException
	 */
	public function writeEntityClass(EntityInfo $entityInfo) {
		$path = $entityInfo->getEntityPath();
		$dir = dirname($path);

		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}

		$this->staticReflection = ['properties' => [], 'methods' => []];
		$classCode = $this->generateEntityClass($entityInfo);

		//echo "SP: " . json_encode($this->staticReflection);
		//echo '<hr /><b>METADATA(FINAL)</b>:<pre>'.print_r($entityInfo, true).'</pre>';
		//echo('<pre>' . htmlentities($classCode) . '</pre>');die();//@todo: KILLER LINE!!!

		file_put_contents($path, $classCode);
	}

	/**
	 * Writes the entity's repository class for the given EntityInfo instance
	 * @param EntityInfo $entityInfo
	 */
	public function writeEntityRepository(EntityInfo $entityInfo) {
		if ($entityInfo->hasRepository()) {
			$repositoryGenerator = new EntityRepositoryGenerator();
			$repositoryGenerator->writeEntityRepositoryClass($entityInfo->getCustomRepositoryClassName(), $entityInfo->getVendorDir());
		}
	}

	/**
	 * Generates a PHP5 Doctrine 2 entity class from the given EntityInfo instance.
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	public function generateEntityClass(EntityInfo $entityInfo) {
		$placeHolders = array(
			'<namespace>',
			'<useStatements>',
			'<entityAnnotation>',
			'<entityClassName>',
			'<entityBody>'
		);

		$replacements = array(
			$this->generateEntityNamespace($entityInfo),
			$this->generateUseStatements($entityInfo),
			$this->generateEntityDocBlock($entityInfo),
			$this->generateEntityClassName($entityInfo),
			$this->generateEntityBody($entityInfo)
		);
		$code = str_replace($placeHolders, $replacements, self::$classTemplate);
		return str_replace('<spaces>', $this->spaces, $code);
	}

	/**
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateEntityNamespace(EntityInfo $entityInfo)	{
		return ($entityInfo->getNamespace() ? 'namespace ' . $entityInfo->getNamespace() .';' : '');
	}

	/**
	 * To avoid creating statements like this: "use Doctrine\Common\Collections\ArrayCollection as ARRAYCOLLECTION;"
	 * we check if alias===ShortClassName and in that case we do NOT use alias at all
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateUseStatements(EntityInfo $entityInfo) {
		$lines = array();
		$useStatements = $entityInfo->getUseStatements();
		if($this->_entityNeedsCollectionTypes($entityInfo)) {
			//reflection gathered use statement array is indexed by lowercase aliases so by adding the use statements
			//below by using lowercase array keys we do not have to check for duplicates
			$useStatements["collection"] = 'Doctrine\Common\Collections\Collection';
			$useStatements["arraycollection"] = 'Doctrine\Common\Collections\ArrayCollection';
		}
		foreach($useStatements as $alias => $useClass) {
			$alias = strtoupper($alias);
			$CNA = explode("\\", $useClass);
			$shortClassName = strtoupper(array_pop($CNA));
			$lines[] = 'use ' . $useClass
				. ($alias!=$shortClassName ? ' as ' . strtoupper($alias) : '') . ';';
		}
		return (implode("\n", $lines));
	}

	/**
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateEntityDocBlock(EntityInfo $entityInfo) {
		$lines = array();
		$lines[] = '/**';
		$lines[] = ' * ' . $entityInfo->getClassName();
		$lines[] = ' * Autogenerated: ' . date("Y-m-d H:i:s");
		$lines[] = ' *';

		$methods = array(
			'generateJabAnnotation',
			'generateTableAnnotation',
			'generateInheritanceAnnotation',
			'generateDiscriminatorColumnAnnotation',
			'generateDiscriminatorMapAnnotation'
		);

		foreach ($methods as $method) {
			if ($code = call_user_func([$this, $method], $entityInfo)) {
				$lines[] = ' * ' . $code;
			}
		}

		if ($entityInfo->isMappedSuperclass()) {
			$lines[] = ' * @' . $this->annotationsPrefix . 'MappedSuperclass';
		} else {
			$lines[] = ' * @' . $this->annotationsPrefix . 'Entity';
		}

		if ($entityInfo->getCustomRepositoryClassName()) {
			$lines[count($lines) - 1] .= '(repositoryClass="' . $entityInfo->getCustomRepositoryClassName() . '")';
		}

		if (count($entityInfo->getLifecycleCallbacks())) {
			$lines[] = ' * @' . $this->annotationsPrefix . 'HasLifecycleCallbacks';
		}

		$lines[] = ' */';
		return implode("\n", $lines);
	}

	/**
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateJabAnnotation(EntityInfo $entityInfo) {
		$lines = [];
		if($entityInfo->isManagedEntity()) {
			$lines[] = ' @JAB\Entity(';

			// managedEntity
			$lines[] = $this->spaces . 'managedEntity=true';

			// type
			if($entityInfo->getType()) {
				$lines[count($lines) - 1] .=  ', ';
				$lines[] = $this->spaces . 'type="' . $entityInfo->getType() . '"';
			}

			// read only fields
			if(count($entityInfo->getDeclaredReadOnlyFieldList())) {
				$lines[count($lines) - 1] .=  ', ';
				$lines[] = $this->spaces . 'readOnlyFields=' . $this->convertNonAssociativeArrayForAnnotation($entityInfo->getDeclaredReadOnlyFieldList());
			}
			$lines[] = ')';
		}
		return(implode("\n * ", $lines));
	}



	/**
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateTableAnnotation(EntityInfo $entityInfo) {
		if(!$entityInfo->isMappedSuperclass()) {
			$table = array();

			if ($entityInfo->getDatabaseTableSchema()) {
				$table[] = 'schema="' . $entityInfo->getDatabaseTableSchema() . '"';
			}

			if ($entityInfo->getDatabaseTableName()) {
				$table[] = 'name="' . $entityInfo->getDatabaseTableName() . '"';
			}

			if (count($entityInfo->getDatabaseTableUniqueConstraints())) {
				$constraints = $this->generateTableConstraints('UniqueConstraint', $entityInfo->getDatabaseTableUniqueConstraints());
				$table[] = 'uniqueConstraints={' . $constraints . '}';
			}

			if (count($entityInfo->getDatabaseTableIndexes())) {
				$constraints = $this->generateTableConstraints('Index', $entityInfo->getDatabaseTableIndexes());
				$table[] = 'indexes={' . $constraints . '}';
			}

			return '@' . $this->annotationsPrefix . 'Table(' . implode(', ', $table) . ')';
		} else {
			return '';
		}
	}

	/**
	 * @param string $constraintName
	 * @param array  $constraints
	 * @return string
	 */
	protected function generateTableConstraints($constraintName, $constraints) {
		$annotations = array();
		foreach ($constraints as $name => $constraint) {
			$columns = array();
			foreach ($constraint['columns'] as $column) {
				$columns[] = '"' . $column . '"';
			}
			$annotations[] = '@' . $this->annotationsPrefix . $constraintName . '(name="' . $name . '", columns={' . implode(', ', $columns) . '})';
		}
		return implode(', ', $annotations);
	}

	/**
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateInheritanceAnnotation(EntityInfo $entityInfo) {
		if ($entityInfo->getInheritanceType() != ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
			return '@' . $this->annotationsPrefix . 'InheritanceType("'.$this->getInheritanceTypeString($entityInfo->getInheritanceType()).'")';
		} else {
			return '';
		}
	}

	/**
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateDiscriminatorColumnAnnotation(EntityInfo $entityInfo) {
		if ($entityInfo->getInheritanceType() != ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
			$discrColumn = $entityInfo->getDiscriminatorColumn();
			$columnDefinition = 'name="' . $discrColumn['name']
				. '", type="' . $discrColumn['type']
				. '", length=' . $discrColumn['length'];

			return '@' . $this->annotationsPrefix . 'DiscriminatorColumn(' . $columnDefinition . ')';
		} else {
			return '';
		}
	}

	/**
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateDiscriminatorMapAnnotation(EntityInfo $entityInfo) {
		if ($entityInfo->getInheritanceType() != ClassMetadataInfo::INHERITANCE_TYPE_NONE) {
			$inheritanceClassMap = array();
			foreach ($entityInfo->getDiscriminatorMap() as $type => $class) {
				$inheritanceClassMap[] .= '"' . $type . '" = "' . $class . '"';
			}
			return '@' . $this->annotationsPrefix . 'DiscriminatorMap({' . implode(', ', $inheritanceClassMap) . '})';
		} else {
			return '';
		}
	}

	/**
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateEntityClassName(EntityInfo $entityInfo) {
		return 'class ' . $entityInfo->getClassName() .
		($entityInfo->getExtendedClass() ? ' extends \\' . $entityInfo->getExtendedClass() : '');
	}

	/**
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateEntityBody(EntityInfo $entityInfo) {
		$fieldMappingProperties = $this->generateEntityFieldMappingProperties($entityInfo);
		$associationMappingProperties = $this->generateEntityAssociationMappingProperties($entityInfo);
		$stubMethods = $this->generateEntityStubMethods($entityInfo);
		$lifecycleCallbackMethods = $this->generateEntityLifecycleCallbackMethods($entityInfo);

		$code = array();

		if ($fieldMappingProperties) {
			$code[] = $fieldMappingProperties;
		}

		if ($associationMappingProperties) {
			$code[] = $associationMappingProperties;
		}

		$code[] = $this->generateEntityConstructor($entityInfo);

		if ($stubMethods) {
			$code[] = $stubMethods;
		}

		if ($lifecycleCallbackMethods) {
			$code[] = $lifecycleCallbackMethods;
		}

		return implode("\n", $code);
	}

	/**
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateEntityFieldMappingProperties(EntityInfo $entityInfo) {
		$lines = array();
		/** @var EntityFieldInfo $field */
		foreach($entityInfo->getFields() as $field) {
			if($field->isOwned()) {
				/*
				 * the 'hasProperty' is not needed, 'isOwned' is sufficient otherwise entities like JabUser where
				 * 'id' property is declared on both JabUser and on parent class will have problems, ie. property will
				 * be removed by this generator
				 * However 'hasProperty' does indicate if a parent has declared this propery, and if it has
				 * we probably should/must redeclare propery 'protected' and NOT 'private'
				 */
				$isFieldDeclaredOnParent = $this->hasProperty($field->getFieldName(), $entityInfo);
				$fieldVisibility = ($isFieldDeclaredOnParent ? 'protected' : $this->fieldVisibility);

				//if (!$this->hasProperty($field->getFieldName(), $entityInfo)) {
					$lines[] = $this->generateFieldMappingPropertyDocBlock($field, $entityInfo);
					$lines[] = $this->spaces . $fieldVisibility . ' $' . $field->getFieldName()
						. ($field->getDefault() ? ' = ' . var_export($field->getDefault(), true) : null) . ";\n";
					$this->staticReflection["properties"][] = $field->getFieldName();
				//}
			}
		}
		return implode("\n", $lines);
	}

	/**
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateEntityAssociationMappingProperties(EntityInfo $entityInfo) {
		$lines = array();
		//$lines[] = $this->spaces . '/** --- ASSOCIATION MAPPING PROPERTIES BEGIN --- */';
		/** @var EntityAssociationInfo $associationInfo */
		foreach ($entityInfo->getAssociations() as $associationInfo) {
			if (!$this->hasProperty($associationInfo->getFieldName(), $entityInfo)) {
				$lines[] = $this->generateAssociationMappingPropertyDocBlock($associationInfo, $entityInfo);
				$lines[] = $this->spaces . $this->fieldVisibility . ' $' . $associationInfo->getFieldName()
					. ($associationInfo->getType() == 'manyToMany' ? ' = array()' : null) . ";\n";
				$this->staticReflection["properties"][] = $associationInfo->getFieldName();
			}
		}
		//$lines[] = $this->spaces . '/** --- ASSOCIATION MAPPING PROPERTIES END --- */';
		return implode("\n", $lines);
	}

	/**
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateEntityStubMethods(EntityInfo $entityInfo) {
		$methods = array();

		//PROPERTY SETTERS/GETTERS
		/** @var EntityFieldInfo $field */
		foreach($entityInfo->getFields() as $field) {
			if(!$field->isId() || $entityInfo->getGeneratorType() == ClassMetadataInfo::GENERATOR_TYPE_NONE) {
				if ($code = $this->generateEntityStubMethod($entityInfo, 'set', $field->getFieldName(), $field->getType())) {
					$methods[] = $code;
				}
			}
			if ($code = $this->generateEntityStubMethod($entityInfo, 'get', $field->getFieldName(), $field->getType())) {
				$methods[] = $code;
			}
		}

		//ASSOCIATION MAPPING SETTERS/GETTERS
		/** @var EntityAssociationInfo $association */
		foreach ($entityInfo->getAssociations() as $association) {
			if ($association->getType() & ClassMetadataInfo::TO_ONE) {
				$nullable = $this->isAssociationIsNullable($association) ? 'null' : null;
				if ($code = $this->generateEntityStubMethod($entityInfo, 'set', $association->getFieldName(), $association->getTargetEntity(), $nullable)) {
					$methods[] = $code;
				}
				if ($code = $this->generateEntityStubMethod($entityInfo, 'get', $association->getFieldName(), $association->getTargetEntity())) {
					$methods[] = $code;
				}
			} elseif ($association->getType() & ClassMetadataInfo::TO_MANY) {
				if ($code = $this->generateEntityStubMethod($entityInfo, 'add', $association->getFieldName(), $association->getTargetEntity())) {
					$methods[] = $code;
				}
				if ($code = $this->generateEntityStubMethod($entityInfo, 'remove', $association->getFieldName(), $association->getTargetEntity())) {
					$methods[] = $code;
				}
				if ($code = $this->generateEntityStubMethod($entityInfo, 'get', $association->getFieldName(), 'Collection')) {//:Doctrine\Common\Collections\Collection
					$methods[] = $code;
				}
			}
		}

		//CUSTOM METHODS
		$customMethods = $entityInfo->getCustomMethods();
		if(count($customMethods)) {
			$methods[] = $this->spaces . '/** --- CUSTOM METHODS BEGIN --- */';
			foreach($customMethods as $customMethod) {
				$methodCode = ""
					. (!empty($customMethod["comment"]) ? $this->spaces . $customMethod["comment"] . "\n" : "" )
					. $customMethod["body"];
				$methodCode = str_replace("\t", $this->spaces, $methodCode);
				$methods[] = $methodCode;
				$this->staticReflection['methods'][] = $customMethod["name"];
			}
			$methods[] = $this->spaces . '/** --- CUSTOM METHODS END --- */';
		}


		return implode("\n\n", $methods);
	}


	/**
	 * @param EntityInfo        $entityInfo
	 * @param string            $type
	 * @param string            $fieldName - this can be the name of a field or the name of an association
	 * @param string|null       $typeHint
	 * @param string|null       $defaultValue
	 * @return string
	 */
	protected function generateEntityStubMethod(EntityInfo $entityInfo, $type, $fieldName, $typeHint = null,  $defaultValue = null) {
		$methodName = $type . Inflector::classify($fieldName);
		if (in_array($type, array("add", "remove"))) {
			$methodName = Inflector::singularize($methodName);
		}

		//check if this field defined on $EntityInfo
		if( ($fieldInfo = $entityInfo->getField($fieldName)) ) {
			if(!$fieldInfo->isOwned()) {
				return '';
			}
		}

		//check if this field defined on $EntityInfo
		if( ($associationInfo = $entityInfo->getAssociation($fieldName)) ) {
			if(!$associationInfo->isOwned()) {
				return '';
			}
		}


		$methodCode = null;

		//CURRENT(EXISTING) CODE IN FIELD CLASS FILE
		if($fieldInfo) {
			if($fieldInfo->getMethodInfoData($type, "name")) {
				$methodName = $fieldInfo->getMethodInfoData($type, "name");
				$methodCode = $this->spaces
					. $fieldInfo->getMethodInfoData($type, "comment")
					. "\n"
					. $fieldInfo->getMethodInfoData($type, "body");
				$methodCode = str_replace("\t", $this->spaces, $methodCode);
			}
		}

		//CURRENT(EXISTING) CODE IN ASSOCIATION MAPPING CLASS FILE
		if($associationInfo) {
			if($associationInfo->getMethodInfoData($type, "name")) {
				$methodName = $associationInfo->getMethodInfoData($type, "name");
				$methodCode = $this->spaces
					. $associationInfo->getMethodInfoData($type, "comment")
					. "\n"
					. $associationInfo->getMethodInfoData($type, "body");
				$methodCode = str_replace("\t", $this->spaces, $methodCode);
			}
		}

		//GENERATED CODE
		if(!$methodCode) {
			$var = sprintf('%sMethodTemplate', $type);
			$template = self::$$var;

			$methodTypeHint = null;
			$types          = Type::getTypesMap();
			$variableType   = $typeHint ? $this->getType($typeHint) . ' ' : null;

			if ($typeHint && !isset($types[$typeHint])) {
				//add '\' in front of types unless they are in use statements - we need dynamic test for this
				if(!in_array($typeHint,["Collection", "ArrayCollection"])) {
					$variableType   =  '\\' . ltrim($variableType, '\\');
				}
				$methodTypeHint =  '\\' . $typeHint . ' ';
			}

			$replacements = array(
				'<description>'       => ucfirst($type) . ' ' . $fieldName,
				'<methodTypeHint>'    => $methodTypeHint,
				'<variableType>'      => $variableType,
				'<variableName>'      => Inflector::camelize($fieldName),
				'<methodName>'        => $methodName,
				'<fieldName>'         => $fieldName,
				'<variableDefault>'   => ($defaultValue !== null ) ? (' = '.$defaultValue) : '',
				'<entity>'            => $entityInfo->getClassName()
			);

			$method = str_replace(
				array_keys($replacements),
				array_values($replacements),
				$template
			);

			$methodCode = $this->prefixCodeWithSpaces($method);
			$methodCode = str_replace('<spaces>', $this->spaces, $methodCode);
		}

		$this->staticReflection['methods'][] = $methodName;
		return $methodCode;
	}

	/**
	 * @param EntityAssociationInfo $associationInfo
	 * @return bool
	 */
	protected function isAssociationIsNullable(EntityAssociationInfo $associationInfo)	{
		if ($associationInfo->getId()) {
			return false;
		}
		foreach ($associationInfo->getJoinColumns() as $joinColumn) {
			if(isset($joinColumn['nullable']) && !$joinColumn['nullable']) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateEntityLifecycleCallbackMethods(EntityInfo $entityInfo)	{
		if (count($entityInfo->getLifecycleCallbacks())) {
			$methods = array();
			foreach ($entityInfo->getLifecycleCallbacks() as $name => $callbacks) {
				foreach ($callbacks as $callback) {
					if ($code = $this->generateLifecycleCallbackMethod($name, $callback, $entityInfo)) {
						$methods[] = $code;
					}
				}
			}
			return implode("\n\n", $methods);
		}
		return "";
	}

	/**
	 * @param string            $name
	 * @param string            $methodName
	 * @param EntityInfo        $entityInfo
	 * @return string
	 */
	protected function generateLifecycleCallbackMethod($name, $methodName, EntityInfo $entityInfo) {
		if ($this->hasMethod($methodName, $entityInfo)) {
			return '';
		}
		$this->staticReflection['methods'][] = $methodName;
		$replacements = array(
			'<name>'        => $this->annotationsPrefix . ucfirst($name),
			'<methodName>'  => $methodName,
		);
		$method = str_replace(
			array_keys($replacements),
			array_values($replacements),
			self::$lifecycleCallbackMethodTemplate
		);
		return $this->prefixCodeWithSpaces($method);
	}


	/**
	 * todo: Cisti! this is special case because if it is not autogenerated(because considered custom method) - as it is now
	 * when adding/removing associations the <collections> placeholder must change!!! Sleep on it!
	 *
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateEntityConstructor(EntityInfo $entityInfo) {
		$answer = '';
		if (!$this->hasMethod('__construct', $entityInfo)) {
			$collections = array();
			/** @var EntityAssociationInfo $associationInfo */
			foreach ($entityInfo->getAssociations() as $associationInfo) {
				if ($associationInfo->getType() & ClassMetadataInfo::TO_MANY) {
					$collections[] = '$this->'.$associationInfo->getFieldName().' = new ArrayCollection();';
				}
			}
			if ($collections) {
				$answer = $this->prefixCodeWithSpaces(str_replace("<collections>", implode("\n".$this->spaces, $collections), self::$constructorMethodTemplate));
				$this->staticReflection["methods"][] = '__construct';
			}
		}
		return $answer;
	}




	/**
	 * @param EntityAssociationInfo $associationInfo
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateAssociationMappingPropertyDocBlock(EntityAssociationInfo $associationInfo, EntityInfo $entityInfo) {
		$lines = array();
		$lines[] = $this->spaces . '/**';

		if ($associationInfo->getType() & ClassMetadataInfo::TO_MANY) {
			$lines[] = $this->spaces . ' * @var Collection';//: \Doctrine\Common\Collections\Collection
		} else {
			$lines[] = $this->spaces . ' * @var \\' . ltrim($associationInfo->getTargetEntity(), '\\');
		}

		$lines[] = $this->spaces . ' *';

		if ($associationInfo->getId()) {
			$lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Id';

			if ($generatorType = $this->getIdGeneratorTypeString($entityInfo->getGeneratorType())) {
				$lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'GeneratedValue(strategy="' . $generatorType . '")';
			}
		}

		$type = null;
		switch ($associationInfo->getType()) {
			case ClassMetadataInfo::ONE_TO_ONE:
				$type = 'OneToOne';
				break;
			case ClassMetadataInfo::MANY_TO_ONE:
				$type = 'ManyToOne';
				break;
			case ClassMetadataInfo::ONE_TO_MANY:
				$type = 'OneToMany';
				break;
			case ClassMetadataInfo::MANY_TO_MANY:
				$type = 'ManyToMany';
				break;
		}
		$typeOptions = array();

		if ($associationInfo->getTargetEntity()) {
			$typeOptions[] = 'targetEntity="' . $associationInfo->getTargetEntity() . '"';
		}

		if ($associationInfo->getInversedBy()) {
			$typeOptions[] = 'inversedBy="' . $associationInfo->getInversedBy() . '"';
		}

		if ($associationInfo->getMappedBy()) {
			$typeOptions[] = 'mappedBy="' . $associationInfo->getMappedBy() . '"';
		}

		if (count($associationInfo->getCascade())) {
			$cascades = array();

			if ($associationInfo->getIsCascadePersist()) $cascades[] = '"persist"';
			if ($associationInfo->getIsCascadeRemove()) $cascades[] = '"remove"';
			if ($associationInfo->getIsCascadeDetach()) $cascades[] = '"detach"';
			if ($associationInfo->getIsCascadeMerge()) $cascades[] = '"merge"';
			if ($associationInfo->getIsCascadeRefresh()) $cascades[] = '"refresh"';

			if (count($cascades) === 5) {
				$cascades = array('"all"');
			}

			$typeOptions[] = 'cascade={' . implode(',', $cascades) . '}';
		}

		if ($associationInfo->getOrphanRemoval()) {
			$typeOptions[] = 'orphanRemoval=' . ($associationInfo->getOrphanRemoval() ? 'true' : 'false');
		}

		if ($associationInfo->getFetch() !== ClassMetadataInfo::FETCH_LAZY) {
			$fetchMap = array(
				ClassMetadataInfo::FETCH_EXTRA_LAZY => 'EXTRA_LAZY',
				ClassMetadataInfo::FETCH_EAGER      => 'EAGER',
			);

			$typeOptions[] = 'fetch="' . $fetchMap[$associationInfo->getFetch()] . '"';
		}

		$lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . '' . $type . '(' . implode(', ', $typeOptions) . ')';

		if (count($associationInfo->getJoinColumns())) {
			$lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'JoinColumns({';

			$joinColumnsLines = array();

			foreach ($associationInfo->getJoinColumns() as $joinColumn) {
				if ($joinColumnAnnot = $this->generateJoinColumnAnnotation($joinColumn)) {
					$joinColumnsLines[] = $this->spaces . ' *   ' . $joinColumnAnnot;
				}
			}

			$lines[] = implode(",\n", $joinColumnsLines);
			$lines[] = $this->spaces . ' * })';
		}

		if (count($associationInfo->getJoinTable())) {
			$joinTableArray = $associationInfo->getJoinTable();
			$joinTable = array();
			$joinTable[] = 'name="' . $joinTableArray['name'] . '"';

			if (isset($joinTableArray['schema'])) {
				$joinTable[] = 'schema="' . $joinTableArray['schema'] . '"';
			}

			$lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'JoinTable(' . implode(', ', $joinTable) . ',';
			$lines[] = $this->spaces . ' *   joinColumns={';

			$joinColumnsLines = array();

			foreach ($joinTableArray['joinColumns'] as $joinColumn) {
				$joinColumnsLines[] = $this->spaces . ' *     ' . $this->generateJoinColumnAnnotation($joinColumn);
			}

			$lines[] = implode(",". PHP_EOL, $joinColumnsLines);
			$lines[] = $this->spaces . ' *   },';
			$lines[] = $this->spaces . ' *   inverseJoinColumns={';

			$inverseJoinColumnsLines = array();

			foreach ($joinTableArray['inverseJoinColumns'] as $joinColumn) {
				$inverseJoinColumnsLines[] = $this->spaces . ' *     ' . $this->generateJoinColumnAnnotation($joinColumn);
			}

			$lines[] = implode(",". PHP_EOL, $inverseJoinColumnsLines);
			$lines[] = $this->spaces . ' *   }';
			$lines[] = $this->spaces . ' * )';
		}

		if (count($associationInfo->getOrderBy())) {
			$lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'OrderBy({';

			foreach ($associationInfo->getOrderBy() as $name => $direction) {
				$lines[] = $this->spaces . ' *     "' . $name . '"="' . $direction . '",';
			}

			$lines[count($lines) - 1] = substr($lines[count($lines) - 1], 0, strlen($lines[count($lines) - 1]) - 1);
			$lines[] = $this->spaces . ' * })';
		}

		$lines[] = $this->spaces . ' */';


		//RESTORE CUSTOM ANNOTATIONS
		$prevDocComment = $associationInfo->getDocComment();
		$mustStartWith = $this->spaces . ' * ';
		$excludes = [
			$this->spaces . ' * @var',
			$this->spaces . ' * @' . $this->annotationsPrefix . 'Id',
			$this->spaces . ' * @' . $this->annotationsPrefix . 'GeneratedValue',
			$this->spaces . ' * @' . $this->annotationsPrefix . 'OneToOne',
			$this->spaces . ' * @' . $this->annotationsPrefix . 'ManyToOne',
			$this->spaces . ' * @' . $this->annotationsPrefix . 'OneToMany',
			$this->spaces . ' * @' . $this->annotationsPrefix . 'ManyToMany',
			$this->spaces . ' * @' . $this->annotationsPrefix . 'JoinColumns',
			$this->spaces . ' * @' . $this->annotationsPrefix . 'JoinTable',
			$this->spaces . ' * @' . $this->annotationsPrefix . 'OrderBy',
			/* SPECIAL JOIN COLUMN STUFF */
			$this->spaces . ' * @' . $this->annotationsPrefix . 'JoinColumn',
			$this->spaces . ' *   @' . $this->annotationsPrefix . 'JoinColumn',
			$this->spaces . ' * })'

		];
		$marker = '';//'---(CUSTOM ANNOTATIONS)---';
		$lines = $this->restoreCustomAnnotations($lines, $prevDocComment, $mustStartWith, $excludes, $marker);

		return implode("\n", $lines);
	}

	/**
	 * @param array $joinColumn
	 * @return string
	 */
	protected function generateJoinColumnAnnotation(array $joinColumn) {
		$joinColumnAnnot = array();

		if (isset($joinColumn['name'])) {
			$joinColumnAnnot[] = 'name="' . $joinColumn['name'] . '"';
		}

		if (isset($joinColumn['referencedColumnName'])) {
			$joinColumnAnnot[] = 'referencedColumnName="' . $joinColumn['referencedColumnName'] . '"';
		}

		if (isset($joinColumn['unique']) && $joinColumn['unique']) {
			$joinColumnAnnot[] = 'unique=' . ($joinColumn['unique'] ? 'true' : 'false');
		}

		if (isset($joinColumn['nullable'])) {
			$joinColumnAnnot[] = 'nullable=' . ($joinColumn['nullable'] ? 'true' : 'false');
		}

		if (isset($joinColumn['onDelete'])) {
			$joinColumnAnnot[] = 'onDelete="' . ($joinColumn['onDelete'] . '"');
		}

		if (isset($joinColumn['columnDefinition'])) {
			$joinColumnAnnot[] = 'columnDefinition="' . $joinColumn['columnDefinition'] . '"';
		}

		return '@' . $this->annotationsPrefix . 'JoinColumn(' . implode(', ', $joinColumnAnnot) . ')';
	}

	/**
	 * @param EntityFieldInfo $fieldInfo
	 * @param EntityInfo $entityInfo
	 * @return string
	 */
	protected function generateFieldMappingPropertyDocBlock(EntityFieldInfo $fieldInfo, EntityInfo $entityInfo) {
		$lines = array();
		$lines[] = $this->spaces . '/**';
		$lines[] = $this->spaces . ' * @var ' . $this->getType($fieldInfo->getType());
		$lines[] = $this->spaces . ' *';

		$column = array();
		if ($fieldInfo->getColumnName()) {
			$column[] = 'name="' . $fieldInfo->getColumnName() . '"';
		}

		if ($fieldInfo->getType()) {
			$column[] = 'type="' . $fieldInfo->getType() . '"';
		}

		if ($fieldInfo->getLength()) {
			$column[] = 'length=' . $fieldInfo->getLength();
		}

		if ($fieldInfo->getPrecision()) {
			$column[] = 'precision=' .  $fieldInfo->getPrecision();
		}

		if ($fieldInfo->getScale()) {
			$column[] = 'scale=' . $fieldInfo->getScale();
		}

		//if ($fieldInfo->isNullable()) {
			$column[] = 'nullable=' .  var_export($fieldInfo->isNullable(), true);
		//}

		if ($fieldInfo->getColumnDefinition()) {
			$column[] = 'columnDefinition="' . $fieldInfo->getColumnDefinition() . '"';
		}

		//if ($fieldInfo->isUnique()) {
			$column[] = 'unique=' . var_export($fieldInfo->isUnique(), true);
		//}

		$lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Column(' . implode(', ', $column) . ')';

		if ($fieldInfo->isId()) {
			$lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Id';

			if ( ($generatorType = $this->getIdGeneratorTypeString($entityInfo->getGeneratorType())) ) {
				$lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'GeneratedValue(strategy="' . $generatorType . '")';
			}

			if ( ($SGD = $entityInfo->getSequenceGeneratorDefinition()) ) {
				$sequenceGenerator = array();

				if (isset($SGD['sequenceName'])) {
					$sequenceGenerator[] = 'sequenceName="' . $SGD['sequenceName'] . '"';
				}

				if (isset($SGD['allocationSize'])) {
					$sequenceGenerator[] = 'allocationSize=' . $SGD['allocationSize'];
				}

				if (isset($SGD['initialValue'])) {
					$sequenceGenerator[] = 'initialValue=' . $SGD['initialValue'];
				}

				$lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'SequenceGenerator(' . implode(', ', $sequenceGenerator) . ')';
			}
		}

		//@NOTE: This is not present on EntityFieldInfo - do we need this? (or better: what is this?)
		/*
		if (isset($fieldMapping['version']) && $fieldMapping['version']) {
			$lines[] = $this->spaces . ' * @' . $this->annotationsPrefix . 'Version';
		}*/

		$lines[] = $this->spaces . ' */';


		//RESTORE CUSTOM ANNOTATIONS
		$prevDocComment = $fieldInfo->getDocComment();
		$mustStartWith = $this->spaces . ' * ';
		$excludes = [
			$this->spaces . ' * @var',
			$this->spaces . ' * @' . $this->annotationsPrefix . 'Column',
			$this->spaces . ' * @' . $this->annotationsPrefix . 'Id',
			$this->spaces . ' * @' . $this->annotationsPrefix . 'GeneratedValue',
			$this->spaces . ' * @' . $this->annotationsPrefix . 'SequenceGenerator'
		];
		$marker = '';//'---(CUSTOM ANNOTATIONS)---';
		$lines = $this->restoreCustomAnnotations($lines, $prevDocComment, $mustStartWith, $excludes, $marker);

		return implode("\n", $lines);
	}


	/**
	 * Finds all lines in previous docBlock which start with $mustStartWith and which are not listed in $excluded and
	 * re-injects them into annotations(at the end) below customMarker line if specified
	 * @t-o-d-o: quite weak - surely we can do better than this
	 *
	 * @param array $generatedAnnotations
	 * @param string $previousDocCommentBlock
	 * @param string $mustStartWith
	 * @param array $excludes
	 * @param string|bool $customAnnotationMarker - it will be prefixed with $mustStartWith
	 * @return array
	 */
	protected function restoreCustomAnnotations($generatedAnnotations, $previousDocCommentBlock, $mustStartWith, $excludes, $customAnnotationMarker=false) {
		$answer = $generatedAnnotations;
		$annotationsToRestore = [];
		if($customAnnotationMarker && !empty($customAnnotationMarker)) {
			$customAnnotationMarker = $mustStartWith . $customAnnotationMarker;
			$excludes[] = $customAnnotationMarker;
		}
		$oldAnnotations = explode("\n", $previousDocCommentBlock);
		//find lines to be considered as custom annotation
		foreach($oldAnnotations as $line) {
			if(substr($line, 0, strlen($mustStartWith)) == $mustStartWith) {
				$excluded = false;
				foreach($excludes as $exclude) {
					if(substr($line, 0, strlen($exclude)) == $exclude) {
						$excluded = true;
						break;
					}
				}
				if(!$excluded && $line != $mustStartWith) {
					$annotationsToRestore[] = $line;
				}
			}
		}
		//restore custom annotations below the marker
		if(count($annotationsToRestore)) {
			if($customAnnotationMarker && !empty($customAnnotationMarker)) {
				array_splice($answer, count($answer)-1, 0, $mustStartWith);
				array_splice($answer, count($answer)-1, 0, $customAnnotationMarker);
			}
			foreach($annotationsToRestore as $line) {
				array_splice($answer, count($answer)-1, 0, $line);
			}
		}
		return ($answer);
	}


	//---------------------------------------------------------------------------------------------------GETTERS/SETTERS
	/**
	 * Sets the number of spaces the exported class should have.
	 * @param integer $numSpaces
	 * @return void
	 */
	public function setNumSpaces($numSpaces) {
		$this->spaces = str_repeat(' ', $numSpaces);
		$this->numSpaces = $numSpaces;
	}

	/**
	 * Sets whether or not to try and update the entity if it already exists (used in writeEntityClass)
	 * @param bool $bool
	 * @return void
	 */
	public function setUpdateEntityIfExists($bool) {
		$this->updateEntityIfExists = $bool;
	}

	/**
	 * Sets whether or not to regenerate the entity if it exists (used in writeEntityClass)
	 * @param bool $bool
	 * @return void
	 */
	public function setRegenerateEntityIfExists($bool) {
		$this->regenerateEntityIfExists = $bool;
	}

	/**
	 * Should an existing entity be backed up if it already exists? (used in writeEntityClass)
	 * @param bool $bool
	 * @return void
	 */
	public function setBackupExisting($bool) {
		$this->backupExisting = $bool;
	}

	/**
	 * Adds spaces in front of each line in a code block
	 * @param string $code
	 * @param int $num
	 * @return string
	 */
	protected function prefixCodeWithSpaces($code, $num = 1) {
		$lines = explode("\n", $code);
		foreach ($lines as $key => $value) {
			if ( ! empty($value)) {
				$lines[$key] = str_repeat($this->spaces, $num) . $lines[$key];
			}
		}
		return implode("\n", $lines);
	}

	/**
	 * Returns php type for Doctrine DBAL types
	 * @param string $type
	 * @return string
	 */
	protected function getType($type) {
		if (isset($this->typeAlias[$type])) {
			return $this->typeAlias[$type];
		}
		return $type;
	}

	/**
	 * @param integer $type The inheritance type used by the class and its subclasses.
	 * @return string The literal string for the inheritance type.
	 * @throws \InvalidArgumentException When the inheritance type does not exists.
	 */
	protected function getInheritanceTypeString($type) {
		if ( ! isset(self::$inheritanceTypeMap[$type])) {
			throw new \InvalidArgumentException(sprintf('Invalid provided InheritanceType: %s', $type));
		}
		return self::$inheritanceTypeMap[$type];
	}

	/**
	 * @param integer $type The policy used for change-tracking for the mapped class.
	 * @return string The literal string for the change-tracking type.
	 * @throws \InvalidArgumentException When the change-tracking type does not exists.
	 */
	protected function getChangeTrackingPolicyString($type)	{
		if ( ! isset(self::$changeTrackingPolicyMap[$type])) {
			throw new \InvalidArgumentException(sprintf('Invalid provided ChangeTrackingPolicy: %s', $type));
		}
		return self::$changeTrackingPolicyMap[$type];
	}

	/**
	 * @param integer $type The generator to use for the mapped class.
	 * @return string The literal string for the generator type.
	 * @throws \InvalidArgumentException    When the generator type does not exists.
	 */
	protected function getIdGeneratorTypeString($type) {
		if ( ! isset(self::$generatorStrategyMap[$type])) {
			throw new \InvalidArgumentException(sprintf('Invalid provided IdGeneratorType: %s', $type));
		}
		return self::$generatorStrategyMap[$type];
	}

	/**
	 * @param string $property
	 * @param EntityInfo $entityInfo
	 * @return bool
	 */
	protected function hasProperty($property, EntityInfo $entityInfo)	{
		if ($entityInfo->getExtendedClass()) {
			// don't generate property if its already on the base class.
			$reflClass = new \ReflectionClass($entityInfo->getExtendedClass());
			if ($reflClass->hasProperty($property)) {
				return true;
			}
		}
		return (in_array($property, $this->staticReflection['properties']));
	}

	/**
	 * @param string $method
	 * @param EntityInfo $entityInfo
	 *
	 * @return bool
	 */
	protected function hasMethod($method, EntityInfo $entityInfo) {
		if ($entityInfo->getExtendedClass()) {
			// don't generate method if its already on the base class.
			$reflClass = new \ReflectionClass($entityInfo->getExtendedClass());
			if ($reflClass->hasMethod($method)) {
				return true;
			}
		}
		return (in_array($method, $this->staticReflection['methods']));
	}

	/**
	 * From ["a", "b", "c"] -> '{"a","b","c"}'
	 * @param array $arr
	 * @return string
	 */
	private function convertNonAssociativeArrayForAnnotation($arr) {
		$values = array();
		foreach ($arr as $v) {
			$values[] = '"' . $v . '"';
		}
		return ('{' . implode(', ', $values) . '}');
	}

	/**
	 * Checks if entity has any association mappings with ???ToMany types
	 * This is used primarily so that we know we must add to use statements the \Doctrine\Common\Collections\Collection types
	 * @param EntityInfo $entityInfo
	 * @return bool
	 */
	private function _entityNeedsCollectionTypes(EntityInfo $entityInfo) {
		$answer = false;
		/** @var EntityAssociationInfo $associationInfo */
		foreach ($entityInfo->getAssociations() as $associationInfo) {
			if($associationInfo->getType() & ClassMetadataInfo::TO_MANY) {
				$answer = true;
				break;
			}
		}
		return $answer;
	}
}
