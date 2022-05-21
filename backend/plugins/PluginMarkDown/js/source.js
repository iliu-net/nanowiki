function mm_save() {
    var txt = textarea1.cm.getDoc().getValue();
    document.getElementById("payload").value = txt;
    document.getElementById("edform").submit();
}

textarea1 = mirrorMark(document.getElementById("srcedit"), {
  showToolbar: true
});
textarea1.registerActions({
  "save": function() {
    mm_save();
  }
});
textarea1.registerTools([
  { name: "save", action: "save" }
]);
textarea1.registerKeyMaps({ "Cmd-S": "save" });
textarea1.render();

tb_display("tb-tools-show-source","inline");
tb_display("tb-tools-show-content","none");
tb_display("tb-tools-save","none");
tb_display("source","none");

tb_hook("tb-tools-show-source", function() {
  tb_display("tb-tools-show-source","none");
  tb_display("tb-tools-show-content","inline");
  tb_display("tb-tools-save","inline");

  tb_display("source", "block");
  tb_display("main", "none");
});

tb_hook("tb-tools-show-content", function() {
  tb_display("tb-tools-show-source","inline");
  tb_display("tb-tools-show-content","none");
  tb_display("tb-tools-save","none");

  tb_display("source", "none");
  tb_display("main", "block");
});

tb_hook("tb-tools-save", function() {
  mm_save();
});


//~ document.getElementById("source").style.display = "none";

//~ function toggle_element(id) {
  //~ var x = document.getElementById(id)
  //~ if (x.style.display === "none") {
    //~ x.style.display = "block";
  //~ } else {
    //~ x.style.display = "none";
  //~ }
//~ }

//~ function toggle_source() {
  //~ toggle_element("source");
  //~ toggle_element("main");
//~ }
