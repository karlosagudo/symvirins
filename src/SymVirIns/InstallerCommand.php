<?php

namespace SymVirIns;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Twig_Environment;
use Twig_Loader_Filesystem;



class InstallerCommand extends Command {

    protected function configure() {
        $this
                ->setName('install')
                ->setDescription('This command will create a complete LAMP env '
                        . 'with symfony installed')
                ->addOption(
                        'defaults', 'd', InputOption::VALUE_NONE, 'If set, the '
                        . 'task will install everything automatically'
                )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        //phar:///home/karlos/workspace/symvirins/symvirins.phar/src/SymVirIns/InstallerCommand.class.php
        $file = substr(__FILE__,0,-strlen("/src/SymVirIns/InstallerCommand.php"))."/";

        $dirPhar = $this->universalDirectory($file);
        $dir = getcwd().DIRECTORY_SEPARATOR;
        
        $twig = new Twig_Environment(new Twig_Loader_Filesystem(
                $dirPhar.$this->universalDirectory("src/SymVirIns/templates/")));
        
        $default = 0;
        if ($input->getOption('defaults')) {
            $default = 1;
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            throw new Exception("Not available for windows. Sorry");
        }

        //$this->setStyles($output);

        $output->writeln('<info>' . PHP_EOL . 'This command will install a virtual machine,' .
                'using virtual box, ansible with a complete lamp and symfony installed.'
                . '</info>'.PHP_EOL);
        $output->write("<info>Default:$default</info>".PHP_EOL);

        $this->checkMinimumRequisites($output);

        $output->write("<info>Minimum Requisited Passed. Congrats!</info>".PHP_EOL);

        //start interacting with user. Minimum questions
        $helper = $this->getHelper('question');
        
        $questionProject = new Question('Please enter the name of your project: ', 'ProjectTest');
        $project = $helper->ask($input, $output, $questionProject);
        $project = $this->slugify($project);
        
        $questionMemory =  new Question('Please enter the amount of memory for '
                . 'the virtual box in M. (Defaults: 2048) : ', '2048');
        $memory = $helper->ask($input, $output, $questionMemory);
        if(!is_integer($memory)) $memory = 2048;

        $questionCpu =  new Question('Please enter the number of cpus for '
            . 'the virtual box. (Defaults: 2) : ', '2');
        $cpu = $helper->ask($input, $output, $questionCpu);
        if(!is_integer($cpu)) $cpu = 2;
        
        $questionLocalUrl = new Question('Please introduce the name of the local'
                . 'new url. (Defaults project.local) : ', 'project.local');
        $localUrl = $helper->ask($input, $output, $questionLocalUrl);
        
        $questionRootDb = new Question('Please introduce your root DB password:'
                . '(default: root) : ', 'root');
        $passDb = $helper->ask($input, $output, $questionRootDb);
        
        /** @todo: ask for phpmyadmin and pass for root phpmyadmin **/
        $output->write("Phpmyadmin will be available at:http://".$localUrl."/phpmyadmin with user:root pass:root\n");

        $privateIp = "10.0.0.".rand(1,255);

        $output->write("In order to better perfomance for cache apt packages and composer ones, please install vagrant cache.\n".
            "Execute: 'vagrant plugin install vagrant-cachier'\n");
        $output->write("Remember to update your vhosts file with this line: '$privateIp  $localUrl' \n");
        $output->write("Execute : 'vagrant up' And your machine will start installing itself (the first time is slower) \n");

        $DateTimeZone = date_default_timezone_get();
        
        try {
            
            $vagrantfile = $twig->render("Vagrantfile.twig",
                    array("project"     => $project,
                          "memory"      => $memory,
                          "cpu"         => $cpu,
                          "privateIp"   => $privateIp)
                    );
            /** hosts part **/
            $hosts_defaults_main_yml = $twig->render("hosts_defaults_main.yml.twig",
                    array("project" => $project)
                    );
            $hosts_tasks_main_yml = $twig->render("hosts_tasks_main.yml.twig",
                    array("project" => $project)
                    );
            $hosts_templates_j2 =  $twig->render("sym2.j2.twig",
                    array("project" => $project ,
                          "localUrl" => $localUrl,)
                    );
            /** mysql part **/
            $mysql_defaults = $twig->render("mysql-defaults-main.yml.twig",
                    array("passDb" => $passDb));
            /** symfony initialize **/
            $symfony_app = $twig->render("copySymfony-templates-autoload.j2.twig",
                array("project" => $project));
            $symfony_defaults = $twig->render("symfony-tasks-main.yml.twig",
                    array("project" => $project));
            $symfony_copy = $twig->render("copySymfony-tasks-main.yml.twig",
                array("project" => $project));


            $phpIni = $twig->render("etc-php5-apache2-php-ini.j2.twig",
                    array("DateTimeZone" => $DateTimeZone));

            $phpIniCli = $twig->render("etc-php5-cli-php-ini.j2.twig",
                array("DateTimeZone" => $DateTimeZone));
            
            $finals = $twig->render("finals-tasks-main.yml.twig",
                array("project"     => $project,
                      "localUrl"    => $localUrl,
                      "privateIp"   => $privateIp  ));
            
            //create the ansible directory
            $this->copyRecursive($file."/src/SymVirIns/ansible", $dir."ansible");

            //write files
            file_put_contents($dir."Vagrantfile",$vagrantfile);
            file_put_contents($dir.
                    $this->universalDirectory("ansible/roles/hosts/defaults/main.yml"),
                    $hosts_defaults_main_yml);
            file_put_contents($dir.
                    $this->universalDirectory("ansible/roles/hosts/tasks/main.yml"),
                    $hosts_tasks_main_yml);
            file_put_contents($dir.
                    $this->universalDirectory("ansible/roles/hosts/templates/etc-apache2-sites-available-".$project.".j2"),
                    $hosts_templates_j2);
            file_put_contents($dir.
                    $this->universalDirectory("ansible/roles/mysql/defaults/main.yml"),
                    $mysql_defaults);
            file_put_contents($dir.
                    $this->universalDirectory("ansible/roles/symfony/tasks/main.yml"),
                    $symfony_defaults);
            file_put_contents($dir.
                $this->universalDirectory("ansible/roles/copySymfony/templates/autoload.j2"),
                $symfony_app);
            file_put_contents($dir.
                $this->universalDirectory("ansible/roles/copySymfony/tasks/main.yml"),
                $symfony_copy);
            file_put_contents($dir.
                $this->universalDirectory("ansible/roles/php5/templates/etc-php5-apache2-php-ini.j2"),
                $phpIni);
            file_put_contents($dir.
                $this->universalDirectory("ansible/roles/php5/templates/etc-php5-cli-php-ini.j2"),
                $phpIniCli);
            file_put_contents($dir.
                $this->universalDirectory("ansible/roles/finals/tasks/main.yml"),
                $finals);
            
            
        }catch(Exception $e) {
            echo $e->getCode();
            echo $e->getTraceAsString();
                   
        }
        
    }

