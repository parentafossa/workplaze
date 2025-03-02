<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\{TrelloBoard, TrelloList, TrelloCard, TrelloComment, TrelloAttachment, TrelloCardActivity};
use Carbon\Carbon;

class TrelloSync extends Command
{
    protected $signature = 'trello:sync {--board=} {--all}';
    protected $description = 'Synchronize Trello data with the Laravel application';

    // Configuration for multiple boards and their corresponding lists with wildcard support
    private $boardConfigurations = [
        'Main' => [
            'lists' => [
                'Inbox',
                'Application Review Mgr',
                'Application Review GM',
                'Application Review PD',
                'Application Approved',
                'Application Signed',
                'ERF',
                'Application Pending',
                'Pending Sonny',
                'CANCELLED',
                'Application Form 20*',         // Wildcard for any list starting with Application
            ],
            'statusMap' => [
                'Inbox' => 'Waiting Submit',
                'Application Review Mgr' => 'Waiting Manager',
                'Application Review GM' => 'Waiting GM',
                'Application Review PD' => 'Waiting PD',
                'Application Approved' => 'Approved',
                'Application Signed' => 'Issued',
                'ERF' => 'ERF Done',
                'Application Pending' => 'Pending',
                'Pending Sonny' => 'Pending',
                'CANCELLED' => 'Cancelled',
                'Application Form 20*' => 'Done',  // Default status for any Application* list not specifically defined
                //'Review*' => 'In Review Process'             // Default status for any Review* list not specifically defined
            ]
        ],
        'SBY Application Form' => [
            'lists' => [
                'Inbox',
                //'In Progress',
                //'Review',
                //'Done',
                //'Backlog*',             // Wildcard for any list starting with Backlog
                //'Test*'                 // Wildcard for any list starting with Test
            ],
            'statusMap' => [
                'Inbox' => 'Waiting Vendor Confirm',
                //'In Progress' => 'Working',
                //'Review' => 'Under Review',
                //'Done' => 'Completed',
                //'Backlog*' => 'Planning',       // Default status for any Backlog* list
                //'Test*' => 'Testing'            // Default status for any Test* list
            ]
        ],
        // Add more boards as needed
    ];

    // Transition status maps for each board with wildcard support
    private $transitionStatusMaps = [
        'Main' => [
            'Inbox' => [
                'Application Review Mgr' => 'Waiting Manager',
                'Application Review GM' => 'Waiting GM, w/o Manager Review',
                'Application Review PD' => 'Waiting PD, w/o GM & Manager Review',
                'Application*' => 'In Application Process',  // Wildcard transition
                'Review*' => 'In Review Process'             // Wildcard transition
            ],
            'Application Review Mgr' => [
                'Inbox' => 'Rejected by Manager, waiting Resubmit',
                'Application Review GM' => 'Waiting GM',
                'Application Review PD' => 'Waiting PD, w/o GM Review',
                'Application*' => 'Progressing in Application'  // Wildcard transition
            ],
            'Application Review GM' => [
                'Inbox' => 'Rejected by GM, waiting Resubmit',
                'Application Review Mgr' => 'Rejected by GM, waiting Manager',
                'Application Review PD' => 'Waiting PD',
                'Application*' => 'GM Reviewed'  // Wildcard transition
            ],
            'Application Review PD' => [
                'Inbox' => 'Rejected by PD, waiting Resubmit',
                'Application Review Mgr' => 'Rejected by PD, waiting Manager',
                'Application Review GM' => 'Rejected by PD, waiting GM',
                'Application Approved' => 'Approved',
                'Application Signed' => 'Issued',
                'Application*' => 'PD Processed'  // Wildcard transition
            ],
            'Application*' => [  // Wildcard from state
                'Inbox' => 'Returned to Inbox',
                //'Application Review*' => 'In Review Process' // Nested wildcard handling
            ]
        ],
        'Secondary' => [
            'To Do' => [
                'In Progress' => 'Started',
                'Review' => 'Skipped to Review',
                'Done' => 'Completed Directly'
            ],
            'In Progress' => [
                'To Do' => 'Returned to Planning',
                'Review' => 'Ready for Review',
                'Done' => 'Completed without Review'
            ],
            'Review' => [
                'To Do' => 'Rejected in Review',
                'In Progress' => 'Needs More Work',
                'Done' => 'Approved'
            ]
        ],
        // Add more transition maps as needed
    ];

