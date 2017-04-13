# -*- mode: ruby -*-
# vi: set ft=ruby :

#GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY 'password';

$script = <<SCRIPT
sudo apt-get install php-gettext;
phpmemory_limit=256M && sudo sed -i 's/memory_limit = .*/memory_limit = '${phpmemory_limit}'/' /etc/php5/apache2/php.ini;
max_execution_time=300 && sudo sed -i 's/max_execution_time = .*/max_execution_time = '${max_execution_time}'/' /etc/php5/apache2/php.ini;
sudo sed -i 's#DocumentRoot /var/www/public#DocumentRoot /var/www#' /etc/apache2/sites-available/000-default.conf;
sudo service apache2 restart;
sudo sed -i "s/.*bind-address.*/bind-address = 0.0.0.0/" /etc/mysql/my.cnf;
sudo service mysql restart;

SCRIPT

Vagrant.configure("2") do |config|

    config.vm.box = "scotch/box"
    config.vm.network "private_network", ip: "192.168.33.10"
    config.vm.hostname = "scotchbox"
    config.vm.synced_folder ".", "/var/www", :mount_options => ["dmode=777", "fmode=666"]
    
    # Optional NFS. Make sure to remove other synced_folder line too
    #config.vm.synced_folder ".", "/var/www", :nfs => { :mount_options => ["dmode=777","fmode=666"] }
    
    config.vm.provision "shell", inline: $script
end
