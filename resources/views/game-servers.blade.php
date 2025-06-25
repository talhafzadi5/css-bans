@php use App\Helpers\PermissionsHelper; @endphp
<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        {{ __('dashboard.gameServers') }}
    </x-slot>

    <x-slot:headerFiles>
        <style>
            .server-card {
                transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
                cursor: pointer;
                border: 1px solid #e3e6f0;
                border-radius: 10px;
            }
            .server-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            }
            .server-status-online {
                color: #28a745;
            }
            .server-status-offline {
                color: #dc3545;
            }
            .players-count {
                font-size: 1.2rem;
                font-weight: bold;
            }
            .server-map {
                background: linear-gradient(45deg, #007bff, #6610f2);
                color: white;
                border-radius: 15px;
                padding: 5px 10px;
                font-size: 0.9rem;
            }
        </style>
    </x-slot>

    <div class="layout-px-spacing">
        <div class="middle-content container-xxl p-0">
            <!-- BREADCRUMB -->
            <div class="page-meta">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ __('dashboard.title') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('dashboard.gameServers') }}</li>
                    </ol>
                </nav>
            </div>
            <!-- /BREADCRUMB -->

            <div class="row layout-top-spacing">
                <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
                    <div class="widget-content widget-content-area br-8">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="mb-0">{{ __('dashboard.gameServers') }}</h4>
                                <p class="text-muted">{{ __('Click on a server card to view players') }}</p>
                            </div>
                        </div>
                        
                        <div class="row" id="serverCards">
                            <!-- Server cards will be loaded here -->
                        </div>
                        
                        <x-loader id="servers_loader" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Server Players -->
    <div class="modal fade" id="playersModal" tabindex="-1" aria-labelledby="playersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="playersModalLabel">{{ __('Server Players') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="playersModalBody">
                    <!-- Players list will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <x-slot:footerFiles>
        <script>
            let serversData = [];
            
            function showLoader() {
                document.getElementById('servers_loader').style.display = 'block';
            }
            
            function hideLoader() {
                document.getElementById('servers_loader').style.display = 'none';
            }
            
            function showPlayersModal() {
                const modal = new bootstrap.Modal(document.getElementById('playersModal'));
                modal.show();
            }
            
            function getPlayerInfoUrl(serverId) {
                return "{!! env('VITE_SITE_DIR') !!}/servers/"+serverId+"/players";
            }
            
            function loadServers() {
                showLoader();
                fetch('{{ url("/servers") }}')
                    .then(response => response.json())
                    .then(data => {
                        serversData = data;
                        renderServerCards(data);
                        hideLoader();
                    })
                    .catch(error => {
                        console.error('Error loading servers:', error);
                        hideLoader();
                        document.getElementById('serverCards').innerHTML = 
                            '<div class="col-12"><div class="alert alert-danger">Failed to load servers</div></div>';
                    });
            }
            
            function renderServerCards(servers) {
                const container = document.getElementById('serverCards');
                let html = '';
                
                servers.forEach(server => {
                    const isOnline = !server.map.includes('badge-danger');
                    const playerCount = isOnline ? server.players : '0/0';
                    const statusClass = isOnline ? 'server-status-online' : 'server-status-offline';
                    const statusText = isOnline ? '{{ __("Online") }}' : '{{ __("Offline") }}';
                    
                    html += `
                        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-4">
                            <div class="card server-card h-100" onclick="loadServerPlayers(${server.id}, '${server.name.replace(/'/g, "\\'")}')">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-12 mb-3">
                                            <h5 class="card-title mb-1">${server.name}</h5>
                                            <span class="badge ${statusClass === 'server-status-online' ? 'badge-success' : 'badge-danger'}">${statusText}</span>
                                        </div>
                                        
                                        <div class="col-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-users me-2 text-primary"></i>
                                                <span class="players-count ${statusClass}">${playerCount}</span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-network-wired me-2 text-secondary"></i>
                                                <small class="text-muted">${server.ip}:${server.port}</small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-6 text-end">
                                            ${isOnline && server.map && !server.map.includes('badge-danger') ? 
                                                `<span class="server-map">${server.map}</span>` : 
                                                '<span class="badge badge-secondary">No Map</span>'
                                            }
                                        </div>
                                        
                                        <div class="col-12 mt-3">
                                            ${isOnline ? 
                                                `<button class="btn btn-success btn-sm w-100" onclick="event.stopPropagation(); window.open('steam://connect/${server.ip}:${server.port}', '_blank')">
                                                    <i class="fas fa-play me-1"></i> {{ __('dashboard.connect') }}
                                                </button>` :
                                                '<button class="btn btn-secondary btn-sm w-100" disabled>{{ __("dashboard.offline") }}</button>'
                                            }
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                if (servers.length === 0) {
                    html = '<div class="col-12"><div class="alert alert-info text-center">{{ __("No servers available") }}</div></div>';
                }
                
                container.innerHTML = html;
            }
            
            function loadServerPlayers(serverId, serverName) {
                document.getElementById('playersModalLabel').textContent = `${serverName} - {{ __('Players') }}`;
                document.getElementById('playersModalBody').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
                
                showPlayersModal();
                
                fetch(getPlayerInfoUrl(serverId))
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('playersModalBody').innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error loading players:', error);
                        document.getElementById('playersModalBody').innerHTML = 
                            '<div class="alert alert-danger">{{ __("Failed to load players") }}</div>';
                    });
            }
            
            // Player action functionality (if user has permissions)
            document.addEventListener('click', function(event) {
                if (event.target.parentNode && event.target.parentNode.classList.contains('player')) {
                    event.preventDefault();
                    const playerName = event.target.parentNode.dataset.playerName;
                    const action = event.target.parentNode.dataset.action;
                    const server = event.target.parentNode.dataset.serverId;
                    const reason = prompt("{{ __('Please provide reason for this action:') }}");
                    
                    if (reason !== null && reason.trim() !== "") {
                        playerAction(playerName, action, server, reason);
                    }
                }
            });
            
            function playerAction(playerName, action, serverId, reason) {
                const formData = new FormData();
                formData.append('name', playerName);
                formData.append('action', action);
                formData.append('serverId', serverId);
                formData.append('reason', reason);
                
                fetch('{{ url("/players/action") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Remove the player row from the table
                        const playerRow = document.getElementById(playerName);
                        if (playerRow) {
                            playerRow.remove();
                        }
                        // Show success message (you can use toastr or another notification system)
                        alert(`{{ __('Player') }} ${action} {{ __('successful') }}.`);
                    } else {
                        alert('{{ __("Failed to perform action") }}');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('{{ __("Failed to perform action") }}');
                });
            }
            
            // Load servers when page loads
            document.addEventListener('DOMContentLoaded', function() {
                loadServers();
            });
        </script>
    </x-slot>
</x-base-layout> 