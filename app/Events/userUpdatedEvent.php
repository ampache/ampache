<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class userUpdatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $username;
    public $method;
    public $msg;
    
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($request, $msg){
        $this->username = $request->username;
        $this->method = $request->method();
        $this->msg = $msg;
    }
    
    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}