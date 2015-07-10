<?php
namespace Akamai\Open\EdgeGrid\Client;

class Timestamp
{
    public function __toString()
    {
        return (new \DateTime(null, new \DateTimeZone('UTC')))->format('Ymd\TH:i:sO');
    }
}
