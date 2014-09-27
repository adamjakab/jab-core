<?php
namespace Jab\Config\EntityBundle\Form\Type;

use Jab\Config\EntityBundle\Tools\Info\InfoFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Jab\Config\EntityBundle\Tools\Info\EntityInfo;
use Jab\Config\EntityBundle\Tools\Info\FieldType;
use Jab\Config\EntityBundle\Tools\Info\EntityFieldInfo;

/**
 * Entity form
 * Class EntityType
 */
class EntityType extends AbstractType {
	/**
	 * @var InfoFactory
	 */
	private $infoFactory;

	public function __construct(InfoFactory $infoFactory) {
		$this->infoFactory = $infoFactory;
	}

	/**
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'data_class' => 'Jab\Config\EntityBundle\Tools\Info\EntityInfo'
		));
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param array                $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('database_table_name', 'text', ['label' => 'Database Table Name'])
			->add('save', 'submit', ['label' => 'Save It!', "attr" => ["class"=>"hidden_xxx"]]);/*invisible button*/

		$formModifier = function (FormEvent $event, $eventType) {
			$form = $event->getForm();
			/** @var EntityInfo $entityInfo */
			$entityInfo = $event->getData();
			$isNew = $entityInfo->isUnsavedEntity();

			//ENTITY BUNDLE
			if ($isNew) {
				$choices = $this->infoFactory->getBundlesList();
				$form->add('bundleName', 'choice', ['label' => 'Bundle Name', "choices"=>$choices, "required"=>true]);
			} else {
				$form->add('bundleName', 'hidden', []);
			}

			//ENTITY NAME
			if ($isNew) {
				$form->add('className', 'text', ['label' => 'Entity Name']);
			} else {
				$form->add('className', 'hidden', []);
			}

			//CUSTOM REPOSITORY
			if ($isNew) {
				$form->add('hasRepository', 'checkbox', ['label' => 'Has Repository', "required"=>false]);
			} else {
				$form->add('hasRepository', 'hidden', []);
			}

			//MAPPED SUPERCLASS



			//add extended class choice field by adding the current class name to the exclude list
			$excludeList = [];
			$excludeList[] = $entityInfo->getEntityName();
			$choices = $this->infoFactory->getSuperclassNamesList($excludeList);
			$form->add('extendedClass', 'choice', ["label"=>"Extended Class", "choices"=>$choices, "required"=>true]);

		};

		$builder->addEventListener(
			FormEvents::PRE_SET_DATA,
			function (FormEvent $event) use ($formModifier) {
				$formModifier($event, "PRE_SET_DATA");
			}
		);
	}

	/**
	 * @return string
	 */
	public function getName() {
		return('entity');
	}
}
