<?php
namespace Akamai\Open\EdgeGrid\Client;

class Nonce
{
    protected $function;
    
    public function __construct()
    {
        $this->function = 'openssl_random_pseudo_bytes';

        // Use PHP 7's random_bytes()
        if (function_exists('random_bytes')) {
            $this->function = 'random_bytes';
        }
    }
    
    public function __toString()
    {
        // because ($this->function)() won't work til PHP 7 :(
        $function = $this->function;
        return bin2hex($function(16));
    }
}
