<?php

namespace App\Http\Controllers;
use DB;

use App\Helpers\ScriptSigner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Server;
use App\Models\GameToken;
use App\Http\Controllers\AssetController;
use App\Models\User;
use App\Models\BodyColors;
use App\Models\OwnedItems;
use App\Models\Item;
use App\Models\Ban;
use App\Http\Cdn\Thumbnail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;


use Illuminate\Support\Facades\Http;
use Zip;
class ClientController extends Controller
{
    const ADJECTIVES = ["MLG", "Dope", "Mrs.", "Gay", "Mr.", "Squandalous", "Big", "Obtuse", "Dumb", "Large", "Quintavious", "Deaf", "Pondering", "Punished", "Zapped", "Hilarious", "Big", "Frowning", "Fishy", "Premium", "Pristine", "Tubular", "Geeky", "Tweezy", "Condescending", "Repenting", "Quindarious", "Unnerving", "Reverberating", "Squashnabbing", "Guilty", "Nationwide", "Scrumptious", "Absurd", "Atrocious", "Flabbergasted", "Red", "Green", "Yellow", "Blue", "Purple", "Orange", "White", "Black", "Helen"];
    const NOUNS = ["Buford", "Pie", "Furry", "Duncan", "Peter scully", "Omar", "Cheese", "Blox", "Connor", "Pacifist", "Gooch", "Ears", "Kyle", "Hearing", "Taxes", "Gas", "Clownfish", "Snake", "Cake", "Checkers", "McDonalds", "Chess", "Bowler", "Andy", "Jason", "Yeat", "Gerald", "Pimp", "Emmanuel", "Scrotum", "Boosie", "Tupac", "Feet", "Shakur", "Tupacalypse", "Topaz yates", "Boomer", "Tank", "Jockey", "Hunter", "Spitter", "Witch", "Smoker", "Bill", "Zoey", "Louis", "Francis", "Rochelle", "Nick", "Ellis", "Coach", "Eminem", "Cole", "Keller", "Geico", "Nation", "Scallywag", "Scoundrel", "Squat", "Hercule", "Poppy", "Rose", "Straw", "Kendrick", "Tesla", "Baljeet", "Demarcus", "Rye", "Low"];

public function getRandomUnusedPort($server_owner_id)
{
    $all_ports = range(50000, 60000);
    $used_ports = DB::table('ports')->select('port')->get()->pluck('port')->toArray();
    $port = '';
    $unused_ports = array_diff($all_ports, $used_ports);

    if (empty($unused_ports)) {
        throw new \Exception("All ports are used.");
    }

    do {
        $rand_key = array_rand($unused_ports);
        $port = $unused_ports[$rand_key];
    } while (in_array($port, $used_ports));

    DB::table('ports')->updateOrInsert(
        ['id' => $server_owner_id],
        ['port' => $port]
    );

    return $port;
}
public function shownew(Request $request)
{
    if (!$request->user()) { abort(404); }
    return view('2020.landing');
}
public function joinWaitlist(Request $request)
{
    if (!$request->user()) { abort(404); }
    // Check if the user is already on the waitlist
    $existingUser = DB::table('waitlist')->where('username', $request->user()->username)->first();

    // If the user is already on the waitlist, redirect them to the confirmation page
    if ($existingUser) {
        return view('2020.nope');
    }

    // Add the user to the waitlist database
    DB::table('waitlist')->insert([
        'username' => $request->user()->username ,
        'created_at' => now(),
        'updated_at' => now()
    ]);

    // Show the waitlist confirmation page
    return view('2020.inwl');
}

public function client(Request $request, $client)
    {
        $clients = config('app.clients');

        if (!array_key_exists($client, $clients)) {
            abort(404);
        }

        $path = 'clients/' . $client . '.zip';
        $hash = 'none';
        if (Storage::disk('public')->exists($path)) {
            $hash = hash('sha256', Storage::disk('public')->get($path));
        }

        $data = [
            'version' => $clients[$client],
            'url' => route('client.downloadversion', $client),
            'sha256' => $hash
        ];

        $response = Response::make(json_encode($data), 200, ["Content-Type" => "application/json"]);
        return $response;
    }

    public function download(Request $request, $client)
    {
        $clients = config('app.clients');

        if (!array_key_exists($client, $clients)) {
            return 'badversion';
        }

        $path = 'clients/' . $client . '.zip';
        if (!Storage::disk('public')->exists($path)) {
            return 'nofiles';
        }

        return Storage::disk('public')->download($path);
    }
public function download2016(Request $request)
{
    $path = 'clients2016/2016studio.zip';
    // Read the contents of the zip file
    $zipContents = Storage::disk('public')->get($path);

    // XOR the client's name to the start of the zip file

    // Return the watermarked zip file as a downloadable response
    $response = response($zipContents, 200, [
        'Content-Type' => 'application/zip',
        'Content-Disposition' => 'attachment; filename="kapish-2016-studio.zip"',
    ]);

    return $response;
}

public function download2016c(Request $request)
{
    $usernames = [
        'Iaying',
        'alosh',
        'joss',
        'gyro',
        'Shedletsky',
        'radiocentric',
        'MugMan',
        'm1neep',
        'dew',
        'LocalPlayer',
        'sudoapt',
        'ROBLOX',
        'j4x',
        'idiot',
        'kz1',
        'Joel',
        'Yapz',
        'zaza',
        'cowboy',
        'Xevi',
        'dew',
        'carlos',
        'jttttsound',
        'simul',
        'skulyire',
        'xell',
        'fun',
        'para',
        'ouya',
        'quato',
        'AdonisAdminOnTop',
        'bomber',
        'Diel',
        'Facbook',
        'foid',
        'row',
        'punch',
	'stan'
    ];

    $path = 'clients2016/2016client-canary.zip';
    if (!in_array($request->user()->username, $usernames)) {
        abort(403);
    }

    // Read the contents of the zip file
    $zipContents = Storage::disk('public')->get($path);

    // XOR the client's name to the start of the zip file
    $clientName = str_replace("=", "f", base64_encode($request->user()->username));
    $watermarkedContents = $zipContents . $clientName;

    // Return the watermarked zip file as a downloadable response
    $response = response($watermarkedContents, 200, [
        'Content-Type' => 'application/zip',
        'Content-Disposition' => 'attachment; filename="kapish-2016-for-' . $request->user()->username . '-watermarked-do-not-leak-UNSTABLE.zip"',
    ]);

    return $response;
}

