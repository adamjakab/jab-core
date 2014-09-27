<?php

namespace Jab\App\AccountBundle\Controller;


use Jab\Config\EntityBundle\Tools\Info\EntityInfo;
use Jab\Platform\PlatformBundle\Controller\JabController;
use Jab\Platform\Tools\EntityAbstractor\AbstractEntityType;
use Jab\Platform\Tools\EntityAbstractor\EntityAbstractor;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class DefaultController
 * @Route(path="/")
 */
class DefaultController extends JabController {
	/**
	 * @var string
	 */
	protected $entityName = 'Jab\App\AccountBundle\Entity\Account';


    /**
     * @Route(name="jab-app-accountbundle-entity-account-index", path="/")
     * @Template()
     */
    public function indexAction() {
	    return($this->container->get("jab.tool.entity_abstractor")
		    ->setOperationMode("INDEX")
		    ->setEntity($this->entityName, "a")
		    ->setDisplayFields(["id", "name", "vatId", "TypeDesc"])
			->setViewLinkFieldName("name")
		    ->execute()
		    ->getResponse()
	    );
    }


	/**
	 * @Route(name="jab-app-accountbundle-entity-account-view", path="/view/{id}")
	 * @Template()
	 * @param integer $id
	 * @return array
	 */
	public function viewAction($id) {
		return($this->container->get("jab.tool.entity_abstractor")
			->setOperationMode("VIEW")
			->setEntity($this->entityName, "a")
			->setWhere('a.id = :id', 'id', $id)
			/*->setDisplayFields(["name", "vatId", "TypeDesc"])*/
			->execute()
			->getResponse()
		);
	}

	/**
	 * @Route(name="jab-app-accountbundle-entity-account-new", path="/new")
	 * @Template(template="JabAppAccountBundle:Default:edit.html.twig")
	 * @return array
	 */
	public function newAction() {
		return($this->container->get("jab.tool.entity_abstractor")
			->setOperationMode("NEW")
			->setEntity($this->entityName, "a")
			->setDisplayFields(["name", "vatId"])
			->execute()
			->getResponse()
		);
	}

	/**
	 * @Route(name="jab-app-accountbundle-entity-account-edit", path="/edit/{id}")
	 * @Template()
	 * @param integer $id
	 * @return array
	 */
	public function editAction($id) {
		return($this->container->get("jab.tool.entity_abstractor")
			->setOperationMode("EDIT")
			->setEntity($this->entityName, "a")
			->setWhere('a.id = :id', 'id', $id)
			->setDisplayFields(["name", "vatId", "type"])
			->execute()
			->getResponse()
		);
	}

	/**
	 * @Route(name="jab-app-accountbundle-entity-account-delete", path="/delete/{id}")
	 * @param integer $id
	 * @return RedirectResponse
	 */
	public function deleteAction($id) {
		return($this->container->get("jab.tool.entity_abstractor")
			->setOperationMode("DELETE")
			->setEntity($this->entityName, "a")
			->setWhere('a.id = :id', 'id', $id)
			->execute()
			->getResponse()
		);
	}
}
