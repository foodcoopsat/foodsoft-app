function show_more_orders() {
    console.log("show_more_orders");
    document.getElementById("show-more").style = "display:none";
    // let i = 0;
    for (const e of document.querySelectorAll(".past-order")) {
        // unvollständig: document.getElementsByClassName("past-order")
        // console.log(i++ + e.tagName + ": " + e.innerHTML);
        e.classList.remove("past-order");
    }
}



// ==== ajax for checkbox sync ==========================================

function page_log(text) {
    // document.getElementById("log").innerHTML += text;
}


function ajaxOnChange(element) {
    let xmlhttp = new XMLHttpRequest();
    let value = "";
    let sub_ids = [];
    if (element.type == "checkbox") {
        value = (element.checked ? true : false);
    } else { // element.type == "input"
        value = element.value;
        // console.log("   element type: " + element.type + ", value: " + value);
        if (element.id.search("total") != -1) { // input-131484-total_received, input-132089-total_weight_received
            // determine sub_ids of input elements for individual ordergroups
            // this is used in read_distributions() in common.php to set the amount of all ordergoups to 0 if the total amount is 0
            let id_article = element.id.split("-")[1];
            let classname = input_id2("received", id_article);
            for (let e of document.getElementsByClassName(classname)) {
                sub_ids.push(e.id.split("-")[1]);
            }
            console.log("  sub ids with " + classname + ": " + sub_ids);
        }
    }
    let url = "?" +
        [
            "app=distribute",
            "action=ajax-write",
            "ajax-data=" + JSON.stringify({
                date: currentDateAndTimeString(),
                session_id: window.my_session_id,
                username: window.username,
                element_id: element.id,
                value: value,
                sub_ids: sub_ids,
                // request: window.n_ajax_requests++
            })
        ].join("&");
    console.log("get url with join: " + url);
    page_log(currentDateAndTimeString() + " " + url + "\n");
    xmlhttp.open("GET", url, true);
    xmlhttp.onreadystatechange = function () {
        page_log(xmlhttp.readyState + "w ");
        // Check if the request is complete
        if (xmlhttp.readyState === 4) {
            if (xmlhttp.status === 200) {
                // Request was successful
                page_log("Success: " + xmlhttp.responseText + "\n");
            } else {
                // Request failed
                page_log("Error: " + xmlhttp.status + " " + xmlhttp.statusText + "\n");
            }
        }
    };
    xmlhttp.send();
}


function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function update(xmlhttp) {
    let sleeptime = 0;
    window.unprocessed_response = true;
    let i = 0;
    while (true) {
        await sleep(1000); // 1000 ms = 1 Sekunde
        document.getElementById("sync-status").innerHTML =
            window.n_ajax_empy_responses + " " +
            sleeptime + "/" + window.ajax_timeout + " " +
            window.n_ajax_events;
        sleeptime += 1;
        // console.log(`update ${i}: ${window.n_ajax_events} events, sleeptime ${sleeptime}, ${window.ajax_idle_time} s idle`);
        if (window.unprocessed_response || sleeptime > window.ajax_timeout + 10) { // sleeptime >= seconds
            //console.log(`update ${i} ${seconds} s: ${window.n_ajax_events} events, ${t} s idle`);
            if (window.n_ajax_empy_responses * window.ajax_timeout >= 600) {
                //alert("Es ist seit 10 Minuten keine Aktivität mehr erfolgt. Möchtest du weitermachen?");
                if (!window.confirm(currentDateAndTimeString() + ": es ist seit mehr als 10 Minuten keine Aktivität mehr erfolgt. Ok um weiterzumachen, Abbrechen/Cancel um neu anzufangen.")) {
                    window.onbeforeunload = null;
                    window.location = document.referrer;
                    break;
                }
                window.n_ajax_empy_responses = 0;
            }
            window.unprocessed_response = false;
            load_events(xmlhttp, window.n_ajax_events);
            sleeptime = 0;
        }
        i++;
    }
}


function start_update(username, ajax_timeout) {
    console.log("start_update('" + username + "')");

    // window.ajax_filename = filename;
    window.n_ajax_events = 0;
    window.n_ajax_requests = 0;
    window.n_ajax_empy_responses = 0;
    window.my_session_id = Math.floor(Math.random() * 100000);
    window.username = username;
    window.ajax_timeout = ajax_timeout;


    xmlhttp = new XMLHttpRequest()
    xmlhttp.onreadystatechange = function () {
        page_log(xmlhttp.readyState + "r ");
        if (xmlhttp.readyState == 4) {
            if (xmlhttp.status == 200) {
                window.unprocessed_response = true;
                page_log("Success: " + xmlhttp.responseText + "\n");
                process_ajax_response(xmlhttp.responseText, window.my_session_id);
            } else {
                // Request failed
                page_log("Error: " + xmlhttp.status + " " + xmlhttp.statusText + "\n");
            }
        }
    };
    update(xmlhttp);
}

