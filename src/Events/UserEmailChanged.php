<?php

namespace Modules\Opx\Users\Events;

use Modules\Opx\Users\Models\User;

class UserEmailChanged extends UserEvent
{
    /** @var string */
    public $oldEmail;

    /** @var string */
    public $newEmail;

    /**
     * User event constructor.
     *
     * @param User $user
     * @param string $oldEmail
     * @param string $newEmail
     *
     * @return void
     */
    public function __construct(User $user, string $oldEmail, string $newEmail)
    {
        parent::__construct($user);
        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
    }
}