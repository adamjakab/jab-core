<?php
namespace Jab\Platform\Tools\EntityAbstractor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Jab\App\AccountBundle\Entity\Account;
use Jab\Config\EntityBundle\Tools\Info\EntityFieldInfo;
use Jab\Config\EntityBundle\Tools\Info\EntityInfo;
use Jab\Config\EntityBundle\Tools\Info\InfoFactory;
use Jab\Platform\PlatformBundle\Router\DynamicRoutesLoader;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Knp\Component\Pager\Paginator;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class EntityAbstractor
 * @DI\Service(id="jab.tool.entity_abstractor")
 */
class EntityAbstractor {
	/**
	 * @var array
	 */
	protected static $_instances = [];

	/**
	 * @var EntityAbstractor
	 */
	protected static $_current_instance;

	/**
	 * @var string
	 */
	private $operationMode;

	/**
	 * @var array
	 */
	protected $_config = [];

	/**
	 * @var AbstractEntityQueryBuilder
	 */
	protected $_queryBuilder;

	/**
	 * @var InfoFactory
	 */
	private $infoFactory;

	/**
	 * @var FormFactory
	 */
	private $formFactory;

	/**
	 * @var Paginator
	 */
	private $paginator;

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var DynamicRoutesLoader
	 */
	private $dynamicRoutesLoader;

	/**
	 * @var Router
	 */
	private $router;

	/**
	 * @var bool
	 */
	protected $executed = false;

	/**
	 * @var EntityInfo
	 */
	protected $entityInfo;

	/**
	 * @var ArrayCollection
	 */
	protected $_results;

	/**
	 * @var mixed (we should have an interface for all JabEntities)- The "real"(not abstracted) entity currently being edited
	 */
	private $editEntity;

	/**
	 * @var int
	 */
	protected $pagination_limit = 10;

	/**
	 * @var int
	 */
	protected $pagination_page = 1;

	/**
	 * @var ArrayCollection
	 */
	protected $displayFields;

	/**
	 * @var string
	 */
	protected $viewLinkFieldName;


	/**
	 * @DI\InjectParams({
	 *      "em"  = @DI\Inject("doctrine.orm.entity_manager"),
	 *      "if"  = @DI\Inject("jab.config.entity.info_factory"),
	 *      "ff"  = @DI\Inject("form.factory"),
	 *      "pg"  = @DI\Inject("knp_paginator"),
	 *      "rs"  = @DI\Inject("request_stack"),
	 *      "drl" = @DI\Inject("jab.platform.platform.dynamic_routes_loader"),
	 *      "rr"  = @DI\Inject("router")
	 * })
	 * @param InfoFactory $if
	 * @param Paginator $pg
	 * @param FormFactory $ff
	 * @param EntityManager $em
	 * @param RequestStack $rs
	 * @param DynamicRoutesLoader $drl
	 * @param Router $rr
	 */
	public function __construct($em, $if, $ff, $pg, $rs, $drl, $rr) {
		$this->_config = []; //$this->_container->getParameter('some_param');
		$this->_queryBuilder = new AbstractEntityQueryBuilder($em);
		$this->infoFactory = $if;
		$this->formFactory = $ff;
		$this->paginator = $pg;
		$this->request = $rs->getCurrentRequest();
		$this->dynamicRoutesLoader = $drl;
		$this->router = $rr;
		$this->_results = new ArrayCollection();
		$this->displayFields = new ArrayCollection();
		self::$_current_instance = $this;
	}

	/**
	 * Apply the predefined filters found in request to the query (sorting/page/etc)
	 * @throws \LogicException
	 */
	private function applyRequestFilters() {
		if(!$this->entityInfo) {
			throw new \LogicException("Entity must be set before applying Request filters!");
		}
		if(!$this->operationMode) {
			throw new \LogicException("Operation mode must be set for execution!");
		}
		if($this->operationMode != "INDEX") {
			return;
		}

		// sorting
		if ( ($sortFieldName = $this->request->get('sort')) ) {
			/** @var EntityFieldInfo $field */
			if( ($field = $this->entityInfo->getField($sortFieldName))) {
				$sortCol = $field->getFieldName();
				//todo: is this column really in this entity and not in join?
				$sortCol = $this->getEntityAlias() . '.' . $sortCol;
				$sortOrd = $this->request->get('direction') && strtoupper($this->request->get('direction')) == "DESC" ? "DESC" : "ASC";
				$this->setOrder($sortCol, $sortOrd);
			}
		}

		// limits
		$this->setPaginationLimit($this->request->get('recs', 10));
		$this->setPaginationPage($this->request->get('page', 1));

		// search & filters
		/*
			if ($this->search == TRUE) {
				$this->request = $this->request;
				$search_fields = array_values($this->fields);
				foreach ($search_fields as $i => $search_field) {
					$search_param = $this->request->get("sSearch_{$i}");
					if ($this->request->get("sSearch_{$i}") !== false && !empty($search_param)) {
						$field = explode(' ', trim($search_field));
						$search_field = $field[0];

						$queryBuilder->andWhere(" $search_field like '%{$this->request->get("sSearch_{$i}")}%' ");
					}
				}
			}
		 */
	}