    private function checkMinimumRequisites(OutputInterface $output) {

        if (!$this->checkVirtualBox()) {
            throw new Exception("Virtual Box is required. Please check" . PHP_EOL .
            "https://www.virtualbox.org/");
        }

        if (!$this->checkVagrant()) {
            throw new Exception("Vagrant is required. Please check" . PHP_EOL .
            "https://www.vagrantup.com/");
        }

        if (!$this->checkVagrantVBGuest()) {
            throw new Exception("Vagrant virtualbox guest is required."
            . " Please check" . PHP_EOL .
            "Execute: vagrant plugin install vagrant-vbguest");
        }

        if (!$this->checkAnsible()) {
            throw new Exception("Ansible is required. Please check" . PHP_EOL .
            "http://docs.ansible.com/intro_installation.html");
        }

        $distribution = false;
        if (strtoupper(substr(PHP_OS, 0, 5)) === 'LINUX') {
            $distribution = $this->getLinuxDistro();
            if (!$this->checkNfsSupport($distribution)) {
                throw new Exception("NFS support is required. Please check" . PHP_EOL .
                "Ubuntu / Debian: sudo apt-get install nfs-kernel-server nfs-common portmap");
            }
            $output->writeln("<info>Detected:" . $distribution . "<info>");
        }
        
        
        
    }

