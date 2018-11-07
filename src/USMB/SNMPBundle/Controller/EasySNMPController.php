<?php
/**
 * Created by IntelliJ IDEA.
 * User: tfoissard
 * Date: 27/09/2018
 * Time: 18:51
 */

namespace USMB\SNMPBundle\Controller;


use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use USMB\SNMPBundle\Entity\Device;
use USMB\SNMPBundle\Entity\Profile;
use USMB\SNMPBundle\Form\DeviceType;
use USMB\SNMPBundle\Form\ProfileType;
use USMB\UserBundle\Entity\User;


/**
 * Class EasySNMPController
 * @package USMB\SNMPBundle\Controller
 */
class EasySNMPController extends Controller
{
    /**
     * @var EntityManager
     */
    protected $em;


    /**
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardAction(Request $request)
    {
        $result = 'nothing';
        //Tests :
        //$this->get('monolog.logger.db')->error('something wrong happened ! =( ');
        //$this->get('monolog.logger.db')->info('something  happened !');
        //$this->get('usmbsnmp_monitor')->monitoring();


        $this->em = $this->getDoctrine()->getEntityManager();
        $repositoryDevice = $this->em->getRepository('USMBSNMPBundle:Device');

        $nbDevices = $repositoryDevice->getNbHost();
        $nbOnlineDevices = $repositoryDevice->getNbHostOnline();
        $nbOfflineDevices = $nbDevices - $nbOnlineDevices;

        return $this->render('USMBSNMPBundle:SNMPBundle:dashboard.html.twig', array(
            'result' => $result,
            'offline_device' => $nbOfflineDevices,
            'online_device' => $nbOnlineDevices,
        ));
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function devicesAction(Request $request)
    {
        $this->em = $this->getDoctrine()->getEntityManager();
        $repositoryDevice = $this->em->getRepository('USMBSNMPBundle:Device');

        $devices = $repositoryDevice->findAll();

        return $this->render('@USMBSNMP/SNMPBundle/devices.html.twig', array(
            'devices' => $devices,
        ));
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deviceAction(Request $request, $id)
    {
        $this->em = $this->getDoctrine()->getEntityManager();
        $repositoryDevice = $this->em->getRepository('USMBSNMPBundle:Device');

        $device = $repositoryDevice->find($id);
        $profiles = $device->getProfiles();

        $data = array();

        foreach ($profiles as $profile){
            $repository = 'USMBSNMPBundle:Device_'  .$id. '_Profile_' .$profile->getId();
            $repositoryData = $this->em->getRepository($repository);
            $allRows = $repositoryData->findAll();
            foreach ($allRows as $row){
                $singleArray = array(
                    'createdAt' => $row->getCreatedAt(),
                    'result' => $row->getResult(),
                );
                $data[$profile->getName()] [] = $singleArray;

            }

        }

        return $this->render('@USMBSNMP/SNMPBundle/device.html.twig', array(
            'device' => $device,
            'profiles' => $profiles,
            'data' => $data,
        ));

    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function manageDevicesAction(Request $request)
    {
        $this->em = $this->getDoctrine()->getEntityManager();
        $repositoryDevice = $this->em->getRepository('USMBSNMPBundle:Device');

        $devices = $repositoryDevice->findAll();

        return $this->render('@USMBSNMP/SNMPBundle/manageDevices.html.twig', array(
            'devices' => $devices,
        ));
    }


    public function manageDeviceAction(Request $request, $id)
    {
        if ($id == "create") {
            $device = new Device();
        } else {
            $this->em = $this->getDoctrine()->getEntityManager();
            $repositoryDevice = $this->em->getRepository('USMBSNMPBundle:Device');
            $device = $repositoryDevice->find($id);

        }
        $form = $this->get('form.factory')->create(DeviceType::class, $device);

        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($device);
            $em->flush();

            $this->get('monolog.logger.db')->info('Device created or updated : '.$device->getName().' by '. $this->getUser()->getUsername());

            return $this->redirectToRoute('usmbsnmp_manage_devices');
        }

        return $this->render('USMBSNMPBundle:SNMPBundle:manageDevice.html.twig', array(
            'form' => $form->createView(),
        ));

    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteDeviceAction(Request $request, $id)
    {
        $this->em = $this->getDoctrine()->getEntityManager();
        $reposioryDevice = $this->em->getRepository('USMBSNMPBundle:Device');


        $device = $reposioryDevice->findOneById($id);
        $this->em->remove($device);
        $this->em->flush();

        $this->get('monolog.logger.db')->info('Device deleted : '.$device->getName().' by '. $this->getUser()->getUsername());

        return $this->redirectToRoute('usmbsnmp_manage_devices');

    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function manageProfilesAction(Request $request)
    {
        $this->em = $this->getDoctrine()->getEntityManager();
        $repositoryProfile = $this->em->getRepository('USMBSNMPBundle:Profile');

        $profiles = $repositoryProfile->findAll();

        return $this->render('@USMBSNMP/SNMPBundle/manageProfils.html.twig', array(
            'profiles' => $profiles,
        ));
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function manageProfileAction(Request $request, $id)
    {
        if ($id == "create") {
            $profile = new Profile();
        } else {
            $this->em = $this->getDoctrine()->getEntityManager();
            $repositoryProfile = $this->em->getRepository('USMBSNMPBundle:Profile');
            $profile = $repositoryProfile->find($id);

        }
        $form = $this->get('form.factory')->create(ProfileType::class, $profile);


        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($profile);
            $em->flush();

            $this->get('monolog.logger.db')->info('Profile created or updated : '.$profile->getName().' by '. $this->getUser()->getUsername());

            return $this->redirectToRoute('usmbsnmp_manage_profiles');
        }

        return $this->render('USMBSNMPBundle:SNMPBundle:manageProfil.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteProfileAction(Request $request, $id)
    {
        $this->em = $this->getDoctrine()->getEntityManager();
        $reposioryProfile = $this->em->getRepository('USMBSNMPBundle:Profile');


        $profile = $reposioryProfile->findOneById($id);
        $this->em->remove($profile);
        $this->em->flush();

        $this->get('monolog.logger.db')->info('Profile deleted : '.$profile->getName().' by '. $this->getUser()->getUsername());

        return $this->redirectToRoute('usmbsnmp_manage_profiles');

    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function manageLogsAction(Request $request)
    {
        $this->em = $this->getDoctrine()->getEntityManager();
        $reposioryLog = $this->em->getRepository('USMBSNMPBundle:Log');
        $logs_sys = $reposioryLog->findAll();


        return $this->render('@USMBSNMP/SNMPBundle/manageLogs.html.twig', array(
            'logs_sys' => $logs_sys,
        ));
    }

    /**
     * @param Request $request
     * @param $id
     */
    public function manageLogAction(Request $request, $id)
    {

    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function manageUsersAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $users = $userManager->findUsers();

        return $this->render('@USMBSNMP/SNMPBundle/manageUsers.html.twig', array(
            'users' => $users,
        ));
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function manageUserAction(Request $request, $id)
    {
        if ($id == "create") {
            $user = new User();
        } else {
            $userManager = $this->get('fos_user.user_manager');
            $user = $userManager->findUserBy(array(
                'id' => $id,
            ));
        }

        $form = $this->createFormBuilder($user)
            ->add('username', TextType::class)
            ->add('email', EmailType::class)
            ->add('roles', ChoiceType::class, array(
                'choices' => array(
                    'Opérateur' => 'ROLE_OPERATEUR',
                    'Modérateur' => 'ROLE_MODERATEUR',
                    'Administrateur' => 'ROLE_ADMIN',
                ),
                'multiple' => true,
            ))
            ->add('password', PasswordType::class)
            ->add('save', SubmitType::class, array('label' => 'Save'))
            ->getForm();


        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
            $userManager = $this->get('fos_user.user_manager');
            $datadForm = $form->getData();

            $user->setUsername($datadForm->getUsername())
                ->setEmail($datadForm->getEmail())
                ->setPassword($datadForm->getPassword());
            foreach ($datadForm->getRoles() as $role) {
                $user->addRole($role);
            }
            $userManager->updateUser($user);


            $this->get('monolog.logger.db')->info('User created or updated : '.$user->getUsername().' by '. $this->getUser()->getUsername());

            return $this->redirectToRoute('usmbsnmp_manage_users');
        }

        return $this->render('@USMBSNMP/SNMPBundle/manageUser.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteUserAction(Request $request, $id)
    {

        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUser($id);
        $userManager->deleteUser($user);

        $this->get('monolog.logger.db')->info('User deleted : '.$user->getUsername().' by '. $this->getUser()->getUsername());

        return $this->redirectToRoute('usmbsnmp_manage_users');

    }
}