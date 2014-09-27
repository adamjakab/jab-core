<?php
namespace Jab\Platform\PlatformBundle\Twig;

use Jab\Platform\PlatformBundle\Router\DynamicRoutesLoader;
use Jab\Platform\Tools\EntityAbstractor\AbstractEntity;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/**
 * Class AbstractEntityExtension
 * @DI\Service(id="jab.platform.platform.abstract_entity_extension")
 * @DI\Tag(name="twig.extension")
 */
class AbstractEntityExtension extends \Twig_Extension{
	/**
	 * @var \Twig_Environment
	 */
	protected $environment;

	/**
	 * @var Router
	 */
	private $router;

	/**
	 * @var DynamicRoutesLoader
	 */
	private $routesLoader;

	/**
	 * @var string
	 */
	private $fieldTemplateName = 'JabPlatformBundle:AbstractEntity/Fields:%s.html.twig';

	/**
	 * @DI\InjectParams({
	 *      "router" = @DI\Inject("router"),
	 *      "dynamic_routes_loader" = @DI\Inject("jab.platform.platform.dynamic_routes_loader")
	 * })
	 */
	public function __construct(Router $router, DynamicRoutesLoader $dynamic_routes_loader) {
		$this->router = $router;
		$this->routesLoader = $dynamic_routes_loader;
	}

	/**
	 * {@inheritDoc}
	 */
	public function initRuntime(\Twig_Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * @return array
	 */
	public function getFunctions() {
		return array(
			'jab_abstract_entity_render_field' => new \Twig_SimpleFunction('jab_abstract_entity_render_field', array($this, 'renderAbstractEntityField'), ['is_safe' => ['html'] ]),
			'jab_abstract_entity_path' => new \Twig_SimpleFunction('jab_abstract_entity_path', array($this, 'getAbstractEntityPath'), ['is_safe' => ['html'] ])
		);
	}


	/**
	 * @param AbstractEntity|string $abstractEntity
	 * @param string $action
	 * @return string
	 * @throws \LogicException
	 */
	public function getAbstractEntityPath($abstractEntity, $action) {
		$answer = '';
		if(is_object($abstractEntity) && get_class($abstractEntity) == 'Jab\Platform\Tools\EntityAbstractor\AbstractEntity') {
			$entityName = $abstractEntity->getEntityName();
		} else if (is_string($abstractEntity)) {
			$entityName = $abstractEntity;
		} else {
			throw new \LogicException("Parameter abstractEntity must be an instance of AbstractEntity or a string!");
		}
		$routeName = $this->routesLoader->convertEntityNameToRouteName($entityName, $action);

		switch(strtoupper($action)) {
			case "VIEW":
				if($abstractEntity->getId()) {
					$answer = $this->router->generate($routeName, ['id'=>$abstractEntity->getId()]);
				} else {
					//like CANCEL button on edit view which on adding new entity will not have an id on $abstractEntity
					$routeName = $this->routesLoader->convertEntityNameToRouteName($entityName, "INDEX");
					$answer = $this->router->generate($routeName, ['id'=>$abstractEntity->getId()]);
				}
				break;
			case "EDIT":
				$answer = $this->router->generate($routeName, ['id'=>$abstractEntity->getId()]);
				break;
			case "DELETE":
				$answer = $this->router->generate($routeName, ['id'=>$abstractEntity->getId()]);
				break;
			default: /* INDEX, NEW, ...*/
				$answer = $this->router->generate($routeName);
				break;
		}
		return($answer);
	}

	/**
	 * @param AbstractEntity $abstractEntity
	 * @param string $fieldName
	 * @return string
	 */
	public function renderAbstractEntityField($abstractEntity, $fieldName) {
		$answer = "";
		if($abstractEntity->hasField($fieldName)) {
			//$twigLoader = $this->environment->getLoader();
			//$templating = $this->_getKernel()->getContainer()->get("templating");
			$fallbackFieldType = 'unknown';
			$fieldType = $abstractEntity->getFieldAttribute($fieldName, "type");
			$fieldValue = $abstractEntity->getFieldAttribute($fieldName, "value");
			$fieldTemplateName = sprintf($this->fieldTemplateName, $fieldType);
			if(!$this->templateExists($fieldTemplateName)) {
				$fieldTemplateName = sprintf($this->fieldTemplateName, $fallbackFieldType);
			}
			if($this->templateExists($fieldTemplateName)) {
				$answer = $this->environment->render(
					$fieldTemplateName,
					['field' => $fieldValue, 'fieldType' => $fieldType]
				);
			} else {
				$answer = '<span title="No renderer found for this field!" data-toggle="tooltip" data-placement="top">'
					. print_r($fieldValue, true)
					. '</span>';
			}

		}
		return($answer);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	private function templateExists($name) {
		return($this->environment->getLoader()->exists((string)$name));
	}

	public function getName() {
		return 'abstract_entity_extension';
	}
}