    public function generate(Request $request, $uuid)
    {
        $server = Server::where('uuid', $uuid)->firstOrFail();

        if (!$request->user()) {
            if (!$server->allow_guests) {
                abort(404);
            }
        }

        if ($request->user()) {
            if ($request->user()->banned) {
                abort(403);
            }
        }

        if ($server->version != "2016" && !Cache::has('server_online' . $server->id, 0)) {
            abort(402);
        }

        if (Cache::get('server_online') == 1 && sizeof(Cache::get('server_online' . $server->id, 0)) >= $server->maxplayers) {
            abort(403);
        }
        $tokenString = Str::random(20);
        $token = new GameToken;
        if ($request->user()) {
            $token->user_id = $request->user()->id;
        }
        $token->server_id = $server->id;
        $token->token = $tokenString;
        $token->save();

        return $tokenString . "?session=". Cookie::get("kapish_session");
    }


    public function verifyuser(Request $request, $requestToken)
    {
        $token = GameToken::where('token', $requestToken)->first();

        if (is_null($token)) {
            return "invalid";
        }

        // already validated, so invalidate if an already validated token is used
        if ($token->validated) {
            return "invalid";
        }

        // deny users who do not have verified discord
        if ($token->user) {
            if (config('app.discord_verification_required') && !$token->user->discord_id) {
                return "invalid";
            }

            if ($token->user->banned) {
                return "invalid";
            }

            if ($token->user == $token->server->user) {
                $token->validated = true;
                $token->save();
                return "valid";
            }
        }

        if ($token) {
            if ($token->user) {
                if ($request->username == $token->user->username) {
                    $token->validated = true;
                    $token->save();

                    $token->server->update(['visits' => $token->server->visits + 1]);
                    return "valid";
                }
            } else {
                $token->validated = true;
                $token->save();

                $token->server->update(['visits' => $token->server->visits + 1]);
                return "valid";
            }
        }

        return "invalid";
    }

    public function host(Request $request, $secret)
    {
        $server = Server::where('secret', $secret)->first();

        if (!$server) {
            return 'print("Invalid server.")';
        }

        $script = file_get_contents(resource_path('lua/host.lua'));
        $script = strtr(
            $script,
            [
                '%SERVERPORT%' => $server->port,
                '%SERVERSECRET%' => $server->secret,
                '%SERVERUUID%' => $server->uuid,
                '%SITE%' => request()->getHttpHost(),
                '%PLACEID%' => $server->id,
                '%MAXPLAYERS%' => $server->maxplayers
            ]
        );

        return $script;
    }
    public function verifyuser_fe(Request $request, $requestToken) // poc for verifying the user (for filteringenabled)
    {
        $token = GameToken::where('token', $requestToken)->first();

        if (is_null($token)) {
            return "invalid";
        }

        // already validated, so invalidate if an already validated token is used
        if ($token->validated) {
            return "invalid";
        }

        // deny users who do not have verified discord
        if ($token->user) {
            if (config('app.discord_verification_required') && !$token->user->discord_id) {
                return "invalid";
            }

            if ($token->user->banned) {
                return "invalid";
            }

            if ($token->user == $token->server->user) {
                $token->validated = true;
                $token->save();
                return $token->user->username;
            }
        }

        if ($token) {
            if ($token->user) {
                    $token->validated = true;
                    $token->save();
                    $token->server->update(['visits' => $token->server->visits + 1]);
                    return $token->user->username;
                }      
        }
        return "invalid";
    }
    public function admin(Request $request, $secret)
    {
        $server = Server::where('secret', $secret)->first();

        if (!$server) {
            return 'print("Invalid server.")';
        }

        $admins = User::where('admin', true)->get();

        return view('client.admin')->with(['server' => $server, 'admins' => $admins]);
    }

    public function body_colors(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $colors = BodyColors::where('user_id', $user->id)->firstOrFail();
        $document = simplexml_load_file(resource_path('xml/BodyColors.xml'));

        $document->xpath('//int[@name="HeadColor"]')[0][0] = $colors->head_color;
        $document->xpath('//int[@name="TorsoColor"]')[0][0] = $colors->torso_color;
        $document->xpath('//int[@name="LeftArmColor"]')[0][0] = $colors->left_arm_color;
        $document->xpath('//int[@name="LeftLegColor"]')[0][0] = $colors->left_leg_color;
        $document->xpath('//int[@name="RightArmColor"]')[0][0] = $colors->right_arm_color;
        $document->xpath('//int[@name="RightLegColor"]')[0][0] = $colors->right_leg_color;

        return response($document->asXML())->header('Content-Type', 'text/xml');
    }

    public function body_colors_asset(Request $request)
    {
        $user = User::find($request->userId);
        $colors = BodyColors::where('user_id', $user->id)->firstOrFail();
        $document = simplexml_load_file(resource_path('xml/BodyColors.xml'));

        $document->xpath('//int[@name="HeadColor"]')[0][0] = $colors->head_color;
        $document->xpath('//int[@name="TorsoColor"]')[0][0] = $colors->torso_color;
        $document->xpath('//int[@name="LeftArmColor"]')[0][0] = $colors->left_arm_color;
        $document->xpath('//int[@name="LeftLegColor"]')[0][0] = $colors->left_leg_color;
        $document->xpath('//int[@name="RightArmColor"]')[0][0] = $colors->right_arm_color;
        $document->xpath('//int[@name="RightLegColor"]')[0][0] = $colors->right_leg_color;

        return response($document->asXML())->header('Content-Type', 'text/xml');
    }


