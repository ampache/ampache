<?php

namespace App\Listeners;

use App\Events\CatalogCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class InformCatalogCreated
{
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
     * @param  CatalogCreated  $event
     * @return void
     */
    public function handle(CatalogCreated $event)
    {
        //
    }
}
