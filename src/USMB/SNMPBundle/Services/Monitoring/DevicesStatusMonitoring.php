<?php
/**
 * Created by IntelliJ IDEA.
 * User: Avawind
 * Date: 28/11/2018
 * Time: 18:29
 */

namespace USMB\SNMPBundle\Services\Monitoring;

use Doctrine\ORM\EntityManager;

/**
 * Class DevicesStatusMonitoring
 * @package USMB\SNMPBundle\Services\Monitoring
 */
class DevicesStatusMonitoring
{
    /**
     * @var EntityManager $entityManager
     */
    protected $entityManager;

    /**
     * DevicesStatusMonitoring constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return array os devices data
     */
    public function monitorDevice()
    {
        $jsonResponseArray = array();
        $repositoryDevices = $this->entityManager->getRepository("USMBSNMPBundle:Device");
        $hosts = $repositoryDevices->findAll();
        foreach ($hosts as $host){
            $jsonResponseArray[$host->getName()] = array(
                "id" => $host->getId(),
                "status" => $host->getIsAlive()
            );
    }
        return $jsonResponseArray;
    }
}