    public function charapp_asset(Request $request)
    {
        // DO NOT CHANGE THIS TO USE url()!!! IT WILL BREAK 2010 DUE TO HTTPS!!!

        $user = User::find($request->userId);

        if(!$user)
        {
            return 'nil';
        };

        $appearance = array();
        $appearance[] = 'http://' . request()->getHost() . '/Asset/BodyColors.ashx?userId=' . $user->id . '&tick=' . time();

        $wearingItems = OwnedItems::where(['user_id' => $user->id, 'wearing' => true])->get();

        foreach ($wearingItems as $wearingItem) {
            $item = Item::find($wearingItem->item_id);

            if ($item && $item->approved == 1) {
                if ($item->isXmlAsset()) {
                    $appearance[] = 'http://' . request()->getHost() . '/Asset?id=' . $item->id;
                } else {
                    $appearance[] = 'http://' . request()->getHost() . '/Asset?id=' . $item->id. "&xmlon=1";
                }
            }
        }

        return join(';', $appearance);
    }

    public function charapp(Request $request, $id)
    {
        // DO NOT CHANGE THIS TO USE url()!!! IT WILL BREAK 2010 DUE TO HTTPS!!!

        $user = User::find($id);

        if(!$user)
        {
            return 'nil';
        };

        $appearance = array();
        $appearance[] = 'http://' . request()->getHost() . '/Asset/BodyColors.ashx?userId=' . $user->id . '&tick=' . time();

        $wearingItems = OwnedItems::where(['user_id' => $user->id, 'wearing' => true])->get();

        foreach ($wearingItems as $wearingItem) {
            $item = Item::find($wearingItem->item_id);

            if ($item->approved == 1) {
                if ($item->isXmlAsset()) {
                    $appearance[] = 'http://' . request()->getHost() . '/asset?id=' . $item->id;
                } else {
                    $appearance[] = 'http://' . request()->getHost() . '/xmlasset?id=' . $item->id;
                }
            }
        }

        return join(';', $appearance);
    }

    public function ping(Request $request, $id)
    {
        $server = Server::where('id', $id)->first();
        
        if (!$server) {
            return "err";
        }
        $whitelistedIPs = env('WHITELISTED_IP'); 
        $clientIP = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER['REMOTE_ADDR'];;
    
        if (!in_array($clientIP, explode(',', $whitelistedIPs))) {
            return "err";
        }
        $users = [];

        if ($request->players) {
            $userIds = explode(",", $request->players);

            if (sizeof($userIds) > $server->maxplayers + 10) {
                return "err";
            }

            foreach ($userIds as $userId) {
                if (!is_numeric($userId)) {
                    return "err";
                }

                $user = User::where('id', '=', (int)$userId)->first();

                if (!$user) {
                    $users[] = "guest";
                } else {
                    $users[] = $user;
                }
            }
        }

        Cache::put('server_online' . $server->id, $users, Carbon::now()->addMinutes(1));
        Cache::put('server_online' . $server->id, $users, Carbon::now()->addMinutes(1));
        $server->touch();

        return 'OK';
    }


    public function getuserthumbnail(Request $request)
    {
        if (!$request->has('userId'))
        {
            return abort(404);
        }

        User::findOrFail($request->userId);
        $thumbnail = Thumbnail::resolve('user', $request->userId);

        $url = match ($thumbnail['status'])
        {
            -1, 1, 3 => Thumbnail::static_image('blank.png'),
            2 => Thumbnail::static_image('disapproved.png'),
            0 => $thumbnail['result']['headshot']
        };

        return redirect($url);
    }

    public function getitemthumbnail(Request $request)
    {
        if (!$request->has('itemId') && !$request->has('assetid'))
        {
            return abort(404);
        }

        $id = $request->has('itemId') ? $request->itemId : $request->assetid;
        Item::findOrFail($id);
        $thumbnail = Thumbnail::resolve('item', $id);

        $url = match ($thumbnail['status'])
        {
            -1, 1, 3 => Thumbnail::static_image('blank.png'),
            2 => Thumbnail::static_image('disapproved.png'),
            0 => $thumbnail['result']['url']
        };

        return redirect($url);
    }
    public function Sign($data = "", $ct = false, $assetId = 0) {
            global $_STORAGE;
            $assetId = intval($assetId) ?? 0;
            if ($ct == false) {
                $data = "\n" . $data;
            }
            if($assetId != 0) {
                $data = '\n--rbxassetid%' . $assetId . '%\n' . $data;
            }

            $signature; // why do i need to pre-initialize the variable bruh ....
            openssl_sign($data, $signature, file_get_contents(resource_path('keys/PrivateKey.pem')));
            if ($ct == true) {
                return base64_encode($signature);
            }
            return "--rbxsig%" . base64_encode($signature) . "%" . $data;
        }
    public function negotiate(Request $request)
    {
        abort(403);
    }
    public function playsolo(Request $request)
    {
        $script = file_get_contents(resource_path('lua/playsolo.lua'));

        $playername = "Player";
        $playerid = "1";

        if ($request->user()) {
            $playername = $request->user()->username;
            $playerid = $request->user()->id;
        }

        $script = strtr(
            $script,
            [
                '%PLAYERNAME%' => $playername,
                '%PLAYERID%' => $playerid,
                '%PLAYERCHARAPP%' => route('users.getcharacter', $playerid),
                '%SITE%' => request()->getHttpHost()
            ]
        );

        $signedscript = $this->Sign($script);

        $response = Response::make($signedscript);
        $response->header('Content-Type', 'text/plain');

        $response->header('Content-Encoding', 'en');
        return $response;
    }
    public function newplaysolo(Request $request)
    {
        $script = file_get_contents(resource_path('lua/playsolo.lua'));

        $playerid = "0";

        if ($request->user()) {
            $playerid = $request->user()->id;
        }

        $script = strtr(
            $script,
            [
                '%PLAYERID%' => $playerid,
                '%PLAYERCHARAPP%' => route('users.getcharacter', $playerid),
                '%SITE%' => request()->getHttpHost()
            ]
        );

        $signedscript = Sign($script);

        $response = Response::make($signedscript);
        $response->header('Content-Type', 'text/plain');
        $response->header('Content-Encoding', 'en');
        return $response;
    }

    public function studioscript(Request $request)
    {
        $script = file_get_contents(resource_path('lua/studio.lua'));
        $signedscript = ScriptSigner::instance()->sign($script, 'old');

        $response = Response::make($signedscript);
        $response->header('Content-Type', 'text/plain');
        return $response;
    }

