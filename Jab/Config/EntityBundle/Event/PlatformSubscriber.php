<?php
namespace Jab\Config\EntityBundle\Event;

use Jab\Config\EntityBundle\Tools\Generator\EntityGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Jab\Config\EntityBundle\Tools\Info\InfoFactory;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class PlatformSubscriber - Listens to platform events
 * @DI\Service()
 * @DI\Tag(name="kernel.event_subscriber")
 */
class PlatformSubscriber implements EventSubscriberInterface {
	/**
	 * @var InfoFactory
	 */
	private $infoFactory;

	/**
	 * @DI\InjectParams({
	 *		"infoFactory" = @DI\Inject("jab.config.entity.info_factory")
	 * })
	 *
	 * @param InfoFactory $infoFactory
	 */
	public function __construct(InfoFactory $infoFactory) {
		$this->infoFactory = $infoFactory;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return [
			PlatformEvents::JAB_ENTITY_UPDATE => array('onEntityUpdate', 0),
			PlatformEvents::JAB_ENTITY_REMOVE => array('onEntityRemove', 0),
		];
	}

	/**
	 * A Jab Entity has been changed
	 * @param EntityChangeEvent $event
	 */
	public function onEntityUpdate(EntityChangeEvent $event) {
		$entityInfo = $event->getEntityInfo();
		$entityGenerator = new EntityGenerator();
		//WRITE ENTITY CLASS FILE & REPOSITORY
		$entityGenerator->writeEntityClass($entityInfo);
		$entityGenerator->writeEntityRepository($entityInfo);
		$this->infoFactory->removeEntityCache($entityInfo);
	}

	/**
	 * A Jab Entity has been removed
	 * @param EntityChangeEvent $event
	 * @throws \Exception
	 */
	public function onEntityRemove(EntityChangeEvent $event) {
		throw new \Exception("onEntityRemove event handler is not yet defined! CLASS: " . __CLASS__);
	}

}