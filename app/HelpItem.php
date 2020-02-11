<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class HelpItem extends Model
{
  
  use Notifiable;
  
  public function routeNotificationForSlack($notification)
  {
      return 'https://hooks.slack.com/services/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';
  }
  

}