    public function newstudioscript(Request $request)
    {
        $script = file_get_contents(resource_path('lua/newstudio.lua'));
        $signedscript = ScriptSigner::instance()->sign($script, 'new');

        $response = Response::make($signedscript);
        $response->header('Content-Type', 'text/plain');
        return $response;
    }

    public function joinlegacy(Request $request, $requestToken) // 2010 and below
    {
        // DO NOT CHANGE THIS TO USE url()!!! IT WILL BREAK 2010 DUE TO HTTPS!!!

        $token = GameToken::where('token', $requestToken)->first();

        if (!$token) {
            return 'game:SetMessage("Invalid join token. If this error persists, contact us.")';
        }

        if (!$token->server->allow_guests) {
            if (config('app.discord_verification_required') && !$token->user->discord_id) {
                return "no discord linked";
            }
        }

        if ($token->generated) {
            return 'game:SetMessage("This token was already used.")';
        }

        if (!Cache::has('server_online' . $token->server->id, 0)) {
            return 'game:SetMessage("This server is offline.")';
        }

        if (sizeof(Cache::get('server_online' . $token->server->id, 0)) >= $token->server->maxplayers) {
            return 'game:SetMessage("This server is currently full.")';
        }

        $ip = $token->server->ip;
        if ($token->user) {
            if ($token->user == $token->server->user && $token->server->loopback_ip) {
                $ip = $token->server->loopback_ip;
            }
        }

        $script = file_get_contents(resource_path('lua/join.lua'));
        if (!$token->user) {
            $script = strtr(
                $script,
                [
                    '%PLAYERNAME%' => $token->user->username,
                    '%PLAYERID%' => $token->user->id,
                    '%PLAYERCHARAPP%' => 'http://' . request()->getHttpHost() . '/users/' . $token->user->id . '/character',
                    '%TOKEN%' => $token->token,
                    '%SERVERIP%' => $ip,
                    '%SERVERPORT%' => $token->server->port,
                    '%SITE%' => request()->getHttpHost(),
                    '%GUEST%' => 'false'
                ]
            );
        } else {
            $script = strtr(
                $script,
                [
                    '%PLAYERNAME%' => self::ADJECTIVES[array_rand(self::ADJECTIVES)] . ' ' . self::NOUNS[array_rand(self::NOUNS)] . ' ' . strval(rand(0, 99)),
                    '%PLAYERID%' => rand(1, 100000) * -1,
                    '%PLAYERCHARAPP%' => 'http://' . request()->getHttpHost() . '/users/146/character',
                    '%TOKEN%' => $token->token,
                    '%SERVERIP%' => $ip,
                    '%SERVERPORT%' => $token->server->port,
                    '%SITE%' => request()->getHttpHost(),
                    '%GUEST%' => 'true'
                ]
            );
        }

        $token->generated = true;
        $token->save();

        $response = Response::make($script);
        $response->header('Content-Type', 'text/plain');
        return $response;
    }

    public function joinold(Request $request, $requestToken) // 2012
    {
        $token = GameToken::where('token', $requestToken)->first();

        if (!$token) {
            return ScriptSigner::instance()->sign('game:SetMessage("Invalid join token. If this error persists, contact us.")', 'old');
        }

        if (config('app.discord_verification_required') && !$token->user->discord_id) {
            return ScriptSigner::instance()->sign('game:SetMessage("Link your Discord account to play!")', 'old');
        }

        if ($token->generated) {
            return ScriptSigner::instance()->sign('game:SetMessage("This token was already used.")', 'old');
        }

        if (!Cache::has('server_online' . $token->server->id, 0)) {
            return ScriptSigner::instance()->sign('game:SetMessage("This server is offline.")', 'old');
        }

        if (sizeof(Cache::get('server_online' . $token->server->id, 0)) >= $token->server->maxplayers) {
            return ScriptSigner::instance()->sign('game:SetMessage("This server is currently full.")', 'old');
        }

        $ip = $token->server->ip;
        if ($token->user == $token->server->user && $token->server->loopback_ip) {
            $ip = $token->server->loopback_ip;
        }

        $script = file_get_contents(resource_path('lua/join.lua'));
        $script = strtr(
            $script,
            [
                '%PLAYERNAME%' => $token->user->username,
                '%PLAYERID%' => $token->user->id,
                '%PLAYERCHARAPP%' => 'http://' . request()->getHttpHost() . '/users/' . $token->user->id . '/character',
                '%TOKEN%' => $token->token,
                '%SERVERIP%' => $ip,
                '%SERVERPORT%' => $token->server->port,
                '%SITE%' => request()->getHttpHost()
            ]
        );

        $token->generated = true;
        $token->save();

        $response = Response::make($script);
        $response->header('Content-Type', 'text/plain');
        return $response;
    }

