/**
 *
 * Script génération dynamique de contenu
 * By TF le 22/11/2018
 *
 * */

//Useful functions for the next part
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
    return h+":"+m+":"+s;
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
var ctx = document.getElementById("graph").getContext('2d');
//Load Chart
var chartMessageQueued = Chart.Line(ctx, {
    data: {
        labels: [now()],
        datasets: [{
            label: 'SNMP Request',
            borderColor: snmp_color,
            backgroundColor: snmp_color,
            fill: false,
            data: [],
            yAxisID: 'y-axis-1'
        }, {
            label: 'ICMP Request',
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
                    suggestedMax: 100
                }
            }]
        },
        elements: {
            point:{
                radius: 0
            }
        }
    }
});
/**
 * Let's do the trick :
 * Initial Request
 */
//Retrieve the principal <div> when loading dashboard page
var content = document.getElementById("content");
//XMLHttpRequest initialRequest when page is loaded
var initialRequest = new XMLHttpRequest();
//Open a new connection, using the GET request on the URL endpoint
initialRequest.open('GET', 'https://' + ip + '/app_dev.php/data', true);
//Send get request to api an retrieve the data :
initialRequest.onload = function () {
    //Parse JSON data
    var response = this.response;
    var data = JSON.parse(response);
    if (initialRequest.status >= 200 && initialRequest.status < 400) {
        //Process data
        for (var queue in data) {
            //Create content pattern with bootstrap
            var container = document.createElement("div");
            container.classList.add("col-lg-12", "col-md-12", "col-sm-12");
            var card = document.createElement("div");
            card.classList.add("card", "bg-light", "mb-1");
            var cardHeader = document.createElement("div");
            cardHeader.classList.add("card-header");
            var cardHeaderContent = document.createTextNode("Queue : " + queue);
            cardHeader.appendChild(cardHeaderContent);
            var cardBody = document.createElement("div");
            cardBody.classList.add("card-body");
            if (data[queue].consumer_isAlive) {
                card.classList.add("border-success");
                var consumer_status = "Running";
            } else {
                card.classList.add("border-danger");
                var consumer_status = "Down";
            }
            var cardBodyContent = document.createElement("div");
            cardBodyContent.innerHTML = '<b> Satus : </b>' + data[queue].status + '     <b>Node : </b>' + data[queue].node + '<b>    Consumer : </b>' + consumer_status;

            cardBody.appendChild(cardBodyContent);
            card.appendChild(cardHeader);
            card.appendChild(cardBody);
            container.appendChild(card);
            content.appendChild(container);
        }

        //Set first value
        chartMessageQueued.data.labels.push(now());
        chartMessageQueued.data.datasets[0].data.push(data['snmp_request_queue'].queued_messages);
        chartMessageQueued.data.datasets[1].data.push(data['icmp_request_queue'].queued_messages);
        console.log("initial update : " + data['snmp_request_queue'].queued_messages);
        chartMessageQueued.update();
    }
};
//Send request
initialRequest.send();


/**
 * Retrieve data dynamically and draw chart
 */
function updateChart() {
    // XMLHttpRequest initialRequest when page is loaded
    var periodicRequest = new XMLHttpRequest();
    periodicRequest.open('GET', 'https://' + ip + '/app_dev.php/data', true);
    //Send get request to api an retrieve the data :
    periodicRequest.onload = function () {

        //Parse JSON data
        var response = this.response;
        var data = JSON.parse(response);
        if (periodicRequest.status >= 200 && periodicRequest.status < 400) {
            //Process data
            chartMessageQueued.data.labels.push(now());
            chartMessageQueued.data.datasets[0].data.push(data['snmp_request_queue'].queued_messages);
            chartMessageQueued.data.datasets[1].data.push(data['icmp_request_queue'].queued_messages);
            chartMessageQueued.update();
            console.log("Request send & arrays pushed - Chart updated !");
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
        updateChart();
    }, 5000);
};