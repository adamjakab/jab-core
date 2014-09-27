<?php
namespace Jab\Platform\Tools\EntityAbstractor;

use Doctrine\Common\Collections\ArrayCollection;
use Jab\Config\EntityBundle\Tools\Info\EntityInfo;
use Jab\Config\EntityBundle\Tools\Info\EntityFieldInfo;
use Jab\Config\EntityBundle\Tools\Info\FieldType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;



class AbstractEntityType extends AbstractType {

	/**
	 * @var AbstractEntity
	 */
	private $abstractEntity;

	/**
	 * @var EntityInfo
	 */
	private $entityInfo;

	/**
	 * @var ArrayCollection
	 */
	private $displayFields;

	/**
	 * @param AbstractEntity $abstractEntity
	 * @param EntityInfo $entityInfo
	 * @param ArrayCollection $displayFields
	 */
	public function __construct($abstractEntity, $entityInfo, $displayFields) {
		$this->abstractEntity = $abstractEntity;
		$this->entityInfo = $entityInfo;
		$this->displayFields = $displayFields;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return('abstractEntityType');
	}

	/**
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Jab\Platform\Tools\EntityAbstractor\AbstractEntity'
		));
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param array                $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder = $this->addFieldsToForm($builder);
		//SUBMIT BUTTONS!?
		$builder->add('save', 'submit', ['label' => 'Save', "attr" => ["class" => "hidden_xxx"]]);
	}

	/**
	 * Enumerate and add all listed (and existent) fields to form (exclude id fields)
	 * @param FormBuilderInterface $builder
	 * @return FormBuilderInterface
	 */
	private function addFieldsToForm(FormBuilderInterface $builder) {
		foreach($this->displayFields as $fieldName) {
			if ( ($field = $this->entityInfo->getField($fieldName)) ) {
				if(!$field->isId()) {
					$builder = $this->addFieldToForm($field, $builder);
				}
			}
		}
		return($builder);
	}

	/**
	 * @param EntityFieldInfo $fieldInfo
	 * @param FormBuilderInterface $builder
	 * @return FormBuilderInterface
	 */
	private function addFieldToForm(EntityFieldInfo $fieldInfo, FormBuilderInterface $builder) {
		$fieldName = $fieldInfo->getFieldName();
		$fieldType = $this->abstractEntity->getFieldAttribute($fieldName, "type");
		$fieldTypeHint = $this->abstractEntity->getFieldAttribute($fieldName, "formTypeHint");
		$fieldOptions = $this->abstractEntity->getFieldAttribute($fieldName, "formTypeOptions", []);
		//
		$formFieldTypeName = $this->getMappedFormTypeName($fieldType, $fieldTypeHint);

		//check if we have specific method to assemble field options specific to this field type
		$FFTOptionsAssemblerCallable = [$this, 'getFieldOptions___' . $formFieldTypeName];
		if(is_callable($FFTOptionsAssemblerCallable)) {
			$fieldOptions = call_user_func($FFTOptionsAssemblerCallable, $fieldName);
		}
		//
		return($builder->add($fieldName, $formFieldTypeName, $fieldOptions));
	}


	/**
	 * @param string $fieldName
	 * @return array
	 */
	private function getFieldOptions___integer($fieldName) {
		$fieldOptions = $this->abstractEntity->getFieldAttribute($fieldName, "formTypeOptions", []);
		$fieldOptions["label"] = strtoupper($fieldName);
		return($fieldOptions);
	}



	/**
	 * 1) if we have typeHint(hinting what form field type to use) and it is compatible with doctrineType - then it wins
	 * 2) otherwise the first form field type compatible with doctrineType will be retured
	 * !!! On no match Exception will be thrown!
	 * @param string $doctrineType
	 * @param string $typeHint
	 * @return string
	 */
	private function getMappedFormTypeName($doctrineType, $typeHint=null) {
		$answer = false;
		if($typeHint && isset($this->fieldTypesMap[$typeHint]) && is_array($this->fieldTypesMap[$typeHint])) {
			$compatibilityList = $this->fieldTypesMap[$typeHint];
			if(in_array($doctrineType, $compatibilityList)) {
				$answer = $typeHint;
			}
		}
		if(!$answer) {
			foreach($this->fieldTypesMap as $formFieldType => $compatibilityList) {
				if(in_array($doctrineType, $compatibilityList)) {
					$answer = $formFieldType;
					break;
				}
			}
		}
		if(!$answer) {
			throw new \LogicException(sprintf("No Form Field Type was found for doctrineType: '%s' and typeHint: '%s'!", $doctrineType, $typeHint));
		}
		return($answer);
	}


	/**
	 * @var array - default mapping: "key" is the form field type and "value" is array of doctrine types allowed to use it
	 */
	private $fieldTypesMap = [
		//TEXTUAL
		"text"              => ["string"],
		"textarea"          => ["string","text"],
		"email"             => ["string"],
		"password"          => ["string"],
		"url"               => ["string"],
		"search"            => ["string"],
		//NUMERIC
		"integer"           => ["integer","smallint","bigint"],
		"number"            => ["integer","smallint","bigint","decimal","float"],
		"money"             => ["integer","smallint","bigint","decimal","float"],
		"percent"           => ["integer","smallint","bigint","decimal","float"],
		//DATE
		"date"              => ["date"],
		"time"              => ["time"],
		"datetime"          => ["datetime"],
		"birthday"          => ["date"],
		//CHOICE
		"choice"            => ["string","integer","smallint","boolean"],
		"entity"            => ["string","integer","smallint","boolean"],
		"country"           => ["string"],
		"language"          => ["string"],
		"locale"            => ["string"],
		"timezone"          => ["string"],
		"currency"          => ["string"],
		/*OTHER - TBC*/
	];
	//array,json_array,object ??
}