<?php
namespace Jab\App\UserBundle\Controller;

use Jab\Platform\PlatformBundle\Controller\JabController;
use Jab\Platform\PlatformBundle\Entity\JabUser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class SecurityController
 * @Route(path="/")
 */
class SecurityController extends \FOS\UserBundle\Controller\SecurityController {
	/**
	 * @Route(name="security-login", path="/login")
	 */
	public function loginAction() {
		return(parent::loginAction());
	}

	/**
	 * @Route(name="security-login-check", path="/login_check")
	 */
	public function checkAction() {
		parent::checkAction();
	}

	/**
	 * @Route(name="security-logout", path="/logout")
	 */
	public function logoutAction() {
		parent::logoutAction();
	}


}