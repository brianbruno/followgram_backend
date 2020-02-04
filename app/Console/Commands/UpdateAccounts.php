<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\UserInstagram;
use Phpfastcache\Helper\Psr16Adapter;
use Illuminate\Support\Facades\Cache;

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
        $accounts = UserInstagram::where('confirmed', 1)->get();
      
        $this->line('Conectando com o Instagram');
        $instagram = \InstagramScraper\Instagram::withCredentials('marketingfollowgram', 'marketing2020', new Psr16Adapter('Files'));
        $instagram->login();
        sleep(2);
        $this->info('Conectado.');
      
        foreach($accounts as $account) {
          
            $minutes = 15;
          
            $accountInsta = Cache::remember('getAccountUsername-'.$account->username, $minutes*60, function () use ($account, $instagram) {
                $this->line('Atualizando cache de: '.$account->username);
                $retorno = $instagram->getAccount($account->username);
                sleep(7);
                return $retorno;
            });
            $account->profile_pic_url = $accountInsta->getProfilePicUrl();
            $account->external_url = $accountInsta->getExternalUrl();
            $account->full_name = $accountInsta->getFullName();
            $account->biography = $accountInsta->getBiography(); 
            $account->is_private = $accountInsta->isPrivate();
            $account->is_verified = $accountInsta->isVerified(); 
            $account->save();
            
        }     
      
        $this->info('Atualizações de contas finalizada.');
    }
}
