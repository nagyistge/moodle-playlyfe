$(function() {
  $("#plDialog").dialog({
    dialogClass: "no-close",
    closeOnEscape: false,
    //draggable: false,
    //resizable: false,
    height: "auto",
    width: "auto",
    modal: true,
    //position: { my: "center", at: "center", of: "body" }
    buttons: [
      {
        text: "OK",
        click: function() {
          $( this ).dialog( "close" );
        }
      }
    ]
  });
});
