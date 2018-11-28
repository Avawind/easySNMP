/**
 *------------------By Avawind  22/11/2018--------------------------
 * - Resumé : Script génération dynamique de contenu dashboard
 *
 * - Fonctionnement :  Interroge cycliquement l'api de rabbitmq Management plugin
 *                     via le seveur web. Mise à disopsition d'un lien /data/dashboard pour
 *                     récuperer les données en json
 *
 *  1- First Call
 *      Construit dynamiquement les div contenant les informations de Queues
 *      Initialise le graphique des requêtes en queues
 *
 *  2- Update
 *      Interroge cycliquement l'api mise à disposition sur /data
 *      Parse le JSON et update les datasets du graphique
 *
 * */

/**
 * Useful functions for the next part
 */
function sleep(milliseconds) {
    var start = new Date().getTime();
    for (var i = 0; i < 1e7; i++) {
        if ((new Date().getTime() - start) > milliseconds) {
            break;
        }
    }
}

function checkTime(i) {
    if (i < 10) {
        i = "0" + i;
    }
    return i;
}

function now() {
    var now = new Date();
    var h = now.getHours();
    var m = now.getMinutes();
    var s = now.getSeconds();
    m = checkTime(m);
    s = checkTime(s);
    return h + ":" + m + ":" + s;
}

function getRandomRgb() {
    var num = Math.round(0xffffff * Math.random());
    var r = num >> 16;
    var g = num >> 8 & 255;
    var b = num & 255;
    return 'rgb(' + r + ', ' + g + ', ' + b + ')';
}

/**
 *
 * Parameters
 */
//Srv ip
var ip = location.host;

/**
 * Queues Chart
 */
//Setup queues color
var snmp_color = getRandomRgb();
var icmp_color = getRandomRgb();
//Queues Chart
//Retrieve chart's container element
var ctx = document.getElementById("queue_graph").getContext('2d');
//Load Chart
var chartMessageQueued = Chart.Line(ctx, {
    data: {
        labels: [now()],
        datasets: [{
            label: 'SNMP Requests Stacked',
            borderColor: snmp_color,
            backgroundColor: snmp_color,
            fill: false,
            data: [],
            yAxisID: 'y-axis-1'
        }, {
            label: 'ICMP Requests Stacked',
            borderColor: icmp_color,
            backgroundColor: icmp_color,
            fill: false,
            data: [],
            yAxisID: 'y-axis-1'
        }]
    },
    options: {
        responsive: true,
        hoverMode: 'index',
        stacked: false,
        scales: {
            yAxes: [{
                type: 'linear',
                display: true,
                position: 'left',
                id: 'y-axis-1',
                ticks: {
                    suggestedMin: 0,
                    suggestedMax: 20
                }
            }]
        },
        elements: {
            point: {
                radius: 0
            }
        }
    }
});
/**
 * Devices Chart
 */
//Retrieve chart's container element
var ctx_1 = document.getElementById("device_status").getContext('2d');
//Load Chart
var deviceStatusChart = new Chart(ctx_1, {
    type: 'doughnut',
    data: {
        labels: ['Online', 'Offline'],
        datasets: [{
            label: 'Hosts Status',
            data: [],
            backgroundColor: [
                'rgb(40,167,69)',
                'rgb(220,53,69)'
            ]
        }]
    },
    options: {}
});

/**
 * Let's do the trick :
 * Initial Request
 */
//Retrieve the <div> element when loading dashboard page
var content = document.getElementById("queues_info");
//XMLHttpRequest initialRequest when page is loaded
var initialRequest = new XMLHttpRequest();
//Open a new connection, using the GET request on the URL endpoint
initialRequest.open('GET', 'https://' + ip + '/app_dev.php/data/dashboard', true);
//Send get request to api an retrieve the data :
initialRequest.onload = function () {
    //Parse JSON data
    var response = this.response;
    var data = JSON.parse(response);
    if (initialRequest.status >= 200 && initialRequest.status < 400) {
        //Process data
        for (var queue in data) {
            if (queue !== "devices") {
                //Create content pattern with bootstrap
                //Div
                var container = document.createElement("div");
                container.classList.add("col-lg-12", "col-md-12", "col-sm-12", "mb-3");
                //Card
                var card = document.createElement("div");
                card.classList.add("card", "bg-light", "card-consumer-status-" + queue);
                //Card Style
                var consumer_status;
                if (data[queue].consumer_isAlive) {
                    card.classList.add("border-success");
                    consumer_status = "Running";
                } else {
                    card.classList.add("border-danger");
                    consumer_status = "Down";
                }
                //CardHeader
                var cardHeader = document.createElement("div");
                cardHeader.classList.add("card-header", "header-" + queue);
                if (!data[queue].consumer_isAlive) {
                    cardHeader.innerHTML = '<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2">' +
                        '        <div>' + queue + '</div>' +
                        '        <div class="btn-toolbar btn-' + queue + ' btn-notAlive">' +
                        '            <a href="/app_dev.php/Consumer/Start/' + queue + '" class="btn btn-sm btn-outline-secondary"' +
                        '               role="button"><span data-feather="play"></span> Start Consumer</a>' +
                        '        </div>' +
                        '    </div>';
                } else {
                    cardHeader.innerHTML = '<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2">' +
                        '        <div>' + queue + '</div>' +
                        '        <div class="btn-toolbar btn-' + queue + ' btn-isAlive">' +
                        '            <a href="/app_dev.php/Consumer/Stop/' + queue + '" class="btn btn-sm btn-outline-secondary"' +
                        '               role="button"><span data-feather="pause"></span> Stop All Consumers</a>' +
                        '        </div>' +
                        '        <div class="btn-toolbar btn-' + queue + ' btn-isAlive">' +
                        '           <a href="/app_dev.php/Consumer/Start/' + queue + '" class="btn btn-sm btn-outline-secondary"' +
                        '           role="button"><span data-feather="plus"></span> Add Consumer</a>' +
                        '        </div>' +
                        '    </div>';
                }
                //Card Body
                var cardBody = document.createElement("div");
                cardBody.classList.add("card-body");
                var cardBodyContent = document.createElement("div");
                cardBodyContent.innerHTML = '<b> Satus : </b>' + data[queue].status + '     <b>Node : </b>' + data[queue].node + '<div id="nbConsumer_' + queue + '" style="display: inline"><b>    Consumer : </b>' + consumer_status + '<b>    Nb : </b>' + data[queue].nb_consumer +'</div>';

                //Append Child in th right order
                cardBody.appendChild(cardBodyContent);
                card.appendChild(cardHeader);
                card.appendChild(cardBody);
                container.appendChild(card);
                content.appendChild(container);
                //reload icons
                feather.replace();
            }

        }

        //Queue Chart : Init chart
        chartMessageQueued.data.labels.push(now());
        chartMessageQueued.data.datasets[0].data.push(data['snmp_request_queue'].queued_messages);
        chartMessageQueued.data.datasets[1].data.push(data['icmp_request_queue'].queued_messages);
        chartMessageQueued.update();

        //Device Chart : Init chart
        deviceStatusChart.data.datasets[0].data.push(data['devices'].online, data['devices'].offline);
        deviceStatusChart.update();
    }
};
//Send request
initialRequest.send();