function load_events(xmlhttp = null, from_event = 0) {
    console.log("load_events(from_event: " + from_event + ")");

    if (xmlhttp === null) {
        console.log("  creating xmlhttp");
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                process_ajax_response(xmlhttp.responseText, null);
            }
        };
    }
    let qs =
        [
            "app=distribute",
            "action=ajax-read",
            "from_event=" + window.n_ajax_events,
            // "session_id=" + window.my_session_id,
            // "request=" + window.n_ajax_requests++
        ].join("&");
    console.log("  sending " + qs);
    xmlhttp.open("GET", "?" + qs, true);
    xmlhttp.send();
}



function process_ajax_response(text, my_session_id) {
    const events = text.split("\n").filter(n => n); // filter: Leerzeilen entfernen
    if (events.length == 0) {
        window.n_ajax_empy_responses++;
    }
    else {
        window.n_ajax_empy_responses = 0;
    }
    // page_log("process_ajax_response: n events: " + n + " n_ajax_empy_responses: " + window.n_ajax_empy_responses + "\n");
    console.log("events: " + events.length + "\n" + text);
    for (const event of events) {
        const d = JSON.parse(event);

        // element of d used here: 
        //   element_id: element.id,
        //   value: value,
        //   session_id: window.my_session_id,

        // not here used:
        //   date: currentDateAndTimeString(),

        //   username: window.username,
        //   sub_ids: sub_ids,

        // unused:
        //   request: window.n_ajax_requests++

        if (my_session_id === null) {
            // document.getElementById("sync-status").innerHTML = i + "/" + n;
        }
        if (d.session_id == my_session_id) {
            // ignore events from own session: no need to update form element
            // console.log("ignoriert: " + checkbox_id)
        } else {
            // update form elements from changes in other sessions
            e = document.getElementById(d.element_id);
            console.log("processing element id " + d.element_id);
            if (e) {
                console.log("  element tagName: " + e.tagName + " type: " + e.type + " => " + d.value)
                if (e.tagName == "INPUT") {
                    if (e.type == "checkbox") {
                        e.checked = d.value;
                    }
                    else if (e.type == "number") {
                        e.value = d.value.trim();
                        console.log("  setting " + e.id + " to " + e.value); // ", onChange: ");

                        /* e.onchange: "function onchange(event) {
                            updateInput(this,0,15000); 
                            updateTotalReceived(4430,1000, / *update_weight=* / false); 
                            ajaxOnChange(this); }" */

                        //e.dispatchEvent(new Event("change"));
                        // cannot completely replace the following code section, because the onchange code contains more than the update..Received() function 

                        let u = /updateTotalReceived\(([^()]+)\)/.exec(e.onchange);
                        // e.g. u = "updateTotalReceived(4430,128488,1000, / *update_weight=* / false)"
                        // neu       updateTotalReceived(182800, 1000, /*update_weight=*/ true)
                        if (u) {
                            let p = u[1].split(",");
                            //console.log(p); // Array(3) [ "128440", "0", " /*update_weight=*/ true" ]
                            let id_article = p[0];
                            let unit_weight = parseInt(p[1]);
                            let update_weight = true;
                            if (p.length >= 3) update_weight = p[2].indexOf("false") < 0;
                            // console.log("  update_weight: " + update_weight + " [" + p + "]");
                            updateTotalReceived(id_article, unit_weight, update_weight);
                            // updateTotalReceived(p[0], p[1], parseInt(p[2]), update_weight);
                        }

                        u = /updateReceived\(([^()]+)\)/.exec(e.onchange);
                        if (u) {
                            let p = u[1].split(",");
                            //console.log(p); 
                            // Array(3) [ "128432", "/*update_weight=*/ false", ... ]
                            // Array [ "4430", "128431 /*update_weight=true*/ " ]
                            let id_article = p[0];
                            let update_weight = true;
                            if (p.length >= 2) update_weight = p[1].indexOf("false") < 0;
                            let group_id = null;
                            if (p.length >= 3) group_id = p[2];
                            updateReceived(id_article, update_weight, group_id);
                            //updateReceived(p[0], parseInt(p[1]), update_weight);
                        }
                    } else {
                        console.log("  *** unknown type: " + e.type);
                        e.innerHTML = value.trim();
                    }
                } else {
                    console.log("  *** unknown tagName: " + e.tagName);
                }
            }
            else console.log("getElementById failed with " + d.element_id);
        }
        if (my_session_id) {
            window.n_ajax_events++;
        }
    }
    if (my_session_id == null) {
        document.getElementById("sync-status").innerHTML = "ready";
    }
}


function currentDateAndTimeString() {
    const currentDate = new Date();
    return currentDate.toLocaleString();
}