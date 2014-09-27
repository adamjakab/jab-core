<?php
namespace Jab\Platform\PlatformBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Jab\Config\EntityBundle\Tools\Generator\EntityGenerator;
use Jab\Platform\PlatformBundle\Entity\JabUser;
use Jab\Platform\PlatformBundle\Session\JabUserPreferences;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Jab\Config\EntityBundle\Tools\Info\InfoFactory;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * Class KernelSubscriber - Listens to kernel events - generic subscriber
 * @DI\Service()
 * @DI\Tag(name="kernel.event_subscriber")
 */
class KernelSubscriber implements EventSubscriberInterface {
	/**
	 * @var SecurityContextInterface
	 */
	private $security;

	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @DI\InjectParams({
	 *      "security" =    @DI\Inject("security.context"),
	 *      "em" =          @DI\Inject("doctrine.orm.entity_manager"),
	 *      "logger" =      @DI\Inject("logger")
	 * })
	 * @param Logger $logger
	 * @param EntityManager $em
	 * @param SecurityContextInterface $security
	 */
	public function __construct(SecurityContextInterface $security, EntityManager $em, Logger $logger = null) {
		$this->security = $security;
		$this->em = $em;
		$this->logger = $logger;
	}


	/**
	 * Every time User preferences are changed write it back to db
	 * @param FilterResponseEvent $event
	 */
	public function onkernelResponse(FilterResponseEvent $event) {
		if(!JabUserPreferences::$is_modified) {
			return;
		}
		if (!$event->isMasterRequest()) {
			return;
		}
		if (!$event->getRequest()->hasSession()) {
			return;
		}
		/** @var JabUser $currentUser */
		$currentUser = $this->security->getToken()->getUser();
		if(!is_a($currentUser, 'Jab\Platform\PlatformBundle\Entity\JabUser')) {
			return;
		}

		$request = $event->getRequest();
		$session = $request->getSession();
		/** @var ArrayCollection $userPreferences */
		$userPreferences = $session->get(JabUserPreferences::$user_pref_attr_key);
		if(!$userPreferences) {
			return;
		}

		$currentUser->setPreferences($userPreferences->toArray());
		$this->em->persist($currentUser);
		$this->em->flush($currentUser);

		if (null !== $this->logger) {
			$this->logger->warning('Saved User Preferences', ["Pref"=>$userPreferences]);
		}

	}

	/**
	 * Set saved preferences for user in session - on successfull login
	 * @param InteractiveLoginEvent $event
	 */
	public function onInteractiveLogin(InteractiveLoginEvent $event) {
		if (!$event->getRequest()->hasSession()) {
			return;
		}
		/** @var JabUser $currentUser */
		$currentUser = $this->security->getToken()->getUser();
		if(!is_a($currentUser, 'Jab\Platform\PlatformBundle\Entity\JabUser')) {
			return;
		}
		$request = $event->getRequest();
		$session = $request->getSession();

		/** @var JabUser $currentUser */
		$currentUser = $event->getAuthenticationToken()->getUser();
		if(!is_a($currentUser, 'Jab\Platform\PlatformBundle\Entity\JabUser')) {
			return;
		}
		$preferences = $currentUser->getPreferences();
		$preferences = (is_array($preferences) ? $preferences : []);

		//increment login count - this is for testing only and should be removed
		$preferences["login_count"] = isset($preferences["login_count"]) ? (int)$preferences["login_count"]+1 : 1;
		JabUserPreferences::$is_modified = true;
		//
		$session->set(JabUserPreferences::$user_pref_attr_key, new ArrayCollection($preferences));

	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return [
			KernelEvents::RESPONSE => array('onkernelResponse', 0),
			SecurityEvents::INTERACTIVE_LOGIN => array('onInteractiveLogin', 0)
		];


	}
}