/* When the user clicks on the button,
toggle between hiding and showing the dropdown content */

//~ filesToggle = document.getElementById("tb-nav-file-toggle");
//~ filesToggle.onclick = function() {
  //~ document.getElementById("tb-nav-file-list").classList.toggle("dropdown-show");
//~ }
function tb_hook(id, fn) {
  var tb = document.getElementById(id);
  if (tb === null) {
    console.log("MISSING OBJECT: "+id);
    return;
  }
  tb.onclick = fn;
}

function tb_enable_dropdown(tid, did) {
  var tb = document.getElementById(tid);
  if (tb === null) {
    console.log("MISSING OBJECT: "+tid);
    return true;
  }
  var dl = document.getElementById(did);
  if (dl === null) {
    console.log("MISSING OBJECT: "+did);
    return true;
  }

  tb.onclick = function() {
    dl.classList.toggle("dropdown-show");
  }
}
tb_enable_dropdown("tb-nav-file-toggle", "tb-nav-file-list");


// Close the dropdown menu if the user clicks outside of it
window.onclick = function(event) {
  if (!event.target.matches('.dropbtn')) {
    var dropdowns = document.getElementsByClassName("dropdown-content");
    var i;
    for (i = 0; i < dropdowns.length; i++) {
      var openDropdown = dropdowns[i];
      if (openDropdown.classList.contains('show')) {
        openDropdown.classList.remove('show');
      }
    }
  }
}

function isHidden(el) {
  var style = window.getComputedStyle(el);
  return (style.display === 'none')
}

function tb_isHidden(id,value) {
  var tb = document.getElementById(id);
  if (tb === null) {
    console.log("MISSING OBJECT: "+id);
    return true;
  }
  return isHidden(tb);
}

function tb_display(id,value) {
  var tb = document.getElementById(id);
  if (tb === null) {
    console.log("MISSING OBJECT: "+id);
    return;
  }
  tb.style.display = value;
}

function tb_toggle_tool(id) {
  var tb = document.getElementById(id);
  if (tb === null) {
    console.log("MISSING OBJECT: "+id);
    return;
  }
  if (isHidden(tb)) {
    tb.style.display = "inline";
  } else {
    tb.style.display = "none";
  }
}

