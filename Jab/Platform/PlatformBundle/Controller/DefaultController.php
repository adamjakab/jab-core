<?php
namespace Jab\Platform\PlatformBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Class DefaultController
 * @Route(path="/")
 */
class DefaultController extends JabController {
    /**
     * @Route(name="platform-info", path="/info")
     * @Template()
     */
    public function indexAction() {
        return [
	        "name" => "Jab Platform",
	        "version" => "0.1"
        ];
    }
}