	//todo: execute && getResponse could/should be merged!?
	/**
	 * The main execution of the query and conversion of entities to AbstractEntity instances
	 * @return $this
	 * @throws \LogicException
	 */
	public function execute() {
		if(!$this->entityInfo) {
			throw new \LogicException("Entity must be set for execution!");
		}
		if(!$this->operationMode) {
			throw new \LogicException("Operation mode must be set for execution!");
		}
		//apply filters found in request to the query (sorting/page/etc) when in INDEX mode
		$this->applyRequestFilters();

		//get array of entities from query builder
		//todo: implement a custom hydrator for this
		if($this->operationMode == "NEW") {
			$newEntityName = $this->entityInfo->getEntityName();
			$newEntity = new $newEntityName();
			$objects = [$newEntity];
		} else {
			$objects = $this->_queryBuilder->getData();
		}

		//convert entities to AbstractEntity objects and add them to _results
		$this->_results = new ArrayCollection();
		$addFieldsWithValuesOnly = ($this->operationMode == "INDEX");//in index mode limit it as much as possible
		foreach($objects as $entityObject) {
			$this->_results->add(new AbstractEntity($entityObject, $this->entityInfo, $this->displayFields, $addFieldsWithValuesOnly));
		}

		//keep the original entity instance so on recieving the form values we can update and persist it to db
		if(in_array($this->operationMode, ["EDIT", "NEW", "DELETE"]) && count($objects)==1) {
			$this->editEntity = $objects[0];
		}
		//
		$this->executed = true;
		return($this);
	}



	//--------------------------------------------------------------------------------- AFTER EXECUTION GETTERS/SETTERS
	/**
	 * @param array $additional - additional data to include in the reply
	 * @return array|RedirectResponse
	 * @throws \LogicException
	 */
	public function getResponse($additional=[]) {
		if(!$this->isExecuted()) {
			throw new \LogicException("Cannot get results before execution!");
		}
		$answer = [];
		$answer["entityName"] = $this->entityInfo->getEntityName();
		$answer["fields"] = $this->getFields();
		$answer["linkViewFieldName"] = $this->getViewLinkFieldName();

		//TEMPORARY DATA - TO BE REMOVED!
		$answer["_tmp_"] = [
			"DISPLAY FIELDS" => $this->displayFields,
			"RAW RESULT" => $this->getResults()
		];


		switch($this->operationMode) {
			case "INDEX":
				$answer["entities"] = $this->getPaginatedResults();
				$answer["entityTotalRecords"] = $this->getTotalNumberOfEntityRecords();
				break;
			case "VIEW":
				$answer["entity"] = $this->getSingleResult();
				break;
			case "EDIT":
			case "NEW":
				$answer["entity"] = $this->getSingleResult();
				$form = $this->getAbstractEntityForm();
				if(!$form instanceof RedirectResponse) {
					$answer["abstract_entity_form"] = $this->getAbstractEntityForm()->createView();
				} else {
					$answer = $form;//we have RedirectResponse in here
				}
				break;
			case "DELETE":
				$answer = $this->removeAndRedirect();
				break;
		}

		//add additional data - only if answer is still an array
		if(is_array($answer)) {
			$answer = array_merge($answer, $additional);
		}
		return($answer);
	}

