<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    public function store(Request $request)
    {
        // walidacja nie zwraca komunikatu w przypadku bledow, a zamiast tego serwowana jest strona /
    	$this->validate($request, [
    		'name' => ['required', 'min:3', 'max:255'],
    		'email'=> ['required', 'email', Rule::unique('users', 'email')],
    		'password'=> ['required', 'min:6'],
    	]);

    	// brak komunikatu jak user juz istnieje
    	return User::forceCreate([
    		'api_token' => str_random(60), // brak jwt
			'name' => $request->name,
			'email' => $request->email,
			'password' => bcrypt($request->password), // plus za bcrypt
    	]);
    }

    // plus za patch, raczej powinien byc put
    public function update(Request $request, int $id)
    {
        // komunikaty nie w formacie json
    	if ($id !== $request->user()->id) {
    		return abort(403, 'Unathorized');
    	}

    	// brak komunikatow walidacji
    	$this->validate($request, [
    		'name' => ['required_without:password', 'min:3', 'max:255'],
    		'password'=> ['required_without:name', 'min:6'],
    	]);

    	// plus za spr
    	$user = User::findOrFail($id);

    	// slaby design, powinna to robic walidacja
    	if ($request->name) {
    		$user->name = $request->name;
    	}

    	if ($request->password) {
    		$user->password = bcrypt($request->password);
    	}

    	// update nie dziala
    	$user->save();
    }

    // plus za metode /me
    public function me(Request $request)
    {
    	return $request->user();
    }

    // duzy plus za cache'owanie, aczkolwiek ttl=60s to sredni pomysl, ale rozumiem ideę
    public function index()
    {
        // plus za oczywistosc, jaka jest nie zwracanie hasla ;)
        // brak paginacji
    	return Cache::remember('users', 1, function () {
	    	return User::all([
	    		'id',
	    		'name',
	    		'email',
	    		'created_at',
	        	'updated_at'
	    	]);
	    });
    }

    // komunikaty bledow nie sa w formacie json :(
    public function show(int $id)
    {
    	return User::findOrFail($id, [
    		'id',
    		'name',
    		'email',
    		'created_at',
        	'updated_at'
    	]);
    }

    public function destroy(Request $request, int $id)
    {
        // plus za spr
    	if ($id === $request->user()->id) {
    		return abort(403, 'You can\'t destroy yourself');
    	}

    	User::findOrFail($id)->delete();
    }
}