    /**
     * Checks if virtual box is available
     * returns @bool
     */
    private function checkVirtualBox() {
        $command = "virtualbox --help";
        $needle = "Oracle VM VirtualBox Manager";
        return $this->checkExit($command, $needle);
    }

    /**
     * Checks if vagrant is available
     * returns @bool
     */
    private function checkVagrantVBGuest() {
        $command = "vagrant vbguest --help";
        $needle = "Usage: vagrant vbguest [vm-name] ";
        return $this->checkExit($command, $needle);
    }

    /**
     * Checks if vagrant virtualbox is available
     * returns @bool
     */
    private function checkVagrant() {
        $command = "vagrant --help";
        $needle = "Usage: vagrant [options] <command> [<args>]";
        return $this->checkExit($command, $needle);
    }

    /**
     * Checks if ansible is available
     * returns @bool
     */
    private function checkAnsible() {
        $command = "ansible --help";
        $needle = "Usage: ansible <host-pattern> [options]";
        return $this->checkExit($command, $needle);
    }

    private function checkNfsSupport($distribution) {

        if ($distribution == "Ubuntu" || $distribution == "Debian") {
            return $this->checkExit("/etc/init.d/nfs-kernel-server status", "nfsd ");
        }
        if ($distribution == "Redhat" || $distribution == "CentOs" || $distribution == "Fedora") {
            return $this->checkExit("service nfs status", "rpc.svcgssd");
        }
    }

    /**
     * Check if the command return a needle in a line
     * @param type $command
     * @param type $needle
     * @param type $line
     * @return bool 
     */
    private function checkExit($command, $needle, $line = 0) {

        $command = exec($command, $arOutput);
        if (strpos($arOutput[$line], $needle) === false) {
            return false;
        }
        return true;
    }

    private function setStyles(OutputInterface $output) {

        $styleInfo = new OutputFormatterStyle('white','black');
        //$styleRed = new OutputFormatterStyle('red', 'white', array('bold'));

        $output->getFormatter()->setStyle('info', $styleInfo);
        //$output->getFormatter()->setStyle('problem', $styleRed);
    }

    private function getLinuxDistro() {
        //declare Linux distros(extensible list).
        $distros = array(
            "Arch" => "arch-release",
            "Debian" => "debian_version",
            "Fedora" => "fedora-release",
            "Ubuntu" => "lsb-release",
            'Redhat' => 'redhat-release',
            'CentOS' => 'centos-release');

        //Get everything from /etc directory.
        $etcList = scandir('/etc');

        //Loop through /etc results...
        $OSDistro = "";
        foreach ($etcList as $entry) {
            //Loop through list of distros..
            foreach ($distros as $distroReleaseFile) {
                //Match was found.
                if ($distroReleaseFile === $entry) {
                    //Find distros array key(i.e. Distro name) by value(i.e. distro release file)
                    $OSDistro = array_search($distroReleaseFile, $distros);

                    break 2; //Break inner and outer loop.
                }
            }
        }

        return $OSDistro;
    }
    
    /**
     * Sounds like ...mmm.. joobet?? :D
     * @param type $text
     * @return string
     */
    public function slugify($text) {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
    
    private function universalDirectory($str) {
        return str_replace("/",DIRECTORY_SEPARATOR,$str);
    }
    
    function copyRecursive($src,$dst) { 
        $dir = opendir($src); 
        @mkdir($dst); 
        while(false !== ( $file = readdir($dir)) ) { 
            if (( $file != '.' ) && ( $file != '..' )) { 
                if ( is_dir($src . '/' . $file) ) { 
                    $this->copyRecursive($src . '/' . $file,$dst . '/' . $file); 
                } 
                else { 
                    copy($src . '/' . $file,$dst . '/' . $file); 
                } 
            } 
        } 
        closedir($dir); 
    } 
}
