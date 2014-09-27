<?php
namespace Jab\Platform\PlatformBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Controller - Generic controller for Jab Platform
 */
class JabController extends Controller {

	/**
	 * @param string $type
	 * @param string $message
	 */
	protected function addFlashBagMessage($message, $type = "info") {
		$types = ["info","danger","success","warning"];
		$type = ($type && in_array($type, $types) ? $type : "info");
		if($message) {
			$fb = $this->get("session")->getFlashBag();
			$fb->add($type, $message);
		}
	}
}