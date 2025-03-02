<?php

namespace App\Http\Livewire\Auth;

use Filament\Http\Livewire\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class Login extends BaseLogin
{
    public function authenticate(): mixed
    {
        $data = $this->form->getState();

        //dd($data);
        
        $fieldType = filter_var($data['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'emp_id';
        $credentials = [
            $fieldType => $data['email'],
            'password' => $data['password'],
        ];

        if (! Auth::attempt($credentials, $data['remember'] ?? false)) {
            throw ValidationException::withMessages([
                'email' => __('filament::login.messages.failed'),
            ]);
        }

        session()->regenerate();

        return redirect()->intended(config('filament.home_url'));
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('email')
                ->label(__('filament::login.fields.email.label'))
                ->placeholder('Email or Employee ID')
                ->required()
                ->autofocus(),
            TextInput::make('password')
                ->label(__('filament::login.fields.password.label'))
                ->password()
                ->required(),
            Checkbox::make('remember')
                ->label(__('filament::login.fields.remember.label')),
        ];
    }
}