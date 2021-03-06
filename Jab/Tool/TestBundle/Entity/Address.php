<?php
namespace Jab\Tool\TestBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Jab\Config\EntityBundle\Annotation as JAB;

/**
 * Address
 * Autogenerated: 2014-09-04 19:37:44
 *
 *  @JAB\Entity(
 *     managedEntity=true,
 *     type="CUSTOM"
 * )
 * @ORM\Table(name="address_info")
 * @ORM\Entity(repositoryClass="Jab\Tool\TestBundle\Entity\AddressRepository")
 */
class Address extends \Jab\Config\EntityBundle\Entity\Presets\JabEntityTimestampable
{
    /**
     * @var string
     *
     * @ORM\Column(name="streetaddress", type="string", length=138, nullable=true, unique=false)
     */
    private $streetAddress;

    /**
     * @var \Jab\App\AccountBundle\Entity\Account
     *
     * @ORM\ManyToOne(targetEntity="\Jab\App\AccountBundle\Entity\Account", inversedBy="addresses")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $account;


    /**
     * Set streetAddress
     *
     * @param string $streetAddress
     * @return Address
     */
    public function setStreetAddress($streetAddress) {
        $this->streetAddress = $streetAddress;
        return $this;
    }

    /**
     * Get streetAddress
     *
     * @return string
     */
    public function getStreetAddress() {
        return $this->streetAddress;
    }

    /**
     * Set account
     *
     * @param \Jab\App\AccountBundle\Entity\Account $account
     * @return Address
     */
    public function setAccount(\Jab\App\AccountBundle\Entity\Account $account = null) {
        $this->account = $account;
        return $this;
    }

    /**
     * Get account
     *
     * @return \Jab\App\AccountBundle\Entity\Account
     */
    public function getAccount() {
        return $this->account;
    }
}
