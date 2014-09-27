<?php
namespace Jab\Config\EntityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Jab\Config\EntityBundle\Tools\Info\FieldType;
use Jab\Config\EntityBundle\Tools\Info\EntityFieldInfo;

/**
 * Entity Field/column form
 * Class EntityFieldType
 */
class EntityFieldType extends AbstractType {

	/**
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Jab\Config\EntityBundle\Tools\Info\EntityFieldInfo'
		));
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param array                $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('columnName', 'text', ["label"=>"Column Name", "required"=>false])
			->add('fieldName', 'text', ["label"=>"Field Name", "required"=>false])
			->add('save', 'submit', ['label' => 'Save It!', "attr" => ["class"=>"hidden"]]);/*invisible button*/

		/**
		 * @param FormEvent $event
		 * @param string $eventType
		 */
		$formModifier = function (FormEvent $event, $eventType) {
			$form = $event->getForm();
			/** @var EntityFieldInfo $JAB */
			$JAB = $event->getData();
			$columnType = (is_array($JAB)?$JAB["type"]:$JAB->getType());
			if(!FieldType::hasTypeDefinition($columnType)) {
				$columnType = "string";
				$event->getData()->setType($columnType);
				$event->getData()->setLength(32);
			}
			$FTD = FieldType::getTypeDefinition($columnType);

			//echo '<br/><br/><pre>FORMDATA:' . print_r($FTD, true) . '</pre>';

			//TYPE
			if (!is_array($JAB)&&$JAB->getFieldName()) {
				$form->add('type', 'hidden', []);
			} else {
				$form->add('type', 'choice', ["label"=>"Column Type", "choices"=>FieldType::getDefinitionList()]);
			}

			//LENGTH
			$fieldType = ($FTD["hasLength"]===true?"integer":"hidden");
			$form->add('length', $fieldType, ["label"=>"Column Length", "required"=>false]);

			//PRECISION
			$fieldType = ($FTD["hasPrecision"]===true?"integer":"hidden");
			$form->add('precision', $fieldType, ["label"=>"Precision", "required"=>false]);

			//SCALE
			$fieldType = ($FTD["hasScale"]===true?"integer":"hidden");
			$form->add('scale', $fieldType, ["label"=>"Scale", "required"=>false]);

			//UNIQUE
			if($FTD["canBeUnique"]===true) {
				$form->add('unique', 'choice', ["label"=>"Unique", "expanded"=>true, "choices"=>[true=>"Yes", false=>"No"]]);
			} else {
				$form->add('unique', 'hidden', []);
			}

			//NULLABLE
			if($FTD["canBeNullable"]===true) {
				$form->add('nullable', 'choice', ["label"=>"Nullable", "expanded"=>true, "choices"=>[true=>"Yes", false=>"No"]]);
			} else {
				$form->add('nullable', 'hidden', []);
			}
		};

		$builder->addEventListener(
			FormEvents::PRE_SET_DATA,
			function (FormEvent $event) use ($formModifier) {
				$formModifier($event, "PRE_SET_DATA");
			}
		);


		$builder->addEventListener(
			FormEvents::PRE_SUBMIT,
			function (FormEvent $event) use ($formModifier) {
				$formModifier($event, "PRE_SUBMIT");
			}
		);


	}

	/**
	 * @return string
	 */
	public function getName() {
		return('entity_field');
	}
}
