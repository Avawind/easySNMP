<?php
/**
 * Created by IntelliJ IDEA.
 * User: user
 * Date: 16/11/2018
 * Time: 16:32
 */

namespace USMB\SNMPBundle\Services\Config;


use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class CreateTable
 * @package USMB\SNMPBundle\Services\Config
 */
class CreateTable
{

    /**
     * @var KernelInterface $kernel
     */
    protected $kernel;

    /**
     * @var String $path
     */
    protected $path;

    /**
     * CreateTableCommand constructor.
     * @param $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->path = str_replace('web','', getcwd());
    }

    /**
     * @param $deviceId
     * @param $profileId
     * @param $type
     * @throws \Exception
     */
    public function execute($deviceId, $profileId, $type)
    {
        if(!$this->fileExist($deviceId, $profileId)) {
            //Création du fichier :
            $this->createEntityFile($deviceId, $profileId, $type);
            $this->createRepositoryFile($deviceId,$profileId);

            exec("cd ".$this->path." && php bin/console doctrine:schema:update --force");

        }
    }

    /**
     * @param $deviceId
     * @param $profileId
     * @return bool
     */
    public function fileExist($deviceId, $profileId){
        if(file_exists($this->path."src/USMB/SNMPBundle/Entity/Device_".$deviceId."_Profile_".$profileId.".php")){
            return true;
        } else {
            return false;
        }
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
                $dbType = "integer";
                break;
            case "DateTime":
                $dbType = "datetime";
                break;
        }
        $content = "<?php

namespace USMB\SNMPBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Device_".$deviceId."_Profile_".$profileId."
 *
 * @ORM\Table(name=\"Device_".$deviceId."_Profile_".$profileId."\")
 * @ORM\Entity(repositoryClass=\"USMB\SNMPBundle\Repository\Device_".$deviceId."_Profile_".$profileId."Repository\")
 * @ORM\HasLifecycleCallbacks()
 */
class Device_".$deviceId."_Profile_".$profileId."
{
    /**
     * @var int
     *
     * @ORM\Column(name=\"id\", type=\"integer\")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy=\"AUTO\")
     */
    private \$id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name=\"createdAt\", type=\"datetime\")
     */
    private \$createdAt;

    /**
     * @var ".$dbType."
     *
     * @ORM\Column(name=\"result\", type=\"".$dbType."\")
     */
    private \$result;

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        \$this->createdAt = new \DateTime();
    }
    
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return \$this->id;
    }

    /**
     * Set createdAt
     * @param \DateTime \$createdAt
     * @return Device_".$deviceId."_Profile_".$profileId."    
     */
    public function setCreatedAt(\$createdAt)
    {
        \$this->createdAt = \$createdAt;

        return \$this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return \$this->createdAt;
    }

    /**
     * @param \$result
     * @return \$this
     */
    public function setResult(\$result)
    {
        \$this->result = \$result;

        return \$this;
    }

    /**
     * Get result
     *
     * @return string
     */
    public function getResult()
    {
        return \$this->result;
    }
}
";

        $file = fopen($this->path."src/USMB/SNMPBundle/Entity/Device_".$deviceId."_Profile_".$profileId.".php", "w+");
        fwrite($file, $content);
        fclose($file);

    }

    private function createRepositoryFile($deviceId, $profileId){
        $content = "<?php

namespace USMB\SNMPBundle\Repository;

/**
 * Device_".$deviceId."_Profile_".$profileId."Repository 
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Device_".$deviceId."_Profile_".$profileId."Repository extends \Doctrine\ORM\EntityRepository
{
}
";
        $file = fopen($this->path."src/USMB/SNMPBundle/Repository/Device_".$deviceId."_Profile_".$profileId."Repository.php", "w+");
        fwrite($file, $content);
        fclose($file);

    }

}
