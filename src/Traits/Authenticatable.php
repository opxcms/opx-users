<?php

namespace Modules\Opx\Users\Traits;

trait Authenticatable
{
    /**
     * The column name of the "remember me" token.
     *
     * @var string
     */
    protected $rememberTokenName = 'remember_token';

    /**
     * The column name of the "password me" token.
     *
     * @var string
     */
    protected $passwordName = 'password';

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return  string
     */
    public function getAuthIdentifierName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return  mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getAttribute($this->getAuthIdentifierName());
    }

    /**
     * Get the name of the password for the user.
     *
     * @return  string
     */
    public function getPasswordName(): string
    {
        return $this->passwordName;
    }

    /**
     * Get the password hash for the user.
     *
     * @return  string
     */
    public function getAuthPassword(): string
    {
        return $this->getAttribute($this->getPasswordName());
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return  string|null
     */
    public function getRememberToken(): ?string
    {
        return (string)$this->getAttribute($this->getRememberTokenName());
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param string $value
     *
     * @return  void
     */
    public function setRememberToken($value): void
    {
        $this->setAttribute($this->getRememberTokenName(), $value);
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName(): ?string
    {
        return $this->rememberTokenName;
    }
}