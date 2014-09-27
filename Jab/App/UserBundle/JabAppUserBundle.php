<?php
namespace Jab\App\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class JabAppUserBundle extends Bundle {


	/**
	 * This bundle is declared to be child of "FOSUserBundle"
	 * So we can overwrite templates easily from in here
	 * @return string
	 */
	public function getParent() {
		return 'FOSUserBundle';
	}
}