    private $authorMap = [
        'yutakhs' => '324000002',
        'harisa17' => '322000007',
        'riaangelika' => '313000009',
        'parentafossa' => '313000007',
        'harisa29' => '322000007',
        'mathiaswenno' => '322000002',
        'aldaqhd' => '324000003',
        // Add more mappings as needed
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $client = new Client();
        $apiKey = config('services.trello.key');
        $token = config('services.trello.token');
        $memberId = 'me';

        $this->info("Starting synchronization... ");

        // Determine which boards to process
        $targetBoards = [];

        if ($this->option('all')) {
            // Process all configured boards
            $targetBoards = array_keys($this->boardConfigurations);
            $this->info("Processing all configured boards: " . implode(', ', $targetBoards));
        } elseif ($this->option('board')) {
            // Process specific board by name
            $boardName = $this->option('board');
            if (isset($this->boardConfigurations[$boardName])) {
                $targetBoards = [$boardName];
                $this->info("Processing specified board: $boardName");
            } else {
                $this->error("Board configuration not found for: $boardName");
                return 1;
            }
        } else {
            // Default to Main board for backward compatibility
            $targetBoards = ['Main'];
            $this->info("No board specified, defaulting to Main board");
        }

        // Fetch user's boards
        $this->info("Fetching boards...");
        $response = $client->get("https://api.trello.com/1/members/{$memberId}/boards", [
            'query' => [
                'key' => $apiKey,
                'token' => $token,
            ],
        ]);

        $boards = json_decode($response->getBody(), true);

        foreach ($boards as $boardData) {
            if (in_array($boardData['name'], $targetBoards)) {
                $boardName = $boardData['name'];
                $this->info("Processing board: " . $boardName);

                $board = TrelloBoard::updateOrCreate(
                    ['trello_id' => $boardData['id']],
                    ['name' => $boardName]
                );

                // Get configuration for current board
                $currentBoardConfig = $this->boardConfigurations[$boardName];
                $targetListNames = $currentBoardConfig['lists'];
                $statusMap = $currentBoardConfig['statusMap'];

                // Fetch lists for the current board
                $this->info("Fetching lists for board: " . $boardName);
                $response = $client->get("https://api.trello.com/1/boards/{$boardData['id']}/lists", [
                    'query' => [
                        'key' => $apiKey,
                        'token' => $token,
                    ],
                ]);
                $listsData = json_decode($response->getBody(), true);

                foreach ($listsData as $listData) {
                    if ($this->matchesListPattern($listData['name'], $targetListNames)) {
                        $this->info("Processing list: " . $listData['name']);
                        $list = TrelloList::updateOrCreate(
                            ['trello_id' => $listData['id']],
                            ['name' => $listData['name'], 'board_id' => $board->id]
                        );

                        // Fetch cards for the current list
                        $this->info("Fetching cards for list: " . $listData['name']);
                        $response = $client->get("https://api.trello.com/1/lists/{$listData['id']}/cards", [
                            'query' => [
                                'key' => $apiKey,
                                'token' => $token,
                            ],
                        ]);
                        $cardsData = json_decode($response->getBody(), true);

                        foreach ($cardsData as $cardIndex => $cardData) {
                            $status = $statusMap[$listData['name']] ?? null;
                            $defaultStatus = $this->getDefaultStatusForList($boardName, $listData['name']);

                            // Determine business area and urgent status from labels
                            $labels = array_column($cardData['labels'], 'name');
                            $businessArea = null;
                            $urgent = in_array('Urgent', $labels) ? 1 : 0;

                            foreach (['TD10', 'TD50', 'TD90', 'TD60'] as $label) {
                                if (in_array($label, $labels)) {
                                    $businessArea = $label;
                                    break;
                                }
                            }

                            // Check if card already exists
                            $card = TrelloCard::where('trello_id', $cardData['id'])->first();
                            $cardCreationDate = Carbon::parse($cardData['dateLastActivity'])->toDateTimeString();

                            if (!$card) {
                                // Initial sync: Fetch earliest action date from Trello
                                $initialActivityDate = $this->getEarliestActivityDate($client, $cardData['id'], $apiKey, $token);

                                $card = TrelloCard::create([
                                    'name' => $cardData['name'],
                                    'status' => $status ?? $defaultStatus,
                                    'trello_id' => $cardData['id'],
                                    'list_id' => $list->id,
                                    'business_area' => $businessArea,
                                    'urgent' => $urgent,
                                    'created_at' => $initialActivityDate
                                ]);

                                // Record initial activity
                                $description = $cardData['desc'] ?? '';
                                $emailSender = $this->extractEmailFromDescription($description);
                                $testid = $cardData['idMembers'][0] ?? 'No ID';
                                $this->info('Created ' . $card->name . ':' . $testid . '/' . $emailSender);

                                TrelloCardActivity::create([
                                    'card_id' => $card->id,
                                    'action' => 'created',
                                    'list_from' => null,
                                    'list_to' => $listData['name'],
                                    'status' => $status ?? $defaultStatus,
                                    'user' => $cardData['idMembers'][0] ?? $emailSender ?? 'Unknown',
                                    'created_at' => $initialActivityDate
                                ]);
                            } else {
                                // Routine sync
                                if ($card->list_id !== $list->id || $card->business_area !== $businessArea || $card->urgent !== $urgent) {
                                    $previousList = TrelloList::find($card->list_id);

                                    // Determine new status based on the list changes
                                    $newStatus = $this->determineNewStatus($boardName, $previousList->name, $listData['name']);
                                    if (empty($newStatus)) {
                                        $newStatus = $status ?? $defaultStatus;
                                    }

                                    $card->update([
                                        'status' => $newStatus,
                                        'list_id' => $list->id,
                                        'business_area' => $businessArea,
                                        'urgent' => $urgent
                                    ]);

                                    $description = $cardData['desc'] ?? '';
                                    $emailSender = $this->extractEmailFromDescription($description);

                                    // Record the activity
                                    TrelloCardActivity::updateOrCreate(
                                        [
                                            'card_id' => $card->id,
                                            'action' => 'moved',
                                            'list_from' => $previousList->name,
                                            'list_to' => $listData['name'],
                                            'status' => $newStatus,
                                            'user' => $cardData['idMembers'][0] ?? $emailSender ?? 'Unknown'
                                        ],
                                        [
                                            'created_at' => now()
                                        ]
                                    );
                                }
                            }

                            // Fetch activities (including list moves) for the current card
                            $response = $client->get("https://api.trello.com/1/cards/{$cardData['id']}/actions", [
                                'query' => [
                                    'filter' => 'updateCard,commentCard,copyCard,convertToCardFromCheckItem,moveCardFromBoard,moveCardToBoard',
                                    'key' => $apiKey,
                                    'token' => $token,
                                ],
                            ]);
                            $activitiesData = json_decode($response->getBody(), true);

                            $latestStatus = $status ?? $defaultStatus;
                            $lastStatusDate = Carbon::create(1970, 1, 1);
                            $finalStatus = '';

                            foreach ($activitiesData as $activityData) {
                                if ($activityData['type'] == 'commentCard') {
                                    // Process comment activities
                                    $this->processComment($activityData, $card);
                                } elseif ($activityData['type'] == 'updateCard' && isset($activityData['data']['listAfter'])) {
                                    // Process move activities and update latest status
                                    $latestStatus = $this->processMove($activityData, $card, $boardName);

                                    if (Carbon::parse($activityData['date']) > $lastStatusDate) {
                                        $finalStatus = $latestStatus;
                                        $lastStatusDate = Carbon::parse($activityData['date']);
                                    }
                                }
                            }

                            if (empty($finalStatus)) {
                                $finalStatus = $status ?? $defaultStatus;
                            }

                            // Update card status to the latest status based on activities
                            $card->update(['status' => $finalStatus]);

                            // Fetch attachments for the current card
                            $response = $client->get("https://api.trello.com/1/cards/{$cardData['id']}/attachments", [
                                'query' => [
                                    'key' => $apiKey,
                                    'token' => $token,
                                ],
                            ]);
                            $attachmentsData = json_decode($response->getBody(), true);

                            foreach ($attachmentsData as $attachmentData) {
                                $attachmentDate = Carbon::parse($attachmentData['date'])->toDateTimeString();

                                $attachment = TrelloAttachment::updateOrCreate(
                                    ['trello_id' => $attachmentData['id']],
                                    [
                                        'name' => $attachmentData['name'],
                                        'url' => $attachmentData['url'],
                                        'card_id' => $card->id
                                    ]
                                );
                                $attachment->created_at = $attachmentDate;
                                $attachment->save();
                            }
                        }
                    }
                }
            }
        }

        $this->info('Trello data synchronized successfully.');
    }

