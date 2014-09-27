<?php
namespace Jab\Config\EntityBundle\Controller;

use Jab\Config\EntityBundle\Event\EntityChangeEvent;
use Jab\Config\EntityBundle\Event\PlatformEvents;
use Jab\Config\EntityBundle\Tools\Info\EntityInfo;
use Jab\Config\EntityBundle\Form\Type\EntityFieldType;
use Jab\Config\EntityBundle\Form\Type\EntityType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Jab\Platform\PlatformBundle\Controller\JabController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultController
 * @Route(path="/")
 */
class DefaultController extends JabController {
	/**
	 * @Route(name="configuration-entity", path="/")
	 * @Template()
	 */
	public function indexAction() {
		$IF = $this->container->get("jab.config.entity.info_factory");
		return array(
			'entities' => $IF->getEntityList(),
			'platformInSync' => $IF->isPlatformInSync()
		);
	}


	/**
	 * @Route(name="configuration-entity-details", path="/view/{entityName}")
	 * @Template()
	 */
	public function viewAction($entityName) {
		$IF = $this->container->get("jab.config.entity.info_factory");
		if ( ($entityData = $IF->getEntityClassInfo($entityName)) ) {
			return array(
				"entityData" => $entityData
			);
		} else {//entity was not found or did not initialize
			$redirectUrl = $this->generateUrl("configuration-entity");
			return $this->redirect($redirectUrl);
		}
	}


	/**
	 * @Route(name="configuration-edit-entity", path="/editentity/{entityName}")
	 * @Template()
	 * @param Request $request
	 * @param string $entityName
	 * @return array
	 * @throws \Exception
	 */
	public function editEntityAction(Request $request, $entityName=null) {
		$IF = $this->container->get("jab.config.entity.info_factory");
		if (!($entityInfo = $IF->getEntityClassInfo($entityName)) ) {
			$entityInfo = new EntityInfo(null);
		}

		if(!$entityInfo->isManagedEntity()) {
			throw new \Exception("You cannot edit a not Jab managed entity!");
		}
		if(!$entityInfo->isEditable()) {
			throw new \Exception("You cannot edit Jab(SYSTEM) entity!");
		}
		$form = $this->createForm(new EntityType($IF), $entityInfo, []);
		$form->handleRequest($request);
		if($form->isSubmitted() && $form->isValid()) {

			//Tell listeners that Entity has been updated
			$dispatcher = $this->container->get("event_dispatcher");
			$event = new EntityChangeEvent($entityInfo);
			$dispatcher->dispatch(PlatformEvents::JAB_ENTITY_UPDATE, $event);

			$redirectUrl = $this->generateUrl("configuration-entity-details", ["entityName"=>$entityInfo->getEntityName()]);
			return $this->redirect($redirectUrl);
		}

		return array(
			"entityName" => $entityName,
			"entityData" => $entityInfo,
			"form" => $form->createView()
		);
	}


	/**
	 * @Route(name="configuration-edit-field", path="/editfield/{entityName}/{fieldName}")
	 * @Template()
	 * @param Request $request
	 * @param string $entityName
	 * @param string $fieldName
	 * @return array
	 * @throws \Exception
	 */
	public function editFieldAction(Request $request, $entityName, $fieldName=null) {
		$IF = $this->container->get("jab.config.entity.info_factory");
		$entityInfo = $IF->getEntityClassInfo($entityName);
		if(!$entityInfo->isManagedEntity()) {
			throw new \Exception("You cannot edit the field of a not Jab managed entity!");
		}

		if(!($fieldInfo = $entityInfo->getField($fieldName))) {
			//NEW FIELD
			$fieldInfo = $entityInfo->addField([
				"declaringClass" => $entityInfo->getEntityName(),
				"access" => "private"
			]);
		} else {
			//ALREADY EXISTING FIELD
		}

		if(!$fieldInfo->isEditable()) {
			throw new \Exception("You cannot edit this field!");
		}

		$form = $this->createForm(new EntityFieldType(), $fieldInfo, []);
		$form->handleRequest($request);

		//was save button clicked? (otherwise it was a type change (on new field) that submits the form to get type specific fields)
		$saveButtonClick = $form->get('save')->isClicked();
		if($saveButtonClick && $form->isSubmitted() && $form->isValid()) {
			//Tell listeners that Entity has been updated
			$dispatcher = $this->container->get("event_dispatcher");
			$event = new EntityChangeEvent($entityInfo);
			$dispatcher->dispatch(PlatformEvents::JAB_ENTITY_UPDATE, $event);
			$redirectUrl = $this->generateUrl("configuration-entity-details", ["entityName"=>$entityInfo->getEntityName()]);
			return $this->redirect($redirectUrl);
		}

		return array(
			"entityName" => $entityName,
			"fieldName" => $fieldName,
			"entityData" => $entityInfo,
			"fieldData" => $fieldInfo,
			"form" => $form->createView()
		);
	}


	/**
	 * @Route(name="configuration-delete-field", path="/deletefield/{entityName}/{fieldName}")
	 * @param string $entityName
	 * @param string $fieldName
	 * @return RedirectResponse
	 */
	public function deleteFieldAction($entityName, $fieldName) {
		$IF = $this->container->get("jab.config.entity.info_factory");
		$entityInfo = $IF->getEntityClassInfo($entityName);
		$entityInfo->removeField($fieldName);

		//Tell listeners that Entity has been updated
		$dispatcher = $this->container->get("event_dispatcher");
		$event = new EntityChangeEvent($entityInfo);
		$dispatcher->dispatch(PlatformEvents::JAB_ENTITY_UPDATE, $event);

		$redirectUrl = $this->generateUrl("configuration-entity-details", ["entityName"=>$entityName]);
		return $this->redirect($redirectUrl);
	}


	/**
	 * Clears platform cache and redirects to entities listing
	 * This is needed when there are changes in database from oputside the app
	 * @Route(name="configuration-platform-recheck", path="/recheck")
	 * @return RedirectResponse
	 */
	public function recheckAction() {
		$IF = $this->container->get("jab.config.entity.info_factory");
		$IF->clearFactoryCache(true);
		$redirectUrl = $this->generateUrl("configuration-entity");
		return $this->redirect($redirectUrl);
	}

	/**
	 * Syncs platform and redirects to entities listing
	 * @Route(name="configuration-platform-sync", path="/sync")
	 * @return RedirectResponse
	 */
	public function syncAction() {
		$IF = $this->container->get("jab.config.entity.info_factory");
		$IF->syncPlatform();
		$redirectUrl = $this->generateUrl("configuration-entity");
		return $this->redirect($redirectUrl);
	}
}
