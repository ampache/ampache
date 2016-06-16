<?php
namespace App\Events;

use App\Models\Media;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class SongLikeToggled extends Event
{
    use SerializesModels;
    
    /**
     * The target media for the action.
     * 
     * @var Media
     */
    public $media;
    
    /**
     * The user who carries the action.
     * 
     * @var User
     */
    public $user;
    
    /**
     * Create a new event instance.
     *
     * @param Media $media
     * @param User  $user
     *
     * @return void
     */
    public function __construct(Media $media, User $user = null)
    {
        $this->media = $media;
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
