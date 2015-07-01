<div class="ui vertical inverted admin menu hide-on-mobile">
    <a href="/" class="item">Home</a>
    <a class="item">User Settings</a>
    @if(Auth::user()->admin)
        <div class="item">
            <div class="header">User Management</div>
            <div class="menu">
                <a class="item">Create User</a>
                <a class="item">Modify User</a>
            </div>
        </div>
    @endif
    <a href="/logout" class="item">Logout</a>
</div>
<div class="ui buttons hide-on-desktop hide-on-tablet">
    <a href="/" class="ui button">Home</a>
    <a class="ui button">User Settings</a>
    <a href="/logout" class="ui button">Logout</a>
</div>