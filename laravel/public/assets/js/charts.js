$(function () {
    $.get('/statistics/getTotalServers', function(data){
        var players = [];
        var servers = [];
        $.each(data, function(i, item){
            var timestamp = Date.toTimestamp(item.timestamp);
            players.push(
                array(timestamp, parseInt(item.players))
            );
            servers.push(
                array(timestamp, parseInt(item.servers))
            );
        });

        $('#serversGraph').highcharts({
            chart: {
                type: 'area'
            },
            title: {
                text: null
            },
            subtitle: {
                text: null
            },
            xAxis: {
                type: 'datetime',
                tickWidth: 0,
                gridLineWidth: 1,
                labels: {
                    align: 'left',
                    x: 3,
                    y: -3
                }
            },
            tooltip: {
                pointFormat: '<b>{point.y:,.0f} {series.name}</b>'
            },
            plotOptions: {
                area: {
                    lineWidth: 1,
                    marker: {
                        enabled: false
                    },
                    shadow: false,
                    states: {
                        hover: {
                            lineWidth: 1
                        }
                    },
                    threshold: null
                }
            },
            series: [{
                name: 'Players',
                data: players
            },{
                name: 'Servers',
                data: servers
            }]
        });
    }, 'json');
});

Date.toTimestamp = function(s)
{
    s = s.split(/[-A-Z :\.]/i);
    var d = new Date(Date.UTC(s[0], --s[1], s[2], s[3], s[4], s[5]));
    return Math.round(d.getTime());
}