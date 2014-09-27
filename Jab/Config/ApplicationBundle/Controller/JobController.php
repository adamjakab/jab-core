<?php
namespace Jab\Config\ApplicationBundle\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Jab\Platform\PlatformBundle\Controller\JabController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class DefaultController
 *
 * @Route(path="/jobs")
 */
class JobController extends JabController {
	/**
	 * @DI\Inject("doctrine")
	 * @var Registry
	 */
	private $registry;

	/** @DI\Inject */
	private $request;

	/** @DI\Inject */
	private $router;

	/** @DI\Inject("%jms_job_queue.statistics%") */
	private $statisticsEnabled;

	/**
	 * @Route(name="configuration-jobs", path="/")
	 * @Template()
	 */
	public function indexAction() {

		$lastJobsWithError = $this->getRepo()->findLastJobsWithError(5);

		$qb = $this->getEm()->createQueryBuilder();
		$qb->select('j')->from('JMSJobQueueBundle:Job', 'j')
			->where($qb->expr()->isNull('j.originalJob'))
			->orderBy('j.id', 'desc');

		//exclude jobs with errors
		foreach ($lastJobsWithError as $i => $job) {
			$qb->andWhere($qb->expr()->neq('j.id', '?' . $i));
			$qb->setParameter($i, $job->getId());
		}
		$lastJobs = $qb->getQuery()->getResult();

		return array(
			"title" => "Background Jobs",
			"jobs" => $lastJobs,
			"jobsWithError" => $lastJobsWithError
		);

	}

	//todo: we need the other actions from "@JMSJobQueueBundle/Controller/"


	/**
	 * @return EntityManager
	 */
	private function getEm() {
		return $this->registry->getManagerForClass('JMSJobQueueBundle:Job');
	}

	/**
	 * @return \JMS\JobQueueBundle\Entity\Repository\JobRepository
	 */
	private function getRepo() {
		return $this->getEm()->getRepository('JMSJobQueueBundle:Job');
	}
}