    public function joinnew(Request $request, $requestToken) // 2013-2016(?)
    {
        $token = GameToken::where('token', $requestToken)->first();

        if (!$token) {
            return 'game:SetMessage("Invalid join token. If this error persists, contact us.")';
        }

        if (sizeof(Cache::get('server_online' . $token->server->id, 0)) >= $token->server->maxplayers) {
            return 'game:SetMessage("This server is currently full.")';
        }

        $ip = $token->server->ip;
        if ($token->user == $token->server->user && $token->server->loopback_ip) {
            $ip = $token->server->loopback_ip;
        }

        $script = file_get_contents(resource_path('lua/join.lua'));
        $script = strtr(
            $script,
            [
                '%PLAYERNAME%' => $token->user->username,
                '%PLAYERID%' => $token->user->id,
                '%PLAYERCHARAPP%' => route('users.getcharacter', $token->user->id),
                '%TOKEN%' => $token->token,
                '%SERVERIP%' => $ip,
                '%SERVERPORT%' => $token->server->port,
                '%SITE%' => request()->getHttpHost()
            ]
        );

        $response = Response::make($script);
        $response->header('Content-Type', 'text/plain');
        return $response;
    }
    public function join2016(Request $request, $requestToken) // for 2016 QA
    {
        
        $token = GameToken::where('token', $requestToken)->first();

        if (!$token) {
            return ScriptSigner::instance()->sign('game:SetMessage("Invalid join token. If this error persists, contact us.")', 'new');
        }

        
            if (config('app.discord_verification_required') && !$token->user->discord_id) {
                return "no discord linked";
            }
       

        if ($token->generated) {
            return ScriptSigner::instance()->sign('game:SetMessage("This token was already used.")', 'new');
        }

        $ip = $token->server->ip;
        if ($token->user) {
            if ($token->user == $token->server->user && $token->server->loopback_ip) {
                $ip = $token->server->loopback_ip;
            }
        }
        if ($token->server->version != "2016" && !Cache::has('server_online' . $token->server->id, 0)) {
            return ScriptSigner::instance()->sign('game:SetMessage("This server is offline.")', 'new');
        }

        // TODO: fix this -- sizeof(): Argument #1 ($value) must be of type Countable|array, int given when not 2016 commented out
        if ($token->server->version != "2016" && sizeof(Cache::get('server_online' . $token->server->id, 0)) >= $token->server->maxplayers) {
            return ScriptSigner::instance()->sign('game:SetMessage("This server is currently full.")', 'new');
        }

        if($token->server->version != "2016") {
            $script = file_get_contents(resource_path('lua/newjoin.lua'));
            if ($token->user) {
                $script = strtr(
                    $script,
                    [
                        '%PLAYERNAME%' => $token->user->username,
                        '%PLAYERID%' => $token->user->id,
                        '%TOKEN%' => $token->token,
                        '%SERVERIP%' => $ip,
                        '%SERVERPORT%' => $token->server->port,
                        "%MEMBERSHIP%" => (int)$token->user->booster,
                        "%CHATTYPE%" => $token->server->chat_type,
                        "%PLACEID%" => $token->server->id,
                        '%CREATOR%' => $token->server->user->id,
                        '%CORESCRIPT_PREFRENCE%' => ($token->user->old_cores ? 1086 : 39),
                        '%SITE%' => request()->getHttpHost(),
                        '%GUEST%' => 'false'
                    ]
                );
            } else {
                $script = strtr(
                    $script,
                    [
                        '%PLAYERNAME%' => self::ADJECTIVES[array_rand(self::ADJECTIVES)] . ' ' . self::NOUNS[array_rand(self::NOUNS)] . ' ' . strval(rand(0, 99)),
                        '%PLAYERID%' => rand(1, 100000) * -1,
                        '%TOKEN%' => $token->token,
                        '%SERVERIP%' => $ip,
                        '%SERVERPORT%' => $token->server->port,
                        "%MEMBERSHIP%" => 0,
                        "%CHATTYPE%" => $token->server->chat_type,
                        "%PLACEID%" => $token->server->id,
                        '%CREATOR%' => $token->server->user->id,
                        '%CORESCRIPT_PREFRENCE%' => 39,
                        '%SITE%' => request()->getHttpHost(),
                        '%GUEST%' => 'true'
                    ]
                );
            }

            $token->generated = true;
            $token->save();

            $response = Response::make(ScriptSigner::instance()->sign($script, 'new'));
            $response->header('Content-Type', 'text/plain');
            return $response;
        } else {
            $arbiterkey = preg_replace("/[^a-zA-Z0-9]+/", "", env('RCC_KEY'));
            $ChatType = ["None", "Classic", "ClassicAndBubble"];
            $port = DB::table('ports')->where('id', $token->server->id)->value('port');
            if ($port) {
                $response2 = file_get_contents("http://127.0.0.1:64989/?key=". $arbiterkey);
                $responseJson = json_decode($response2, true);
            
                if (isset($responseJson['runningGames']) && is_array($responseJson['runningGames'])) {
                    $runningGames = $responseJson['runningGames'];
                    $matchingServerId = false;
            
                    foreach ($runningGames as $gameId) {
                        if (intval($gameId) === intval($token->server->id)) {
                            $matchingServerId = true;
                            break;
                        }
                    }
            
                    if (!$matchingServerId) {
                        DB::table('ports')->where('id', $token->server->id)->delete();
                        $port = null;
                    }
                }
            }
            
            if (!$port) {
                $r = $this->getRandomUnusedPort($token->server->id);
                $response = file_get_contents("http://127.0.0.1:64989/game/start/".$token->server->id."?key=". $arbiterkey . "&port=".$r. "&creatorId=".intval($token->server->user->id));
		$port = DB::table('ports')->where('id', $token->server->id)->value('port');
            }
            $time = strval(time());
            $joinscript = [
                'ClientPort' => 0,
                'MachineAddress' => env("RCC_IP"),
                'ServerPort' => intval($port),
                'PingUrl' => 'http://kapish.fun/pinger',
                'PingInterval' => 120,
                'UserName' => $token->user->username,
                'SeleniumTestMode' => false,
                'UserId' => $token->user->id,
                'SuperSafeChat' => false, 
                'ClientTicket' => $time.";".$this->Sign($token->user->id."\n".$token->user->username."\n".'https://kapish.fun/Asset/CharacterFetch.ashx?userId='.$token->user->id."\n".$token->server->id."\n".$time, true).";".$this->Sign($token->user->id."\n".$token->server->id."\n".$time, true),
                'GameId' => Str::uuid()->toString(),
                'PlaceId' => intval($token->server->id),
                'BaseUrl' => 'http://www.kapish.fun/',
                'ChatStyle' => $ChatType[$token->server->chat_type],
                'VendorId' => 0,
                'ScreenshotInfo' => '',
                'VideoInfo' => '',
                'CreatorId' =>$token->server->user->id,
                'CreatorTypeEnum' => 'User',
                'MembershipType' => 'None',
                'AccountAge' => 0,
                'CookieStoreFirstTimePlayKey' => 'rbx_evt_ftp',
                'CookieStoreFiveMinutePlayKey' => 'rbx_evt_fmp',
                'CookieStoreEnabled' => true,
                'IsRobloxPlace' => false, // $request->trust ?? -- what the hell does this mean
                'GenerateTeleportJoin' => false,
                'InUnknownOrUnder13' => false,
                'SessionId' => Str::uuid()->toString() . '|' . Str::uuid()->toString() . '|0|' . $request->ip ?? 'localhost' . '|0|2022-01-01T24:00:00.0000000Z|0|null|null|0|0|0',
                'DataCenterId' => 0,
                'UniverseId' => 0,
                'BrowserTrackerId' => 0,
                'UsePortraitMode' => false,
                'FollowUserId' => 0
            ];

            $joinscript['CharacterAppearance'] = 'https://kapish.fun/Asset/CharacterFetch.ashx?userId=' . $joinscript['UserId'];
            $joinscript['characterAppearanceId'] = $joinscript['UserId'];

            $token->server->update(['visits' => $token->server->visits + 1]);
            $token->generated = true;
            $token->save();
            $cookie = Cookie::make('auth_token', $requestToken, 60);

            $response = Response::make(ScriptSigner::instance()->sign(json_encode($joinscript, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK), 'new'));
            $response->header('Content-Type', 'text/plain');
            $response->withCookie($cookie);
            
            return $response;
        }
    }
public function jointest(Request $request) {
            return "disabled";
    }
    public function newjoin(Request $request, $requestToken) // for 2013 and above
    {
        $token = GameToken::where('token', $requestToken)->first();

        if (!$token) {
            return ScriptSigner::instance()->sign('game:SetMessage("Invalid join token. If this error persists, contact us.")', 'new');
        }

        if (!$token->server->allow_guests) {
            if (config('app.discord_verification_required') && !$token->user->discord_id) {
                return ScriptSigner::instance()->sign('game:SetMessage("Link your Discord account to play!")', 'new');
            }
        }

        if ($token->generated) {
            return ScriptSigner::instance()->sign('game:SetMessage("This token was already used.")', 'new');
        }

        $ip = $token->server->ip;
        if ($token->user) {
            if ($token->user == $token->server->user && $token->server->loopback_ip) {
                $ip = $token->server->loopback_ip;
            }
        }

        if (!Cache::has('server_online' . $token->server->id, 0)) {
            return ScriptSigner::instance()->sign('game:SetMessage("This server is offline.")', 'new');
        }

        if (sizeof(Cache::get('server_online' . $token->server->id, 0)) >= $token->server->maxplayers) {
            return ScriptSigner::instance()->sign('game:SetMessage("This server is currently full.")', 'new');
        }

        $script = file_get_contents(resource_path('lua/newjoin.lua'));
        if ($token->user) {
            $script = strtr(
                $script,
                [
                    '%PLAYERNAME%' => $token->user->username,
                    '%PLAYERID%' => $token->user->id,
                    '%TOKEN%' => $token->token,
                    '%SERVERIP%' => $ip,
                    '%SERVERPORT%' => $token->server->port,
                    "%MEMBERSHIP%" => (int)$token->user->booster,
                    "%CHATTYPE%" => $token->server->chat_type,
                    "%PLACEID%" => $token->server->id,
                    '%CREATOR%' => $token->server->user->id,
                    '%CORESCRIPT_PREFRENCE%' => ($token->user->old_cores ? 1086 : 39),
                    '%SITE%' => request()->getHttpHost(),
                    '%GUEST%' => 'false'
                ]
            );
        } else {
            $script = strtr(
                $script,
                [
                    '%PLAYERNAME%' => self::ADJECTIVES[array_rand(self::ADJECTIVES)] . ' ' . self::NOUNS[array_rand(self::NOUNS)] . ' ' . strval(rand(0, 99)),
                    '%PLAYERID%' => rand(1, 100000) * -1,
                    '%TOKEN%' => $token->token,
                    '%SERVERIP%' => $ip,
                    '%SERVERPORT%' => $token->server->port,
                    "%MEMBERSHIP%" => 0,
                    "%CHATTYPE%" => $token->server->chat_type,
                    "%PLACEID%" => $token->server->id,
                    '%CREATOR%' => $token->server->user->id,
                    '%CORESCRIPT_PREFRENCE%' => 39,
                    '%SITE%' => request()->getHttpHost(),
                    '%GUEST%' => 'true'
                ]
            );
        }

        $token->generated = true;
        $token->save();

        $response = Response::make(ScriptSigner::instance()->sign($script, 'new'));
        $response->header('Content-Type', 'text/plain');
        return $response;
    }

