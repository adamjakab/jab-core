<?php
namespace Jab\Config\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jab\Config\EntityBundle\Annotation as JAB;

/**
 * JabEntity
 *
 *  @JAB\Entity(
 *     managedEntity=true,
 *     type="SYSTEM",
 *     readOnlyFields={"id"}
 * )
 * @ORM\MappedSuperclass
 */
class JabEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }
}
