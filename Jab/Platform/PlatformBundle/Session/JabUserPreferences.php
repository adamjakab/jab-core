<?php
namespace Jab\Platform\PlatformBundle\Session;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Jab\Config\EntityBundle\Tools\Info\EntityInfo;
use Jab\Platform\PlatformBundle\Entity\JabUser;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class JabUserPreferences
 * @DI\Service(id="jab.platform.platform.jab_user_preferences")
 */
class JabUserPreferences {
	/**
	 * @var SecurityContext
	 */
	private $security;

	/**
	 * @var Session
	 */
	private $session;

	/**
	 * @var string
	 */
	public static $user_pref_attr_key = '_jab_pref';

	/**
	 * @var boolean
	 */
	public static $is_modified = false;

	/**
	 * @DI\InjectParams({
	 *      "security"      =   @DI\Inject("security.context"),
	 *      "session"       =   @DI\Inject("session")
	 * })
	 * @param SecurityContext $security
	 * @param Session $session
	 */
	public function __construct(SecurityContext $security, Session $session) {
		$this->security = $security;
		$this->session = $session;

		//just in case nobody has done this before
		if(!$this->session->has(self::$user_pref_attr_key)) {
			$this->session->set(self::$user_pref_attr_key, new ArrayCollection());
		}
	}

	/**
	 * @param string $name
	 * @param mixed $default
	 * @return mixed|null
	 */
	public function get($name, $default=null) {
		$prefs = $this->_getUserPrefs();
		return ($prefs->containsKey($name) ? $prefs->get($name) : $default);
	}

	/**
	 * @param string $name
	 * @param mixed  $value
	 */
	public function set($name, $value) {
		$prefs = $this->_getUserPrefs();
		$prefs->set($name, $value);
		self::$is_modified = true;
	}

	/**
	 * @return ArrayCollection
	 */
	public function all() {
		return($this->_getUserPrefs());
	}



	/**
	 * @return ArrayCollection
	 */
	private function _getUserPrefs() {
		return ($this->session->get(self::$user_pref_attr_key));
	}
}