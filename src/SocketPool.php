<?php

namespace pekand\SocketServer;

class SocketPool {   
    private $actions = [];
    private $ticks = 0;
    
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
    
	public function listen($clients = null) {
        foreach ($clients as $client) {
            $client->connect();
        }
        
        
        while(true) {   
            foreach ($clients as $client) {
                $client->listenBody();
            }
        
            $actionsToDiscard = [];    
            foreach ($this->actions as $key => &$action) {
                if($action['executed']) {
                    continue;
                }
                
                if($action['executeat'] > $this->ticks) {
                    continue;
                }
                   
                if($action['repeat'] > 0) {
                    $action['executeat'] = $this->ticks + $action['repeat'];
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
            
            
            usleep(10000);
            $this->ticks += 10000;
        }
    }
}
