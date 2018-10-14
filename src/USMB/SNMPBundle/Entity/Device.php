<?php

namespace USMB\SNMPBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Device
 *
 * @ORM\Table(name="device")
 * @ORM\Entity(repositoryClass="USMB\SNMPBundle\Repository\DeviceRepository")
 *
 */

class Device
{
    /**
     * Device constructor.
     */
    public function __construct()
    {
        $this->profiles = new ArrayCollection();
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="host", type="string", length=255)
     */
    private $host;

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string", length=255, nullable=true)
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(name="community", type="string", length=255)
     */
    private $community;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isAlive", type="boolean", nullable=true)
     */
    private $isAlive;

    /**
     * @ORM\ManyToMany(targetEntity="USMB\SNMPBundle\Entity\Profile", cascade={"persist"})
     * @ORM\JoinTable(name="usmb_device_profiles")
     */
    private $profiles;

    /**
     * add profile to monitor
     *
     *
     * @param Profile $profile
     */
    public function addProfile(Profile $profile)
    {
        $this->profiles[] = $profile;
    }

    /**
     * remove profile from list
     *
     * @param Profile $profile
     */
    public function removeProfile(Profile $profile)
    {
        $this->profiles->removeElement($profile);
    }

    /**
     * @return mixed
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Device
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set host
     *
     * @param string $host
     *
     * @return Device
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Get host
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set location
     *
     * @param string $location
     *
     * @return Device
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set community
     *
     * @param string $community
     *
     * @return Device
     */
    public function setCommunity($community)
    {
        $this->community = $community;

        return $this;
    }

    /**
     * Get community
     *
     * @return string
     */
    public function getCommunity()
    {
        return $this->community;
    }

    /**
     * Set isAlive
     *
     * @param boolean $isAlive
     *
     * @return Device
     */
    public function setIsAlive($isAlive)
    {
        $this->isAlive = $isAlive;

        return $this;
    }

    /**
     * Get isAlive
     *
     * @return boolean
     */
    public function getIsAlive()
    {
        return $this->isAlive;
    }
}
