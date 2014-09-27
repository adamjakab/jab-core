<?php
namespace Jab\Tool\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jab\Config\EntityBundle\Annotation as JAB;
use Gedmo\Mapping\Annotation as GEDMO;
use Symfony\Component\Validator\Constraints as ASSERT;

/**
 * Bubucs
 * Autogenerated: 2014-09-17 10:01:43
 *
 *  @JAB\Entity(
 *     managedEntity=true,
 *     type="CUSTOM"
 * )
 * @ORM\Table(name="bubucs")
 * @ORM\Entity(repositoryClass="Jab\Tool\TestBundle\Entity\BubucsRepository")
 */
class Bubucs extends \Jab\Config\EntityBundle\Entity\Presets\JabEntityTimestampable
{
    /**
     * @var string
     *
     * @ORM\Column(name="email_address", type="string", length=128, nullable=false, unique=true)
     * ---(CUSTOM ANNOTATIONS)---
     * some notes for property $email_address
     * @ASSERT\NotBlank(message="No blanks please!")
     * @ASSERT\Email()
     */
    private $Email;

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=64, nullable=true, unique=false)
     */
    private $firstname;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=33, nullable=true, unique=false)
     */
    private $lastname;


    /**
     * Set Email
     *
     * @param string $email
     * @return Bubucs
     */
    public function setEmail($email) {
        $this->Email = $email;
        return $this;
    }

    /**
     * Get Email
     *
     * @return string
     */
    public function getEmail() {
        return $this->Email;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return Bubucs
     */
    public function setFirstname($firstname) {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname() {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return Bubucs
     */
    public function setLastname($lastname) {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname() {
        return $this->lastname;
    }
}