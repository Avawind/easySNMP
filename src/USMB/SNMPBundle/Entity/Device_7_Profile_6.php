<?php

namespace USMB\SNMPBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Device_7_Profile_6
 *
 * @ORM\Table(name="Device_7_Profile_6")
 * @ORM\Entity(repositoryClass="USMB\SNMPBundle\Repository\Device_7_Profile_6Repository")
 * @ORM\HasLifecycleCallbacks()
 */
class Device_7_Profile_6
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="result", type="integer")
     */
    private $result;

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime();
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
     * Set createdAt
     * @param \DateTime $createdAt
     * @return Device_7_Profile_6    
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param $result
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get result
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }
}
