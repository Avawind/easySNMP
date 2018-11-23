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
use Symfony\Component\HttpFoundation\Request;
use USMB\SNMPBundle\Services\Logging\Logging;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;


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
     * @var TokenStorage $securityToken
     */
    protected $securityToken;

    /**
     * @var FormFactory $formFactory
     */
    protected $formFactory;

    /**
     * @var UserManager $userManager
     */
    protected $userManager;

    /**
     * @var CreateTable $createTable
     */
    protected $createTable;

    /**
     * Config constructor.
     * @param Logging $logging_service
     * @param EntityManager $entityManager
     * @param TokenStorage $securityToken
     * @param FormFactory $formFactory
     * @param UserManager $userManager
     * @param CreateTable $createTable
     */
    public function __construct(Logging $logging_service, EntityManager $entityManager, TokenStorage $securityToken, FormFactory $formFactory, UserManager $userManager, CreateTable $createTable)
    {
        $this->logging_service = $logging_service;
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->securityToken = $securityToken;
        $this->userManager = $userManager;
        $this->createTable = $createTable;
    }


    /**
     * @param $entity
     * @return array|\USMB\SNMPBundle\Entity\Device[]|\USMB\SNMPBundle\Entity\Log[]|\USMB\SNMPBundle\Entity\Profile[]
     *
     *  // Select all entries by entity (Devices, Profiles, Logs) in Database
     */

    public function retrieveAllEntries($entity)
    {
        $repositoryDevice = $this->entityManager->getRepository($entity);
        $result = $repositoryDevice->findAll();

        return $result;
    }


    /**
     * @return array|\Traversable
     *
     * // Select all entries in table Users
     */
    public function retrieveUsers()
    {
        $users = $this->userManager->findUsers();

        return $users;
    }


    /**
     * @param Request $request
     * @param $id
     * @param $entityName
     * @param $username
     * @return \Symfony\Component\Form\FormInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     *
     *   // To create/manage a entity (Device or Profile) and add, return the form to manage entity
     */
    public function manageEntityAction(Request $request, $id, $entityName, $username)
    {

        if ($id == "create") {
            //When we create a entity
            $entityFullName = "\USMB\SNMPBundle\Entity\\" . $entityName;
            $entity = new $entityFullName();

        } else {
            //When we manage a entity
            $repositoryEntity = $this->entityManager->getRepository('USMBSNMPBundle:' . $entityName);
            $entity = $repositoryEntity->find($id);
        }

        //Select the form with the entityName
        $entityFormName = "\USMB\SNMPBundle\Form\\" . $entityName . "Type";
        $form = $this->formFactory->create($entityFormName, $entity);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid())
        {
            // Save add/manage entity
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            // Add a log in the table Logs
            $this->logging_service->logInfo($entityName.' created or updated : '.$entity->getName().' by '.$username);

            if($entityName == "Device"){
                $device = $entity;
                if($device == null){
                    $repositoryDevice = $this->entityManager->getRepository("USMBSNMPBundle:Device");
                    $device = $repositoryDevice->find($id);
                }
                $profiles = $device->getProfiles();

                foreach($profiles as $profile){
                    $this->createTable->execute($device->getId(), $profile->getId(), $profile->getType());
                }
            }
        }



        return $form;
    }

    /**
     * @param $id
     * @param $entityName
     * @param $username
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * Delete a entity
     *
     */
    public function deleteEntityAction($id, $entityName, $username)
    {

        $repositoryEntity = $this->entityManager->getRepository("\USMB\SNMPBundle\Entity\\" .$entityName);
        $entity = $repositoryEntity->find($id);

        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        $this->logging_service->logInfo($entityName.' deleted : '.$entity->getName().' by '.$username);

    }


}