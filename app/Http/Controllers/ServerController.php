<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionsHelper;
use App\Models\SaAdmin;
use App\Models\SaAdminsFlags;
use App\Models\SaBan;
use App\Models\SaServer;
use App\Models\ServerStats;
use App\Models\ServerVisibilitySetting;
use App\Services\RconService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ServerController extends Controller
{
    /**
     * @param RconService $rcon
     * @return \Illuminate\Http\JsonResponse
     */

    public function showServerSettings()
    {
        $servers = SaServer::all();
        $serverVisibilitySettings = ServerVisibilitySetting::pluck('is_visible', 'server_id')->toArray();

        return view('settings.servers', compact('servers', 'serverVisibilitySettings'));
    }

    public function updateServerSettings(Request $request)
    {
        $serverSettings = $request->input('servers', []);

        try {
            // Insert new settings
            foreach ($serverSettings as $serverId => $isVisible) {
                ServerVisibilitySetting::updateOrCreate(
                    ['server_id' => $serverId],
                    ['is_visible' => $isVisible]
                );
            }

            return redirect()->back()->with('success', 'Server visibility settings updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to update server visibility settings: ' . $e->getMessage()]);
        }
    }

    public function getAllServerInfo(RconService $rcon)
    {
        $servers = SaServer::all();
        $serverVisibilitySettings = ServerVisibilitySetting::pluck('is_visible', 'server_id')->toArray();

        $formattedServers = [];
        $loggedInPlayerSteam = null;
        if(auth()->check()){
            $loggedInPlayerSteam = Auth::user()?->steam_id;
        }
        foreach ($servers as $server) {
            if (isset($serverVisibilitySettings[$server->id]) && !$serverVisibilitySettings[$server->id]) {
                continue; // Skip hidden servers
            }

            list($serverIp, $serverPort) = explode(":", $server->address);
            // Fetch server information using the SteamService
            try {
                $serverDetails = $this->getServerDetails($serverIp, $serverPort);
                if ($serverDetails) {
                    $banned = '';
                    if($loggedInPlayerSteam){
                        if(SaBan::where('player_steamid', $loggedInPlayerSteam)
                            ->where('server_id', $server->id)
                            ->where('status', 'ACTIVE')
                            ->exists()) {
                            $banned = ' <span class="badge badge-light-danger mb-2 me-4">' . __('admins.banned') . '</span>';
                        }
                    }
                    $formattedServer = [
                        'id' => $server->id,
                        'name' => $server->hostname.$banned,
                        'ip' => $serverIp,
                        'port' => $serverPort,
                        'players' => $serverDetails['players'] . "/" . $serverDetails['max_players'],
                        'map' => $serverDetails['map'],
                        'connect_button' => '<a class="btn btn-success" href="steam://connect/' . $serverIp . ':' . $serverPort . '">' . __('dashboard.connect') . '</a>',
                    ];
                } else {
                    $formattedServer = [
                        'id' => $server->id,
                        'name' => $server->hostname,
                        'ip' => $serverIp,
                        'port' => $serverPort,
                        'players' => '0',
                        'map' => '<h6><span class="badge badge-danger">' . __('dashboard.offline') . '</span></h6>',
                        'connect_button' => '<h6><span class="badge badge-danger">' . __('dashboard.offline') . '</span></h6>'
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Steam Web API Error: ' . $e->getMessage());
                $formattedServer = [
                    'id' => $server->id,
                    'name' => $server->hostname,
                    'ip' => $serverIp,
                    'port' => $serverPort,
                    'players' => '0',
                    'map' => '<h6><span class="badge badge-danger">' . __('dashboard.offline') . '</span></h6>',
                    'connect_button' => '<h6><span class="badge badge-danger">' . __('dashboard.offline') . '</span></h6>'
                ];
            }

            $formattedServers[] = $formattedServer;
        }

        return response()->json($formattedServers);
    }
    public function syncNewServers(Request $request)
    {
        $servers = SaServer::all();
        $synced = false;

        foreach ($servers as $server) {
            $existingSetting = ServerVisibilitySetting::where('server_id', $server->id)->first();
            if (!$existingSetting) {
                ServerVisibilitySetting::create([
                    'server_id' => $server->id,
                    'is_visible' => 1 // Default to visible, change as needed
                ]);
                $synced = true;
            }
        }

        if ($synced) {
            return response()->json(['success' => 'New servers synced successfully.']);
        } else {
            return response()->json(['error' => 'No new servers to sync.']);
        }
    }
    private function isPortOpen($ip, $port, $timeout = 1) {
        $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        if (!in_array($errno, [SOCKET_ETIMEDOUT,SOCKET_EHOSTUNREACH,SOCKET_ENETUNREACH]) && stripos(strtolower($errstr), 'failed') === false) {
            $fp ? fclose($fp) : '';
            return true;
        } else {
            return false;
        }
    }

    /**
     * Parse RCON status command response to extract player information
     * This is useful for CS2 servers where GetPlayers() might not work properly
     */
    private function parseRconStatusResponse($statusResponse) {
        $players = [];
        
        if (empty($statusResponse)) {
            return $players;
        }
        
        Log::info('Parsing RCON status response: ' . $statusResponse);
        
        // Split response into lines
        $lines = explode("\n", $statusResponse);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Look for player lines (they typically contain # followed by player info)
            // Example: #  2 "PlayerName"          STEAM_1:0:123456789  25:30       73    0 active
            if (preg_match('/^#\s*(\d+)\s+"([^"]+)"\s+(\S+)\s+(\d+:\d+)\s+(\d+)\s+(\d+)\s+(\w+)/', $line, $matches)) {
                $players[] = [
                    'Name' => $matches[2],
                    'SteamID' => $matches[3],
                    'TimeF' => $matches[4],
                    'Frags' => (int)$matches[5],
                    'Deaths' => (int)$matches[6],
                    'Status' => $matches[7]
                ];
                Log::info('Parsed player from RCON status: ' . json_encode($players[count($players) - 1]));
            }
            // Alternative pattern for different server formats
            elseif (preg_match('/^#\s*(\d+)\s+(\d+)\s+"([^"]+)"\s+(\S+)\s+(\d+:\d+)\s+(\d+)\s+(\d+)/', $line, $matches)) {
                $players[] = [
                    'Name' => $matches[3],
                    'SteamID' => $matches[4],
                    'TimeF' => $matches[5],
                    'Frags' => (int)$matches[6],
                    'Deaths' => (int)$matches[7]
                ];
                Log::info('Parsed player from RCON status (alt pattern): ' . json_encode($players[count($players) - 1]));
            }
        }
        
        return $players;
    }

    /**
     * @param Request $request
     * @param $serverId
     * @param RconService $rcon
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     */
    public function getPlayers(Request $request, $serverId, RconService $rcon) {
        $players = [];
        $error = null;
        $server = SaServer::where('id', $serverId)->first();
        
        if (!$server) {
            $error = 'Server not found';
            return view('admin.servers.players', compact('players', 'server', 'error'));
        }
        
        list($serverIp, $serverPort) = explode(":", $server->address);
        Log::info('Attempting to connect to server: ' . $serverIp . ':' . $serverPort);
        
        if($this->isPortOpen($serverIp, $serverPort)) {
            try {
                $rcon->connect($serverIp, $serverPort);
                
                // Try to get server info first to understand the server type
                try {
                    $serverInfo = $rcon->getInfo();
                    Log::info('Server info: ' . json_encode($serverInfo, JSON_PRETTY_PRINT));
                } catch (\Exception $infoException) {
                    Log::warning('Could not get server info: ' . $infoException->getMessage());
                }
                
                $rconPlayersWorked = false;
                
                // Check if server has RCON configured and try RCON commands first
                if ($server->rcon && $server->rcon->password) {
                    try {
                        $decryptedPassword = \Illuminate\Support\Facades\Crypt::decrypt($server->rcon->password);
                        $rcon->setRconPassword($decryptedPassword);
                        Log::info('Using RCON password for server: ' . $serverId);
                        
                        // Try 'status' command which often works better for CS2
                        try {
                            $statusCmd = $rcon->rcon('status');
                            Log::info('RCON status command response: ' . $statusCmd);
                            
                            if (!empty($statusCmd)) {
                                $rconPlayers = $this->parseRconStatusResponse($statusCmd);
                                if (!empty($rconPlayers)) {
                                    $players = $rconPlayers;
                                    $rconPlayersWorked = true;
                                    Log::info('Successfully parsed players from RCON status command');
                                }
                            }
                            
                        } catch (\Exception $rconCmdException) {
                            Log::warning('RCON status command failed: ' . $rconCmdException->getMessage());
                        }
                        
                        // If status didn't work, try other commands
                        if (!$rconPlayersWorked) {
                            try {
                                $rconPlayerList = $rcon->rcon('listplayers');
                                Log::info('RCON listplayers response: ' . $rconPlayerList);
                                
                                // Also try the 'players' command
                                $rconPlayersCmd = $rcon->rcon('players');
                                Log::info('RCON players command response: ' . $rconPlayersCmd);
                                
                            } catch (\Exception $rconCmdException) {
                                Log::warning('RCON commands failed: ' . $rconCmdException->getMessage());
                            }
                        }
                        
                    } catch (\Exception $rconException) {
                        Log::warning('RCON setup failed: ' . $rconException->getMessage());
                    }
                }
                
                // If RCON didn't work or no RCON password, try SourceQuery GetPlayers
                if (!$rconPlayersWorked) {
                    $players = $rcon->getPlayers();
                    Log::info('Raw players data from SourceQuery: ' . json_encode($players, JSON_PRETTY_PRINT));
                    Log::info('Players data type: ' . gettype($players));
                    Log::info('Players count: ' . (is_array($players) ? count($players) : 'not array'));
                    
                    // Debug: Check what keys are available in the player data
                    if (is_array($players) && !empty($players)) {
                        Log::info('First player structure: ' . json_encode($players[0] ?? null, JSON_PRETTY_PRINT));
                        Log::info('Available keys in first player: ' . json_encode(array_keys($players[0] ?? []), JSON_PRETTY_PRINT));
                        
                        // Check if this might be CS2 with different structure
                        if (isset($players[0]) && is_array($players[0])) {
                            $firstPlayer = $players[0];
                            if (isset($firstPlayer['Id']) || isset($firstPlayer['UserID']) || isset($firstPlayer['SteamID'])) {
                                Log::info('Detected possible CS2 player structure');
                            }
                        }
                    }
                    
                    // Ensure players array has proper structure
                    if (is_array($players)) {
                        foreach ($players as $key => $player) {
                            Log::info("Processing player {$key}: " . json_encode($player, JSON_PRETTY_PRINT));
                            
                            // Try different possible name fields
                            $playerName = null;
                            $possibleNameFields = ['Name', 'name', 'PlayerName', 'player_name', 'nick', 'nickname', 'Username', 'username'];
                            
                            foreach ($possibleNameFields as $field) {
                                if (isset($player[$field]) && !empty(trim($player[$field]))) {
                                    $playerName = trim($player[$field]);
                                    Log::info("Found player name in field '{$field}': {$playerName}");
                                    break;
                                }
                            }
                            
                            if (!$playerName) {
                                Log::warning('No valid name found for player: ' . json_encode($player));
                                // Create a more descriptive name using available information
                                $playerId = $player['Id'] ?? $player['UserID'] ?? $player['SteamID'] ?? ($key + 1);
                                $playTime = $player['TimeF'] ?? $player['Time'] ?? '00:00';
                                $playerName = 'Player #' . $playerId . ' (' . $playTime . ')';
                            }
                            
                            $players[$key]['Name'] = $playerName;
                            
                            // Handle other fields with more variations
                            if (!isset($player['Frags'])) {
                                $players[$key]['Frags'] = $player['frags'] ?? $player['kills'] ?? $player['score'] ?? $player['Score'] ?? 0;
                            }
                            if (!isset($player['TimeF'])) {
                                $players[$key]['TimeF'] = $player['time'] ?? $player['TimeF'] ?? $player['connected'] ?? $player['Time'] ?? '00:00';
                            }
                            
                            Log::info("Final player data for {$key}: " . json_encode($players[$key], JSON_PRETTY_PRINT));
                        }
                    } else {
                        Log::warning('Players data is not an array or is empty. Type: ' . gettype($players));
                    }
                }
                
                $rcon->disconnect();
            } catch (\Exception $e) {
                Log::error('rcon.players.error: ' . $e->getMessage());
                Log::error('Stack trace: ' . $e->getTraceAsString());
                $error = 'Failed to get server players! Error: ' . $e->getMessage();
            }
        } else {
            Log::warning('Port is not open for server: ' . $serverIp . ':' . $serverPort);
            $error = __('admins.rconError');
        }
        
        return view('admin.servers.players', compact('players', 'server', 'error'));
    }


    public function setup(Request $request)
    {
        $request->validate([
            'APP_URL' => 'required|url',
            'APP_NAME' => 'required|string',
            'DB_HOST' => 'required|string',
            'DB_DATABASE' => 'required|string',
            'DB_USERNAME' => 'required|string',
            'DB_PASSWORD' => 'required|string',
            'STEAM_CLIENT_SECRET' => 'required|string',
            'STEAM_ID_64' => 'required|string|digits:17',
            'RCON_PASSWORD'=> 'required|string'
        ]);

        try {
            config([
                'database.connections.mysql.host' => $request->DB_HOST,
                'database.connections.mysql.database' => $request->DB_DATABASE,
                'database.connections.mysql.username' => $request->DB_USERNAME,
                'database.connections.mysql.password' => $request->DB_PASSWORD,
            ]);

            DB::connection()->getPdo();

            $sqlFilePath = storage_path('app/cssbans.sql');
            $sql = file_get_contents($sqlFilePath);
            DB::unprepared($sql);
            $envContent = File::get(base_path('.env'));
            foreach ($request->all() as $key => $value) {
                if($key !== 'STEAM_ID_64' && $key !== 'APP_NAME')
                    $envContent .= "$key=$value\n";
                if($key == 'APP_NAME')
                    $envContent .= "$key='$value'\n";
            }
            $envContent .= "\nSETUP=true";
            $envContent .= "\nASSET_URL=".$request->input('APP_URL');
            File::put(base_path('.env'), $envContent);
            foreach(SaServer::all() as $server) {
                $admin = new SaAdmin();
                $admin->player_steamid = $request->input('STEAM_ID_64');
                $admin->player_name = 'Admin';
                $admin->immunity = 1;
                $admin->server_id = $server->id;
                $admin->ends = Carbon::now()->addYears(5)->format(('Y-m-d'));
                $admin->created = now();
                $admin->save();

                $adminFlag = new SaAdminsFlags();
                $adminFlag->admin_id= $admin->id;
                $adminFlag->flag = '@css/root';
                $adminFlag->save();
            }
            return redirect()->route('home')->with('success', 'Environment variables updated successfully. Database connection established. Tables imported.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors(['error' => 'Setup failed: ' . $e->getMessage()]);
        }
    }

    public function serverPlayerAction(Request $request) {
        $requestType = $request->input('action');
        $playerName = $request->input('name');
        $serverId = $request->input('serverId');
        $reason = $request->input('reason');
        switch ($requestType){
            case "ban":
                if(PermissionsHelper::hasUnBanPermission())
                    return $this->executeCommand('css_ban "' . $playerName . '" 1440 "' . $reason . '"', $serverId);
                break;
            case "kick":
                if(PermissionsHelper::hasKickPermission())
                    return $this->executeCommand('css_kick "' . $playerName . '" "' . $reason . '"', $serverId);
                break;
            case "mute":
                if(PermissionsHelper::hasMutePermission())
                    return $this->executeCommand('css_mute "' . $playerName . '" 1440 "' . $reason . '"', $serverId);
                break;
            default: abort(403);
        }
    }

    private function executeCommand(string $command, string $serverId)
    {
        $server = SaServer::where('id', $serverId)->first();
        list($serverIp, $serverPort) = explode(":", $server->address);

        try {
            $rcon = app(RconService::class);
            $rcon->connect($serverIp, $serverPort);
            $rcon->setRconPassword(env('RCON_PASSWORD'));
            $output = $rcon->rcon($command);
            $rcon->disconnect();
            $pattern = "/Target [a-zA-Z]+ not found\./";
            if (preg_match($pattern, $output)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Player not found.'
                ],500);
            } else {
                return response()->json([
                    'status' => 'success',
                    'message' => __('admins.rconSuccess')
                ]);
            }
        } catch(\Exception $e){
            Log::error('rcon.players.error ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => __('admins.rconStatusFailed')
            ], 500);
        }
    }

    private function getServerDetails($ip, $port)
    {
        $apiKey = env('STEAM_CLIENT_SECRET');
        $response = Http::get("https://api.steampowered.com/IGameServersService/GetServerList/v1/?key=$apiKey&filter=addr\\$ip:$port");
        if ($response->successful()) {
            return $response->json('response.servers')[0];
        } else {
            Log::error('steam.api.server.listing '. $response->body());
            return null;
        }
    }


    public function trackServerPlayerCounts(Request $request)
    {
        $token = $request->query('token');

        // Validate the token
        if ($token !== env('_token')) {
            Log::warning('Unauthorized access attempt with token: ' . $token);
            return response()->json(['status' => 'error', 'message' => 'Invalid API token'], 403);
        }
        $servers = SaServer::has('visible')->get();
        foreach ($servers as $server) {
            list($serverIp, $serverPort) = explode(":", $server->address);

            try {
                $serverDetails = $this->getServerDetails($serverIp, $serverPort);
                if ($serverDetails) {
                    // Save the player count using the ServerStats model
                    if($serverDetails['players'] > 0 ) {
                        ServerStats::create([
                            'server_id' => $server->id,
                            'player_count' => $serverDetails['players'],
                            'map' => $serverDetails['map'],
                            'recorded_at' => now(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Steam Web API Error: ' . $e->getMessage());
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Player counts recorded']);
    }

    public function gameServers()
    {
        return view('game-servers');
    }
}
