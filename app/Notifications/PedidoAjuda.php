<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class PedidoAjuda extends Notification
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

    public function toSlack($notifiable)
    {
        $data = $this->data;
        return (new SlackMessage)
                    ->success()
                    ->content('Um novo pedido de ajuda!')
                    ->attachment(function ($attachment) use ($data) {
                              $attachment->title("Informações do Pedido")
                                         ->fields([
                                              'Usuário:' => $data['nome'],
                                              'Pontos:'  => $data['points'],
                                              'Seguidores Ganhados:' => $data['follows'],
                                              'Curtidas Ganhadas:' => $data['likes'],
                                              'Pontos Pendentes:' => $data['pending_points'],
                                          ]);
                          })
                    ->content('Pedido de ajuda: '.$data['texto'].' -> Contas do Instagram: '.$data['contas'].'ajude esse usuário em alguma dessas contas.');
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
