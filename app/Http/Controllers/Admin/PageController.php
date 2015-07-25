<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Paste;
use App\Models\ShortUrl;
use App\Models\Task;
use App\Models\User;
use App\Tools\Misc\GitHub;
use App\Tools\Misc\Jenkins;
use App\Tools\Misc\LaravelLogViewer;
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
            view()->share('alerts', auth()->user()->alerts);
            view()->share('alert_bar', ServerSetting::get('alert_bar'));
        }

        $this->request = $request;
    }

    public function index() {
        return view('admin.index', [
            'title'          => 'Dashboard',
            'issues'         => count(GitHub::getIssues()),
            'blogs'          => count(Blog::all()),
            'tasks'          => new Task,
            'jenkins'        => Jenkins::getAllBuilds(null, 3),
            'updateMins'     => $this->updateMins,
            'github'         => GitHub::getEventsFeed(),
            'myTasks'        => count(auth()->user()->tasks),
            'closedTasks'    => count(Task::whereCompleted(true)->get()),
            'pastes'         => count(Paste::all()),
            'urls'           => count(ShortUrl::all()),
            'downloads'      => Jenkins::getBuildDownloadCount(),
            'jenkins_online' => Domain::remoteFileExists('http://ci.battleplugins.com'),
            'log'            => LaravelLogViewer::getPaginated(null, 1, 1)[0]
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
            'title'        => 'Manage Content',
            'jenkins'      => ServerSetting::get('jenkins'),
            'registration' => ServerSetting::get('registration'),
            'footer'       => ServerSetting::get('footer'),
            'alert_bar'    => ServerSetting::get('alert_bar'),
            'comment_feed' => ServerSetting::get('comment_feed')
        ]);
    }

    public function serverStats() {
        return view('admin.partials.dashboard.serverstats', [
            'serverData' => Cache::get('serverData'),
            'updateMins' => $this->updateMins
        ]);
    }

    public function github() {
        return view('admin.github', [
            'title'   => 'GitHub Information',
            'github'  => GitHub::getEventsFeed(25),
            'members' => GitHub::getOrgMembers(),
            'repos'   => GitHub::getRepositories()
        ]);
    }

    public function logs($l = null, $curPage = 1, $perPage = 15) {
        return view('admin.logs', [
            'title'        => 'Logs',
            'logs'         => LaravelLogViewer::getPaginated($l, $curPage, $perPage),
            'files'        => LaravelLogViewer::getFiles(true),
            'current_file' => LaravelLogViewer::getFileName(),
            'perPage'      => $perPage,
            'url'          => $this->request->url()
        ]);
    }

    public function shortUrls($curPage = 1, $perPage = 35) {
        $urls = ShortUrl::orderBy('last_used', 'desc')->get();
        $urls = new LengthAwarePaginator($urls->forPage($curPage, $perPage), $urls->count(), $perPage, $curPage);

        return view('admin.shorturls', [
            'title'   => 'Short URLs',
            'urls'    => $urls,
            'perPage' => $perPage
        ]);
    }
}