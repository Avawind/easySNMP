<?php
/**
 * Created by IntelliJ IDEA.
 * User: coco
 * Date: 27/09/2018
 * Time: 20:49
 */

namespace USMB\SNMPBundle\Services\Config;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Doctrine\UserManager;
use Symfony\Component\Form\FormFactory;
use USMB\SNMPBundle\Entity\Device;
use USMB\SNMPBundle\Form\DeviceType;
use USMB\SNMPBundle\Services\Logging\Logging;
use USMB\SNMPBundle\USMBSNMPBundle;
use USMB\SNMPBundle\Entity\Profile;
use USMB\SNMPBundle\Form\ProfileType;



/**
 * Class Config
 * @package USMB\SNMPBundle\Services\Config
 */
class Config
{
    /**
     * @var Logging $logging_service
     */
    protected $logging_service;

    /**
     * @var EntityManager $entityManager

     */
    protected $entityManager;

    /**
     * @var UserManager $userManager
     */
    protected $userManager;

    /**
     * @var FormFactory $formFactory
     */
    protected $formFactory;

    /**
     * Config constructor.
     * @param Logging $logging_service
     * @param EntityManager $entityManager
     * @param UserManager $userManager
     * @param FormFactory $formFactory
     */
    public function __construct(Logging $logging_service, EntityManager $entityManager, UserManager $userManager, FormFactory $formFactory)
    {
        $this->logging_service = $logging_service;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->userManager = $userManager;
    }

    /**
     * @param $entity
     * @return array|\USMB\SNMPBundle\Entity\Device[]|\USMB\SNMPBundle\Entity\Log[]|\USMB\SNMPBundle\Entity\Profile[]
     */
    public function retrieveAllEntries($entity)
    {
        // Select all entries by entity in Database
        $repositoryDevice = $this->entityManager->getRepository($entity);
        $result = $repositoryDevice->findAll();

        return $result;
    }

    public function manageEntityAction($request, $id, $entityName)
    {
        if ($id == "create") {
            $entity = new $entityName();
        } else {
            $repositoryEntity = $this->entityManager->getRepository('USMBSNMPBundle:'.$entityName);
            $entity = $repositoryEntity->find($id);

        }
        $entityType ="\USMB\SNMPBundle\Form\\".$entityName."Type";

        $form = $this->formFactory->create($entityType, $entity);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->logging_service->logInfo('Device created or updated : '.$entity->getName().' by '. $this->userManager);
        }

        return $form;

    }







}