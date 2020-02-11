<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class BotInformation extends Notification
{
    use Queueable;
  
    protected $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
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
        $data = $this->data;
        $url = "https://insta.brian.place/inativarbot/".$data['botId'];
        return (new SlackMessage)
                    ->to('#bot')
                    ->success()
                    ->attachment(function ($attachment) use ($data, $url) {
                              $attachment->title("Remover bot ".$data['username'], $url)
                                         ->fields([
                                              'IG:' => $data['username'],
                                              'Ação:' => $data['action'],
                                              'Concluídas:' => $data['made'],
                                              'Não concluídas:' => $data['notMade']                                           
                                          ]);
                          })
                    ->content('Total de quests feitas: '.$data['questsMade']);
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
