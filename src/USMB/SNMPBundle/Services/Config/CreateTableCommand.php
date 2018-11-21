<?php
/**
 * Created by IntelliJ IDEA.
 * User: user
 * Date: 16/11/2018
 * Time: 16:32
 */

namespace USMB\SNMPBundle\Services\Config;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class CreateTableCommand
{

    protected $kernel;

    /**
     * CreateTableCommand constructor.
     * @param $kernel
     */
    public function __construct($kernel)
    {
        $this->kernel = $kernel;
    }


    public function execute($deviceId, $profileId, $type)
    {
        //Création du fichier :
        //TODO: verifier que le fichier existe
        $this->createEntityFile($deviceId,$profileId,$type);

        //Execution de la création de la table
        $kernel = $this->get('kernel');
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
            'command' => "doctrine:mapping:import",
            '--bundle' => "USMBSNMPBundle"
        ));
        // Use the NullOutput class instead of BufferedOutput.
        $output = new NullOutput();

        $application->run($input, $output);
    }

    /**
     * @param $deviceId
     * @param $profileId
     * @param $type
     */
    private function createEntityFile($deviceId, $profileId, $type)
    {
        $dbType = null;
        switch ($type){
            case "String":
                $dbType = "string";
                break;
            case "Float":
                $dbType = "float";
                break;
            case "Integer":
                $dbType = "int";
                break;
            case "Time":
                $dbType = "datetime";
                break;
        }
        $content = "
<?php

namespace USMB\SNMPBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Table(name=\"Device_".$deviceId."_Profile_".$profileId."\")
 * @ORM\HasLifecycleCallbacks()
 */
class Device_1_Profile_1
{
    /**
     * @var int
     *
     * @ORM\Column(name=\"id\", type=\"integer\")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy=\"AUTO\")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name=\"createdAt\", type=\"datetime\")
     */
    private $createdAt;

    /**
     * @var ".$dbType."
     *
     * @ORM\Column(name=\"result\", type=\"".$dbType."\")
     */
    private $result;

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime();
    }
    
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param $result
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get result
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }
}
";

        $file = fopen("/var/www/easySNMP/src/USMB/SNMPBundle/Entity/Device_".$deviceId."_Profile_".$profileId, "w+");
        fwrite($file, $content);
        fclose($file);

    }

}
