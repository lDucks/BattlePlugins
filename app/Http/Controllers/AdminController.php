<?php
namespace App\Http\Controllers;

use App\Jenkins\Jenkins;
use App\Models\Blog;
use App\Models\Paste;
use App\Models\Permission;
use App\Models\ShortUrl;
use App\Models\Task;
use App\Models\User;
use App\Tools\Misc\GitHub;
use App\Tools\Misc\LaravelLogViewer;
use App\Tools\Misc\Settings;
use App\Tools\Misc\UserSettings;
use App\Tools\Repositories\ShortUrlRepository;
use App\Tools\URL\Domain;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Class AdminController
 * @package App\Http\Controllers\Admin
 */
class AdminController extends Controller {
    /**
     * @var int
     */
    protected $updateMins = 1;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param Request $request
     */
    function __construct(Request $request) {
        $this->middleware('auth.admin');

        if (auth()->check()) {
            view()->share('alerts', auth()->user()->alerts);
            view()->share('alert_bar', Settings::get('alert_bar'));
        }

        $this->request = $request;
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getIndex() {
        return view('admin.index', [
            'title' => 'Dashboard',
            'issues' => count(GitHub::getIssues()),
            'blogs' => count(Blog::all()),
            'tasks' => new Task,
            'jenkins' => Jenkins::getAllBuilds(3),
            'updateMins' => $this->updateMins,
            'github' => GitHub::getEventsFeed(),
            'myTasks' => count(auth()->user()->tasks()->whereCompleted(false)->get()),
            'closedTasks' => count(Task::whereCompleted(true)->get()),
            'pastes' => count(Paste::all()),
            'urls' => count(ShortUrl::all()),
            'downloads' => Jenkins::getBuildDownloadCount(),
            'jenkins_online' => Domain::remoteFileExists('http://ci.battleplugins.com'),
            'log' => LaravelLogViewer::getPaginated(null, 1, 1)[0]
        ]);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getSettings() {
        return view('admin.settings', [
            'title' => 'User Settings'
        ]);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getCreateUser() {
        if (UserSettings::hasNode(auth()->user(), UserSettings::CREATE_USER)) {
            return view('admin.createuser', [
                'title' => 'Create User'
            ]);
        } else
            abort(403);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getModifyUser() {
        if (UserSettings::hasNode(auth()->user(), UserSettings::MODIFY_USER)) {
            return view('admin.modifyuser', [
                'title' => 'Modify User',
                'users' => User::all()
            ]);
        } else
            abort(403);
    }

    /**
     * @param $id
     * @return \Illuminate\View\View
     */
    public function getModifyUserPermissions($id) {
        if (UserSettings::hasNode(auth()->user(), UserSettings::MODIFY_USER)) {
            $user = User::find($id);

            return view('admin.modifyuserpermissions', [
                'title' => 'Modify ' . $user->displayname,
                'user' => $user,
                'nodes' => UserSettings::getPossible()
            ]);
        } else
            abort(403);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getAlerts() {
        if (UserSettings::hasNode(auth()->user(), UserSettings::CREATE_ALERT)) {
            return view('admin.alerts', [
                'title' => 'Create Alert'
            ]);
        } else
            abort(403);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getCms() {
        if (UserSettings::hasNode(auth()->user(), UserSettings::MANAGE_CONTENT)) {
            return view('admin.cms', [
                'title' => 'Manage Content',
                'jenkins' => Settings::get('jenkins'),
                'registration' => Settings::get('registration'),
                'alert_bar' => Settings::get('alert_bar'),
                'comment_feed' => Settings::get('comment_feed')
            ]);
        } else
            abort(403);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getServerStats() {
        return view('admin.partials.dashboard.serverstats', [
            'serverData' => Cache::get('serverData'),
            'updateMins' => $this->updateMins
        ]);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getGithub() {
        return view('admin.github', [
            'title' => 'GitHub Information',
            'github' => GitHub::getEventsFeed(25),
            'members' => GitHub::getOrgMembers(),
            'repos' => GitHub::getRepositories()
        ]);
    }

    /**
     * @param null $l
     * @param int $curPage
     * @param int $perPage
     * @return \Illuminate\View\View
     */
    public function getLogs($l = null, $curPage = 1, $perPage = 15) {
        if (UserSettings::hasNode(auth()->user(), UserSettings::DEVELOPER)) {
            return view('admin.logs', [
                'title' => 'Logs',
                'logs' => LaravelLogViewer::getPaginated($l, $curPage, $perPage),
                'files' => LaravelLogViewer::getFiles(true),
                'current_file' => LaravelLogViewer::getFileName(),
                'perPage' => $perPage,
                'url' => $this->request->url()
            ]);
        } else
            abort(403);
    }

    /**
     * @param int $curPage
     * @param int $perPage
     * @return \Illuminate\View\View
     */
    public function getShortUrls($curPage = 1, $perPage = 35) {
        if (UserSettings::hasNode(auth()->user(), UserSettings::DELETE_SHORTURL)) {
            $urls = ShortUrlRepository::all();
            $urls = new LengthAwarePaginator($urls->forPage($curPage, $perPage), $urls->count(), $perPage, $curPage);

            return view('admin.shorturls', [
                'title' => 'Short URLs',
                'urls' => $urls,
                'perPage' => $perPage
            ]);
        } else
            abort(403);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getApiKeys() {
        if (UserSettings::hasNode(auth()->user(), UserSettings::VIEW_API_KEYS)) {
            return view('admin.apikeys', [
                'title' => 'API Keys',
                'nodes' => Permission::whereNode(UserSettings::USE_API)->get()
            ]);
        } else abort(403);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getPermissionOverview() {
        if (UserSettings::hasNode(auth()->user(), UserSettings::VIEW_PERMISSIONS)) {
            return view('admin.permissionoverview', [
                'title' => "Permission Overview",
                'nodes' => UserSettings::getPossible(),
                'permissions' => new Permission()
            ]);
        } else abort(403);
    }

    /**
     * @return \Illuminate\View\View
     */
    public function getPastes() {
        $pastes = new Paste();

        $type = $this->request->has('strict') ? 'where' : 'orWhere';

        if ($this->request->has('serverid')) {
            $serverid = $this->request->input('serverid');
            $serverid = starts_with($serverid, '#sid') ? $serverid : '#sid' . $serverid;

            $pastes = $pastes->$type('title', 'LIKE', '%' . $serverid . '%');
        }

        if ($this->request->has('slug'))
            $pastes = $pastes->$type('slug', 'LIKE', '%' . $this->request->input('slug') . '%');

        if ($this->request->has('author')) {
            $user = User::whereDisplayname($this->request->input('author'))->first();

            if ($user)
                $pastes = $pastes->$type('user_id', $user->id);
            elseif ($type == 'where')
                $pastes = [];
        }

        if ($pastes)
            $pastes = $pastes->get();

        return view('admin.pastes', [
            'title' => 'Pastes',
            'pastes' => $pastes,
            'inputs' => $this->request
        ]);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postDeletePaste($id) {
        if (UserSettings::hasNode(auth()->user(), UserSettings::DELETE_PASTES_AS_ADMIN)) {
            $paste = Paste::find($id);
            if ($paste)
                $paste->delete();

            return redirect()->back();
        } else abort(403);
    }

    /**
     * @param $slug
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postDeleteShortUrl($slug) {
        if (UserSettings::hasNode(auth()->user(), UserSettings::DELETE_SHORTURL)) {
            ShortUrlRepository::deleteBySlug($slug);
            return redirect()->back();
        } else
            abort(403);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postAlert() {
        if (UserSettings::hasNode(auth()->user(), UserSettings::CREATE_ALERT)) {
            foreach (User::all() as $user) {
                CreateAlert::make($user, $this->request->get('content'));
            }

            return redirect()->back()->with('success', 'Users have been alerted.');
        } else
            abort(403);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postDeleteAlert($id) {
        auth()->user()->alerts()->detach($id);
        return redirect()->back();
    }

    /**
     * @param $setting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postToggleSetting($setting) {
        Settings::toggle($setting);
        return redirect()->back();
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJsonAlerts() {
        $alerts = auth()->user()->alerts;
        return response()->json($alerts);
    }
}