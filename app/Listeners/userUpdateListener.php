<?php

namespace App\Listeners;

use App\Events\userUpdatedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class userUpdatedListener
{
    protected $name;
    
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  userUpdateEvent  $event
     * @return void
     */
    public function handle(userUpdatedEvent $event)
    {
        $this->name = $event->username;

        return $event->msg . '<h4>Message from Event-Listener:</h4>New name: ' . $this->userame . '<br>Using Method: ' . $event->method;
    }
}
