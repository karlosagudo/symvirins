# SYMVIRINS

SYMfony VIRtual INStaller.
This one phar file its a console command that ask you for the steps in order to configure a complete virtual machine using: virtual box / vagrant / ansible.

  - Virtual Box
  - Vagrant
  - Ansible

Do not worry right now to download and install this system. This command will guide you throught the process of install the packages it needs, and after all the process, you will have a vendor/symvirins folder with all the ansible playbook in differents files, ready to setup.

### INSTALL
Download symvirins as a Phar:

```sh
$ curl -LSs https://github.com/karlosagudo/symvirins/raw/master/symvirins.phar
```

From there, you may place it anywhere that will make it easier for you to access (such as /usr/local/bin) and chmod it to 755.
You can even rename it to just symvirins to avoid having to type the .phar extension every time.

```sh
$ symvirins --version
```

And if you want to create the virtual box goes to a directory you want and type:
```sh
$ symvirins install
```

This will ask you some questions or suggest to install some basics packages you will need.
After this, you have a folder with everything ready to start your development.
Just type :
```sh
$ vagrant up
```
And the installation of the virtual box will start. (will take longer the first time).

###EXTRAS
  - htop, vim, curl, sendmal, unzip, git, composer, default-jre, language-pack-en
  - php5, php5-xmlrpc, php-soap, php5-gd, imagemagick, php5-imagick, php5-cli, php-pear, php5-curl, php5-sqlite, php5-intl, php5-mcrypt, php5-dev
  - Apache 2 (Configured) / php log in: /var/log/apache2/php-error_{{ project }}.log
  - Mysql(configured), phpmyadmin(configured)
  - Composer, phpunit, symfony, memcached, redis
Also the virtual box is configured to use vagrant-cachier that generates a machine cache for composer and apt packages.
In order to work install: vagrant plugin install vagrant-cachier


###TODO
   - Autoupdate
   - better way to move symfony files
   - autogenerate git.ignore
   - Apply this: http://by-examples.net/2014/12/09/symfony2-on-vagrant.html
   - Create github page
   - GenerateDocker with ansible/ vagrant / virtualBox

###THANKS
* Based on: [DevBoxAnsible] by jandro-es
* Using [box], and a lot of more stuff


[box]:http://box-project.org/
[DevBoxAnsible]:https://github.com/jandro-es/DevBoxAnsible