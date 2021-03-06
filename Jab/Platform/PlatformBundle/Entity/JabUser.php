<?php
namespace Jab\Platform\PlatformBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jab\App\UserBundle\Entity\Person;
use Jab\Config\EntityBundle\Annotation as JAB;

/**
 * JabUser
 * Autogenerated: 2014-09-18 11:16:42
 *
 *  @JAB\Entity(
 *     managedEntity=true,
 *     type="CUSTOM",
 *     readOnlyFields={"id","preferences"}
 * )
 * @ORM\Table(name="user")
 * @ORM\Entity
 */
class JabUser extends \FOS\UserBundle\Entity\User
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var array
     *
     * @ORM\Column(name="preferences", type="array", length=65535, nullable=true, unique=false)
     */
    private $preferences;

    /**
     * @var \Jab\App\UserBundle\Entity\Person
     *
     * @ORM\OneToOne(targetEntity="Jab\App\UserBundle\Entity\Person")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="person_id", referencedColumnName="id", unique=true, nullable=true)
     * })
     */
    private $person;


    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }



    /**
     * Set preferences
     *
     * @param array $preferences
     * @return JabUser
     */
    public function setPreferences($preferences) {
        $this->preferences = $preferences;
        return $this;
    }

    /**
     * Get preferences
     *
     * @return array
     */
    public function getPreferences() {
        return $this->preferences;
    }

    /**
     * @param Person $person
     */
    public function setPerson($person) {
        $this->person = $person;
    }

    /**
     * @return Person
     */
    public function getPerson() {
        return $this->person;
    }
}
