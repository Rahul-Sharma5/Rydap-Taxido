<?php

namespace Modules\Taxido\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

class RiderResetPassword extends ResetPassword
{
    protected function resetUrl($notifiable)
    {
        return route('front.cab.password.reset', [
            'token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
