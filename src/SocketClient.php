<?php

namespace pekand\SocketServer;

class SocketClient {   
    
    private $socket = null;
       
    protected $options = [
        'ip'=> '127.0.0.1', 
        'port' => 8080,
    ];
    
    private $listeners = [];
       
    public function __construct($options = []) {
        
        $this->options = array_merge($this->options, $options);
    }

    public function connect() {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

        if(false == @socket_connect($this->socket, $this->options['ip'], $this->options['port'])) {
            $this->socket = null;      
            
            if($this->socket != null) {
                echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($this->socket)) . "\n";
            }

             return;
             
        }

        if (isset($this->sendHeader) && is_callable($this->sendHeader)) {
            call_user_func_array($this->sendHeader, [$this]);
        }
        
        if (false === ($headerFromServer = @socket_read($this->socket, 2048, MSG_WAITALL))) {
            echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($this->socket)) . "\n";
        }

        if (isset($this->receiveHeader) && is_callable($this->receiveHeader)) {
            call_user_func_array($this->receiveHeader, [$headerFromServer]);
        }

        
        if(false === @socket_set_nonblock($this->socket)) {
            echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($this->socket)) . "\n";
        }

        return $this;
    }
    
    public function addSendHeader($sendHeader) {
         $this->sendHeader = $sendHeader;
         return $this;
    }
    
    public function addReceiveHeader($receiveHeader) {
         $this->receiveHeader = $receiveHeader;
         return $this;
    }
    
    public function sendData($data) {     
        if(!isset($this->socket)){
            return;    
        }
        
        socket_write($this->socket, $data, strlen($data));
    }
    
    public function close() {
        if ($this->socket == null) {
            return $this;
        }
        
        socket_close($this->socket);
        
        return $this;
    }
    

    public function addListener($listener) {
         $this->listeners[] = $listener;
         return $this;
    }
   
    public function listenBody() {
        if (!$this->socket){
            return;
        }
        
        $data = "";
        while ($buf = @socket_read($this->socket, 1024)) {  
            if ($buf === false) {
                echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($this->socket)) . "\n";
                break;
            }                 
            $data .= $buf;
        }
        
        if(strlen($data)>0) {
            foreach ($this->listeners as $listener) {
                if (is_callable($listener)) {
                    call_user_func_array($listener, [$data]);
                }
            }   
        }
    }
     
    public function listen() {
  
        $this->connect();
        
        if (!$this->socket){
            return;
        }
          
        while(true) {    
            $this->listenBody();
            usleep(50000);
        }    
             
    }
}
