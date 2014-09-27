<?php
namespace Jab\Tool\TemplateBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Ornicar\GravatarBundle\GravatarApi;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Class MenuBuilder
 * @DI\Service(id="jab.tool.template.menu_builder")
 */
class MenuBuilder {
	/**
	 * @var FactoryInterface $factory
	 */
	private $factory;

	/**
	 * @var SecurityContext
	 */
	private $security;

	/**
	 * @var Router
	 */
	private $router;

	/**
	 * @var Request;
	 */
	private $request;

	/**
	 * @var GravatarApi
	 */
	private $gravatarApi;

	/**
	 * @DI\InjectParams({
	 *		"factory"       =   @DI\Inject("knp_menu.factory"),
	 *      "security"      =   @DI\Inject("security.context"),
	 *      "router"        =   @DI\Inject("router"),
	 *      "gravatarApi"   =   @DI\Inject("gravatar.api"),
	 * })
	 * @param FactoryInterface $factory
	 * @param SecurityContext  $security
	 * @param Router           $router
	 * @param GravatarApi      $gravatarApi
	 */
	public function __construct(FactoryInterface $factory, SecurityContext $security, Router $router, GravatarApi $gravatarApi) {
		$this->factory = $factory;
		$this->security = $security;
		$this->router = $router;
		$this->gravatarApi = $gravatarApi;
	}


	/**
	 * icons: http://fortawesome.github.io/Font-Awesome/icons/
	 * @param Request $request
	 * @return \Knp\Menu\ItemInterface
	 */
	public function createMainMenu(Request $request) {
		$this->request = $request;

		/* MAIN MENU */
		$menu = $this->factory->createItem('root');
		$menu->setChildrenAttributes(['class'=>'nav navbar-nav', 'id' => 'mainmenu']);

		/* HOME */
		$this->addMenuItem($menu, "Home", 'dashboard', [], 'fa-home');

		/* ACCOUNT */
		$this->addMenuItem($menu, "Account", 'jab-app-accountbundle-entity-account-index', [], 'fa-institution');


		/* TESTS */
		$test = $this->addMenuItem($menu, 'Tests', null, [], 'fa-flask')->setAttribute('dropdown', true);
		$this->addMenuItem($test, "Test(Abstractor)", 'test-abstractor', [], 'fa-bullseye');
		$this->addMenuItem($test, "Test(Add Bubucs)", 'test-abstractor-register', [], 'fa-bullseye');
		//
		$this->addMenuItem($test, "Test(Session)", 'test-session', [], 'fa-bullseye', true);


		/* ABSTRACT */
		$abstract = $this->addMenuItem($menu, 'Abstract', null, [], 'fa-unlink')->setAttribute('dropdown', true);
		//$abstract->setCurrent(true);
		$this->addMenuItem($abstract, "Abstract(Bubucs)", 'adi-testbundle-entity-bubucs-index', [], 'fa-unlink');
		//$this->addMenuItem($abstract, "Abstract(Account)", 'adi-testbundle-entity-account-index', [], 'fa-unlink');
		$this->addMenuItem($abstract, "Abstract(Address)", 'adi-testbundle-entity-address-index', [], 'fa-unlink');


		/* CONFIGURATION */
		$config = $this->addMenuItem($menu, 'Configuration', null, [], 'fa-sliders')->setAttribute('dropdown', true);
		//--------------------------------------//
		$this->addMenuItem($config, "General", 'configuration-main', [], 'fa-wrench');
		//$this->addMenuItem($config, "Advanced", 'configuration-main', [], 'fa-bolt');
		$this->addMenuItem($config, "Background Jobs", 'configuration-jobs', [], 'fa-tasks');
		$this->addMenuItem($config, "Workflows", 'configuration-workflow', [], 'fa-list-ul');
		//--------------------------------------//
		$this->addMenuItem($config, "Entities", 'configuration-entity', [], 'fa-cubes', true);
		//--------------------------------------//
		$this->addMenuItem($config, "Info", 'platform-info', [], 'fa-info-circle', true);


		return $menu;
	}




	public function createUserMenu(Request $request) {
		$this->request = $request;

		/* MENU */
		$menu = $this->factory->createItem('root');
		$menu->setChildrenAttributes(['class'=>'nav navbar-nav navbar-right', 'id' => 'usermenu']);


		$usermenu = $this->addMenuItem($menu, "Guest", null, [], 'fa-user')->setAttribute('dropdown', true);
		if($this->security->getToken()->isAuthenticated()) {
			/** @var \Jab\Platform\PlatformBundle\Entity\JabUser $currentUser */
			$currentUser = $this->security->getToken()->getUser();
			$displayName = $currentUser->getUsername();


			//If Person has been modified but is not yet persisted in db $currentUser->getPerson() will throw DBAL, PDO exceptions
			//@todo: this should be fixed in entity configurator
			try {
				if (($currentUserPerson = $currentUser->getPerson())) {
					$displayName = $currentUserPerson->getDisplayName();
				}
			} catch (\Exception $e) {/*keep quiet!*/}

			$gravatarUri = $this->gravatarApi->getUrl($currentUser->getEmail(), 18);
			//
			$usermenu->setLabel($displayName)->setAttribute('image', $gravatarUri);
		}

		$this->addMenuItem($usermenu, "Profile", 'user-profile', [], 'fa-edit', false);


		$this->addMenuItem($usermenu, "Logout", 'security-logout', [], 'fa-sign-out', true);

		return $menu;
	}

	/**
	 * Shorthand method for adding menu items
	 * @param ItemInterface $parent
	 * @param string $label
	 * @param string $route
	 * @param array $options
	 * @param string $faIcon
	 * @param boolean $dividerBefore
	 * @return ItemInterface
	 */
	private function addMenuItem($parent, $label, $route, $options=[], $faIcon=null, $dividerBefore=false) {
		if($label) {
			$options = array_merge(['label' => $label], $options);
		}
		if($route) {
			$options = array_merge(['route' => $route], $options);
		}
		$itemName = $parent->getName() . "___" . $label . "___" . $route;

		/** @var ItemInterface $item */
		$item = $parent->addChild($itemName, $options);

		if($faIcon) {
			$item->setAttribute('icon', 'fa ' . $faIcon);
		}

		if($dividerBefore) {
			$item->setAttribute('divider_prepend', true);
		}

		//Activate parent if this item is active
		//this is only good for exact match
		//todo: need more elaborate voter
		if ($item->getUri() === $this->request->getRequestUri()) {
			if ( ($parent = $item->getParent()) ) {
				$parent->setCurrent(true);
			}
		}

		return($item);
	}
}