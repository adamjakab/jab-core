<?php
namespace Jab\Tool\TemplateBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use JMS\DiExtraBundle\Annotation as DI;


//@todo: This service is disabled - it is using "service_container" injection and has very stupid matcher

/**
 * Class RequestVoter
 * @ DI\Service(id="jab.tool.template.request_voter")
 * @ DI\Tag(name="knp_menu.voter")
 */
class RequestVoter implements VoterInterface {
	/** @var Request */
	private $request;

	//injecting "request" throws scope widening exception
	/**
	 * @ DI\InjectParams({
	 *		"container" = @ DI\Inject("service_container")
	 * })
	 * @param ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container) {
		$this->request = $container->get("request");
	}

	/**
	 * @param ItemInterface $item
	 * @return bool|null
	 */
	//todo: this is completely stupid - we need a better one
	public function matchItem(ItemInterface $item) {
		/*
		if ($item->getUri() === $this->request->getRequestUri()) {
			// URL's completely match
			return true;
		} else if($item->getUri() !== $this->request->getBaseUrl().'/' && (substr($this->request->getRequestUri(), 0, strlen($item->getUri())) === $item->getUri())) {
			// URL isn't just "/" and the first part of the URL match
			return true;
		}*/
		return null;

	}

}
