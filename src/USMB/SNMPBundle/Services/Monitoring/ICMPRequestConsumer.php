<?php
/**
 * Created by IntelliJ IDEA.
 * User: Admin
 * Date: 10/10/2018
 * Time: 20:03
 */

namespace USMB\SNMPBundle\Services\Monitoring;


use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use USMB\SNMPBundle\Services\Logging\Logging;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class ICMPRequestConsumer
 * @package USMB\SNMPBundle\Services\Monitoring
 * @src https://github.com/php-amqplib/RabbitMqBundle
 */
class ICMPRequestConsumer implements ConsumerInterface
{
    /**
     * @var Logging $logging_service
     */
    protected $logging_service;

    /**
     * ICMPRequestConsumer constructor.
     * @param Logging $logging_service
     */
    public function __construct(Logging $logging_service)
    {
        $this->logging_service = $logging_service;
    }

    /**
     * @param AMQPMessage $request
     * @return bool|mixed
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function execute(AMQPMessage $request)
    {
        $body = $request->getBody();
        $body = unserialize($body);

        //Process icmp request.
        //$request will be an instance of `PhpAmqpLib\Message\AMQPMessage` with the $request->body being the data sent over RabbitMQ.
        exec("ping -c 2 ".$body['host'], $output,$status);

        if (0 == $status) {
            $result = true;
            $this->logging_service->logICMP($result, $body['idDevice']);
        }else {
            $result = false;
            $this->logging_service->logICMP($result, $body['idDevice']);
            $this->logging_service->logError("ICMP Error - Device : ".$body['deviceName']." unreachable ! ");

        }
        //Requeue the request
        //return false;

        //Avoid endless queued task
        return true;
    }

}