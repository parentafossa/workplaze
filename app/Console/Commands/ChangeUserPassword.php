<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ChangeUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:change-password {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change the password of a user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get the user's email from the command argument
        $email = $this->argument('email');

        // Try to find the user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error('User not found');
            return 1;
        }

        // Prompt for the new password
        $newPassword = $this->secret('Enter the new password');

        // Validate the password length (optional)
        if (strlen($newPassword) < 8) {
            $this->error('The password must be at least 8 characters long.');
            return 1;
        }

        // Update the user's password
        $user->password = Hash::make($newPassword);
        $user->save();

        $this->info('Password updated successfully.');
        return 0;
    }
}
