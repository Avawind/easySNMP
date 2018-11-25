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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        $this->em = $this->getDoctrine()->getEntityManager();
        $repositoryDevice = $this->em->getRepository('USMBSNMPBundle:Device');

        $nbDevices = $repositoryDevice->getNbHost();
        $nbOnlineDevices = $repositoryDevice->getNbHostOnline();
        $nbOfflineDevices = $nbDevices - $nbOnlineDevices;

        return $this->render('USMBSNMPBundle:SNMPBundle:dashboard.html.twig', array(
            'offline_device' => $nbOfflineDevices,
            'online_device' => $nbOnlineDevices
        ));
    }

    public function monitorAction(){
        $this->get('usmbsnmp_monitor')->monitoring();

        return new JsonResponse(array("monitor" => "ok"));
    }

    /**
     * API RabbitMq Management
     * @return JsonResponse
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function dashboardDataAction()
    {
        return new JsonResponse($this->get('usmbsnmp_monitor_rabbit_server')->monitorRabbitServer());
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function startConsumerAction($id){

        $this->get('usmbsnmp_monitor_rabbit_server')->startConsumer($id);
        $this->get('usmbsnmp_logging')->logInfo("Dashboard Action : Consumer added by ".$this->getUser()->getUsername()." for ".$id);

        return $this->redirectToRoute('usmbsnmp_dashboard');
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function stopConsumerAction($id){

        $this->get('usmbsnmp_monitor_rabbit_server')->closeConnections($id);
        $this->get('usmbsnmp_logging')->logCrit("Dashboard Action : All connections closed by ".$this->getUser()->getUsername());

        return $this->redirectToRoute('usmbsnmp_dashboard');
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * Return the devices list in the view (Discovery, not manage)
     */
    public function devicesAction(Request $request)
    {
        $devices = $this->get('usmbsnmp_config')->retrieveAllEntries("USMBSNMPBundle:Device");

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

        foreach ($profiles as $profile) {
            $repository = 'USMBSNMPBundle:Device_' . $id . '_Profile_' . $profile->getId();
            $repositoryData = $this->em->getRepository($repository);
            $allRows = $repositoryData->findAll();
            foreach ($allRows as $row) {
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
     *
     * Return the devices list in the view (Manage, not discover)
     *
     */
    public function manageDevicesAction(Request $request)
    {

        $devices = $this->get('usmbsnmp_config')->retrieveAllEntries("USMBSNMPBundle:Device");

        return $this->render('@USMBSNMP/SNMPBundle/manageDevices.html.twig', array(
            'devices' => $devices,
        ));
    }

    /**
     * Call the function manageEntityAction in USMB\SNMPBundle\Services\Config
     * Return the form of device in the view
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function manageDeviceAction(Request $request, $id)
    {
        $userName = $this->getUser()->getUsername();
        $entityName = "Device";
        $form = $this->get('usmbsnmp_config')->manageEntityAction($request, $id, $entityName, $userName);


        if ($request->isMethod('POST')) {
            return $this->redirectToRoute('usmbsnmp_manage_devices');
        } else {
            return $this->render('USMBSNMPBundle:SNMPBundle:manageDevice.html.twig', array(
                'form' => $form->createView(),
            ));
        }

    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * To delete a device in Manage\Devices
     *
     */
    public function deleteDeviceAction(Request $request, $id)
    {

        $entityName = "Device";
        $userName = $this->getUser()->getUsername();
        $this->get('usmbsnmp_config')->deleteEntityAction($id, $entityName, $userName);

        return $this->redirectToRoute('usmbsnmp_manage_devices');

    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * Return the profiles list in the view
     *
     */
    public function manageProfilesAction(Request $request)
    {
        $profiles = $this->get('usmbsnmp_config')->retrieveAllEntries("USMBSNMPBundle:Profile");

        return $this->render('@USMBSNMP/SNMPBundle/manageProfils.html.twig', array(
            'profiles' => $profiles,
        ));
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * Call the function manageEntityAction in USMB\SNMPBundle\Services\Config
     * Return the form of profile in the view
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function manageProfileAction(Request $request, $id)
    {
        $userName = $this->getUser()->getUsername();
        $entityName = "Profile";
        $form = $this->get('usmbsnmp_config')->manageEntityAction($request, $id, $entityName, $userName);

        if ($request->isMethod('POST')) {
            return $this->redirectToRoute('usmbsnmp_manage_profiles');
        } else {
            return $this->render('USMBSNMPBundle:SNMPBundle:manageProfil.html.twig', array(
                'form' => $form->createView(),
            ));
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteProfileAction(Request $request, $id)
    {
        $entityName = "Profile";
        $userName = $this->getUser()->getUsername();
        $this->get('usmbsnmp_config')->deleteEntityAction($id, $entityName, $userName);

        return $this->redirectToRoute('usmbsnmp_manage_profiles');

    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * Return logs in the view
     *
     */
    public function manageLogsAction(Request $request)
    {
        $logs_sys = $this->get('usmbsnmp_config')->retrieveAllEntries("USMBSNMPBundle:Log");

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
     *
     * Return the users list in the view
     *
     */
    public function manageUsersAction(Request $request)
    {
        $users = $this->get('usmbsnmp_config')->retrieveUsers();

        return $this->render('@USMBSNMP/SNMPBundle/manageUsers.html.twig', array(
            'users' => $users,
        ));
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     *
     *
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

            $factory = $this->get('security.encoder_factory');

            $encoder = $factory->getEncoder($user);
            $user->setSalt(md5(time()));
            $pass = $encoder->encodePassword($datadForm->getPassword(), $user->getSalt());

            $user->setUsername($datadForm->getUsername())
                ->setEmail($datadForm->getEmail())
                ->setPassword($pass)
                ->setEnabled(true);
            foreach ($datadForm->getRoles() as $role) {
                $user->addRole($role);
            }
            $userManager->updateUser($user);

            $this->get('monolog.logger.db')->info('User created or updated : ' . $user->getUsername() . ' by ' . $this->getUser()->getUsername());

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
        $user = $userManager->findUserBy(array('id' => $id));
        $userManager->deleteUser($user);

        $this->get('monolog.logger.db')->info('User deleted : ' . $user->getUsername() . ' by ' . $this->getUser()->getUsername());

        return $this->redirectToRoute('usmbsnmp_manage_users');

    }

}