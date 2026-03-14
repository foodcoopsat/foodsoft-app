/* ===================================================================

   2024-02-19

=====================================================================*/


// ==== input fields with +, -, 0 buttons ===========================0


function updateInput(inputElement, xmin, xmax) {
    const x = parseInt(inputElement.value);
    if (isNaN(x)) {
        alert("Keine gültige Zahl: '" + inputElement.value + "'");
        inputElement.value = inputElement.getAttribute("data-reset-value");
        return;
    }
    const xf = parseFloat(inputElement.value);
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
    const e = document.getElementById(id);
    e.setAttribute('data-action', 'increment');
    let x = parseInt(e.value);
    x += stepsize(x);
    if (x > xmax) x = xmax;
    e.value = x;
    if (e.onchange instanceof Function)
        e.onchange();
}

function decrement(id, xmin = 0) {
    const e = document.getElementById(id);
    e.setAttribute('data-action', 'decrement');
    let x = parseInt(e.value);
    x -= stepsize(x - 1);
    if (x < xmin)
        x = xmin;
    e.value = x;
    if (e.onchange instanceof Function)
        e.onchange();
}

function zero(id, button) {
    const e = document.getElementById(id); // input element
    const a = e.getAttribute("data-article");
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
        //console.log("disable " + id + " => " + id.replace("null","reset"));
        document.getElementById(id + "-reset").disabled = true;
    } else if (button) { // e.value==0
        e.setAttribute('data-action', 'reset');
        e.value = e.getAttribute("data-reset-value");
        button.innerHTML = button.getAttribute("data-text-0");
        //console.log(button.innerHTML);
        if (e.onchange instanceof Function) {
            console.log("zero ==0 > onchange");
            e.onchange();
        }
        document.getElementById(id + "-reset").disabled = false;
    } else {
        e.setAttribute('data-action', '');
    }
}

function update_zero_button(id) {
    // if the input value is changed externally e.g. by ajax synchronisation, 
    // this function has to be called to obtain the correct button text
    const input = document.getElementById(id);
    const button = document.getElementById(id + "-null");
    if (button) {
        button.innerHTML = button.getAttribute("data-text-" + (input.value > 0 ? 0 : 1));
        // console.log("  button innerHtml: " + "data-text-" + (input.value > 0 ? 0 : 1) + " " + button.getAttribute("data-text-" + (input.value > 0 ? 1 : 0)))
    }
    else {
        console.log("  button not found: " + id + "-null");
    }
}

function my_clear(id) {
    const e = document.getElementById(id);
    e.setAttribute('data-action', 'clear');
    //console.log("clear: " + id + " " + e.value);
    e.value = null;
    //e.reset();
}

function my_reset(id, value) {
    console.log("reset");
    const e = document.getElementById(id);
    e.setAttribute('data-action', 'reset');
    e.value = value;
    if (e.onchange instanceof Function) {
        e.onchange();
        console.log("reset > onchange");
    }
}

