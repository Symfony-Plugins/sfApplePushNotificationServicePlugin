<?php
class ApnConecction {
	function __construct() {
        $config = sfYaml::load(sfConfig::get('sf_root_dir')."/plugins/sfApplePushNotificationServicePlugin/config/config.yml");
        $this->host = $config['apn_server']['host'];
        $this->port = $config['apn_server']['port'];
        $this->ssl_pem = sfConfig::get('sf_root_dir')."/plugins/sfApplePushNotificationServicePlugin/config/{$config['apn_server']['ssl_pem']}";
        $this->key = $config['apn_server']['key'];
	}
	
    function connect() {
    	$stream_context = stream_context_create();
        stream_context_set_option($stream_context, 'ssl', 'local_cert', $this->ssl_pem);
        stream_context_set_option($stream_context, 'ssl', 'passphrase', $this->key);
        $this->connection = stream_socket_client('ssl://'.$this->host.':'.$this->port, $error, $errorString, 60, STREAM_CLIENT_CONNECT, $stream_context);
        if($this->connection == false) {
            $this->disconnect();
        }
    }
    
    function disconnect() {
        fclose($this->connection);     
    }
    
    function send($token, $message) {
        $message = chr(0).chr(0).chr(32).pack('H*', $token).chr(0).chr(strlen($message)).$message;
        fwrite($this->connection, $message);
    }
} 
