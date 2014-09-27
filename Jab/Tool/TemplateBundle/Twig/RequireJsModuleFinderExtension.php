<?php
namespace Jab\Tool\TemplateBundle\Twig;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class RequireJsModuleFinderExtension
 * @DI\Service(id="jab.tool.template.requirejs_module_finder_extension")
 * @DI\Tag(name="twig.extension")
 */
class RequireJsModuleFinderExtension extends \Twig_Extension{
	/**
	 * @var RequestStack
	 */
	private $requestStack;

	/**
	 * @var string
	 */
	private $webpath;

	/**
	 * @DI\InjectParams({
	 *		"reqStack" = @DI\Inject("request_stack"),
	 *      "kernel" = @DI\Inject("kernel")
	 * })
	 */
	public function __construct(RequestStack $reqStack, KernelInterface $kernel) {
		//todo: hardcoded web path - get rid of this
		$this->webpath = realpath($kernel->getRootDir() . '/../web');
		$this->requestStack = $reqStack;
	}

	/**
	 * @return array
	 */
	public function getFunctions() {
		return array(
			'get_module_for_route' => new \Twig_SimpleFunction('get_module_for_route', array($this, 'getModuleForRoute'), [])
		);
	}

	/**
	 * @param string $assetsDir
	 * @param string $modulesSubfolder
	 * @param string $defaultModuleName
	 * @param string $extension
	 * @return string
	 */
	public function getModuleForRoute($assetsDir="assets/js", $modulesSubfolder="app", $defaultModuleName="_default", $extension="js") {
		$moduleName = $this->requestStack->getCurrentRequest()->get("_route");
		if(file_exists($this->webpath . $assetsDir . "/" . $modulesSubfolder . "/" . $moduleName . "." . $extension)) {
			$answer = $modulesSubfolder . "/" . $moduleName;
		} else if (file_exists($this->webpath . $assetsDir . "/" . $modulesSubfolder . "/" . $defaultModuleName . "." . $extension)) {
			$answer = $modulesSubfolder . "/" . $defaultModuleName;
		} else {
			$answer = "";
		}
		return($answer);
	}

	public function getName() {
		return 'requirejs_module_finder_extension';
	}
}