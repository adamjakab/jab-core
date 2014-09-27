<?php
namespace Jab\Config\EntityBundle\Entity\Presets;

use Doctrine\ORM\Mapping as ORM;
use Jab\Config\EntityBundle\Annotation as JAB;
use Gedmo\Mapping\Annotation as GEDMO;

/**
 * JabEntityTimestampable
 *
 *  @JAB\Entity(
 *     managedEntity=true,
 *     type="SYSTEM",
 *     readOnlyFields={"created_at", "modified_at"}
 * )
 * @ORM\MappedSuperclass
 */
class JabEntityTimestampable extends \Jab\Config\EntityBundle\Entity\JabEntity
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true, unique=false)
     *
     * ---(CUSTOM ANNOTATIONS)---
     * @GEDMO\Timestampable(on="create")
     */
    private $created_at;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified_at", type="datetime", nullable=true, unique=false)
     *
     * ---(CUSTOM ANNOTATIONS)---
     * @GEDMO\Timestampable(on="update")
     */
    private $modified_at;


    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return JabEntityTimestampable
     */
    public function setCreatedAt($createdAt) {
        $this->created_at = $createdAt;
        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime
     */
    public function getCreatedAt() {
        return $this->created_at;
    }

    /**
     * Set modified_at
     *
     * @param \DateTime $modifiedAt
     * @return JabEntityTimestampable
     */
    public function setModifiedAt($modifiedAt) {
        $this->modified_at = $modifiedAt;
        return $this;
    }

    /**
     * Get modified_at
     *
     * @return \DateTime
     */
    public function getModifiedAt() {
        return $this->modified_at;
    }
}
