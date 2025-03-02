<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Traits\HasApproval;

class DebugModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug all models to check if they use the HasApproval trait';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $modelsPath = app_path('Models');
        $models = [];

        // Iterate through all files in the models directory
        collect(File::files($modelsPath))
            ->map(fn ($file) => 'App\\Models\\' . $file->getBasename('.php')) // Get the fully qualified class name
            ->filter(fn ($class) => class_exists($class)) // Ensure the class exists
            ->each(function ($class) use (&$models) {
                $traits = $this->getAllTraits($class); // Recursively get all traits
                if ($traits->contains(HasApproval::class)) {
                    $models[] = $class; // Add to the list if HasApproval is found
                } else {
                    $this->info("Model does not use HasApproval: {$class}"); // Output to console
                }
            });

        // Output the models that use HasApproval
        $this->info('Models with HasApproval:');
        foreach ($models as $model) {
            $this->line($model);
        }

        return Command::SUCCESS;
    }

    /**
     * Recursively get all traits used by a class.
     */
    private function getAllTraits($class)
    {
        $reflectedClass = new \ReflectionClass($class);
        $traits = collect($reflectedClass->getTraits())->keys();

        foreach ($reflectedClass->getTraits() as $trait) {
            $traits = $traits->merge($this->getAllTraits($trait->getName()));
        }

        return $traits->unique();
    }
}
