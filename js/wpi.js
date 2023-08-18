// Interactive Console
;(function ($) {
  $(function (wp) {
    // init editor
    const wpiTextArea = document.getElementById("self::BASENAME-input");

    let hlLine;
    const wpiEditor = window.wp.CodeMirror.fromTextArea(wpiTextArea, {
      lineNumbers: true,
      matchBrackets: true,
      mode: "application/x-httpd-php",
      indentUnit: 2,
      indentWithTabs: true,
      enterMode: "indent",
      tabMode: "shift",
      electricChars: true,
      onCursorActivity: function () {
        wpiEditor.removeLineClass(hlLine);
        hlLine = wpiEditor.addLineClass(
          wpiEditor.getCursor().line,
          "activeline"
        );
      },
    });

    wpiEditor.focus();

    wpiEditor.setCursor(2, 2);
    hlLine = wpiEditor.addLineClass(wpiEditor.getCursor().line, "activeline");

    // submit handler
    $("#self::BASENAME-submit").on("click", function (e) {
      $("#self::BASENAME-output pre.output")
        .html("")
        .closest("div")
        .addClass("loading");
      $.post(
        ajaxurl,
        {
          action: "wp_interactive",
          wpi_action: "process",
          code: wpiEditor.getValue(),
        },
        function (ret) {
          $("#self::BASENAME-output pre.output")
            .html(ret.eval)
            .closest("div")
            .removeClass("loading");

          var _messages = $("#self::BASENAME-messages");
          if (!ret.success) {
            _messages.html(ret.message).show();
          } else {
            _messages.html("").hide();
          }
        },
        "json"
      );

      // return false;
      e.preventDefault();
    });

    // insert a snippet at the current cursor position
    // @todo accommodate selected text in the editor
    $("#self::BASENAME-insert-snippet").on("click", function (e) {
      var snippet = $("#self::BASENAME-snippets").val();
      var cpos = wpiEditor.getCursor();
      wpiEditor.replaceRange(wpi_snippets[snippet], cpos);
      wpiEditor.focus();
      e.stopPropagation();
      e.preventDefault();
    });

    // clear editor and insert the default open and close php tags
    $("#self::BASENAME-clear").on("click", function () {
      wpiEditor.setValue(wpi_default_text);
      wpiEditor.focus();
      wpiEditor.setCursor(2, 2);
    });
  });
})(window.jQuery, window.wp);