<?php

namespace Jab\Platform\PlatformBundle\Router;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class DynamicRoutesLoader
 * @DI\Service(id="jab.platform.platform.dynamic_routes_loader")
 * @DI\Tag(name="routing.loader")
 */
class DynamicRoutesLoader implements LoaderInterface {//extends Loader

	/**
	 * @var bool - root is loaded
	 */
	private $loaded = false;

	private $abstractEntityControllerName = 'JabPlatformBundle:AbstractEntity';

	/**
	 * Loads a resource.
	 *
	 * @param mixed  $resource The resource
	 * @param string $type     The resource type
	 * @return RouteCollection
	 *
	 * @throws \RuntimeException Loader is added twice
	 */
	public function load($resource, $type = null) {
		if ($this->loaded) {
			throw new \RuntimeException('Do not add this loader twice');
		}
		$routes = new RouteCollection();

		//todo: get list of managable entities
		$routes = $this->addDynamicRoutesForEntity($routes, 'Adi\TestBundle\Entity\Bubucs');
		//$routes = $this->addDynamicRoutesForEntity($routes, 'Adi\TestBundle\Entity\Account');
		$routes = $this->addDynamicRoutesForEntity($routes, 'Adi\TestBundle\Entity\Address');

		$this->loaded = true;
		return $routes;
	}


	/**
	 * @param RouteCollection $routes
	 * @param string $entityName
	 * @return RouteCollection
	 */
	private function addDynamicRoutesForEntity(RouteCollection $routes, $entityName) {
		//todo: get info about this entity - check if it really exists
		$routes = $this->addDynamicActionRouteForEntity($routes, $entityName, "INDEX");
		$routes = $this->addDynamicActionRouteForEntity($routes, $entityName, "VIEW", [], ['id' => '\d+']);
		$routes = $this->addDynamicActionRouteForEntity($routes, $entityName, "NEW", [], []);
		$routes = $this->addDynamicActionRouteForEntity($routes, $entityName, "EDIT", [], ['id' => '.*']);
		$routes = $this->addDynamicActionRouteForEntity($routes, $entityName, "DELETE", [], ['id' => '.*']);
		return($routes);
	}

	/**
	 * @param RouteCollection $routes
	 * @param string $entityName
	 * @param string $action
	 * @param array $defaults
	 * @param array $requirements
	 * @return RouteCollection
	 */
	private function addDynamicActionRouteForEntity(RouteCollection $routes, $entityName, $action, $defaults=[], $requirements=[]) {
		//todo: check if this action mrthod exists on controller
		$_defaults = [
			'_controller' => $this->abstractEntityControllerName . ':' . strtolower($action),
			'entityName' => $entityName
		];
		$defaults = array_merge($_defaults, $defaults);
		//
		$_requirements = [
			'entityName' => '.*'
		];
		$requirements = array_merge($requirements, $_requirements);
		//
		$routeName = $this->convertEntityNameToRouteName($entityName, $action);
		$pathPattern = $this->convertEntityNameToPathPattern($entityName, $action, array_keys($requirements));
		$routes->add($routeName, new Route($pathPattern, $defaults, $requirements));
		return($routes);
	}


	/**
	 * @param string $entityName
	 * @param string $action
	 * @param array $parameters
	 * @return string
	 */
	private function convertEntityNameToPathPattern($entityName, $action, $parameters=[]) {
		$answer = '/jabdyn/';
		$answer .= str_replace('\\', '/', strtolower($entityName . '\\' . $action));
		if(count($parameters)) {
			foreach($parameters as $param) {
				$answer .= '/{' . $param . '}';
			}
		}
		return($answer);
	}

	/**
	 * @param string $entityName
	 * @param string $action
	 * @return string
	 */
	public function convertEntityNameToRouteName($entityName, $action) {
		return(str_replace('\\', '-', strtolower($entityName . '\\' . $action)));
	}

	/**
	 *
	 * @param mixed  $resource A resource
	 * @param string $type     The resource type
	 *
	 * @return boolean This class supports the given resource
	 */
	public function supports($resource, $type = null) {
		return 'jabdyn' === $type;
	}

	/**
	 * @param LoaderResolverInterface $resolver
	 */
	public function setResolver(LoaderResolverInterface $resolver) {}

	/**
	 * @return LoaderResolverInterface
	 */
	public function getResolver() {}
}