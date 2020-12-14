<?php

namespace pekand\SocketServer;

class SocketClient {   
    
    private $socket = null;
       
    protected $options = [
        'ip'=> '127.0.0.1', 
        'port' => 8080,
        'waitInterval' => 10000, // event loop wait cicle (in ms)
    ];
    
    private $listeners = [];
       
    public function __construct($options = []) {
        $this->options = array_merge($this->options, $options);
    }

    public function connect() {
        $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if($this->socket === false){
            $errorcode = socket_last_error($this->socket);
            $errormsg = trim(socket_strerror($errorcode));
            if (isset($this->afterClientErrorEvent) && is_callable($this->afterClientErrorEvent)) {
                call_user_func_array($this->afterClientErrorEvent, [$this, $errorcode, $errormsg]);
            }
            return null;
        }

        if(false === @socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            $errorcode = socket_last_error($this->socket);
            $errormsg = trim(socket_strerror($errorcode));
            if (isset($this->afterClientErrorEvent) && is_callable($this->afterClientErrorEvent)) {
                call_user_func_array($this->afterClientErrorEvent, [$this, $errorcode, $errormsg]);
            }
            return null;
        }

        if(false === @socket_connect($this->socket, $this->options['ip'], $this->options['port'])) {
            $this->socket = null;      
            
            if($this->socket != null) {
                $errorcode = socket_last_error($this->socket);
                $errormsg = trim(socket_strerror($errorcode));
                if (isset($this->afterClientErrorEvent) && is_callable($this->afterClientErrorEvent)) {
                    call_user_func_array($this->afterClientErrorEvent, [$this, $errorcode, $errormsg]);
                }
            }

            return null;
        }
        
        if (isset($this->sendHeader) && is_callable($this->sendHeader)) {
            call_user_func_array($this->sendHeader, [$this]);
        }
        
        if (isset($this->afterReceiveHeaderEvent) && is_callable($this->afterReceiveHeaderEvent)) {
            if (false === ($headerFromServer = @socket_read($this->socket, 2048, MSG_WAITALL))) {
                $errorcode = socket_last_error($this->socket);
                $errormsg = trim(socket_strerror($errorcode));
                if (isset($this->afterClientErrorEvent) && is_callable($this->afterClientErrorEvent)) {
                    call_user_func_array($this->afterClientErrorEvent, [$this, $errorcode, $errormsg]);
                }
            }

            $acceptHeader = call_user_func_array($this->afterReceiveHeaderEvent, [$this, $headerFromServer]);

            if(!$acceptHeader) {
                $this->close();
            }
        }

        if($this->socket != null) {
            if (false === @socket_set_nonblock($this->socket)) {
                $errorcode = socket_last_error($this->socket);
                $errormsg = trim(socket_strerror($errorcode));
                if (isset($this->afterClientErrorEvent) && is_callable($this->afterClientErrorEvent)) {
                    call_user_func_array($this->afterClientErrorEvent, [$this, $errorcode, $errormsg]);
                }
            }
        }

        return $this;
    }

    public function close() {
        if ($this->socket == null) {
            return $this;
        }
        
        socket_close($this->socket);
        
        $this->socket = null;

        return $this;
    }

    public function isConnected() {
        if($this->socket == null) {
            return false;
        }

        return true;
    }

    public function afterClientError($afterClientErrorEvent = null) {
        $this->afterClientErrorEvent = $afterClientErrorEvent;
        return $this;
    }

    public function afterClientConnected($afterClientConnectedEvent = null) {
        $this->afterClientConnectedEvent = $afterClientConnectedEvent;
        return $this;
    }

    public function callAfterClientConnected() {

        if (isset($this->afterClientConnectedEvent) && is_callable($this->afterClientConnectedEvent)) {
            call_user_func_array($this->afterClientConnectedEvent, [$this]);
        }

        return $this;
    }
    
    public function addSendHeader($sendHeader) {
         $this->sendHeader = $sendHeader;
         return $this;
    }

    public function afterReceiveHeader($afterReceiveHeaderEvent) {
         $this->afterReceiveHeaderEvent = $afterReceiveHeaderEvent;
         return $this;
    }
    
    public function sendData($data) {     
        if(!isset($this->socket)){
            return;    
        }
        
        if(false === @socket_write($this->socket, $data, strlen($data))) {
            $errorcode = socket_last_error($this->socket);
            $errormsg = trim(socket_strerror($errorcode));
            if (isset($this->afterClientErrorEvent) && is_callable($this->afterClientErrorEvent)) {
                call_user_func_array($this->afterClientErrorEvent, [$this, $errorcode, $errormsg]);
            }
        }
    }
    
    public function addListener($listener) {
         $this->listeners[] = $listener;
         return $this;
    }
   
    public function listenBody() {
        if (!$this->socket){
            return false;
        }
        
        $data = "";
        while ($buf = @socket_read($this->socket, 1024)) {  
            if ($buf === false) {
                $errorcode = socket_last_error($this->socket);
                $errormsg = trim(socket_strerror($errorcode));
                if (isset($this->afterClientErrorEvent) && is_callable($this->afterClientErrorEvent)) {
                    call_user_func_array($this->afterClientErrorEvent, [$this, $errorcode, $errormsg]);
                }
                break;
            }                 
            $data .= $buf;
        }
        
        if(strlen($data)>0) {
            foreach ($this->listeners as $listener) {
                if (is_callable($listener)) {
                    call_user_func_array($listener, [$this, $data]);
                }
            }   
        }

        return true;
    }
     
    public function listen() {

        $this->connect();

        if (!$this->socket){
            return;
        }

        if($this->isConnected()) {
            $this->callAfterClientConnected();
        }

        while(true) {    
            $this->listenBody();

            if ($this->socket == null) {
                return;
            }
 
            usleep($this->options['waitInterval']);
        }
    }
}
