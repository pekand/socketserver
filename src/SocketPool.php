<?php

namespace pekand\SocketServer;

class SocketPool {   
    protected $options = [
        'waitInterval' => 10000, // event loop wait cicle (in ms)
    ];

    private $clients = [];
    private $actions = [];

    public function __construct($options = []) {
        $this->options = array_merge($this->options, $options);
    }
    
    public function addAction($params, $action) {
    	$action = [
    		'params' => $params,
            'executeat' => 0,
    		'executed' => false,
            'repeat' => false,
    		'callback' => $action,
    	];
        
        if(isset($params['delay'])) {
            $action['executeat'] = $params['delay'];
        }
        
        if(isset($params['repeat'])) {
            $action['repeat'] = $params['repeat'];
        }
        
        $this->actions[] = $action;
    }

    public function addClient($client) {
        $this->clients[] = $client;
    }
    
	public function listen($clients = null) {
        $this->clients = array_merge($this->clients, $clients);

        $this->startTime = microtime(true);

        foreach ($clients as $client) {
            $client->connect();
            if($client->isConnected()) {
                $client->callAfterClientConnected();
            }
        }

        while(true) {
            
            $clientsToRemove = [];
            foreach ($this->clients as $key => $client) {
                $running = $client->listenBody();
                if(!$running) {
                    $clientsToRemove[] = $key;
                }
            }

            foreach ($clientsToRemove as $key) {
                unset($this->clients[$key]);
            }
        
            $ticks = (microtime(true) - $this->startTime) * 1000000;

            $actionsToDiscard = [];    
            foreach ($this->actions as $key => &$action) {
                if($action['executed']) {
                    continue;
                }
                
                if($action['executeat'] > $ticks) {
                    continue;
                }
                   
                if($action['repeat'] > 0) {
                    $action['executeat'] = $ticks + $action['repeat'];
                } else {
                    $actionsToDiscard[] = $key; 
                    $action['executed'] = true;
                }
                    
                    
            	if (is_callable($action['callback'])) {
                    call_user_func_array($action['callback'], []);
                }                	
                
            }
            
            foreach ($actionsToDiscard as $key) {
                unset($this->actions[$key]);
            }

            if(count($this->clients) == 0 && count($this->actions) == 0){
                return;
            }
            
            usleep($this->options['waitInterval']);
        }
    }
}