    public function newhost(Request $request, $secret)
    {
        $server = Server::where('secret', $secret)->first();

        if (!$server) {
            return ScriptSigner::instance()->sign('game:SetMessage("Invalid server.")', "new");
        }

        $script = file_get_contents(resource_path('lua/newhost.lua'));
        $script = strtr(
            $script,
            [
                '%SERVERPORT%' => $server->port,
                '%SERVERSECRET%' => $server->secret,
                '%SERVERUUID%' => $server->uuid,
                '%SITE%' => request()->getHttpHost(),
                '%MAXPLAYERS%' => $server->maxplayers,
                '%PLACEID%' => $server->id,
                '%CREATOR%' => $server->user->id,
            ]
        );

        $script = ScriptSigner::instance()->sign($script, "new");

        $response = Response::make($script);
        $response->header('Content-Type', 'text/plain');
        return $response;
    }

    public function newhosttest(Request $request)
    {
        if ($request->has('key')) {
            if ($request->key == config('app.rcc_key')) {
                $version = $request->has('old') ? "old" : "new";
                $script = file_get_contents(resource_path('lua/newhosttest.lua'));
                $script = strtr(
                    $script,
                    [
                        '%SITE%' => request()->getHttpHost()
                    ]
                );

                $script = ScriptSigner::instance()->sign($script, $version);

                $response = Response::make($script);
                $response->header('Content-Type', 'text/plain');
                return $response;
            } else {
                abort(404);
            }
        } else {
            abort(404);
        }
    }