/**
 * Retrieve data dynamically and update dahsboard
 */
function updateDashboard() {
    // XMLHttpRequest periodicRequest when page is ready
    var periodicRequest = new XMLHttpRequest();
    //Setup request
    periodicRequest.open('GET', 'https://' + ip + '/app_dev.php/data/dashboard', true);
    //Send get request to api an retrieve the data :
    periodicRequest.onload = function () {

        //Parse JSON data
        var response = this.response;
        var data = JSON.parse(response);
        if (periodicRequest.status >= 200 && periodicRequest.status < 400) {
            //Process data
            //Queue Chart
            chartMessageQueued.data.labels.push(now());
            chartMessageQueued.data.datasets[0].data.push(data['snmp_request_queue'].queued_messages);
            chartMessageQueued.data.datasets[1].data.push(data['icmp_request_queue'].queued_messages);
            chartMessageQueued.update();
            //Devices Chart
            deviceStatusChart.data.datasets[0].data[0] = data['devices'].online;
            deviceStatusChart.data.datasets[0].data[1] = data['devices'].offline;
            deviceStatusChart.update();
            //Card divs
            for (var queue in data) {
                if (queue !== "devices") {
                    var card = document.getElementsByClassName("card-consumer-status-" + queue);
                    var cardHeader = document.getElementsByClassName("header-" + queue);
                    var cardBody = document.getElementById("nbConsumer_" + queue);
                    if (data[queue].consumer_isAlive) {
                        //Border
                        if (!card[0].classList.contains("border - success")) {
                            card[0].classList.remove("border-danger");
                            card[0].classList.add("border-success");
                        }
                        //Buttons
                        cardHeader[0].innerHTML = '<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2">' +
                            '        <div>' + queue + '</div>' +
                            '        <div class="btn-toolbar btn-' + queue + ' btn-isAlive">' +
                            '            <a href="/app_dev.php/Consumer/Stop/' + queue + '" class="btn btn-sm btn-outline-secondary"' +
                            '               role="button"><span data-feather="pause"></span> Stop All Consumers</a>' +
                            '        </div>' +
                            '        <div class="btn-toolbar btn-' + queue + ' btn-isAlive">' +
                            '           <a href="/app_dev.php/Consumer/Start/' + queue + '" class="btn btn-sm btn-outline-secondary"' +
                            '           role="button"><span data-feather="plus"></span> Add Consumer</a>' +
                            '        </div>' +
                            '    </div>';
                        //Body nb
                        cardBody.innerHTML = '<b>    Consumer : </b> Running <b>    Nb : </b>' + data[queue].nb_consumer;

                    } else {
                        //Border
                        if (!card[0].classList.contains("border-danger")) {
                            card[0].classList.remove("border-success");
                            card[0].classList.add("border-danger");
                        }
                        //Buttons
                        cardHeader[0].innerHTML = '<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2">' +
                            '        <div>' + queue + '</div>' +
                            '        <div class="btn-toolbar btn-' + queue + ' btn-notAlive">' +
                            '            <a href="/app_dev.php/Consumer/Start/' + queue + '" class="btn btn-sm btn-outline-secondary"' +
                            '               role="button"><span data-feather="play"></span> Start Consumer</a>' +
                            '        </div>' +
                            '    </div>';
                        //Body nb
                        cardBody.innerHTML = '<b>    Consumer : </b> Down <b>    Nb : </b>' + data[queue].nb_consumer;
                    }
                }
            }

            //Log to console
            console.log("Request send & arrays pushed - Chart updated ! - SNMP : " + data['snmp_request_queue'].queued_messages + " - ICMP : " + data['icmp_request_queue'].queued_messages);
        }
    };
    //Send request
    periodicRequest.send();
}

/**
 * Update content
 */
//Wait page ready
window.onload = function () {
    var intervalID = setInterval(function () {
        updateDashboard();
        updateAlerts();
    }, 5000);
};