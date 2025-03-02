<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TrelloClearData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trello:clear-data';
    protected $description = 'Clear all data from Trello models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $this->info('Clearing Trello data...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('trello_card_activities')->truncate();
        $this->info('Cleared: Trello Card Activities');

        DB::table('trello_attachments')->truncate();
        $this->info('Cleared: Trello Attachments');

        DB::table('trello_comments')->truncate();
        $this->info('Cleared: Trello Comments');

        DB::table('trello_cards')->truncate();
        $this->info('Cleared: Trello Cards');

        DB::table('trello_lists')->truncate();
        $this->info('Cleared: Trello Lists');

        DB::table('trello_boards')->truncate();
        $this->info('Cleared: Trello Boards');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('All Trello data has been cleared.');
    }
}
