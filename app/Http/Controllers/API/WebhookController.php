<?php

namespace App\Http\Controllers\API;

use App\Webhook;
use Auth;
use Illuminate\Http\Request;

/**
 * Class WebhookController
 * @package App\Http\Controllers
 */
class WebhookController extends Controller {

	/**
	 * @param Request $request
	 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	public function create(Request $request){
		$url = $request->input('url');
		$event = $request->input('event');

		if(!$event || $event == -1)
			return redirect('/');

		$uid = Auth::user()->id;

		if(!$url)
			Webhook::where('user', $uid)->where('event', $event)->delete();
		else {
			$query = Webhook::where('user', $uid)->where('event', $event);
			$exists = $query->first();
			if($exists) {
				$query->update(['url'=>$url]);
			} else {
				Webhook::create([
					'user' => $uid,
					'url' => $url,
					'event' => $event
				]);
			}
		}

		return redirect('/');
	}

}