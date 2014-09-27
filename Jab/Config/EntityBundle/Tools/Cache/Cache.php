<?php
namespace Jab\Config\EntityBundle\Tools\Cache;

use Doctrine\Common\Cache\FilesystemCache;

/**
 * Class Cache - a temporary proxy class
 */
class Cache extends FilesystemCache {

	/**
	 * @param string $directory
	 * @param null   $extension
	 */
	public function __construct($directory, $extension = null) {
		parent::__construct($directory, $extension);
	}

	/**
	 *
	 * @param string $id
	 * @return string
	 */
	protected function getFilename($id) {
		return $this->directory . '/' . preg_replace('@[\\\/:"*?<>|]+@', '', $id) . $this->extension;
	}
}