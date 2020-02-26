<?php

namespace Modules\Opx\Users\Events;

class Lockout
{
    /** @var array */
    public $credentials;

    /** @var string */
    public $ip;

    /**
     * User event constructor.
     *
     * @param array $credentials
     * @param string $ip
     */
    public function __construct(array $credentials, string $ip)
    {
        $this->credentials = $credentials;
        $this->ip = $ip;
    }
}