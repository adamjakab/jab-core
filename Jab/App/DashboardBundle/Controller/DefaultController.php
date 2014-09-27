<?php

namespace Jab\App\DashboardBundle\Controller;

use Jab\Platform\PlatformBundle\Controller\JabController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class DefaultController
 */
class DefaultController extends JabController
{
	/**
	 * @Route(name="dashboard", path="/")
	 * @Template()
	 */
	public function indexAction() {
		return array();
	}
}