    public function newjointest(Request $request)
    {
        if ($request->has('key')) {
            if ($request->key == config('app.rcc_key')) {
                $version = $request->has('old') ? "old" : "new";
                $script = file_get_contents(resource_path('lua/newjointest.lua'));
                $script = strtr(
                    $script,
                    [
                        '%PLAYERNAME%' => $request->username,
                        '%PLAYERID%' => $request->id,
                        '%SERVERIP%' => $request->ip,
                        '%SERVERPORT%' => $request->port,
                        '%SITE%' => request()->getHttpHost()
                    ]
                );

                // Carrot will be found dead on November 5th 2021 @ exactly 11:59 PM EST
                $script = ScriptSigner::instance()->sign($script, $version);

                $response = Response::make($script);
                $response->header('Content-Type', 'text/plain');
                return $response;
            } else {
                abort(404);
            }
        } else {
            abort(404);
        }
    }

    public function thumbnailasset(Request $request) {
        if (!$request['aid']) {
            abort(404);
        }

        $response = Http::withUserAgent('Roblox/WinInet')->get('https://assetgame.roblox.com/Thumbs/Asset.asmx/RequestThumbnail_v2?width=420&height=420&imageFormat=Png&thumbnailFormatId=296&overrideModeration=false&assetId=' . $request['aid']);
        $response = json_decode($response, true);
        return redirect($response['d']['url']);
    }
    public function md5(Request $request) {
    $data = [
        "data" => [
	        "d8cd929d230a3ba0d10195fdfd6b0fcf",
            "1173e6bff93be928095828d9c05fa989"
        ]
    ];

    return response()->json($data);
    }
    public function security(Request $request) {
    $data = [
        "data" => [
            "0.0.0.1",
	    "PRAFBERQ"
        ]
    ];

    return response()->json($data);
    }
    public function security2(Request $request) {
    $data = [
        "data" => [
	    "PRAFBERQ",
            "0.0.0.1"
        ]
    ];

    return response()->json($data);
    }
    public function fastflags(Request $request, $type)
    {
        $version = "2012";

        if ($type == "CometAppSettings") {
            $version = "2016"; // Fuck yuo im lazy right now. 2016 is in testing phase...... not anything big
        }

        if ($type == "ClientAppSettings") {
            $version = "2016";
        }
        if ($type == "ClientSharedSettings") {
            $version = "ClientShared";
        }

        if ($type == "RCCService76E5A40C-3AE1-4028-9F10-7C62520BD94F") {
            $version = "2016";
        }

        $flags = Storage::disk('local')->get((sprintf('fastflags/%s.json', $version)));

        return response()->json(json_decode($flags));
    }
    public function reportStats(Request $request)
    {
        $whitelistedIPs = env('WHITELISTED_IP'); 
        $clientIP = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER['REMOTE_ADDR'];;
    
        if (!in_array($clientIP, explode(',', $whitelistedIPs))) {
            return "err";
        }
        $userid = $request->input('UserID');
        $message = $request->input('Message');
        $webhookUrl = env("CHEAT_WEBHOOK");
        $user = User::find($userid);
        $payload = [
            'content' => $user->username . ' triggered detection: ' . $message
        ];
    
        $response = Http::post($webhookUrl, $payload);
    
        if ($response->successful()) {
            return 'a';
        } else {
            return 'no';
        }
    }
    
    public function placelauncher(Request $request)
    {
        $id = $request->placeId;
        if(!$id) {
            abort(404);
        };

        $test = '{"jobId":"asdadasd","status":2,"joinScriptUrl":"http://tadah.local/game/join.ashx?ip=127.0.0.1&port=53640&username=Iago&id=45","authenticationUrl":"http://google.com","authenticationTicket":"atest","message":"null"}';
        return $test;
    }

    public function gameserver(Request $request)
    {
        $script = file_get_contents(resource_path('lua/gameserver.lua'));
        $script = strtr(
            $script,
            [
                '%SERVERPORT%' => 53640,
                '%SITE%' => request()->getHttpHost(),
            ]
        );

        $script = ScriptSigner::instance()->sign($script, "new");

        $response = Response::make($script);
        $response->header('Content-Type', 'text/plain');
        return $response;	
    }

    public function validateth(Request $request) {
    	        $response = Response::make("true");
        $response->header('Content-Type', 'text/plain');
        return $response;
    }
    public function studiojoin(Request $request)
    {
        $script = file_get_contents(resource_path('lua/studiojoin.lua'));
        $script = strtr(
            $script,
            [
                '%PLAYERID%' => $request->UserID,
                '%SERVERPORT%' => $request->serverPort,
                '%SITE%' => request()->getHttpHost(),
            ]
        );

        $script = ScriptSigner::instance()->sign($script, "new");

        $response = Response::make($script);
        $response->header('Content-Type', 'text/plain');
        return $response;
    }

    public function awardpoints(Request $request)
    {
        // literally no point in ACTUALLY implementing player points, we just want it to show up ingame
        if(!User::find($request->get('userId'))) {
            abort(404);
        }

        return response()->json([
            'success' => true,
            'userGameBalance' => $request->get('amount'),
            'userBalance' => $request->get('amount'),
            'pointsAwarded' => $request->get('amount')
        ]);
    }

    public function currency(Request $request)
    {
        // roblonium type beat
        // idk how else to do this so oh boy
        // this will absolutely not work with alts LOL
        $ip = $request->ip();
        $user = User::where(['last_ip' => $ip])
                    ->orderBy('last_online', 'DESC')
                    ->first();
        $result = json_encode(
            array(
                "robux" => (int)$user->money,
                "tickets" => 0
            )
        );
        return $result;
    }
    function securityVersions(Request $request, $userId)
    {
        $result = json_encode(
            array(
                "ChatFilter" => "blacklist"
            )
        );

        return $result;
    }


    public function loadPlaceInfo(Request $request)
    {
        if (!$request->PlaceId)
            abort(400);

        $server = Server::where('id', $request->PlaceId)->first();

        if (!$server) {
            return 'print("Invalid server.")';
        }

        $script = file_get_contents(resource_path('lua/loadplaceinfo.lua'));
        $script = strtr(
            $script,
            [
                '%SERVEROWNER%' => $server->user->id,
            ]
        );

        return $script;
    }


