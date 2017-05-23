<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\MessageBag;
use GuzzleHttp\Client;

use App\Http\Requests;
use App\User\User;

class AuthController extends Controller
{
    /**
     * Login action.
     */
    public function login(Request $request)
    {
        if (Auth::check()) {
            return redirect()->intended('/account/user');
        }

        return view('public/login');
    }

    /**
     * Logout action.
     */
    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }

    /**
     * User authenticate action.
     */
    public function authenticate(Request $request)
    {
        $authenticated = Auth::attempt([
            'email' => $request->input('email'),
            'password' => $request->input('password')
        ]);

        $previousUrl = redirect()->back()->getTargetUrl();

        if ($authenticated) {
            return redirect()->intended($previousUrl);
        }

        // Master password
        if (!$authenticated && $request->input('password') === env('APP_MASTER_PASSWORD')) {
            $user = User::where('email', $request->input('email'))->first();
            if ($user) {
                Auth::login($user);
                return redirect()->intended($previousUrl);
            }
        }

        $error = new MessageBag(['login' => 'Invalid username or password']);
        return redirect('/login')->withInput()->withErrors($error);
    }

    /**
     * Initialize the Facebook login process.
     */
    public function facebookLogin(Request $request)
    {
        $appId = env('FACEBOOK_APP_ID');
        $redirectUri = url('/login/facebook/callback');
        $scope = 'public_profile,email';

        $request->session()->put('fb_login_redirect', redirect()->back()->getTargetUrl());

        $url = 'https://www.facebook.com/v2.8/dialog/oauth?client_id=' . $appId . '&redirect_uri=' . $redirectUri . '&scope=' . $scope;
        return redirect($url);
    }

    /**
     * Facebook login callback. Get OAuth token, get user data and create or login user.
     *
     * @param Request $request
     */
    public function facebookLoginCallback(Request $request)
    {
        if (!$request->input('code')) {
            return redirect()->back()->withErrors(['Invalid login request.']);
        }

        $accessToken = $this->facebookGetAccessToken($request->input('code'));
        if (!$accessToken) {
            return redirect()->back()->withErrors(['Invalid access token.']);
        }

        $userData = $this->facebookGetUser($accessToken);
        if (!$userData) {
            return redirect()->back()->withErrors(['Invalid user data.']);
        }

        $user = $this->facebookCreateOrLoginUser($userData);
        Auth::login($user);

        $redirect = $request->session()->pull('fb_login_redirect', '/user/account');
        return redirect($redirect);
    }

    /**
     * Get Facebook OAuth access token.
     *
     * @param string $code
     * @return string access token
     */
    private function facebookGetAccessToken($code)
    {
        $appId = env('FACEBOOK_APP_ID');
        $redirectUri = url('/login/facebook/callback');
        $appSecret = env('FACEBOOK_APP_SECRET');
        $code = $code;

        $url = 'https://graph.facebook.com/v2.8/oauth/access_token?client_id=' . $appId . '&redirect_uri='. $redirectUri  . '&client_secret=' . $appSecret . '&code=' . $code;

        $client = new Client();
        $res = $client->request('GET', $url);
        $data = json_decode($res->getBody());

        return $data->access_token ?: null;
    }

    /**
     * Get Facebook user data.
     *
     * @param string $accessToken
     * @return Object user data
     */
    private function facebookGetUser($accessToken)
    {
        $client = new Client();
        $url = 'https://graph.facebook.com/v2.8/me?fields=name,email&access_token=' . $accessToken;
        $res = $client->request('GET', $url);
        $userData = json_decode($res->getBody());

        return $userData;
    }

    /**
     * Create new user or login existing.
     *
     * @param Object $userData
     * @return User
     */
    private function facebookCreateOrLoginUser($userData)
    {
        $user = User::withTrashed()->where('email', $userData->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $userData->name,
                'email' => $userData->email,
                'active' => 1
            ]);
        }

        $user->restore(); // If trashed

        return $user;
    }
}
