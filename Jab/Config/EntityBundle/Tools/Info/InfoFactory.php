<?php
namespace Jab\Config\EntityBundle\Tools\Info;

use Doctrine\Bundle\DoctrineBundle\Mapping\DisconnectedMetadataFactory;
use Doctrine\Common\Util\Inflector;
use Jab\Config\EntityBundle\Tools\Cache\Cache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Debug\Exception\ClassNotFoundException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class InfoFactory
 * @DI\Service(id="jab.config.entity.info_factory")
 */
class InfoFactory {

	/**
	 * @var KernelInterface
	 */
	private $kernel;

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var bool
	 */
	private $showUnmanagedEntities = false;

	/**
	 * @DI\InjectParams({
	 *		"kernel" = @DI\Inject("kernel")
	 * })
	 */
	public function __construct(KernelInterface $kernel) {
		$this->kernel = $kernel;
		$this->cache = new Cache($kernel->getCacheDir().'/jab/info_factory', '.cache');
		$this->showUnmanagedEntities = $kernel->getContainer()->getParameter("jab_config_entity.show_unmanaged");
	}


	/**
	 * Returns an array containing the JabEntity representation of all registered entities in all bundles
	 * @return array (of JabEntity)
	 */
	public function getEntityList() {
		$doctrine = $this->kernel->getContainer()->get("doctrine");
		$manager = new DisconnectedMetadataFactory($doctrine);
		$list = [];
		$kBundles = $this->kernel->getContainer()->getParameter("kernel.bundles");
		foreach($kBundles as $bClassName => $bNameSpace) {
			$bundleInterface = $this->kernel->getBundle($bClassName);
			try{
				$bundleMetadata = $manager->getBundleMetadata($bundleInterface);
			} catch (\RuntimeException $e) {
				$bundleMetadata = false;
			}
			if($bundleMetadata) {
				/** @var ClassMetadata $entityMetadata */
				foreach($bundleMetadata->getMetadata() as $entityMetadata) {
					//echo "<br />ENTITY: " . print_r($entityMetadata->getName(), true) ;
					if( ($entityInfo = $this->getEntityClassInfo($entityMetadata->getName())) ) {
						if($entityInfo->isManagedEntity() || $this->showUnmanagedEntities) {
							$list[$entityInfo->getEntityName()] = $entityInfo;
						}
					}
				}
			}
		}
		return($list);
	}

	/**
	 * Returns EntityInfo about entity
	 * @param string $entityName - FQCN(Fully Qualified Class Name)
	 * @param boolean $isNew - check EntityInfo constructor
	 * @return EntityInfo|bool
	 */
	public function getEntityClassInfo($entityName, $isNew=false) {
		$entityInfo = false;
		if($this->cache->contains($entityName)) {
			/** @var EntityInfo $entityInfo */
			$entityInfo = $this->cache->fetch($entityName);
			if(!$entityInfo->isFileHashStillValid()) {
				$this->removeEntityCache($entityInfo);
				$entityInfo = false;
			}
		}
		if(!$entityInfo) {
			$entityInfo = new EntityInfo($entityName, $isNew);
			if($entityInfo && !$isNew) {
				$this->cache->save($entityName, $entityInfo);
			}
		}
		return($entityInfo);
	}


	/**
	 * Returns names of superclasses which can be used to extend other classes
	 * Used by EntityType form
	 * @param array $excludes
	 * @return array
	 */
	public function getSuperclassNamesList($excludes=[]) {
		$answer = [];
		$list = $this->getEntityList();
		/** @var EntityInfo $entityInfo */
		foreach($list as $entityInfo) {
			if(!in_array($entityInfo->getEntityName(), $excludes)) {
				if($entityInfo->isMappedSuperclass()) {
					$answer[$entityInfo->getEntityName()] = $entityInfo->getBundleName() . "/" . $entityInfo->getClassName();
				}
			}
		}
		return($answer);
	}


	/**
	 * @return array
	 */
	public function getBundlesList() {
		$appRootPath = realpath($this->kernel->getRootDir().'/..') . '/';
		$answer = [];
		$kBundles = $this->kernel->getContainer()->getParameter("kernel.bundles");
		foreach($kBundles as $bClassName => $bNameSpace) {
			$bundleInterface = $this->kernel->getBundle($bClassName);
			$rPath = str_replace($appRootPath, "", $bundleInterface->getPath());
			$vendorDirName = preg_replace('#\/.*#', '', $rPath);//should give "vendor" | "src"
			//exclude all bundles living inside the "vendor" folder
			if($vendorDirName != "vendor") {
				$answer[$bClassName] = $bClassName;
			}
		}
		return($answer);
	}


