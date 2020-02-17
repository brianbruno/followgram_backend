<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UserInstagram;
use Phpfastcache\Helper\Psr16Adapter;
use Illuminate\Support\Facades\Cache;
use App\Notifications\ErrorLog;

class UpdateAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza informações das contas';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $accounts = UserInstagram::where('confirmed', 1)->latest()->get();
      
        $this->line('Conectando com o Instagram');
        $instagram = \InstagramScraper\Instagram::withCredentials('fabricioneves34', 'marketing2020', new Psr16Adapter('Files'));
        $instagram->login();
        sleep(2);
        $this->info('Conectado.');
      
        foreach($accounts as $account) {
          
            try {
              
                $minutes = 15;

                $accountInsta = Cache::remember('getAccountUsername-'.$account->username, $minutes*60, function () use ($account, $instagram) {
                    try {
                        $this->line('Atualizando cache de: '.$account->username);
                        $retorno = $instagram->getAccount($account->username);
                        sleep(7);
                        return $retorno;
                    } catch (\Exception $e) {
                        $this->line('Conta não encontrada.');
                        return null;
                    }
                });

                if (!empty($accountInsta)) {
                    $account->profile_pic_url = $accountInsta->getProfilePicUrl();
                    $account->external_url = $accountInsta->getExternalUrl();
                    $account->full_name = $accountInsta->getFullName();
                    $account->biography = $accountInsta->getBiography(); 
                    $account->is_private = $accountInsta->isPrivate();
                    $account->is_verified = $accountInsta->isVerified(); 
                    $account->save();
                }
            
            } catch (\Exception $e) {
                $data = array(
                    'class'   => 'UpdateAccounts',
                    'line'    => $e->getLine(),
                    'message' => $e->getMessage()
                );

                $notification = UserInstagram::where('user_id', 1)->first();
                $notification->notify(new ErrorLog($data));
            }
            
        }     
      
        $this->info('Atualizações de contas finalizada.');
    }
}