	/**
	 * @return Form|FormInterface|RedirectResponse
	 */
	private function getAbstractEntityForm() {
		$options = [];
		$abstractEntity = $this->getSingleResult();
		$abstractEntityType = new AbstractEntityType($abstractEntity, $this->entityInfo, $this->displayFields);
		$form = $this->formFactory->create($abstractEntityType, $abstractEntity, $options);
		$form->handleRequest($this->request);
		if($form->isSubmitted() && $form->isValid()) {
			//Tell listeners that Entity has been updated by dispatching some events
			//...
			//update data from abstract entity to "real" entity --- It's a kinda magic!
			$abstractEntity->updateEntityObjectValues($this->editEntity, $this->entityInfo);
			//Persist data and flush entity manager
			$em = $this->_queryBuilder->getEntityManager();
			$em->persist($this->editEntity);
			$em->flush($this->editEntity);
			//redirect to entity view
			$entityId = $abstractEntity->getId();
			if(!$entityId && is_callable([$this->editEntity,"getId"])) {
				$entityId = call_user_func([$this->editEntity,"getId"]);
			}
			if($entityId) {
				$redirectRouteName = $this->dynamicRoutesLoader->convertEntityNameToRouteName($this->entityInfo->getEntityName(), "VIEW");
				$redirectUrl = $this->router->generate($redirectRouteName, ["id"=>$entityId]);
			} else {
				$redirectRouteName = $this->dynamicRoutesLoader->convertEntityNameToRouteName($this->entityInfo->getEntityName(), "INDEX");
				$redirectUrl = $this->router->generate($redirectRouteName);
			}
			return new RedirectResponse($redirectUrl, 302);
		}
		return($form);
	}

	private function removeAndRedirect() {
		$em = $this->_queryBuilder->getEntityManager();
		$em->remove($this->editEntity);
		$em->flush($this->editEntity);
		//redirect to index page
		$redirectRouteName = $this->dynamicRoutesLoader->convertEntityNameToRouteName($this->entityInfo->getEntityName(), "INDEX");
		$redirectUrl = $this->router->generate($redirectRouteName);
		return new RedirectResponse($redirectUrl, 302);
	}




	/**
	 * @return ArrayCollection
	 */
	public function getDisplayFields() {
		return $this->displayFields;
	}

	/**
	 * Set fields that should be available on the abstract entity. You have 2 options
	 *      1) null or empty array -> all fields will be included automatically from entityInfo
	 *      3) ["a","b","c"] where a,b,c are field names of entity
	 * @param array $displayFields
	 * @return $this
	 * @throws \LogicException
	 */
	public function setDisplayFields($displayFields = []) {
		if(!$this->entityInfo) {
			throw new \LogicException("Entity must be set before getting fields!");
		}
		if(!is_array($displayFields)) {
			throw new \LogicException("Use setDisplayFields by supplying an array of field names!");
		}

		if(!$displayFields) {
			//Add all fields from entityInfo
			$this->displayFields = new ArrayCollection($this->entityInfo->getFields()->getKeys());
		} else {
			//use what we have in $displayFields
			$this->displayFields = new ArrayCollection($displayFields);
		}
		return $this;
	}

	/**
	 * Used for displaying table header columns(label) with sorting(fieldName)
	 * Array is indexed by Field name: ["id"] = "Id Column Label"
	 * @return array
	 * @throws \LogicException
	 */
	public function getFields() {
		if(!$this->entityInfo) {
			throw new \LogicException("Entity must be set before getting fields!");
		}
		//if no fields have been set call setDisplayFields without argument to add them all from entityInfo
		if(!$this->displayFields->count()) {
			$this->setDisplayFields();
		}

		$answer = [];
		foreach($this->displayFields as $fieldName) {
			$field = $this->entityInfo->getField($fieldName);
			$fieldLabel = ($field ? $field->getFieldLabel() : $fieldName);
			$answer[$fieldName] = $fieldLabel;
		}

		return($answer);
	}


	/**
	 * Sets the name of the field on which we will have the link to the "VIEW" details view
	 * @param string $viewLinkFieldName
	 * @return $this
	 * @throws \LogicException
	 */
	public function setViewLinkFieldName($viewLinkFieldName) {
		if(!$this->entityInfo) {
			throw new \LogicException("Entity must be set before getting fields!");
		}
		if(!$this->displayFields->count()) {
			//throw new \LogicException("Fields must be set before setting viewLinkFieldName!");
		}
		if($this->displayFields->contains($viewLinkFieldName)) {
			$this->viewLinkFieldName = $viewLinkFieldName;
		}
		return($this);
	}

	/**
	 * @return string
	 */
	public function getViewLinkFieldName() {
		if (!$this->viewLinkFieldName) {
			$possibleFallbackNames = ["name", "title", "id"];
			foreach($possibleFallbackNames as $fldName) {
				$this->setViewLinkFieldName($fldName);
				if ($this->viewLinkFieldName == $fldName) {
					break;
				}
			}
			if (!$this->viewLinkFieldName) {
				//let's fall back to first column
				$this->setViewLinkFieldName($this->displayFields->first());
			}
		}
		return $this->viewLinkFieldName;
	}




	//--------------------------------------------------------------------------------- BEFORE EXECUTION GETTERS/SETTERS

	/**
	 * get entity name
	 * @return string
	 */
	public function getEntityName() {
		return $this->_queryBuilder->getEntityName();
	}

