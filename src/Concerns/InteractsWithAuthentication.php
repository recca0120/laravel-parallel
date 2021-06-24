<?php

namespace Recca0120\AsyncTesting\Concerns;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Queue\SerializesAndRestoresModelIdentifiers;

trait InteractsWithAuthentication
{
    use SerializesAndRestoresModelIdentifiers;

    /**
     * @var string|null
     */
    protected $guard;
    /**
     * @var string|null
     */
    protected $user;

    /**
     * Set the currently logged in user for the application.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param string|null $guard
     * @return $this
     */
    public function actingAs(UserContract $user, $guard = null)
    {
        return $this->be($user, $guard);
    }

    /**
     * Set the currently logged in user for the application.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param string|null $guard
     * @return $this
     */
    public function be(UserContract $user, $guard = null)
    {
        if (isset($user->wasRecentlyCreated) && $user->wasRecentlyCreated) {
            $user->wasRecentlyCreated = false;
        }

        $this->user = base64_encode(serialize($this->getSerializedPropertyValue($user)));
        $this->guard = $guard;

        return $this;
    }
}
