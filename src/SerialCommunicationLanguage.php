<?php declare(strict_types=1);

namespace BrunoNatali\Communication;

use BrunoNatali\Tools\Queue;

Class SerialCommunicationLanguage
{

    $myId; // Store requester id 

    $messageCounter = 0; // Used to handle messagens and it's answer when implemented

    function __construct(int $myId)
    {
        $this->myId = $myId;
    }

    /**
    *   $params could be:
    *   (int) from, forcing other source than was previosly created  
    *   (callable) function that would be called like this:
    *       function (SerialMessageCompiledStructure $compiled)
    *
    *   @return null or (normal) -> (string) $msg compiled 
    */
    Public function send(string $data, int $to, ...$params = null)
    {
        $from = $this->myId;
        $whenAnswer = null;
        foreach ($params as $value) {
            if (\is_callable($value))
                $whenAnswer = $value;
            else if (\is_int($value))
                $from = $value;
        }

        $compiled = new SerialMessageCompiledStructure($from, $to, ++$this->messageCounter, $data)
        
        if ($whenAnswer !== null) {
            // This will help handle counter from requester app & use Queue 4 ex.
            $whenAnswer($compiled); 
            return;
        }
        return $compiled->msg;
    }

    Public function receive()
    {
        // Now receive must not be used
    }

}