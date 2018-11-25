<?php
/**
 * Created by IntelliJ IDEA.
 * User: tfoissard
 * Date: 05/10/2018
 * Time: 20:30
 */

namespace USMB\SNMPBundle\Services\Monitoring;

use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use USMB\SNMPBundle\Services\Logging\Logging;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class SNMPRequestConsumer
 * @package USMB\SNMPBundle\Services\Monitoring
 * @src https://github.com/php-amqplib/RabbitMqBundle
 */
class SNMPRequestConsumer implements ConsumerInterface
{

    /**
     * @var Logging $logging_service
     */
    protected $logging_service;

    /**
     * SNMPRequestConsumer constructor.
     * @param Logging $logging_service
     */
    public function __construct(Logging $logging_service)
    {
        $this->logging_service = $logging_service;
    }


    /**
     * @param AMQPMessage $request
     * @return bool|mixed
     */
    public function execute(AMQPMessage $request)
    {
        //Retrieve request data
        $body = $request->getBody();
        $body = unserialize($body);
        //Wait()
        sleep(intval($body['inter_delay_check']));

        //Process snmp request.
        //$request will be an instance of `PhpAmqpLib\Message\AMQPMessage` with the $request->body being the data sent over RabbitMQ.
        try {
            $result = snmp2_get($body['host'], $body['community'], $body['oid'], 1000000, 0);
            $this->logging_service->logSNMP($result, $body['idDevice'], $body['idProfile']);
        } catch (\Exception $e){
            $error = "SNMP Error - Device : ".$body['deviceName']." Profile : ".$body['profileName']." Message : ".$e->getMessage();
            $this->logging_service->logError($error);

            //Requeue the request
            //return false;

            //Avoid endless queued task
            return true;
        }

        //Remove request from the queue
        return true;
    }

}