	/**
	 * Check if Jab entities are in sync
	 * We are checking only manageable entities even if also listing unmanaged Entities
	 * @return bool
	 */
	public function isPlatformInSync() {
		$answer = true;
		/** @var EntityInfo $entityInfo*/
		foreach($this->getEntityList() as $entityInfo) {
			if(!$entityInfo->isInSync()) {
				if($entityInfo->isManagedEntity()) {
					$answer = false;
					break;
				}
			}
		}
		return($answer);
	}

	/**
	 * Align database with entities - only CRU - Delete will be handled deleting an Entity
	 * Only managed entities will be synced
	 */
	public function syncPlatform() {
		if(!$this->isPlatformInSync()) {
			/** @var EntityManager $em */
			$em = $this->kernel->getContainer()->get("doctrine")->getManager();
			/** @var EntityInfo $entityInfo*/
			foreach($this->getEntityList() as $entityInfo) {
				if($entityInfo->isManagedEntity() && !$entityInfo->isInSync()) {
					foreach($entityInfo->getSqlToSync() as $sql) {
						$em->getConnection()->exec($sql);
					}
				}
			}
			$this->clearFactoryCache();
		}
	}

	/**
	 * Clear the entire cache
	 */
	public function clearFactoryCache() {
		foreach($this->getEntityList() as $entityInfo) {
			$this->_removeCacheFilesForEntity($entityInfo);
		}
	}

	/**
	 * Remove cache file only for single entity
	 * If entity is a mapped superclass (hence extended by others), we need to clear the entire cache
	 *
	 * @param EntityInfo $entityInfo
	 */
	public function removeEntityCache(EntityInfo $entityInfo) {
		$this->_removeCacheFilesForEntity($entityInfo);
		if($entityInfo->isMappedSuperclass()) {
			$this->clearFactoryCache();
		}
	}

	/**
	 * @param EntityInfo $entityInfo
	 */
	private function _removeCacheFilesForEntity(EntityInfo $entityInfo) {
		$this->cache->delete($entityInfo->getEntityName());
		/*
		$fs = $this->kernel->getContainer()->get("filesystem");
		$finder = new Finder();
		$annotCachePath = $this->kernel->getContainer()->getParameter("kernel.cache_dir") . "/annotations/";
		$fileKey = sha1($entityInfo->getEntityName());
		$finder->in($annotCachePath)->files()->name($fileKey . "*.cache.php");
		/** @var SplFileInfo $file * /
		foreach($finder as $file) {
			$fs->remove($file->getRealPath());
		}*/
	}

	//----------------------------------------------------------------------------------Utility methods for Info Classes

	/**
	 * @param string $methodType - one of (get|set|add|remove)
	 * @param string $fieldName - the name of the field for which yo are looking for a method
	 * @param \ReflectionClass $rflClass - the Reflection class in which to look
	 * @return bool
	 */
	public function getDefaultMethodForField($methodType, $fieldName, \ReflectionClass $rflClass) {
		$answer = false;
		$methodType = strtolower($methodType);
		$TDA = [
			"get" => ["get", "is", "has"],
			"set" => ["set"],
			"add" => ["add"],
			"remove" => ["remove"]
		];
		if(array_key_exists($methodType, $TDA)) {
			$TA = $TDA[$methodType];
			$sourceCodeArray = file($rflClass->getFileName());
			foreach($TA as $stub) {
				$methodName = $stub . Inflector::classify($fieldName);
				if (in_array($methodType, array("add", "remove"))) {
					$methodName = Inflector::singularize($methodName);
				}
				if($rflClass->hasMethod($methodName)) {
					$rflMethod = $rflClass->getMethod($methodName);
					$answer["name"] = $methodName;
					$answer["body"] = rtrim(implode("", array_slice($sourceCodeArray, $rflMethod->getStartLine()-1, ($rflMethod->getEndLine()-$rflMethod->getStartLine()+1))));
					$answer["comment"] = $rflMethod->getDocComment();
					break;
				}
			}
		}
		return($answer);
	}

	/**
	 * @param \ReflectionClass $rflClass
	 * @param string $propertyName
	 * @return \ReflectionClass
	 */
	public function getDeclaringClassForProperty(\ReflectionClass $rflClass, $propertyName) {
		//let's try the quick way first
		$declaringClass = $rflClass->hasProperty($propertyName) ? $rflClass->getProperty($propertyName)->getDeclaringClass() : false;
		if(!$declaringClass) {
			//the property is not in the $rflClass so we need to look up some parent class to find it
			while( ($rflClass = $rflClass->getParentClass()) && !$declaringClass) {
				$declaringClass = $rflClass->hasProperty($propertyName) ? $rflClass->getProperty($propertyName)->getDeclaringClass() : false;
			}
		}
		return $declaringClass;
	}

}