	/**
	 * get entity alias
	 * @return string
	 */
	public function getEntityAlias() {
		return $this->_queryBuilder->getEntityAlias();
	}

	/**
	 * get order field
	 * @return string
	 */
	public function getOrderField() {
		return $this->_queryBuilder->getOrderField();
	}

	/**
	 * get order type
	 * @return string
	 */
	public function getOrderType() {
		return $this->_queryBuilder->getOrderType();
	}

	/**
	 * get query builder
	 *
	 * @return AbstractEntityQueryBuilder|QueryBuilder
	 */
	public function getQueryBuilder() {
		return $this->_queryBuilder;
	}

	/**
	 * @return int
	 */
	public function getPaginationLimit() {
		return $this->pagination_limit;
	}

	/**
	 * @return int
	 */
	public function getPaginationPage() {
		return $this->pagination_page;
	}




	/**
	 * set entity
	 * @param string $entity_name
	 * @param string $entity_alias
	 * @return $this
	 * @throws \LogicException
	 */
	public function setEntity($entity_name, $entity_alias) {
		if($this->isExecuted()) {
			throw new \LogicException("Entity cannot be set after execution!");
		}
		$this->entityInfo = $this->infoFactory->getEntityClassInfo($entity_name);
		$this->_queryBuilder->setEntity($entity_name, $entity_alias);
		return $this;
	}




	/**
	 * add join
	 * @param string $join_field
	 * @param string $alias
	 * @param string $type
	 * @param string $cond
	 * @return $this
	 * @throws \LogicException
	 */
	public function addJoin($join_field, $alias, $type, $cond = '') {
		if($this->isExecuted()) {
			throw new \LogicException("Joins cannot be added after execution!");
		}
		$this->_queryBuilder->addJoin($join_field, $alias, $type, $cond);
		return $this;
	}

	/**
	 * set order by
	 * @param string $order_field
	 * @param string $order_type
	 * @return $this
	 * @throws \LogicException
	 */
	public function setOrder($order_field, $order_type) {
		if($this->isExecuted()) {
			throw new \LogicException("Ordering cannot be set after execution!");
		}
		$this->_queryBuilder->setOrder($order_field, $order_type);
		return $this;
	}

	/**
	 * @param int $max
	 * @param int $offset
	 * @return $this
	 * @throws \LogicException
	 */
	public function setLimiting($max, $offset=0) {
		if($this->isExecuted()) {
			throw new \LogicException("Limiting cannot be set after execution!");
		}
		$this->_queryBuilder->setRecordsMax($max);
		$this->_queryBuilder->setRecordsOffset($offset);
		return $this;
	}

	/**
	 * set query where
	 * @param string $where
	 * @param string $key
	 * @param mixed $value
	 * @param mixed $type
	 * @return $this
	 * @throws \LogicException
	 */
	public function setWhere($where, $key, $value, $type = null) {
		if($this->isExecuted()) {
			throw new \LogicException("Where cannot be set after execution!");
		}
		$this->_queryBuilder->setWhere($where, $key, $value, $type);
		return $this;
	}

	/**
	 * set query group
	 * @param string $groupby
	 * @return $this
	 * @throws \LogicException
	 */
	public function setGroupBy($groupby) {
		if($this->isExecuted()) {
			throw new \LogicException("GroupBy cannot be set after execution!");
		}
		$this->_queryBuilder->setGroupBy($groupby);
		return $this;
	}

	/**
	 * @param int $pagination_limit
	 */
	public function setPaginationLimit($pagination_limit) {
		$pagination_limit = intval(abs($pagination_limit));
		$this->pagination_limit = ($pagination_limit?$pagination_limit:20);
	}

	/**
	 * @param int $pagination_page
	 */
	public function setPaginationPage($pagination_page) {
		$pagination_page = intval(abs($pagination_page));
		$this->pagination_page = ($pagination_page?$pagination_page:1);
	}









	//----------------------------------------------------- recheck access to these - they do not need to be public - or even unused!

	/**
	 * @param AbstractEntityQueryBuilder|QueryBuilder $queryBuilder
	 * @return $this
	 * @throws \LogicException
	 */
	public function setQueryBuilder($queryBuilder) {
		if($this->isExecuted()) {
			throw new \LogicException("QueryBuilder cannot be set after execution!");
		}
		$this->_queryBuilder = $queryBuilder;
		return $this;
	}


