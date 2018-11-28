<?php
/**
 * Created by IntelliJ IDEA.
 * User: Avawind
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
     * @var EntityManager $entityManager
     */
    protected $entityManager;

    /**
     * Queue producer
     * @var Producer $old_sound_rabbit_mq
     */
    protected $old_sound_rabbit_mq;

    /**
     * Log events
     * @var Logger $logger
     */
    protected $logger;

    /**
     * Delay between two snmp request (Load Balancing) in seconds
     * @var int $host_inter_check_delay
     *
     */
    private $host_inter_check_delay;

    /**
     * Like 2 min
     * @var int $check_inter
     */
    private $check_inter = 2;


    /**
     * Monitor constructor.
     * @param EntityManager $entityManager
     * @param Producer $old_sound_rabbit_mq
     * @param Logger $logger
     */
    public function __construct(EntityManager $entityManager, Producer $old_sound_rabbit_mq, Logger $logger)
    {
        $this->entityManager = $entityManager;
        $this->old_sound_rabbit_mq = $old_sound_rabbit_mq;
        $this->logger = $logger;
        $this->host_inter_check_delay = $this->interCheckDelayCalculation();
    }


    /**
     * * * * * * * * * *
     * Load Balancing  *
     * * * * * * * * * *
     * Calculations of the inter_check delay when the monitoring module is called :
     *
     * host inter-check delay = (average check interval for all hosts) / (total number of hosts)
     *   average check interval for all hosts = (total host check interval) / (total number of hosts)
     *       total host check interval = (total check_interval of all hosts) * (check_interval)
     *
     * In this calculation whe considerate that the snmp_request delay is nullable (LAN).
     * To avoid queue problems let's said we take 15% of margin.
     *
     *
     * @source https://assets.nagios.com/downloads/nagioscore/docs/nagioscore/4/en/checkscheduling.html
     *
     * @return int $host_inter_check_delay
     */
    public function interCheckDelayCalculation()
    {
        $nbRequestToSend = $this->entityManager->getRepository('USMBSNMPBundle:Device')->getNbRequestToSend();
        $nbRequestToSend = $nbRequestToSend + $nbRequestToSend * 0.15;
        $total_host_check_inter = $nbRequestToSend * $this->check_inter;
        $total_host_check_inter = $total_host_check_inter * 60;
        $total_average_check_inter = $total_host_check_inter / $nbRequestToSend;
        $host_inter_check_delay = $total_average_check_inter / $nbRequestToSend;


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
                        'inter_delay_check' => $this->host_inter_check_delay,
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