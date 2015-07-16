<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Tools\Misc\GitHub;
use App\Tools\Misc\Jenkins;
use App\Tools\Misc\LaravelLogViewer;
use App\Tools\Models\Alert;
use App\Tools\Models\Blog;
use App\Tools\Models\BuildDownloads;
use App\Tools\Models\Paste;
use App\Tools\Models\ShortUrl;
use App\Tools\Models\Task;
use App\Tools\Models\User;
use App\Tools\Queries\ServerSetting;
use App\Tools\URL\Domain;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class PageController extends Controller {
    protected $updateMins = 1;

    /**
     * @var Request
     */
    private $request;

    function __construct(Request $request) {
        $this->middleware('auth');
        $this->middleware('auth.admin', ['except' => ['index', 'serverStats', 'github', 'settings', 'logs']]);

        if (auth()->check()) {
            view()->share('alerts', Alert::whereUser(auth()->user()->id)->latest()->get());
            view()->share('alert_bar', ServerSetting::get('alert_bar'));
        }

        $this->request = $request;
    }

    public function index() {
        $displaynames = [];
        foreach (User::all() as $user)
            $displaynames[$user->id] = $user->displayname;

        $userId = auth()->user()->id;

        $dash_jenkins = ServerSetting::get('dash_jenkins');

        $tasks = Task::whereStatus(false);

        $myIssues = 0;
        $issues = 0;
        $closed = 0;

        foreach (GitHub::getIssues() as $issue) {
            if ($issue->assignee && $issue->assignee->login == auth()->user()->displayname && $issue->state == 'open')
                $myIssues++;

            if ($issue->state == 'open')
                $issues++;
            else
                $closed++;
        }

        $closed = $closed + count(Task::where('status', true)->get());
        $myTasks = count($tasks->where('assigned_to', $userId)->get()) + $myIssues;

        $downloads = 0;
        foreach (BuildDownloads::all() as $d)
            $downloads += $d->downloads;

        return view('admin.index', [
            'title' => 'Dashboard',
            'issues' => $issues,
            'blogs' => count(Blog::all()),
            'tasks' => new Task,
            'displaynames' => $displaynames,
            'jenkins' => $dash_jenkins ? Jenkins::getAllBuilds(null, 3) : null,
            'updateMins' => $this->updateMins,
            'github' => GitHub::getEventsFeed(),
            'myTasks' => $myTasks,
            'closedTasks' => $closed,
            'pastes' => count(Paste::all()),
            'urls' => count(ShortUrl::all()),
            'downloads' => $downloads,
            'jenkins_online' => Domain::remoteFileExists('http://ci.battleplugins.com')
        ]);
    }

    public function settings() {
        return view('admin.settings', [
            'title' => 'User Settings'
        ]);
    }

    public function createUser() {
        return view('admin.createuser', [
            'title' => 'Create User'
        ]);
    }

    public function modifyUser() {
        return view('admin.modifyuser', [
            'title' => 'Modify User',
            'users' => User::all()
        ]);
    }

    public function alerts() {
        return view('admin.alerts', [
            'title' => 'Create Alert'
        ]);
    }

    public function cms() {
        return view('admin.cms', [
            'title' => 'Manage Content',
            'jenkins' => ServerSetting::get('jenkins'),
            'dash_jenkins' => ServerSetting::get('dash_jenkins'),
            'registration' => ServerSetting::get('registration'),
            'footer' => ServerSetting::get('footer'),
            'alert_bar' => ServerSetting::get('alert_bar'),
            'comment_feed' => ServerSetting::get('comment_feed')
        ]);
    }

    public function serverStats() {
        $serverData = Cache::get('serverData');

        return view('admin.partials.dashboard.serverstats', [
            'serverData' => $serverData,
            'updateMins' => $this->updateMins
        ]);
    }

    public function github() {
        return view('admin.github', [
            'title' => 'GitHub Information',
            'github' => GitHub::getEventsFeed(25),
            'members' => GitHub::getOrgMembers(),
            'repos' => GitHub::getRepositories()
        ]);
    }

    public function logs($l = null, $curPage = 1, $perPage = 15) {
        if ($l)
            LaravelLogViewer::setFile(base64_decode($l));

        $logs = collect(LaravelLogViewer::all());
        $logs = new LengthAwarePaginator($logs->forPage($curPage, $perPage), $logs->count(), $perPage, $curPage);

        return view('admin.logs', [
            'title' => 'Logs',
            'logs' => $logs,
            'files' => LaravelLogViewer::getFiles(true),
            'current_file' => LaravelLogViewer::getFileName(),
            'perPage' => $perPage,
            'url' => $this->request->url()
        ]);
    }

    public function shortUrls($curPage = 1, $perPage = 35) {
        $urls = ShortUrl::orderBy('last_used', 'desc')->get();
        $urls = new LengthAwarePaginator($urls->forPage($curPage, $perPage), $urls->count(), $perPage, $curPage);

        return view('admin.shorturls', [
            'title' => 'Short URLs',
            'urls' => $urls,
            'urlCount' => count(ShortUrl::all()),
            'perPage' => $perPage
        ]);
    }
}