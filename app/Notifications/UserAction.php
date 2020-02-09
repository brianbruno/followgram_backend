<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class UserAction extends Notification
{
    use Queueable;
  
    protected $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['slack'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }
    
    public function toSlack($notifiable)
    {
        $user = $this->user;
        return (new SlackMessage)
                    ->success()
                    ->content('Uma nova ação foi feita!')
                    ->attachment(function ($attachment) use ($user) {
                              $attachment->title("Informações da Ação")
                                         ->fields([
                                              'Usuário:' => $user['username'],
                                              'IG:' => $user['ig'],
                                              'Ação:' => $user['action'],
                                          ]);
                          });
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
