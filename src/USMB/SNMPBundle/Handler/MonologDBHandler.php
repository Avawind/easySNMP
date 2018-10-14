<?php
/**
 * Created by IntelliJ IDEA.
 * User: tfoissard
 * Date: 05/10/2018
 * Time: 23:43
 */

namespace USMB\SNMPBundle\Handler;


use USMB\SNMPBundle\Entity\Log;
use Doctrine\ORM\EntityManager;
use Monolog\Handler\AbstractProcessingHandler;

class MonologDBHandler extends AbstractProcessingHandler
{

    /**
     * @var EntityManager
     */
    protected $entityManager;


    /**
     * MonologDBHandler constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    /**
     * Called when writing to our database
     * @param array $record
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function write(array $record)
    {
        $logEntry = new Log();
        $logEntry->setMessage($record['message']);
        $logEntry->setLevel($record['level']);
        $logEntry->setLevelName($record['level_name']);
        $logEntry->setExtra($record['extra']);
        $logEntry->setContext($record['context']);

        $this->entityManager->persist($logEntry);
        $this->entityManager->flush();
    }


}