    public function getproductdetails(Request $request)
    {
        $item = Item::findOrFail($request->productId);
        $result = json_encode(
            array(
                "TargetId" => $item->id,
                "ProductType" => $item->type,
                "AssetId" => $item->id,
                "ProductId" => null,
                "Name" => $item->name,
                "Description" => $item->description,
                "AssetTypeId" => 2,
                "Creator" => array(
                    "Id" => $item->user->id,
                    "Name" => $item->user->username,
                    "CreatorType" => "User",
                    "CreatorTargetId" => $item->user->id,
                ),
                "IconImageAssetId" => $item->id,
                "Created" => $item->created_at,
                "Updated" => $item->updated_at,
                "PriceInRobux" => (int)$item->price,
                "PriceInTickets" => null,
                "IsNew" => false,
                "IsForSale" => (bool)$item->onsale,
                "IsPublicDomain" => false,
                "IsLimited" => false,
                "IsLimitedUnique" => false,
                "Remaining" => null,
                "MinimumMembershipLevel" => 0
            )
        );
        return $result;
    }

    function toolbox(Request $request)
    {
        $models = Item::where(['type' => 'Model'])->get();
        if (request('search')) {
            $models = Item::where(['type' => 'Model'])->where('name', 'LIKE', '%' . request('search') . '%')->get();
        }
        return view('client.toolbox', ['models' => $models]);
    }

    // Static 2016 responses (static for now at least)
    function getChatFilter(Request $request, $userId)
    {
	return response()->json(['ChatFilter' => 'whitelist']);
    }
    function getBalanceApiVersion(Request $request) 
    {
        return redirect('https://kapish.fun/currency/getbalance');
    }
    
    function getBalance(Request $request)
    {
        $token = GameToken::where('token', $request->cookie('auth_token'))->first();

        $result = json_encode(
            array(
                "robux" => $token->user->money,
                "tickets" => 0
            )
        );

        return $result;
    }

    function getAssetOwnership(Request $request)
    {
        $userId = $request->input('userId');
        $assetId = $request->input('assetId');
    
        $ownership = DB::table('owned_items')
            ->where('user_id', intval($userId))
            ->where('item_id', intval($assetId))
            ->exists(); 
            if ($ownership == 1) { return "true"; } else {return "false"; }
    }
    

    function getFilteredText(Request $request)
    {
        $text = e($request["text"]);
      
        if (!$text) {
            abort(400);
        }
    
        $result = json_encode(
            array(
                "data" => [
                    "white" => $text,
                    "black" => "ok"
                ]
            )
        );
    
        return $result;
    }
    
   function getSSC(Request $request)
    {
    
        $result = json_encode(
            array(
                [
                    "ChatFilter" => "whitelist",
                ]
            )
        );
    
        return $result;
    }

    function validatePlaceJoin(Request $request)
    {
        // TODO: make not static!!! SUPER IMPORTANT for valid place joins

        return "true";
    }

    function getEconomyStatus(Request $request)
    {
        $result = json_encode(
            array(
                "robux" => 0,
                "tickets" => 0,
                "isMarketplaceEnabled" => true,
                "isDeveloperProductPurchaseEnabled" => true,
                "areInAppPurchasesEnabled" => false
            )
        );

        return $result;
    }

    function getCanManage(Request $request) {
        // TODO: MAKE NOT STATIC!! THIS IS EASIEST, ENABLES DEV CONSOLE FOR THE OWNER
       $server = Server::where('id', $request->placeid)->first();
       if (!$server) { return "a";}
       $creator = User::where('id', $server->creator)->first();
        $result = json_encode(
            array(
                "Success" => true,
                "CanManage" => ($creator->id == $request->userid)
            )
        );
        
        return $result;
    }

function getProductInfo(Request $request) {
    $assetId = (int)$request->assetId;
    $mps = $request->mps;
    $onsale = true;
    if (!is_int($assetId)) {
        abort(400);
    }
    if ($mps == true) {
        $item = Item::where('id', $assetId)->first();
        $creator = User::where('id', $item->creator)->first();
        if ($item->isLimited() || $item->isLimitedUnique() || $item->onsale == 0) {
            $onsale = false;
        }

        $result = array(
            "TargetId" => 0,
            "ProductType" => null,
            "AssetId" => $assetId,
            "ProductId" => $assetId,
            "Name" => $item->name,
            "Description" => $item->description,
            "Creator" => array(
                "Id" => $creator->id,
                "Name" => $creator->username,
                "CreatorType" => "User",
                "CreatorTargetId" => 1
            ),
            "IconImageAssetId" => "https://cdn.kapish.fun/" . $item->id,
            "Created" => "",
            "Updated" => "",
            "PriceInRobux" => $item->price,
            "PriceInTickets" => 0,
            "Sales" => $item->sales,
            "IsNew" => false,
            "IsForSale" => $onsale,
            "IsPublicDomain" => false,
            "IsLimited" => ($item->is_limited == 1) ? true : false,
            "IsLimitedUnique" => ($item->is_limitedu == 1) ? true : false,
            "Remaining" => ($item->stock_circulating == 0) ? null : $item->stock_circulating,
            "MinimumMembershipLevel" => 0,
            "ContentRatingType" => 0
        );
        return response()->json($result);
    }
    $server = Server::where('id', $assetId)->first();
    $creator = User::where('id', $server->creator)->first();

    $result = array(
        "TargetId" => 0,
        "ProductType" => null,
        "AssetId" => $assetId,
        "ProductId" => $assetId,
        "Name" => htmlspecialchars($server->name),
        "Description" => htmlspecialchars($server->description),
        "Creator" => array(
            "Id" => $creator->id,
            "Name" => htmlspecialchars($creator->username),
            "CreatorType" => "User",
            "CreatorTargetId" => 1
        ),
        "IconImageAssetId" => 1,
        "Created" => "",
        "Updated" => "",
        "PriceInRobux" => 0,
        "PriceInTickets" => 0,
        "Sales" => 0,
        "IsNew" => false,
        "IsForSale" => false,
        "IsPublicDomain" => false,
        "IsLimited" => false,
        "IsLimitedUnique" => false,
        "Remaining" => null,
        "MinimumMembershipLevel" => 0,
        "ContentRatingType" => 0
    );

    return response()->json($result);
}
}
