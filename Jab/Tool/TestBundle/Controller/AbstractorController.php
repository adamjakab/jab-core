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
 * @Route(path="/abstractor")
 */
class AbstractorController extends JabController {
    /**
     * @Route(name="test-abstractor", path="/")
     * @Template(template="JabToolTemplateBundle:Default/Utils:dump.html.twig")
     */
    public function indexAction() {
	    $entityName = "Jab\\Tool\\TestBundle\\Entity\\Bubucs";

	    $EA = $this->container->get("jab.tool.entity_abstractor");
	    $EA->setEntity($entityName, "b");
	    //$EA->setWhere('b.id = :id', 'id', 1);
	    $EA->execute();

	    $data = [
		    "data" => [
			    "ENTITY USED" => $entityName,
			    "FIELDS" => $EA->getFields(),
			    "RESULTS(PAGINATED)" => $EA->getResults()
		    ]
	    ];
        return($data);
    }

	/**
	 * @Route(name="test-abstractor-register", path="/register")
	 * @return RedirectResponse
	 */
	public function registerAction() {
		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();

		for ($i=1;$i<rand(2,10);$i++) {
			$bubucs = new Bubucs();
			$bubucs->setEmail("test_".rand(10000,99999)."@alfazeta.com");
			$bubucs->setFirstname(sha1($bubucs->getEmail()));
			$bubucs->setLastname(md5($bubucs->getEmail()));
			$em->persist($bubucs);
		}
		$em->flush();
		$this->addFlashBagMessage("Registered $i Bubucses!");


		$redirectUrl = $this->generateUrl("test-abstractor");
		return $this->redirect($redirectUrl);
	}
}


