<?php
namespace App\Events;

use App\Models\Media;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class UserRegistered extends Event
{
    use SerializesModels;
    
    /**
     * The user who carries the action.
     * 
     * @var User
     */
    public $user;
    
    /**
     * Create a new event instance.
     *
     * @param User  $user
     *
     * @return void
     */
    public function __construct(User $user = null)
    {
        $this->user = $user ?: auth()->user();
    }
    
    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
