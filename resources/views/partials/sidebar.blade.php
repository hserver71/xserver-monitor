<div class="sidebar">
    <div class="sidebar-header">
        <h3 class="m-0">LoadBalancer Manager</h3>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="{{ route('clients.index') }}" class="{{ request()->routeIs('clients.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i>
                Clients
            </a>
        </li>
        <li>
            <a href="{{ route('vps.index') }}" class="{{ request()->routeIs('vps.*') ? 'active' : '' }}">
                <i class="fas fa-server"></i>
                VPS
            </a>
        </li>
        <li>
            <a href="{{ route('lines.index') }}" class="{{ request()->routeIs('lines.*') ? 'active' : '' }}">
                <i class="fas fa-network-wired"></i>
                Lines
            </a>
        </li>
    </ul>
</div>