<?php declare(strict_types=1);

use BrunoNatali\Tools\Queue;

Class SerialCommunicationLanguage
{

    $myId; // Store requester id 
    $queue;

    $messageCounter = 0; // Used to handle messagens and it's answer when implemented

    function __construct(int $myId)
    {
        $this->myId = $myId;
        $this->queue = new Queue();
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
            // This will help handle counter from app & use Queue 4 ex.
            $whenAnswer($compiled); 
            return;
        }
        return $compiled->msg;
    }

    Public function receive(string $data, int &$to = null, int &$from = null): string
    {
        $decomposed = \explode(';', $data, 4);

        if (count($decomposed) === 4) {
            $from = \intval($decomposed[0]);
            $to = \intval($decomposed[1]);
            // $counter = \intval($decomposed[2]); not implemented yet
            return \strlen($decomposed[3]) !== 0 ? $decomposed[3] : null;
        } else {
            return null;
        }
    }

}