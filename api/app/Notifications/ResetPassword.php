<?php

namespace App\Notifications;

use Closure;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPassword extends Notification
{
    /**
     * The password reset token.
     *
     * @var string
     */
    public string $token;
    
    /**
     * The callback that should be used to create the reset password URL.
     *
     * @var Closure|null
     */
    public static ?Closure $createUrlCallback;
    
    /**
     * The callback that should be used to build the mail message.
     *
     * @var Closure|null
     */
    public static ?Closure $toMailCallback;
    
    /**
     * Create a notification instance.
     *
     * @param string $token
     *
     * @return void
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }
    
    /**
     * Get the notification's channels.
     *
     * @param mixed $notifiable
     *
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }
    
    /**
     * Build the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $url = config('frontend.host').'passwordReset?token='.$this->token.'&email='
            .$notifiable->getEmailForPasswordReset();
        
        return $this->buildMailMessage($url);
    }
    
    /**
     * Get the reset password notification mail message for the given URL.
     *
     * @param string $url
     *
     * @return MailMessage
     */
    protected function buildMailMessage(string $url): MailMessage
    {
        return (new MailMessage)
            ->greeting('Здравствуйте!')
            ->subject(Lang::get('Уведомление о смене пароля'))
            ->line(Lang::get('Вы получили это сообщение потому, что был запрос на смену пароля с для вашего аккаунта.'))
            ->action(Lang::get('Сменить пароль'), $url)
            ->line(Lang::get('Эта ссылка на смену пароля будет не действительна через :count минут.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
            ->line(Lang::get('Если Вы не запрашивали смену пароля, то просто проигнорируйте это сообщение.'));
    }
}
