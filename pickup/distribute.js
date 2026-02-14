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