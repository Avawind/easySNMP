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
            'offline_device' => $nbOfflineDevices,
            'online_device' => $nbOnlineDevices
        ));
    }

    /**
     * API RabbitMq Management
     * @return JsonResponse
     */
    public function dashboardDataAction()
    {
        //Params
        $login = "monitoring";
        $password = "monitoring";
        $url_queues = "http://127.0.0.1:15672/api/queues";
        $url_consumer =  "http://127.0.0.1:15672/api/consumers";

        //Retrieve data in json
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_queues);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$login:$password");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $output_queue = curl_exec($ch);
        curl_setopt($ch, CURLOPT_URL, $url_consumer);
        $output_consumer = curl_exec($ch);
        curl_close($ch);

        $jsonResponse = array();

        //Decode data
        $result_queue = json_decode($output_queue);
        $result_consumer = json_decode($output_consumer);
        //Parse data and construct jsonResponse
        foreach ($result_queue as $queue) {
            $jsonResponse[$queue->name] = array(
                "status" => $queue->state,
                "node" => $queue->node,
                "queued_messages" => $queue->messages,
                "consumer_isAlive" => false,
                "nb_consumer" => 0
            );
        }
        foreach ($result_consumer as $consumer) {
            $queue_binding = $consumer->queue->name;
            $jsonResponse[$queue_binding]["consumer_isAlive"] = true;
            $jsonResponse[$queue_binding]["nb_consumer"] = intval($jsonResponse[$queue_binding]["nb_consumer"]) + 1;
        }


        return new JsonResponse($jsonResponse);
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function startConsumerAction($id){
        $path = str_replace('web','', getcwd());
        exec("cd ".$path." && php bin/console rabbitmq:consumer ".$id."  > /dev/null &");
        $this->get('usmbsnmp_logging')->logInfo("Dashboard Action : Consumer added by ".$this->getUser()->getUsername()." for ".$id);
        return $this->redirectToRoute('usmbsnmp_dashboard');
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function stopConsumerAction($id){
        exec('kill -9 `ps aux | less | grep \'rabbitmq:consumer '.$id.'\' | grep -v grep | awk \'{print $2}\'`');
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

            $user->setUsername($datadForm->getUsername())
                ->setEmail($datadForm->getEmail())
                ->setPassword($datadForm->getPassword());
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
        $user = $userManager->findUser($id);
        $userManager->deleteUser($user);

        $this->get('monolog.logger.db')->info('User deleted : ' . $user->getUsername() . ' by ' . $this->getUser()->getUsername());

        return $this->redirectToRoute('usmbsnmp_manage_users');

    }

}