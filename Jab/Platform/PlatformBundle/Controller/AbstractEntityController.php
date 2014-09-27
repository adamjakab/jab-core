<?php
namespace Jab\Platform\PlatformBundle\Controller;

use Jab\Config\EntityBundle\Tools\Info\EntityInfo;
use Jab\Platform\Tools\EntityAbstractor\AbstractEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Jab\Platform\Tools\EntityAbstractor\EntityAbstractor;

/**
 * Class AbstractEntityController - generic purpose controller for Jab entities
 */
class AbstractEntityController extends JabController {
	/**
	 * @param string $entityName
	 * @return array
	 * @Template(template="JabPlatformBundle:AbstractEntity:index.html.twig")
	 */
	public function indexAction($entityName) {
		return ($this->container->get("jab.tool.entity_abstractor")
			->setOperationMode("INDEX")
			->setEntity($entityName, "a")
			->execute()
			->getResponse()
		);
	}

	/**
	 * @param string $entityName
	 * @param        $id
	 * @return array
	 * @Template(template="JabPlatformBundle:AbstractEntity:view.html.twig")
	 */
	public function viewAction($entityName, $id) {
		return ($this->container->get("jab.tool.entity_abstractor")
			->setOperationMode("VIEW")
			->setEntity($entityName, "a")
			->setWhere('a.id = :id', 'id', $id)
			->execute()
			->getResponse()
		);
	}

	/**
	 * @param $entityName
	 * @return array
	 * @Template(template="JabPlatformBundle:AbstractEntity:edit.html.twig")
	 */
	public function newAction($entityName) {
		return ($this->container->get("jab.tool.entity_abstractor")
			->setOperationMode("NEW")
			->setEntity($entityName, "a")
			->execute()
			->getResponse()
		);
	}

	/**
	 * @param string $entityName
	 * @param        $id
	 * @return array
	 * @Template(template="JabPlatformBundle:AbstractEntity:edit.html.twig")
	 */
	public function editAction($entityName, $id) {
		return ($this->container->get("jab.tool.entity_abstractor")
			->setOperationMode("EDIT")
			->setEntity($entityName, "a")
			->setWhere('a.id = :id', 'id', $id)
			->execute()
			->getResponse()
		);
	}

	/**
	 * @param string $entityName
	 * @param        $id
	 * @return RedirectResponse
	 */
	public function deleteAction($entityName, $id) {
		return ($this->container->get("jab.tool.entity_abstractor")
			->setOperationMode("DELETE")
			->setEntity($entityName, "a")
			->setWhere('a.id = :id', 'id', $id)
			->execute()
			->getResponse()
		);
	}

}