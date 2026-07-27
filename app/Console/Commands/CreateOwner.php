<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;


class CreateOwner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:owner';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This Command Create Owner User';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if(!Role::where('name','owner')->first()){
            Artisan::call('db:seed',['--class' => 'RoleSeeder']);
        }
        $name = $this->ask('What is the Owner Name');
        $email = $this->ask('What is the Owner Email');
        $phone = $this->ask('What is the Owner Phone');
        $password = $this->secret('What is the Owner Password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password
        ],[
            'name' => 'required|string|max:50',
            'email' => 'required|string|email|unique:users,email',
            'phone' => 'required|string|phone:AUTO|unique:users,phone',
            'password' => ['required','string',Password::min(6)->mixedCase()]
        ]);

        if($validator->fails()){
            foreach($validator->errors()->all() as $error){
                $this->error($error);
            }
            return Command::FAILURE;
        }
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'phone' => $phone,
            'favicon' => 'user_favicon.jpg'
        ]);
        $ownerRole = Role::where('name','owner')->first();
        $user->roles()->attach($ownerRole->id);
        $this->info($name.' Owner Created Successfully');
    }
}
