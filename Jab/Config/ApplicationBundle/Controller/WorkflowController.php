<?php
namespace Jab\Config\ApplicationBundle\Controller;

use Jab\Platform\PlatformBundle\Controller\JabController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class WorkflowController
 * @Route(path="/workflow")
 */
class WorkflowController extends JabController {
	/**
	 * @Route(name="configuration-workflow", path="/")
	 * @Template(template="JabToolTemplateBundle:Default/Utils:dump.html.twig")
	 */
    public function indexAction() {
	    return array(
		    "data" => [
			    "title" => "Workflow Configurations"
		    ]
	    );
    }
}
