<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\Registration;

class SendMailRegisteredUser
{
    /**
     * The Registration service instance
     *
     * @var Registration
     */
    protected $registration;
    
    /**
     * Create the event listener.
     *
     * @param Registration $registration
     */
    public function __construct(Registration $registration)
    {
        $this->registration = $registration;
    }
    /**
     * Handle the event.
     *
     * @param UserRegistered $event
     */
    public function handle(UserRegistered $event)
    {
        
    }
}