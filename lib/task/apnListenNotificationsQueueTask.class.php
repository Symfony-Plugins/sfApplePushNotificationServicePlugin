<?php
class apnListenApnQueueTask extends sfBaseTask {
    protected function configure() {
    	$this->namespace = 'apn';
        $this->name = 'listen-apn-queue';
        $this->briefDescription = 'Starts a listener that will take every notification message from the apn_queue and send them to the users through the Apple Push Notification Service';
        
        $this->addOptions(
            array(
                new sfCommandOption('debug_mode', null, sfCommandOption::PARAMETER_OPTIONAL, 'Enable debug mode [on|off]'),
                new sfCommandOption('token', null, sfCommandOption::PARAMETER_OPTIONAL, 'Debug apns token'),
                new sfCommandOption('message', null, sfCommandOption::PARAMETER_OPTIONAL, 'Debug message'),
            )
        );
    }
  	
    protected function execute($arguments = array(), $options = array()) {
    	$arguments['config'] = sfYaml::load(sfConfig::get('sf_root_dir')."/plugins/sfApplePushNotificationServicePlugin/config/config.yml");
    	if(isset($options['debug_mode']) && $options['debug_mode'] == 'on') {
            $apn_connection = new ApnConecction();
            $apn_connection->connect();
            $apn_connection->send($options['token'], $options['message']);
            $apn_connection->disconnect();
    	} else {            
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
    }
    
    protected function executePartial($arguments = array(), $options = array()) {
    	$conecction_class = ucfirst($arguments['config']['queue']['type']).'Connection';
        $queue_connection = new $conecction_class;
        $queue_connection->connect($arguments['config']['queue']['name']);
        if($queue_item = $queue_connection->readMessage()){
        	try {
            	$apn_connection = new ApnConecction();
            	$apn_connection->connect();
        	} catch(Exception $e) {
        		echo $e->getMessage();
        		$queue_connection->disconnect();
        		return false; 
        	}
        	
        	if(isset($queue_item['custom_content'])) {
        		$apn_connection->customSend($queue_item['token'], $queue_item['custom_content']);
        	} elseif(isset($queue_item['custom_message'])) {
        		$apn_connection->customSend($queue_item['token'], $queue_item['custom_message']);
            } elseif(isset($queue_item['message'])) {
            	$apn_connection->send($queue_item['token'], $queue_item['message']);
            } else {
            	echo 'Invalid message format: ';
            	print_r($queue_item);
            	echo "\n";
        		$queue_connection->disconnect();
        		$apn_connection->disconnect();
        		return false;
            }
            $apn_connection->disconnect();
        }
        $queue_connection->disconnect();
    }
}
