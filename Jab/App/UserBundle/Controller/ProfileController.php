<?php
namespace Jab\App\UserBundle\Controller;

use Jab\Platform\PlatformBundle\Controller\JabController;
use Jab\Platform\PlatformBundle\Entity\JabUser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class ProfileController
 * @Route(path="/")
 */
class ProfileController extends JabController {

	/**
	 * @Route(name="user-profile", path="/")
	 * @Template()
	 */
	public function showAction() {
		/** @var JabUser $user */
		$user = $this->container->get('security.context')->getToken()->getUser();
		if (!is_object($user) || !$user instanceof UserInterface) {
			throw new AccessDeniedException('This user does not have access to this section.');
		}
		return array(
			'user' => $user
		);
	}
}