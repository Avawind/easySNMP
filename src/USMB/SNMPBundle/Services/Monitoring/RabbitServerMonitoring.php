<?php
/**
 * Created by IntelliJ IDEA.
 * User: Avawind
 * Date: 25/11/2018
 * Time: 12:32
 */

namespace USMB\SNMPBundle\Services\Monitoring;

use Doctrine\ORM\EntityManager;

/**
 * Class RabbitServerMonitoring
 * @package USMB\SNMPBundle\Services\Monitoring
 */
class RabbitServerMonitoring
{
    /**
     * @var string $login
     */
    protected $login = "monitoring";
    /**
     * @var string $password
     */
    protected $password = "monitoring";
    /**
     * @var string $url_queues
     */
    protected $url_queues = "http://127.0.0.1:15672/api/queues";
    /**
     * @var string $url_consumer
     */
    protected $url_consumer = "http://127.0.0.1:15672/api/consumers";
    /**
     * @var EntityManager $entityManager
     */
    protected $entityManager;

    /**
     * RabbitServerMonitoring constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    /**
     * @return array of queue data
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function monitorRabbitServer()
    {
        //Retrieve data in json
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url_queues);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $output_queue = curl_exec($ch);
        curl_setopt($ch, CURLOPT_URL, $this->url_consumer);
        $output_consumer = curl_exec($ch);
        curl_close($ch);

        $jsonResponseArray = array();

        //Decode data
        $result_queue = json_decode($output_queue);
        $result_consumer = json_decode($output_consumer);
        //Parse data and construct jsonResponse
        foreach ($result_queue as $queue) {
            $jsonResponseArray[$queue->name] = array(
                "status" => $queue->state,
                "node" => $queue->node,
                "queued_messages" => $queue->messages,
                "consumer_isAlive" => false,
                "nb_consumer" => 0
            );
        }

        foreach ($result_consumer as $consumer) {
            $queue_binding = $consumer->queue->name;
            $jsonResponseArray[$queue_binding]["consumer_isAlive"] = true;
            $jsonResponseArray[$queue_binding]["nb_consumer"] = intval($jsonResponseArray[$queue_binding]["nb_consumer"]) + 1;
        }

        $repositoryDevice = $this->entityManager->getRepository("USMBSNMPBundle:Device");
        $nbHost = $repositoryDevice->getNbHost();
        $online = $repositoryDevice->getNbHostOnline();
        $offline = $nbHost - $online;

        $jsonResponseArray['devices'] = array(
            "offline" => intval($offline),
            "online" => intval($online)
        );

        return $jsonResponseArray;
    }

    /**
     * @param $id
     */
    public function closeConnections($id){
        exec('kill -9 `ps aux | less | grep \'rabbitmq:consumer '.$id.'\' | grep -v grep | awk \'{print $2}\'`');
    }

    /**
     * @param $id
     */
    public function startConsumer($id){
        $path = str_replace('web','', getcwd());
        exec("cd ".$path." && php bin/console rabbitmq:consumer ".$id."  > /dev/null &");
    }
}