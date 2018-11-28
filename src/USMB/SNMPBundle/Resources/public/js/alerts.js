/**
 *------------------By Avawind  28/11/2018--------------------------
 * - Resumé : Script génération dynamique des alertes
 *
 * - Fonctionnement :  Interroge cycliquement l'api de rabbitmq Management plugin
 *                     via le seveur web. Mise à disopsition d'un lien /data/alerts pour
 *                     récuperer les données en json
 *
 *  1- First Call
 *      Définition des données
 *
 *  2- Update
 *      Interroge cycliquement l'api mise à disposition sur /data/alerts
 *      Parse le JSON et update les alerts de la barre de naviguation
 *
 *
 *
 * Parameters :
 */
//Srv ip
var ip = location.host;
//Devices data array
var dataDevices = [];
//Retrieve the <ul> element when loading page
var ulMenu = document.getElementById("alerts-list");

/**
 * Let's do the trick :
 * Request's setup
 */
function updateAlerts() {
    // XMLHttpRequest periodicRequest when page is ready
    var periodicRequest = new XMLHttpRequest();
    //Setup request
    periodicRequest.open('GET', 'https://' + ip + '/app_dev.php/data/alerts', true);
    //Send get request to api an retrieve the data :
    periodicRequest.onload = function () {
        //Parse JSON data
        var response = this.response;
        var data = JSON.parse(response);

        if (periodicRequest.status >= 200 && periodicRequest.status < 400) {
            //Process data & create alert <li>
            for (var device in dataDevices) {
                if (!(dataDevices[device].status === data[device].status)) {
                    var li = document.createElement("li");
                    li.classList.add("alert", "alert-dismissible", "fade", "show", "small");
                    li.style.marginBottom = "0.5rem";
                    if (data[device].status) {
                        li.classList.add("alert-success");
                        li.innerHTML = '<a href="/app_dev.php/Device/' + dataDevices[device].id + '" class="alert-link"><strong>' + device + '</strong></a> is up !   ' +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close" style="padding-bottom: 0;">\n' +
                            '    <span aria-hidden="true">&times;</span>\n' +
                            '  </button>';
                    } else {
                        li.classList.add("alert-danger");
                        li.innerHTML = '<a href="/app_dev.php/Device/' + dataDevices[device].id + '" class="alert-link" style="padding-bottom: 0;"><strong>' + device + '</strong></a> is down !  ' +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">\n' +
                            '    <span aria-hidden="true">&times;</span>\n' +
                            '  </button>';
                    }
                    ulMenu.appendChild(li);
                    console.log("li created - " + dataDevices[device].name);
                }
            }
            dataDevices = data;
            console.log("End");
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
        updateAlerts();
    }, 10000);
};