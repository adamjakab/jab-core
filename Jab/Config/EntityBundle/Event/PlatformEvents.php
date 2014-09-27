<?php
namespace Jab\Config\EntityBundle\Event;

/**
 * Defines event names dispatched by this bundle
 *
 * Class PlatformEvents
 */
final class PlatformEvents {
	/**
	 * Thrown when a Jab managed entity is changed/updated
	 * including field change/deletion
	 *
	 * event instance: Event\EntityChangeEvent
	 *
	 * @var string
	 */
	const JAB_ENTITY_UPDATE = 'jab.platform.entity_update';

	/**
	 * Thrown when a Jab managed entity is deleted
	 *
	 * event instance: Event\EntityChangeEvent
	 *
	 * @var string
	 */
	const JAB_ENTITY_REMOVE = 'jab.platform.entity_remove';
}