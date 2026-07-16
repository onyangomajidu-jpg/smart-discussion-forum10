<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Contracts\IAuthentication;
use App\Mail\WelcomeMail;
use App\Services\SessionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    protected IAuthentication $authService;
    protected SessionManager $sessionManager;

    public function __construct(IAuthentication $authService, SessionManager $sessionManager)
    {
        $this->authService = $authService;
        $this->sessionManager = $sessionManager;
    }

    /**
     * Show registration form
     */
    public function showRegistrationForm()
    {
        $groups = \App\Models\Group::orderBy('name')->get();
        return view('auth.register', compact('groups'));
    }

    /**
     * Handle registration request
     */
    public function register(Request $request)
    {
        // Validate input
        $validator = $this->validator($request->all());
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Register user using AuthenticationService
            $user = $this->authService->register(
                $request->all(),
                $request->boolean('accept_rules')
            );

            // Attach student to chosen group
            if ($user->role === 'member' && $request->filled('group_id')) {
                $user->groups()->attach($request->group_id, ['role' => 'member']);
            }

            // Send welcome email (non-blocking)
            try {
                Mail::to($user->email)->send(new WelcomeMail($user));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Welcome email failed: ' . $e->getMessage());
            }

            $this->sessionManager->flash('success', 'Registration successful! Please login to continue.');

            return redirect()->route('login');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Validation rules for registration
     */
    protected function validator(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()],
            'role' => ['required', 'in:member,lecturer,admin'],
            'accept_rules' => ['required', 'accepted'],
        ];

        // Role-specific validation
        if (isset($data['role'])) {
            switch ($data['role']) {
                case 'member':
                    $rules['student_id'] = ['nullable', 'string', 'unique:members'];
                    $rules['programme'] = ['nullable', 'string'];
                    $rules['year_of_study'] = ['nullable', 'integer', 'min:1', 'max:5'];
                    $rules['group_id'] = ['nullable', 'exists:groups,id'];
                    break;
                    
                case 'lecturer':
                    $rules['staff_id'] = ['nullable', 'string', 'unique:lecturers'];
                    $rules['department'] = ['nullable', 'string'];
                    $rules['specialisation'] = ['nullable', 'string'];
                    break;
            }
        }

        return Validator::make($data, $rules, [
            'accept_rules.accepted' => 'You must accept the forum rules to register.',
        ]);
    }
}
