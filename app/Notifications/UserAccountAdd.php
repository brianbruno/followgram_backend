<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class UserAccountAdd extends Notification
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
    public function toSlack($notifiable)
    {
        $user = $this->user;
        return (new SlackMessage)
                    ->success()
                    ->content('Uma nova conta foi adicionada!')
                    ->image($user['image'])
                    ->attachment(function ($attachment) use ($user) {
                              $attachment->title("Informações da Conta")
                                         ->fields([
                                              'Usuário:' => $user['username'],
                                              'IG:' => $user['ig']
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
