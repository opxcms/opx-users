<?php

namespace Modules\Opx\Users\Exceptions;

use Exception;

class BaseUsersException extends Exception
{
    /** @var array */
    protected $errors;

    /** @var array */
    protected $credentials;

    /** @var int */
    protected $seconds;

    /**
     * InvalidCredentials constructor.
     *
     * @param string $message
     * @param array $errors
     * @param array $credentials
     * @param int $code
     * @param int|null $seconds
     *
     * @return  void
     */
    public function __construct(string $message, array $errors, array $credentials, int $code, ?int $seconds = null)
    {
        parent::__construct($message, $code, null);

        $this->errors = $errors;
        $this->seconds = $seconds;
        $this->credentials = $credentials;
    }

    /**
     * Get errors.
     *
     * @return  array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get credentials.
     *
     * @return  array
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }

    /**
     * Format exception to array.
     *
     * @return  array
     */
    public function toArray(): array
    {
        $response = [
            'message' => $this->message,
            'errors' => $this->errors,
        ];

        if ($this->credentials) {
            $response['credentials'] = $this->credentials;
        }

        if ($this->seconds) {
            $response['seconds'] = $this->seconds;
        }

        return $response;
    }
}