<?php namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Tools\Misc\Jenkins;
use App\Tools\Models\Blog;
use App\Tools\Models\ServerSettings;
use App\Tools\Models\User;
use App\Tools\Queries\ServerSetting;

class PageController extends Controller {

    /**
     * Show the application welcome screen to the user.
     *
     * @return Response
     */
    public function index() {
        $blog = Blog::latest()->first();

        if (!$blog) {
            return view('blog.index', [
                'jenkins' => ServerSetting::get('jenkins') ? Jenkins::getStableBuilds(null, 4) : null
            ]);
        }

        return view('blog.index', static::retrieve($blog));
    }

    private static function retrieve($blog) {
        if ($blog instanceof Blog) {
            $users = User::all();
            $displaynames = [];

            foreach ($users as $user)
                $displaynames[$user->id] = $user->displayname;

            ServerSettings::firstOrCreate(['key'=>'blogviews'])->increment('value');

            return [
                'blog' => $blog,
                'list' => Blog::latest()->take(4)->get(),
                'users' => $displaynames,
                'jenkins' => ServerSetting::get('jenkins') ? Jenkins::getStableBuilds() : null,
            ];
        }
    }

    public function getBlog($id) {
        $blog = Blog::find($id);

        if (!$blog)
            return abort(404);

        return view('blog.index', static::retrieve($blog));
    }
}