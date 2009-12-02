<?php
class apnListenApnQueueTask extends sfBaseTask {
    protected function configure() {
    	$this->namespace = 'apn';
        $this->name = 'listen-apn-queue';
        $this->briefDescription = 'Starts a listener that will take every notification message from the apn_queue and send them to the users through the Apple Push Notification Service';
    }
  	
    protected function execute($arguments = array(), $options = array()) {
    	$arguments['config'] = sfYaml::load(sfConfig::get('sf_root_dir')."/plugins/sfApplePushNotificationServicePlugin/config/config.yml");
    	while(true) {
            $pid = pcntl_fork();
            if($pid == -1){
                die('could not fork');
            } else if ($pid) {
                pcntl_wait($status);
                pcntl_waitpid($pid, $stat);
            } else {
                try{
                    $this->executePartial($arguments, $options);
                        posix_kill(getmypid(),9);
                } catch(Exception $e) {
                        posix_kill(getmypid(),9);
                }
            }
	    }
    }
    
    protected function executePartial($arguments = array(), $options = array()) {
    	$conecction_class = ucfirst($arguments['config']['queue']['type']).'Connection';
        $queue_connection = new $conecction_class;
        $queue_connection->connect($arguments['config']['queue']['name']);
        if($queue_item = $queue_connection->readMessage()){
            $apn_connection = new ApnConecction();
            $apn_connection->connect();
            $apn_connection->send($queue_item['token'], $queue_item['message']);
            $apn_connection->disconnect();
        }
        $queue_connection->disconnect();
    }
}