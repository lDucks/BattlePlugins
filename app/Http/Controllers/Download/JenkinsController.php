<?php namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Jobs\UpdateJobs;
use App\Tools\Misc\Jenkins;
use App\Tools\Models\ProductionBuilds;
use App\Tools\URL\Domain;
use Auth;
use Illuminate\Support\Facades\Log;

class JenkinsController extends Controller {

    public function toggleProduction($job) {
        $jobid = ProductionBuilds::find($job);
        if ($jobid)
            $jobid->delete();
        else
            ProductionBuilds::create(['id' => $job]);

        return redirect()->back();
    }

    public function updateJenkins($event = "Manual") {
        Log::info("Updating Jenkins Jobs from webhook event: $event");

        if (Domain::remoteFileExists("http://ci.battleplugins.com"))
            $this->dispatch(new UpdateJobs());
        else
            return "The CI server is offline.";
    }

    public function download($job, $build) {
        $build = Jenkins::getBuild($job, $build);
        $download = Jenkins::downloadJar($build);

        if ($download)
            return redirect($download);
        else {
            Jenkins::deleteBuild($job, $build);
            return redirect()->back();
        }
    }
}
