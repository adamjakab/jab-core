<?php
namespace Jab\Config\ApplicationBundle\Controller;

use Jab\Platform\PlatformBundle\Controller\JabController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class DefaultController
 * @Route(path="/general")
 */
class DefaultController extends JabController {
	/**
	 * @Route(name="configuration-main", path="/")
	 * @Template(template="JabToolTemplateBundle:Default/Utils:dump.html.twig")
	 */
    public function indexAction() {
	    //add JMS JOB
	    /*
		$em = $this->getDoctrine()->getManager();
	    $job = new Job('doctrine:schema:validate', []);//array('some-args', 'or', '--options="foo"')
	    $em->persist($job);
	    $em->flush($job);
			*/

	    return array(
		    "data" => [
			    "title" => "Application Configurations",
			    "extension param(1)" => $this->container->getParameter("jab_config_entity.show_unmanaged"),
			    "extension param(2)" => $this->container->getParameter("jab_config_entity.my_name")
		    ]
	    );
    }
}
