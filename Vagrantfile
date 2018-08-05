Vagrant.configure(2) do |config|
  
  config.vm.box = "bento/centos-7.4"
  config.vm.box_url = "https://app.vagrantup.com/bento/boxes/centos-7.4"

  server_configs = [
    {"hostname" => "webauthn", "ip" => "192.168.33.90", "port" => 2290, "memory_size" => "2048", "execute_script" => true},
  ]

  $script = "
sudo yum update -y --disablerepo=\* --enablerepo=base,updates ca-certificates
sudo yum install -y epel-release
sudo yum install -y epel-release
sudo yum install -y --enablerepo=epel sshpass git
sudo yum install -y --enablerepo=epel-testing ansible
cp vagrant/ssh_config /home/vagrant/.ssh/config
cp vagrant/insecure_private_key /home/vagrant/.ssh/id_rsa
cp vagrant/ansible.cfg /etc/ansible/ansible.cfg
chmod -R og-rwx /home/vagrant/.ssh
chown -R vagrant.vagrant /home/vagrant/.ssh
"

  server_configs.each do |server_config|
    config.vm.define "#{server_config['hostname']}" do |server|
      server.vm.hostname = server_config['hostname']
      server.vm.box = "bento/centos-7.4"
      server.vm.network :private_network, ip: server_config['ip']
      server.vm.network :forwarded_port, guest: 22, host: server_config['port'], id: "ssh"
      server.vm.provider "virtualbox" do |v|
        v.customize ["modifyvm", :id, "--memory", server_config['memory_size']]
        v.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
        v.customize ["setextradata", :id, "VBoxInternal/Devices/VMMDev/0/Config/GetHostTimeDisabled", 0]
      end
      #server.vm.synced_folder "./vue", "/home/vagrant/vue", type: "rsync", rsync__exclude: "node_modules", owner: "vagrant", group: "vagrant", :create => true, :mount_options => ["dmode=777", "fmode=777"]
      server.vm.synced_folder "./vue", "/home/vagrant/vue", rsync__exclude: "node_modules", owner: "vagrant", group: "vagrant", :create => true, :mount_options => ["dmode=777", "fmode=777"]
      server.vm.synced_folder "./yii", "/home/vagrant/yii", owner: "vagrant", group: "vagrant", :create => true, :mount_options => ["dmode=777", "fmode=777"]
      server.vm.synced_folder "./ansible-playbook", "/home/vagrant/ansible-playbook", owner: "vagrant", group: "vagrant", :create => true, :mount_options => ["dmode=777", "fmode=777"]
      server.vm.synced_folder "./vagrant", "/home/vagrant/vagrant", owner: "vagrant", group: "vagrant", :create => true, :mount_options => ["dmode=777", "fmode=777"]
      server.vm.provision :shell, inline: $script
      server.ssh.private_key_path = "./vagrant/insecure_private_key"
      server.ssh.insert_key = false
    end
  end
end