	/**
	 * Returns a single AbstractEntity
	 * @return AbstractEntity
	 * @throws \LogicException
	 */
	public function getSingleResult() {
		if(!$this->isExecuted()) {
			throw new \LogicException("Cannot get results before execution!");
		}
		if ($this->_results->count() !== 1) {
			throw new \LogicException(sprintf("There are %s results in the collection - getSingleResult expects exactly ONE!", $this->_results->count()));
		}
		return($this->_results->first());
	}

	/**
	 * Returns a collection of AbstractEntity instances
	 * @return ArrayCollection
	 * @throws \LogicException
	 */
	public function getResults() {
		if(!$this->isExecuted()) {
			throw new \LogicException("Cannot get results before execution!");
		}
		return($this->_results);
	}

	/**
	 * Returns a paginated collection of AbstractEntity instances
	 * @return SlidingPagination
	 * @throws \LogicException
	 */
	public function getPaginatedResults() {
		if(!$this->isExecuted()) {
			throw new \LogicException("Cannot get results before execution!");
		}
		return($this->paginator->paginate($this->_results, $this->getPaginationPage(), $this->getPaginationLimit()));
	}

	/**
	 * Returns the record count of the current (filtered) query
	 * @return int
	 * @throws \LogicException
	 */
	public function getNumberOfRecords() {
		if(!$this->isExecuted()) {
			throw new \LogicException("Record count cannot be get before execution!");
		}
		return($this->_queryBuilder->getTotalRecords());
	}

	/**
	 * Returns the total number of records in the entity database table
	 * @return int
	 * @throws \LogicException
	 */
	public function getTotalNumberOfEntityRecords() {
		if(!$this->isExecuted()) {
			throw new \LogicException("Cannot get total number of entity records before execution!");
		}
		return($this->entityInfo->getDatabaseTableStatusProperty("Rows"));
	}




	//------------------------------------------------------------------------------------------ GENERIC GETTERS/SETTERS
	/**
	 * @return string
	 */
	public function getOperationMode() {
		return $this->operationMode;
	}

	/**
	 * This should be one of: INDEX, VIEW, NEW, EDIT, DELETE
	 * @param string $operationMode
	 * @return $this
	 */
	public function setOperationMode($operationMode) {
		$this->operationMode = strtoupper($operationMode);
		return($this);
	}

	/**
	 * @return boolean
	 */
	public function isExecuted() {
		return $this->executed;
	}

	/**
	 * get global configuration
	 *
	 * @return array
	 */
	public function getConfiguration() {
		return $this->_config;
	}

	/**
	 * get instance
	 * @param string $id
	 * @return EntityAbstractor
	 * @throws \Exception
	 */
	public static function getInstance($id) {
		$instance = NULL;
		if (array_key_exists($id, self::$_instances)) {
			$instance = self::$_instances[$id];
		} else {
			$instance = self::$_current_instance;
		}
		if (is_null($instance)) {
			throw new \Exception('No EntityAbstractor instance!');
		}
		return $instance;
	}

	/**
	 * Set EntityAbstractor identifier
	 *
	 * @param string $id
	 * @return EntityAbstractor
	 * @throws \LogicException
	 */
	public function setInstanceId($id) {
		if (!array_key_exists($id, self::$_instances)) {
			self::$_instances[$id] = $this;
		} else {
			throw new \LogicException('Identifer already exists!');
		}
		return $this;
	}

	/*
	protected function ___execute($hydration_mode = Query::HYDRATE_ARRAY) {
		$iTotalRecords = $this->_queryBuilder->getTotalRecords();
		list($data, $objects) = $this->_queryBuilder->getData($hydration_mode);
		$id_index = array_search('_identifier_', array_keys($this->getFields()));
		$ids = array();
		array_walk($data, function ($val, $key) use ($data, $id_index, &$ids) {
			$ids[$key] = $val[$id_index];
		});


		// add additional data columns?
		if (!is_null($this->_fixed_data)) {
			$this->_fixed_data = array_reverse($this->_fixed_data);
			foreach ($this->_fixed_data as $item) {
				array_unshift($data, $item);
			}
		}

		if (!is_null($this->_renderer)) {
			array_walk($data, $this->_renderer);
		}
		if (!is_null($this->_renderer_obj)) {
			$this->_renderer_obj->applyTo($data, $objects);
		}

		if (!empty($this->_multiple)) {
			array_walk($data, function ($val, $key) use (&$data, $ids) {
				array_unshift($val, "<input type='checkbox' name='dataTables[actions][]' value='{$ids[$key]}' />");
				$data[$key] = $val;
			});
		}
		$output = array(
			"iTotalRecords" => $iTotalRecords,
			"iTotalDisplayRecords" => $iTotalRecords,
			"aaData" => $data
		);
		return new Response(json_encode($output));
	}
	*/
}
