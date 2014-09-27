<?php
namespace Jab\Tool\TestBundle\Controller;

use Jab\Tool\TestBundle\Entity\Bubucs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Parameter;
use Jab\Platform\PlatformBundle\Controller\JabController;
use Jab\Platform\Tools\EntityAbstractor\EntityAbstractor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @Route(path="/session")
 */
class SessionController extends JabController {
    /**
     * @Route(name="test-session", path="/")
     * @Template(template="JabToolTemplateBundle:Default/Utils:dump.html.twig")
     */
    public function indexAction() {

	    $jabUserPref = $this->container->get("jab.platform.platform.jab_user_preferences");

	    $data = [
		    "data" => [
			    "User Preferences" => $jabUserPref->all()
		    ]
	    ];
        return($data);
    }
}


