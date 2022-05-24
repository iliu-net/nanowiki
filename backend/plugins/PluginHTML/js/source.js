textarea1 = CodeMirror.fromTextArea(document.getElementById("srcedit"), {
    lineNumbers: true,
    mode: "htmlmixed",
    extraKeys: {
      "Ctrl-S": function(instance) {
	cm_save();
      }
    }
  });
function cm_save() {
  var txt = textarea1.getDoc().getValue();
  document.getElementById("payload").value = txt;
  document.getElementById("edform").submit();
}

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
  cm_save();
});
