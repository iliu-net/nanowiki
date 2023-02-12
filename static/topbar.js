

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

  tb.onclick = function(e) {
    //~ console.log("WINDOW Click 44");
    var dropdowns = document.getElementsByClassName("dropdown-content");
    var i;
    for (i = 0; i < dropdowns.length; i++) {
      //~ console.log("WINDOW Click 48 and i="+i);
      var openDropdown = dropdowns[i];
      if (openDropdown.id == dl.id) continue;
      if (openDropdown.classList.contains('dropdown-show')) {
        openDropdown.classList.remove('dropdown-show');
      }
    }

    dl.classList.toggle("dropdown-show");
    e.stopPropagation();
    //~ console.log("TB ON Click");
  }
  dl.onclick = function(e) {
    e.stopPropagation();
    //~ console.log("Dropdown Click");
  }
}
tb_enable_dropdown("tb-nav-file-toggle", "tb-nav-file-list");
tb_enable_dropdown("tb-nav-tools-toggle", "tb-nav-tools-dlg");

// Close the dropdown menu if the user clicks outside of it
window.onclick = function(event) {
  //~ console.log("WINDOW Click 42");
  if (!event.target.matches('.dropbtn') && !event.target.matches('.dropdown-content')) {
    //~ console.log("WINDOW Click 44");
    var dropdowns = document.getElementsByClassName("dropdown-content");
    var i;
    for (i = 0; i < dropdowns.length; i++) {
      //~ console.log("WINDOW Click 48 and i="+i);
      var openDropdown = dropdowns[i];
      if (openDropdown.classList.contains('dropdown-show')) {
        openDropdown.classList.remove('dropdown-show');
      }
    }
  }
}
window.onblur = function() {
    //~ console.log("WINDOW Click 44");
    var dropdowns = document.getElementsByClassName("dropdown-content");
    var i;
    for (i = 0; i < dropdowns.length; i++) {
      //~ console.log("WINDOW Click 48 and i="+i);
      var openDropdown = dropdowns[i];
      if (openDropdown.classList.contains('dropdown-show')) {
        openDropdown.classList.remove('dropdown-show');
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

/*****************************************************************/
// Search functionality
function sd_findFile(sid,lid) {
  var sb = document.getElementById(sid);
  if (sb == null) {
    console.log("UNABLE TO FIND "+sid);
    return;
  }
  var term = sb.value.toLowerCase();
  var re = null;
  try {
    re = re = new RegExp(term);
  } catch(e) {
    //~ console.log(e);
  }
  //~ console.log("Search for "+term);

  var ul = document.getElementById(lid);

  var items = ul.getElementsByTagName("li");
  for (var i = 0; i < items.length; ++i) {
    // display:list-item|none
    var a = items[i].getElementsByTagName('a');
    if (a === null || a.length != 1) continue;
    var text = a[0].innerHTML.toLowerCase();
    if (re === null) {
      found = text.indexOf(term);
    } else {
      found = text.search(re);
    }
    if (found != -1) {
      // found it
      items[i].style.display = "list-item";
    } else {
      items[i].style.display = "none";
    }
  }
}
function sd_hookSearchBox(sid,lid,xid) {
  var sb = document.getElementById(sid);
  if (sb == null) {
    console.log("UNABLE TO FIND "+sid);
    return;
  }
  sb.oninput = function() {
    sd_findFile(sid,lid);
  }
  var xb = document.getElementById(xid);
  xb.onclick = function() {
    sb.value = "";
    sd_findFile(sid,lid)
  }
}
sd_hookSearchBox("tb-nav-file-list-searchbox","tb-nav-file-list","tb-nav-file-list-reset-search");
function sd_hookSearchFullText(cid,fid) {
  var cc = document.getElementById(cid);
  if (cc == null) {
    console.log("UNABLE TO FIND "+cid);
    return;
  }
  var ff = document.getElementById(fid);
  if (ff == null) {
    console.log("UNABLE TO FIND "+fid);
    return;
  }
  cc.onclick = function() {
    ff.submit();
  }
}
sd_hookSearchFullText("tb-nav-file-list-content-search","tb-nav-search-form");

function td_hookAttachTool(cid,fid) {
  var cc = document.getElementById(cid);
  if (cc == null) {
    console.log("UNABLE TO FIND "+cid);
    return;
  }
  var ff = document.getElementById(fid);
  if (ff == null) {
    console.log("UNABLE TO FIND "+fid);
    return;
  }
  cc.onclick = function() {
    ff.submit();
  }
}
td_hookAttachTool("tb-nav-tools-attach","tb-nav-tools-attach-frm");

function tb_rename(obj,name) {
  var input = prompt("Please enter a new name:",name);
  if (input == null) return false;

  obj.href = obj.href + '&name=' + encodeURI(input);
  consoel.log(obj.href);
  console.log(input);
  console.log(obj);
  return true;
}
