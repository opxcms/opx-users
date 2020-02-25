<?php

namespace Modules\Opx\Users;

use Illuminate\Support\Facades\Facade;
use Modules\Opx\Users\Models\User;

/**
 * @method  static boolean  check()
 * @method  static User user()
 * @method  static string  name()
 * @method  static string  get($key)
 * @method  static string  path($path = '')
 * @method  static string  trans($key, $parameters = [], $locale = null)
 * @method  static array|string|null  config($key = null)
 * @method  static mixed  view($view)
 */
class OpxUsers extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'opx_users';
    }
}
