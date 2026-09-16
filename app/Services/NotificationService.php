<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Events\NotificationSentEvent;

class NotificationService
{
    
    public function send(
        User $user,
        string $type,
        string $titleKey,
        string $bodyKey,
        ?array $parameters = null,
        ?array $metadata = null
    ): Notification {
        $notification = Notification::create([
            'user_id'    => $user->id,
            'type'       => $type,
            'title_key'  => $titleKey,
            'body_key'   => $bodyKey,
            'parameters' => $parameters,
            'is_read'    => false,
            'metadata'   => $metadata,
        ]);

        event(new NotificationSentEvent($notification));

        return $notification;
    }
}