    protected function getDefaultStatusForList($boardName, $listName)
    {
        // Default status for specific lists that need special handling
        if ($boardName == 'Main' && $listName == 'Inbox') {
            return 'New';
        }

        // First check exact match in status map
        if (isset($this->boardConfigurations[$boardName]['statusMap'][$listName])) {
            return $this->boardConfigurations[$boardName]['statusMap'][$listName];
        }

        // If no exact match, check for wildcard matches
        foreach ($this->boardConfigurations[$boardName]['statusMap'] as $pattern => $status) {
            if ($this->isWildcardPattern($pattern) && $this->matchesWildcard($listName, $pattern)) {
                return $status;
            }
        }

        return 'Unknown';
    }

    /**
     * Check if a string is a wildcard pattern (ends with *)
     */
    protected function isWildcardPattern($pattern)
    {
        return str_ends_with($pattern, '*');
    }

    /**
     * Check if a string matches a wildcard pattern
     */
    protected function matchesWildcard($string, $pattern)
    {
        if (!$this->isWildcardPattern($pattern)) {
            return $string === $pattern;
        }

        $prefix = substr($pattern, 0, -1); // Remove the trailing *
        return str_starts_with($string, $prefix);
    }

    /**
     * Check if a list name matches any of the target list patterns
     */
    protected function matchesListPattern($listName, $targetListNames)
    {
        // Check direct matches first
        if (in_array($listName, $targetListNames)) {
            return true;
        }

        // Then check wildcard patterns
        foreach ($targetListNames as $pattern) {
            if ($this->isWildcardPattern($pattern) && $this->matchesWildcard($listName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    protected function getEarliestActivityDate($client, $cardId, $apiKey, $token)
    {
        $response = $client->get("https://api.trello.com/1/cards/{$cardId}/actions", [
            'query' => [
                'filter' => 'all',
                'key' => $apiKey,
                'token' => $token,
            ],
        ]);
        $activitiesData = json_decode($response->getBody(), true);

        if (!empty($activitiesData)) {
            $earliestActivity = collect($activitiesData)->sortBy('date')->first();
            return Carbon::parse($earliestActivity['date'])->toDateTimeString();
        }

        return now();
    }

    protected function processComment($commentData, $card)
    {
        $author = $this->getCommentAuthor($commentData);
        $empId = $this->getEmpId($author);
        $commentDate = Carbon::parse($commentData['date'])->toDateTimeString();

        if ($empId) {
            $comment = TrelloComment::updateOrCreate(
                ['trello_id' => $commentData['id']],
                [
                    'text' => $commentData['data']['text'],
                    'emp_id' => $empId,
                    'card_id' => $card->id
                ]
            );
            $comment->created_at = $commentDate;
            $comment->save();
        }
    }

    protected function getCommentAuthor($commentData)
    {
        // Check if the comment was created via email and extract the email address
        if (isset($commentData['data']['textData']['email'])) {
            return $commentData['data']['textData']['email'];
        }

        // Fallback to the Trello username
        return $commentData['memberCreator']['username'];
    }

    protected function processMove($activityData, $card, $boardName)
    {
        $listFrom = $activityData['data']['listBefore']['name'];
        $listTo = $activityData['data']['listAfter']['name'];
        $moveDate = Carbon::parse($activityData['date'])->toDateTimeString();
        $user = $activityData['memberCreator']['username'];
        $empId = $this->getEmpId($user);
        $newStatus = $this->determineNewStatus($boardName, $listFrom, $listTo);

        // If no specific transition status, use the default status for the destination list
        if (empty($newStatus)) {
            $newStatus = $this->getDefaultStatusForList($boardName, $listTo);
        }

        if ($newStatus) {
            $activity = TrelloCardActivity::updateOrCreate(
                [
                    'card_id' => $card->id,
                    'action' => 'moved',
                    'list_from' => $listFrom,
                    'list_to' => $listTo,
                    'status' => $newStatus,
                    'user' => $empId ?? $user
                ],
                [
                    'created_at' => $moveDate
                ]
            );
            $activity->created_at = $moveDate;
            $activity->save();

            return $newStatus;
        }
        return $card->status;
    }

    protected function determineNewStatus($boardName, $fromList, $toList)
    {
        // 1. First try exact match for both from and to lists
        if (isset($this->transitionStatusMaps[$boardName][$fromList][$toList])) {
            return $this->transitionStatusMaps[$boardName][$fromList][$toList];
        }

        // 2. Try exact fromList with wildcard toList match
        if (isset($this->transitionStatusMaps[$boardName][$fromList])) {
            foreach ($this->transitionStatusMaps[$boardName][$fromList] as $toPattern => $status) {
                if ($this->isWildcardPattern($toPattern) && $this->matchesWildcard($toList, $toPattern)) {
                    return $status;
                }
            }
        }

        // 3. Try wildcard fromList match with exact toList
        foreach ($this->transitionStatusMaps[$boardName] as $fromPattern => $transitions) {
            if ($this->isWildcardPattern($fromPattern) && $this->matchesWildcard($fromList, $fromPattern)) {
                if (isset($transitions[$toList])) {
                    return $transitions[$toList];
                }
            }
        }

        // 4. Try wildcard fromList with wildcard toList (double wildcard match)
        foreach ($this->transitionStatusMaps[$boardName] as $fromPattern => $transitions) {
            if ($this->isWildcardPattern($fromPattern) && $this->matchesWildcard($fromList, $fromPattern)) {
                foreach ($transitions as $toPattern => $status) {
                    if ($this->isWildcardPattern($toPattern) && $this->matchesWildcard($toList, $toPattern)) {
                        return $status;
                    }
                }
            }
        }

        return null;
    }

    protected function getEmpId($author)
    {
        return $this->authorMap[$author] ?? null;
    }

    protected function extractEmailFromDescription($description)
    {
        // Pattern to find any standard email address
        $pattern = '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/i';

        if (preg_match($pattern, $description, $matches)) {
            return $matches[0]; // Return the first matched email
        }

        // Try a more generic "From:" pattern often used in email metadata
        if (preg_match('/From:\s*([^<\r\n]*<)?([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})/', $description, $matches)) {
            return $matches[2]; // Return the captured email address
        }

        return null;
    }
}