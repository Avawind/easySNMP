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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use USMB\SNMPBundle\Entity\Device;
use USMB\SNMPBundle\Entity\Profile;
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

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            // Save add/manage entity
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            // Add a log in the table Logs
            $this->logging_service->logInfo($entityName . ' created or updated : ' . $entity->getName() . ' by ' . $username);

            if ($entityName == "Device") {
                $device = $entity;
                if ($device == null) {
                    $repositoryDevice = $this->entityManager->getRepository("USMBSNMPBundle:Device");
                    $device = $repositoryDevice->find($id);
                }
                $profiles = $device->getProfiles();

                foreach ($profiles as $profile) {
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

        $repositoryEntity = $this->entityManager->getRepository("\USMB\SNMPBundle\Entity\\" . $entityName);
        $entity = $repositoryEntity->find($id);

        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        $this->logging_service->logInfo($entityName . ' deleted : ' . $entity->getName() . ' by ' . $username);

    }

    /**
     * Create JSON File to export current config
     */
    public function exportJSON()
    {

        /* Add Export Profiles */
        $jsonResponseArray = array();

        $repositoryProfiles = $this->entityManager->getRepository("USMBSNMPBundle:Profile");
        $profiles = $repositoryProfiles->findAll();
        foreach ($profiles as $profile) {
            $arrayProfile[$profile->getName()] = array(
                "name" => $profile->getName(),
                "oid" => $profile->getOid(),
                "id" => $profile->getId(),
                "type" => $profile->getType()
            );
            $jsonResponseArray["Profiles"] = $arrayProfile;

        }
        /* Add Export Devices */
        $repositoryDevices = $this->entityManager->getRepository("USMBSNMPBundle:Device");
        $devices = $repositoryDevices->findAll();

        foreach ($devices as $device) {
            if ($device->getVersion() === 'V2') {
                $arrayProfiles = array();
                foreach ($device->getProfiles() as $profile) {
                    $arrayProfiles[$profile->getName()] = $profile->getId();

                }
                $arrayDevice[$device->getName()] = array(
                    "name" => $device->getName(),
                    "host" => $device->getHost(),
                    "version" => $device->getVersion(),
                    "location" => $device->getLocation(),
                    "community" => $device->getCommunity(),
                    "profiles" => $arrayProfiles
                );
                $jsonResponseArray["Devices"] = $arrayDevice;
                /* Add SNMPv3 parameters (only if snmp use version 3) */
            } else {
                $arrayProfiles = array();
                foreach ($device->getProfiles() as $profile) {
                    $arrayProfiles[$profile->getName()] = $profile->getId();

                }
                $arrayDevice[$device->getName()] = array(
                    "name" => $device->getName(),
                    "host" => $device->getHost(),
                    "version" => $device->getVersion(),
                    "location" => $device->getLocation(),
                    "community" => $device->getCommunity(),
                    "username" => $device->getUser(),
                    "password" => $device->getPassword(),
                    "crypto_key" => $device->getCryptoKey(),
                    "profiles" => $arrayProfiles
                );
                $jsonResponseArray["Devices"] = $arrayDevice;
            }

        }
        return $jsonResponseArray;

    }

    /**
     * @param  $json
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function importJSON($json)
    {
        //Retrieve the profiles data in json array
        $arrayProfiles = $json->Profiles;
        $arrayDevices = $json->Devices;
        //Setup the repository Profile
        $repositoryProfiles = $this->entityManager->getRepository("USMBSNMPBundle:Profile");
        $repositoryDevices = $this->entityManager->getRepository("USMBSNMPBundle:Device");

        //Process
        foreach ($arrayProfiles as $profile_row) {
            //Retrieve element in dtaabase
            $profile = $repositoryProfiles->findOneByName($profile_row->name);
            //If null does'nt exist.
            if ($profile == null) {
                $profile = new Profile();
                $profile->setName($profile_row->name);
                $profile->setOid($profile_row->oid);
                $profile->setType($profile_row->type);

                $this->logging_service->logInfo("Profile added by config file : " . $profile->getName());

                $this->entityManager->persist($profile);

                // If $profile exist
            } else {
                // Check and Set the OID if it's different
                if ($profile->getOid() != $profile_row->oid) {
                    $profile->setOid($profile_row->oid);
                    $this->logging_service->logInfo("Profile's oid modified by config file : " . $profile->getName());
                }
                // Check and Set the Type if it's different
                if ($profile->getType() != $profile_row->type) {
                    $profile->setType($profile_row->type);
                    $this->logging_service->logInfo("Profile's type modified by config file : " . $profile->getName());
                }
                $this->entityManager->persist($profile);
            }


        }

        //Process Devices
        foreach ($arrayDevices as $device_row) {
            //Retrieve element in database
            $device = $repositoryDevices->findOneByName($device_row->name);
            //If null does'nt exist.
            if ($device == null) {
                $device = new Device();
                $device->setName($device_row->name);
                $device->setHost($device_row->host);
                $device->setVersion($device_row->version);
                $device->setLocation($device_row->location);
                $device->setCommunity($device_row->community);
                $device->setUser($device_row->username);
                $device->setPassword($device_row->password);
                $device->setCryptoKey($device_row->crypto_key);

                foreach ($device_row->profiles as $profile) {
                    $device->addProfile($repositoryProfiles->find(intval($profile)));
                }

                $this->logging_service->logInfo("Device added by config file : " . $device->getName());
                $this->entityManager->persist($device);

                // If $profile exist
            } else {
                // Check and Set the Host if it's different
                if ($device->getHost() != $device_row->host) {
                    $device->setHost($device_row->host);
                    $this->logging_service->logInfo("Device's host modified by config file : " . $device->getHost() . " for " . $device->getName());
                }
                // Check and Set the Version if it's different
                if ($device->getVersion() != $device_row->version) {
                    $device->setVersion($device_row->version);
                    $this->logging_service->logInfo("Device's version modified by config file : " . $device->getVersion() . " for " . $device->getName());
                }
                // Check and Set the Location if it's different
                if ($device->getLocation() != $device_row->location) {
                    $device->setLocation($device_row->location);
                    $this->logging_service->logInfo("Device's location modified by config file : " . $device->getLocation() . " for " . $device->getName());
                }
                // Check and Set the Community if it's different
                if ($device->getCommunity() != $device_row->community) {
                    $device->setCommunity($device_row->community);
                    $this->logging_service->logInfo("Device's community modified by config file : " . $device->getCommunity() . " for " . $device->getName());
                }
                // Check and Set the Username if it's different
                if (isset($device_row->username)) {
                    if ($device->getUser() != $device_row->username) {
                        $device->setUser($device_row->username);
                        $this->logging_service->logInfo("Device's username modified by config file : " . $device->getUser() . " for " . $device->getName());
                    }
                }

                // Check and Set the Password if it's different
                if (isset($device_row->password)) {
                    if ($device->getPassword() != $device_row->password) {
                        $device->setPassword($device_row->password);
                        $this->logging_service->logInfo("Device's password modified by config file : " . $device->getPassword() . " for " . $device->getName());
                    }
                }

                // Check and Set the CryptoKey if it's different
                if (isset($device_row->crypto_key)) {
                    if ($device->getCryptoKey() != $device_row->crypto_key) {
                        $device->setCryptoKey($device_row->crypto_key);
                        $this->logging_service->logInfo("Device's CryptoKey modified by config file : " . $device->getCryptoKey() . " for " . $device->getName());
                    }
                }


                $arrayIdProfiles = (array) $device_row->profiles;
                $arrayProfiles = $device->getProfiles();

                foreach ($arrayProfiles as $profile) {
                    $result = false;
                    foreach ($arrayIdProfiles as $idProfile) {
                        if ($idProfile == $profile->getId()) {
                            $result = true;
                        }
                    }
                    if (!$result) {
                        $device->removeProfile($profile);
                        $this->logging_service->logInfo("Device's profile removed by config file : " . $profile->getName() . " for " . $device->getName());
                    }

                }
                foreach ($arrayIdProfiles as $idProfile) {
                    $result = false;
                    foreach ($arrayProfiles as $profile) {
                        if ($idProfile == $profile->getId()) {
                            $result = true;
                        }
                    }
                    if (!$result) {
                        //Retrieve profile to link it with the device
                        $device->addProfile($profile);
                        $this->logging_service->logInfo("Device's profile added by config file : " . $profile->getName() . " for " . $device->getName());
                        //Create Table
                        $this->createTable->execute($device->getId(), $profile->getId(), $profile->getType());
                    }

                }
                $this->entityManager->persist($device);
            }

        }
        //Flush change
        $this->entityManager->flush();
    }
}