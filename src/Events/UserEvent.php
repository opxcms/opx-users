<?php

namespace Modules\Opx\Users\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Opx\Users\Models\User;

abstract class UserEvent
{
    use SerializesModels;

    public $user;

    /**
     * User event constructor.
     *
     * @param  User $user
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}