<?php
/**
 * Created by IntelliJ IDEA.
 * User: tfoissard
 * Date: 27/09/2018
 * Time: 20:49
 */

namespace USMB\SNMPBundle\Services\Monitoring;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use OldSound\RabbitMqBundle\RabbitMq\Producer;

/**
 * Class Monitor
 * @package USMB\SNMPBundle\Services\Monitoring
 */
class Monitor
{
    /**
     * Manage entity
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Queue producer
     * @var Producer
     */
    protected $old_sound_rabbit_mq;

    /**
     * Log events
     * @var Logger
     */
    protected $logger;

    /**
     * Delay between two snmp request (Load Balancing)
     * @var int
     *
     */
    private $host_inter_check_delay;

    /**
     * Like 5 min
     * @var int
     */
    private $check_inter = 5;

    /**
     * Monitor constructor.
     * @param EntityManager $entityManager
     * @param Producer $old_sound_rabbit_mq
     * @param Logger $logger
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function __construct(EntityManager $entityManager, Producer $old_sound_rabbit_mq, Logger $logger)
    {
        $this->entityManager = $entityManager;
        $this->old_sound_rabbit_mq = $old_sound_rabbit_mq;
        $this->logger = $logger;
        $this->host_inter_check_delay = $this->interCheckDelayCalculation();
    }


    /**
     *
     * Calculations of the inter_check delay when the monitoring module is called :
     *
     * host inter-check delay = (average check interval for all hosts) / (total number of hosts)
     *   average check interval for all hosts = (total host check interval) / (total number of hosts)
     *       total host check interval = (total check_interval of all hosts) * (check_interval)
     *
     * @source https://assets.nagios.com/downloads/nagioscore/docs/nagioscore/4/en/checkscheduling.html
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function interCheckDelayCalculation()
    {

        $nbHost = $this->entityManager->getRepository('USMBSNMPBundle:Device')->getNbHost();
        $total_host_check_inter = $nbHost * $this->check_inter;
        $total_host_check_inter = $total_host_check_inter * 60;
        $total_average_check_inter = $total_host_check_inter / $nbHost;
        $host_inter_check_delay = $total_average_check_inter / $nbHost;

        return $host_inter_check_delay;
    }

    /**
     * Process devices monitoring
     * @source https://github.com/php-amqplib/RabbitMqBundle
     */
    public function monitoring()
    {
        //Retrieve devices
        $devices = $this->entityManager->getRepository('USMBSNMPBundle:Device')->findAll();

        //Device counter
        $SNMPRequestCounter = 0;
        $ICMPRequestCounter = 0;

        //Iterate on each device
        foreach ($devices as $device) {

            //1 - Check ICMP

            //Setup request
            $requestICMP = array(
                'idDevice' => $device->getId(),
                'deviceName' => $device->getName(),
                'host' => $device->getHost(),
            );

            //Send request params to queue
            $this->old_sound_rabbit_mq->publish(serialize($requestICMP), 'icmp_request_queue');
            $ICMPRequestCounter++;

            //Iterate on each profile
            foreach ($device->getProfiles() as $profile) {
                //2 - Check SNMP

                //If host isn't alive then do nothing
                if($device->getIsAlive()) {
                    //Setup request
                    $requestSNMP = array(
                        'idDevice' => $device->getId(),
                        'deviceName' => $device->getName(),
                        'idProfile' => $profile->getId(),
                        'profileName' => $profile->getName(),
                        'host' => $device->getHost(),
                        'community' => $device->getCommunity(),
                        'oid' => $profile->getOid()
                    );

                    //Send request params to queue
                    $this->old_sound_rabbit_mq->publish(serialize($requestSNMP), 'snmp_request_queue');
                    $SNMPRequestCounter++;
                }
            }
        }

        $this->logger->info("Monitor - monitoring() called : Request queued : " . $SNMPRequestCounter . " SNMP / ".$ICMPRequestCounter." ICMP");


    }
}