<?php

/* namespace App\Filament\Survey\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Http\Responses\Auth\LoginResponse as DefaultLoginResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\SurveyUser;

class SurveyLogin extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $request = request();

        $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $user = SurveyUser::where('email', $request->input('identifier'))
                    ->orWhere('employee_id', $request->input('identifier'))
                    ->first();

        if (!$user) {
            $this->addError('identifier', __('The provided identifier does not match our records.'));
            return null;
        }

        Auth::guard('survey')->login($user);

        return app(DefaultLoginResponse::class);
    }

    protected function getFormSchema(): array
    {
        return [
            \Filament\Forms\Components\TextInput::make('identifier')
                ->label(__('Email or Employee ID'))
                ->required()
                ->autocomplete('off'),
        ];
    }
} */
