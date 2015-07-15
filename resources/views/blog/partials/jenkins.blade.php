@if($download_server && count($jenkins) > 0)
    <div class="grid-container">
        <div class="grid-100 grid-parent">
            <div class="ui items">
                @foreach($jenkins as $build)
                    <div class="grid-25">
                        <div class="ui segment">
                            <div class="item">
                                <div class="content text-center">
                                    <strong>
                                        {{ explode(' ', $build->fullDisplayName)[0] }}
                                        {{ Jenkins::getBuildVersion(Jenkins::getJobFromBuild($build), $build->number) }}
                                    </strong>

                                    <div class="description">
                                        Last updated <span
                                                title="{{ Carbon::createFromTimestampUTC(str_limit($build->timestamp, 10)) }}">
                                        {{ Carbon::createFromTimestampUTC(str_limit($build->timestamp, 10))->diffForHumans() }}
                                    </span>
                                    </div>
                                    <div class="top-margin ten">
                                        <a href="{{ $build->url }}" class="ui button icon labeled mini"><i
                                                    class="icon book"></i> Build Details</a>
                                        <a href="{{ action('Download\JenkinsController@download', ['job'=>explode(' ', $build->fullDisplayName)[0], 'build'=>$build->number]) }}"
                                           class="ui button green mini labeled icon"><i class="icon download"></i>
                                            Download</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif