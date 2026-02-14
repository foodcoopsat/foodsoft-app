/* ===================================================================

   2024-02-19

=====================================================================*/


// ==== input fields with +, -, 0 buttons ===========================0


function updateInput(inputElement, xmin, xmax) {
    x = parseInt(inputElement.value);
    if (isNaN(x)) {
        alert("Keine gültige Zahl: '" + inputElement.value + "'");
        inputElement.value = inputElement.getAttribute("data-reset-value");
        return;
    }
    xf = parseFloat(inputElement.value);
    if (x - xf != 0) {
        alert(`Die Eingabe von Zahlen mit Komma (${inputElement.value}) ist nicht möglich - bitte nur ganze Zahlen (ohne Komma) eingeben.`)
        inputElement.value = Math.round(xf); // + round(parseFloat(inputElement.value));
    }
    if (x < xmin) {
        inputElement.value = xmin;
    }
    if (x > xmax) {
        inputElement.value = xmax;
        inputElement.style.backgroundColor = "red";
    }
    else {
        inputElement.style.backgroundColor = "";
    }
}

function stepsize(x) {
    if (x < 100)
        return 1;
    if (x < 200)
        return 2;
    if (x < 500)
        return 5;
    if (x < 1000)
        return 10;
    if (x < 2000)
        return 20;
    if (x < 5000)
        return 50;
    return 100;
}


function increment(id, xmax = 9999) {
    let e = document.getElementById(id);
    e.setAttribute('data-action', 'increment');
    let x = parseInt(e.value);
    let x0 = x;
    x += stepsize(x);
    if (x > xmax) x = xmax;
    e.value = x;
    //console.log("increment " + id + " " + e.value);
    //return;

    if (e.onchange instanceof Function)
        e.onchange();
    if (x0 == 0)
        disable(id, false);
}

function decrement(id, xmin = 0) {
    let e = document.getElementById(id);
    e.setAttribute('data-action', 'decrement');
    let x = parseInt(e.value);
    x -= stepsize(x - 1);
    if (x < xmin)
        x = xmin;
    e.value = x;
    if (e.onchange instanceof Function)
        e.onchange();
    if (x == 0)
        disable(id, true);
}

function zero(id, button) {
    let e = document.getElementById(id); // input element
    let a = e.getAttribute("data-article");
    if (e.value > 0) {
        e.setAttribute('data-action', 'zero');
        if (button) {
            if (!confirm(a + " " + button.innerHTML + ": bist du sicher?")) return;
            button.innerHTML = button.getAttribute("data-text-1");
        } else {
            if (!confirm(a + "Auf Null setzen: bist du sicher?")) return;
        }
        //console.log(button.innerHTML);
        e.value = 0;
        if (e.onchange instanceof Function) {
            console.log("zero >0 > onchange");
            e.onchange();
        }
        disable(id, true);
        //console.log("disable " + id + " => " + id.replace("null","reset"));
        document.getElementById(id + "-reset").disabled = true;
    } else if (button) {
        e.setAttribute('data-action', 'reset');
        e.value = e.getAttribute("data-reset-value");
        button.innerHTML = button.getAttribute("data-text-0");
        //console.log(button.innerHTML);
        if (e.onchange instanceof Function) {
            console.log("zero ==0 > onchange");
            e.onchange();
        }
        disable(id, false);
        document.getElementById(id + "-reset").disabled = false;
    } else {
        e.setAttribute('data-action', '');
    }
}

function my_clear(id) {
    let e = document.getElementById(id);
    e.setAttribute('data-action', 'clear');
    //console.log("clear: " + id + " " + e.value);
    e.value = null;
    //e.reset();
}

function my_reset(id, value) {
    console.log("reset");
    let e = document.getElementById(id);
    e.setAttribute('data-action', 'reset');
    e.value = value;
    if (e.onchange instanceof Function) {
        e.onchange();
        console.log("reset > onchange");
    }
}