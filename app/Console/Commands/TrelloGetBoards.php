<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;

class TrelloGetBoards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trello:get-boards';
    protected $description = 'Get Trello board IDs';
    /**
     * The console command description.
     *
     * @var string
     */
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $apiKey = config('services.trello.key');
        $token = config('services.trello.token');

        $client = new Client();

        $response = $client->get('https://api.trello.com/1/members/me/boards', [
            'query' => [
                'key' => $apiKey,
                'token' => $token,
            ],
        ]);

        $boards = json_decode($response->getBody(), true);

        foreach ($boards as $board) {
            $this->info("Board Name: " . $board['name']);
            $this->info("Board ID: " . $board['id']);
        }        //
    }
}
